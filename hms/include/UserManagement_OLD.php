<?php
/**
 * ============================================================================
 * CLASS: UserManagement (VERSIÓN MYSQLI)
 * ============================================================================
 * Gestión completa de usuarios con auditoría y seguridad
 * ADAPTADO PARA MYSQLI (no PDO)
 *
 * @version 2.3.0 MYSQLI
 * @package HMS
 * @subpackage UserManagement
 * ============================================================================
 */

class UserManagement {
    private $db;
    private $current_user_id;
    private $current_user_ip;

    /**
     * Constructor
     * @param mysqli $db_connection Conexión MySQLi a la base de datos
     * @param int $current_user_id ID del usuario actual (de sesión)
     */
    public function __construct($db_connection, $current_user_id = null) {
        $this->db = $db_connection;
        $this->current_user_id = $current_user_id ?? $_SESSION['id'] ?? null;
        $this->current_user_ip = $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Crear nuevo usuario con auditoría
     *
     * @param array $data Datos del usuario
     * @param string $reason Razón de creación
     * @return array ['success' => bool, 'user_id' => int|null, 'message' => string]
     */
    public function createUser($data, $reason = null) {
        try {
            // Validar email único
            if ($this->emailExists($data['email'])) {
                return [
                    'success' => false,
                    'user_id' => null,
                    'message' => 'El email ya está registrado'
                ];
            }

            // Hashear password
            $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);

            // Llamar al stored procedure
            $stmt = $this->db->prepare("CALL create_user_with_audit(?, ?, ?, ?, ?, ?, ?, ?, ?, @new_user_id)");

            $full_name = $data['full_name'];
            $address = $data['address'] ?? '';
            $city = $data['city'] ?? '';
            $gender = $data['gender'] ?? 'Male';
            $email = $data['email'];
            $created_by = $this->current_user_id;
            $ip = $this->current_user_ip;
            $reason_text = $reason ?? 'Usuario creado desde panel de administración';

            $stmt->bind_param("ssssssiis",
                $full_name,
                $address,
                $city,
                $gender,
                $email,
                $hashed_password,
                $created_by,
                $ip,
                $reason_text
            );

            $stmt->execute();
            $stmt->close();

            // Obtener el ID del nuevo usuario
            $result = $this->db->query("SELECT @new_user_id as user_id");
            $row = $result->fetch_assoc();
            $new_user_id = $row['user_id'];

            if ($new_user_id > 0) {
                return [
                    'success' => true,
                    'user_id' => $new_user_id,
                    'message' => 'Usuario creado exitosamente'
                ];
            } elseif ($new_user_id == -1) {
                return [
                    'success' => false,
                    'user_id' => null,
                    'message' => 'El email ya está registrado'
                ];
            } else {
                return [
                    'success' => false,
                    'user_id' => null,
                    'message' => 'Error al crear usuario'
                ];
            }

        } catch (Exception $e) {
            error_log("Error en createUser: " . $e->getMessage());
            return [
                'success' => false,
                'user_id' => null,
                'message' => 'Error de base de datos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar usuario existente con historial
     */
    public function updateUser($user_id, $data, $reason = null) {
        try {
            // Verificar email único (si se está cambiando)
            if (isset($data['email']) && $this->emailExists($data['email'], $user_id)) {
                return [
                    'success' => false,
                    'message' => 'El email ya está registrado'
                ];
            }

            // Llamar al stored procedure
            $stmt = $this->db->prepare("CALL update_user_with_history(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @result)");

            $full_name = $data['full_name'] ?? null;
            $address = $data['address'] ?? null;
            $city = $data['city'] ?? null;
            $gender = $data['gender'] ?? null;
            $email = $data['email'] ?? null;
            $status = isset($data['status']) ? (int)$data['status'] : null;
            $updated_by = $this->current_user_id;
            $ip = $this->current_user_ip;
            $reason_text = $reason ?? 'Actualización de información';

            $stmt->bind_param("isssssiiss",
                $user_id,
                $full_name,
                $address,
                $city,
                $gender,
                $email,
                $status,
                $updated_by,
                $ip,
                $reason_text
            );

            $stmt->execute();
            $stmt->close();

            // Obtener resultado
            $result = $this->db->query("SELECT @result as result");
            $row = $result->fetch_assoc();
            $update_result = $row['result'];

            if ($update_result == 1) {
                return [
                    'success' => true,
                    'message' => 'Usuario actualizado exitosamente'
                ];
            } elseif ($update_result == 2) {
                return [
                    'success' => false,
                    'message' => 'No se realizaron cambios'
                ];
            } elseif ($update_result == -1) {
                return [
                    'success' => false,
                    'message' => 'El email ya está registrado'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al actualizar usuario'
                ];
            }

        } catch (Exception $e) {
            error_log("Error en updateUser: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error de base de datos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar usuario (soft delete)
     */
    public function deleteUser($user_id, $reason = null) {
        return $this->updateUser($user_id, ['status' => 0], $reason ?? 'Usuario eliminado');
    }

    /**
     * Obtener usuario por ID
     */
    public function getUserById($user_id) {
        try {
            $sql = "SELECT u.*,
                    GROUP_CONCAT(DISTINCT r.display_name ORDER BY r.priority SEPARATOR ', ') as roles,
                    GROUP_CONCAT(DISTINCT r.id ORDER BY r.priority) as role_ids,
                    (SELECT COUNT(*) FROM user_change_history WHERE user_id = u.id) as total_changes
                    FROM users u
                    LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_active = 1
                    LEFT JOIN roles r ON ur.role_id = r.id AND r.status = 'active'
                    WHERE u.id = ?
                    GROUP BY u.id";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();

        } catch (Exception $e) {
            error_log("Error en getUserById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los usuarios
     */
    public function getAllUsers($filters = []) {
        try {
            $sql = "SELECT u.id, u.full_name, u.email, u.city, u.gender, u.status, u.reg_date,
                    GROUP_CONCAT(DISTINCT r.display_name ORDER BY r.priority SEPARATOR ', ') as roles
                    FROM users u
                    LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_active = 1
                    LEFT JOIN roles r ON ur.role_id = r.id AND r.status = 'active'
                    WHERE 1=1";

            $types = "";
            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND u.status = ?";
                $types .= "i";
                $params[] = &$filters['status'];
            }

            if (isset($filters['gender'])) {
                $sql .= " AND u.gender = ?";
                $types .= "s";
                $params[] = &$filters['gender'];
            }

            if (isset($filters['city'])) {
                $sql .= " AND u.city LIKE ?";
                $types .= "s";
                $city_param = '%' . $filters['city'] . '%';
                $params[] = &$city_param;
            }

            $sql .= " GROUP BY u.id ORDER BY u.full_name ASC";

            $stmt = $this->db->prepare($sql);

            if (!empty($params)) {
                array_unshift($params, $types);
                call_user_func_array([$stmt, 'bind_param'], $params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }

            return $users;

        } catch (Exception $e) {
            error_log("Error en getAllUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Asignar roles a usuario
     */
    public function assignRoles($user_id, $role_ids, $reason = null, $expires_at = null) {
        try {
            if (!is_array($role_ids) || empty($role_ids)) {
                return [
                    'success' => false,
                    'message' => 'Debe seleccionar al menos un rol'
                ];
            }

            $success_count = 0;
            foreach ($role_ids as $role_id) {
                $stmt = $this->db->prepare("CALL assign_role_to_user(?, ?, ?, ?, @result)");
                $stmt->bind_param("iiis", $user_id, $role_id, $this->current_user_id, $expires_at);
                $stmt->execute();
                $stmt->close();

                $result = $this->db->query("SELECT @result as result");
                $row = $result->fetch_assoc();
                if ($row['result'] == 1) {
                    $success_count++;
                }
            }

            return [
                'success' => $success_count > 0,
                'message' => "Se asignaron $success_count rol(es) exitosamente"
            ];

        } catch (Exception $e) {
            error_log("Error en assignRoles: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al asignar roles: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Revocar roles de usuario
     */
    public function revokeRoles($user_id, $role_ids, $reason = null) {
        try {
            if (!is_array($role_ids) || empty($role_ids)) {
                return [
                    'success' => false,
                    'message' => 'Debe seleccionar al menos un rol'
                ];
            }

            $success_count = 0;
            foreach ($role_ids as $role_id) {
                $stmt = $this->db->prepare("CALL revoke_role_from_user(?, ?, ?, @result)");
                $stmt->bind_param("iii", $user_id, $role_id, $this->current_user_id);
                $stmt->execute();
                $stmt->close();

                $result = $this->db->query("SELECT @result as result");
                $row = $result->fetch_assoc();
                if ($row['result'] == 1) {
                    $success_count++;
                }
            }

            return [
                'success' => $success_count > 0,
                'message' => "Se revocaron $success_count rol(es) exitosamente"
            ];

        } catch (Exception $e) {
            error_log("Error en revokeRoles: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al revocar roles: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener roles de un usuario
     */
    public function getUserRoles($user_id) {
        try {
            $sql = "SELECT r.*, ur.assigned_at, ur.expires_at, ur.is_active
                    FROM user_roles ur
                    INNER JOIN roles r ON ur.role_id = r.id
                    WHERE ur.user_id = ? AND ur.is_active = 1
                    ORDER BY r.priority ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $roles = [];
            while ($row = $result->fetch_assoc()) {
                $roles[] = $row;
            }

            return $roles;

        } catch (Exception $e) {
            error_log("Error en getUserRoles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar usuarios con filtros
     */
    public function searchUsers($search_term = '', $filters = []) {
        try {
            $stmt = $this->db->prepare("CALL search_users(?, ?, ?, ?, ?, ?, ?)");

            $role_id = $filters['role_id'] ?? null;
            $status = $filters['status'] ?? null;
            $gender = $filters['gender'] ?? null;
            $city = $filters['city'] ?? null;
            $limit = $filters['limit'] ?? 50;
            $offset = $filters['offset'] ?? 0;

            $stmt->bind_param("siissii", $search_term, $role_id, $status, $gender, $city, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();

            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }

            return $users;

        } catch (Exception $e) {
            error_log("Error en searchUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener historial de cambios de un usuario
     */
    public function getUserHistory($user_id, $limit = 50) {
        try {
            $sql = "SELECT * FROM user_changes_detailed
                    WHERE user_id = ?
                    ORDER BY created_at DESC
                    LIMIT ?";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $user_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            $history = [];
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }

            return $history;

        } catch (Exception $e) {
            error_log("Error en getUserHistory: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Registrar cambio manual
     */
    public function logChange($user_id, $change_type, $data) {
        try {
            $sql = "INSERT INTO user_change_history
                    (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);

            $field_changed = $data['field_changed'] ?? null;
            $old_value = $data['old_value'] ?? null;
            $new_value = $data['new_value'] ?? null;
            $reason = $data['reason'] ?? null;

            $stmt->bind_param("iissssss",
                $user_id,
                $this->current_user_id,
                $change_type,
                $field_changed,
                $old_value,
                $new_value,
                $reason,
                $this->current_user_ip
            );

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Error en logChange: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas generales
     */
    public function getStatistics() {
        try {
            $result = $this->db->query("CALL get_user_statistics()");
            return $result->fetch_assoc() ?: [];

        } catch (Exception $e) {
            error_log("Error en getStatistics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener usuarios activos
     */
    public function getActiveUsers() {
        try {
            $sql = "SELECT * FROM active_sessions_view ORDER BY last_activity DESC";
            $result = $this->db->query($sql);

            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }

            return $users;

        } catch (Exception $e) {
            error_log("Error en getActiveUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si un email existe
     */
    public function emailExists($email, $exclude_user_id = null) {
        try {
            if ($exclude_user_id !== null) {
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?");
                $stmt->bind_param("si", $email, $exclude_user_id);
            } else {
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            return $row['count'] > 0;

        } catch (Exception $e) {
            error_log("Error en emailExists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un usuario existe
     */
    public function userExists($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            return $row['count'] > 0;

        } catch (Exception $e) {
            error_log("Error en userExists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar datos de usuario
     */
    public function validateUserData($data, $mode = 'create') {
        if ($mode === 'create') {
            if (empty($data['full_name'])) {
                return ['valid' => false, 'message' => 'El nombre completo es requerido'];
            }
            if (empty($data['email'])) {
                return ['valid' => false, 'message' => 'El email es requerido'];
            }
            if (empty($data['password'])) {
                return ['valid' => false, 'message' => 'La contraseña es requerida'];
            }
        }

        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Email inválido'];
        }

        if (isset($data['password']) && strlen($data['password']) < 6) {
            return ['valid' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
        }

        if (isset($data['gender']) && !in_array($data['gender'], ['Male', 'Female', 'Other'])) {
            return ['valid' => false, 'message' => 'Género inválido'];
        }

        return ['valid' => true, 'message' => 'Validación exitosa'];
    }
}
?>
