<?php
/**
 * ============================================================================
 * RBAC (Role-Based Access Control) - Sistema de Roles y Permisos
 * ============================================================================
 *
 * Descripción: Funciones para gestionar roles, permisos y control de acceso
 *
 * Características:
 * - Verificación de permisos granulares
 * - Asignación/revocación de roles
 * - Caché de permisos en sesión (performance)
 * - Auditoría de cambios de roles
 * - Soporte para roles temporales (con expiración)
 *
 * Proyecto: SIS 321 - Seguridad de Sistemas
 * Versión: 2.2.0
 * Fecha: 2025-10-20
 * ============================================================================
 */

// Incluir configuración de base de datos si no está incluida
if (!isset($con)) {
    require_once(dirname(__FILE__) . '/config.php');
}

/**
 * ============================================================================
 * CLASE: RBAC
 * ============================================================================
 */
class RBAC {

    private $con;
    private $cache_enabled = true;
    private $cache_duration = 300; // 5 minutos en segundos

    /**
     * Constructor
     */
    public function __construct($connection) {
        $this->con = $connection;
    }

    /**
     * ========================================================================
     * VERIFICAR SI USUARIO TIENE UN PERMISO ESPECÍFICO
     * ========================================================================
     *
     * @param int $user_id - ID del usuario
     * @param string $permission_name - Nombre del permiso (ej: 'view_patients')
     * @return bool - true si tiene el permiso, false si no
     */
    public function hasPermission($user_id, $permission_name) {
        // Verificar en caché primero
        if ($this->cache_enabled) {
            $cached = $this->getCachedPermissions($user_id);
            if ($cached !== null) {
                return in_array($permission_name, $cached);
            }
        }

        // Consultar en base de datos
        $query = "SELECT EXISTS(
                    SELECT 1
                    FROM user_effective_permissions
                    WHERE user_id = ? AND permission_name = ?
                  ) AS has_permission";

        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "is", $user_id, $permission_name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        return (bool)$row['has_permission'];
    }

    /**
     * ========================================================================
     * VERIFICAR SI USUARIO TIENE UN ROL ESPECÍFICO
     * ========================================================================
     *
     * @param int $user_id - ID del usuario
     * @param string $role_name - Nombre del rol (ej: 'admin', 'doctor')
     * @return bool
     */
    public function hasRole($user_id, $role_name) {
        $query = "SELECT EXISTS(
                    SELECT 1
                    FROM user_roles ur
                    INNER JOIN roles r ON ur.role_id = r.id
                    WHERE ur.user_id = ?
                      AND r.role_name = ?
                      AND ur.is_active = 1
                      AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
                      AND r.status = 'active'
                  ) AS has_role";

        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "is", $user_id, $role_name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        return (bool)$row['has_role'];
    }

    /**
     * ========================================================================
     * VERIFICAR SI USUARIO TIENE AL MENOS UNO DE LOS ROLES
     * ========================================================================
     *
     * @param int $user_id
     * @param array $role_names - Array de nombres de roles
     * @return bool
     */
    public function hasAnyRole($user_id, $role_names) {
        if (empty($role_names)) {
            return false;
        }

        foreach ($role_names as $role) {
            if ($this->hasRole($user_id, $role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ========================================================================
     * VERIFICAR SI USUARIO TIENE TODOS LOS ROLES
     * ========================================================================
     */
    public function hasAllRoles($user_id, $role_names) {
        if (empty($role_names)) {
            return false;
        }

        foreach ($role_names as $role) {
            if (!$this->hasRole($user_id, $role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * ========================================================================
     * OBTENER TODOS LOS PERMISOS DE UN USUARIO
     * ========================================================================
     *
     * @param int $user_id
     * @return array - Array de nombres de permisos
     */
    public function getUserPermissions($user_id) {
        // Verificar caché
        if ($this->cache_enabled) {
            $cached = $this->getCachedPermissions($user_id);
            if ($cached !== null) {
                return $cached;
            }
        }

        $permissions = [];

        $query = "SELECT DISTINCT permission_name
                  FROM user_effective_permissions
                  WHERE user_id = ?
                  ORDER BY permission_name";

        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $permissions[] = $row['permission_name'];
        }

        // Guardar en caché
        if ($this->cache_enabled) {
            $this->cachePermissions($user_id, $permissions);
        }

        return $permissions;
    }

    /**
     * ========================================================================
     * OBTENER TODOS LOS ROLES DE UN USUARIO
     * ========================================================================
     *
     * @param int $user_id
     * @return array - Array de información de roles
     */
    public function getUserRoles($user_id) {
        $roles = [];

        $query = "SELECT r.id, r.role_name, r.display_name, r.priority,
                         ur.expires_at, ur.assigned_at
                  FROM user_roles ur
                  INNER JOIN roles r ON ur.role_id = r.id
                  WHERE ur.user_id = ?
                    AND ur.is_active = 1
                    AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
                    AND r.status = 'active'
                  ORDER BY r.priority ASC";

        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $roles[] = $row;
        }

        return $roles;
    }

    /**
     * ========================================================================
     * ASIGNAR ROL A USUARIO
     * ========================================================================
     *
     * @param int $user_id - ID del usuario
     * @param int $role_id - ID del rol
     * @param int $assigned_by - ID del admin que asigna
     * @param string $expires_at - Fecha de expiración (opcional, NULL = permanente)
     * @return array - ['success' => bool, 'message' => string]
     */
    public function assignRoleToUser($user_id, $role_id, $assigned_by, $expires_at = null) {
        // Verificar que el usuario existe
        if (!$this->userExists($user_id)) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }

        // Verificar que el rol existe y está activo
        if (!$this->roleExists($role_id)) {
            return [
                'success' => false,
                'message' => 'Rol no encontrado o inactivo'
            ];
        }

        // Insertar o actualizar la asignación
        $query = "INSERT INTO user_roles (user_id, role_id, assigned_by, expires_at, is_active)
                  VALUES (?, ?, ?, ?, 1)
                  ON DUPLICATE KEY UPDATE
                      assigned_by = VALUES(assigned_by),
                      expires_at = VALUES(expires_at),
                      is_active = 1,
                      assigned_at = CURRENT_TIMESTAMP";

        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "iiis", $user_id, $role_id, $assigned_by, $expires_at);

        if (mysqli_stmt_execute($stmt)) {
            // Registrar en auditoría
            $this->logRoleChange($user_id, $role_id, 'assigned', $assigned_by);

            // Invalidar caché
            $this->clearUserCache($user_id);

            // Obtener nombre del rol
            $role_info = $this->getRoleInfo($role_id);

            return [
                'success' => true,
                'message' => "Rol '{$role_info['display_name']}' asignado exitosamente"
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al asignar el rol'
            ];
        }
    }

    /**
     * ========================================================================
     * REVOCAR ROL DE USUARIO
     * ========================================================================
     */
    public function revokeRoleFromUser($user_id, $role_id, $revoked_by) {
        // Verificar que la asignación existe
        $query_check = "SELECT 1 FROM user_roles WHERE user_id = ? AND role_id = ?";
        $stmt_check = mysqli_prepare($this->con, $query_check);
        mysqli_stmt_bind_param($stmt_check, "ii", $user_id, $role_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) == 0) {
            return [
                'success' => false,
                'message' => 'El usuario no tiene este rol asignado'
            ];
        }

        // Eliminar la asignación
        $query = "DELETE FROM user_roles WHERE user_id = ? AND role_id = ?";
        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $role_id);

        if (mysqli_stmt_execute($stmt)) {
            // Registrar en auditoría
            $this->logRoleChange($user_id, $role_id, 'revoked', $revoked_by);

            // Invalidar caché
            $this->clearUserCache($user_id);

            $role_info = $this->getRoleInfo($role_id);

            return [
                'success' => true,
                'message' => "Rol '{$role_info['display_name']}' revocado exitosamente"
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al revocar el rol'
            ];
        }
    }

    /**
     * ========================================================================
     * OBTENER INFORMACIÓN DE UN ROL
     * ========================================================================
     */
    public function getRoleInfo($role_id) {
        $query = "SELECT * FROM roles WHERE id = ?";
        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "i", $role_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        return mysqli_fetch_assoc($result);
    }

    /**
     * ========================================================================
     * OBTENER INFORMACIÓN DE UN ROL POR NOMBRE
     * ========================================================================
     */
    public function getRoleByName($role_name) {
        $query = "SELECT * FROM roles WHERE role_name = ? AND status = 'active'";
        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "s", $role_name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        return mysqli_fetch_assoc($result);
    }

    /**
     * ========================================================================
     * OBTENER TODOS LOS ROLES DISPONIBLES
     * ========================================================================
     */
    public function getAllRoles() {
        $roles = [];

        $query = "SELECT * FROM roles WHERE status = 'active' ORDER BY priority ASC";
        $result = mysqli_query($this->con, $query);

        while ($row = mysqli_fetch_assoc($result)) {
            $roles[] = $row;
        }

        return $roles;
    }

    /**
     * ========================================================================
     * OBTENER TODOS LOS PERMISOS DISPONIBLES
     * ========================================================================
     */
    public function getAllPermissions($group_by_module = false) {
        $permissions = [];

        $query = "SELECT p.*, pc.display_name as category_display_name
                  FROM permissions p
                  LEFT JOIN permission_categories pc ON p.category_id = pc.id
                  WHERE p.is_active = 1 AND (pc.is_active = 1 OR pc.is_active IS NULL)
                  ORDER BY pc.sort_order, p.module, p.permission_name";

        $result = mysqli_query($this->con, $query);

        while ($row = mysqli_fetch_assoc($result)) {
            if ($group_by_module) {
                $permissions[$row['module']][] = $row;
            } else {
                $permissions[] = $row;
            }
        }

        return $permissions;
    }

    /**
     * ========================================================================
     * OBTENER PERMISOS DE UN ROL
     * ========================================================================
     */
    public function getRolePermissions($role_id) {
        $permissions = [];

        $query = "SELECT p.*
                  FROM permissions p
                  INNER JOIN role_permissions rp ON p.id = rp.permission_id
                  WHERE rp.role_id = ? AND p.is_active = 1
                  ORDER BY p.module, p.permission_name";

        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "i", $role_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $permissions[] = $row;
        }

        return $permissions;
    }

    /**
     * ========================================================================
     * VERIFICACIONES AUXILIARES
     * ========================================================================
     */
    private function userExists($user_id) {
        $query = "SELECT 1 FROM users WHERE id = ? AND status = 'active'";
        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        return mysqli_num_rows($result) > 0;
    }

    private function roleExists($role_id) {
        $query = "SELECT 1 FROM roles WHERE id = ? AND status = 'active'";
        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "i", $role_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        return mysqli_num_rows($result) > 0;
    }

    /**
     * ========================================================================
     * CACHÉ DE PERMISOS (Para performance)
     * ========================================================================
     */
    private function getCachedPermissions($user_id) {
        if (!isset($_SESSION)) {
            session_start();
        }

        $cache_key = "rbac_permissions_{$user_id}";
        $cache_time_key = "rbac_permissions_time_{$user_id}";

        if (isset($_SESSION[$cache_key]) && isset($_SESSION[$cache_time_key])) {
            // Verificar si el caché no ha expirado
            if ((time() - $_SESSION[$cache_time_key]) < $this->cache_duration) {
                return $_SESSION[$cache_key];
            }
        }

        return null;
    }

    private function cachePermissions($user_id, $permissions) {
        if (!isset($_SESSION)) {
            session_start();
        }

        $_SESSION["rbac_permissions_{$user_id}"] = $permissions;
        $_SESSION["rbac_permissions_time_{$user_id}"] = time();
    }

    private function clearUserCache($user_id) {
        if (!isset($_SESSION)) {
            session_start();
        }

        unset($_SESSION["rbac_permissions_{$user_id}"]);
        unset($_SESSION["rbac_permissions_time_{$user_id}"]);
    }

    /**
     * ========================================================================
     * AUDITORÍA DE CAMBIOS
     * ========================================================================
     */
    private function logRoleChange($user_id, $role_id, $action, $performed_by) {
        $ip = $this->getClientIP();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        $query = "INSERT INTO audit_role_changes (user_id, role_id, action, performed_by, ip_address, user_agent)
                  VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "iisiss", $user_id, $role_id, $action, $performed_by, $ip, $user_agent);
        mysqli_stmt_execute($stmt);
    }

    private function getClientIP() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return '0.0.0.0';
    }

    /**
     * ========================================================================
     * OBTENER ROL PRINCIPAL DEL USUARIO (Mayor prioridad)
     * ========================================================================
     */
    public function getUserPrimaryRole($user_id) {
        $query = "SELECT r.*
                  FROM user_roles ur
                  INNER JOIN roles r ON ur.role_id = r.id
                  WHERE ur.user_id = ?
                    AND ur.is_active = 1
                    AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
                    AND r.status = 'active'
                  ORDER BY r.priority ASC
                  LIMIT 1";

        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        return mysqli_fetch_assoc($result);
    }
}

/**
 * ============================================================================
 * FUNCIONES HELPER (Para usar sin instanciar la clase)
 * ============================================================================
 */

/**
 * Verificar si el usuario actual tiene un permiso
 */
function hasPermission($permission_name, $user_id = null, $connection = null) {
    global $con;
    $db = $connection ?? $con;

    // Si no se especifica user_id, usar el de la sesión
    if ($user_id === null) {
        if (!isset($_SESSION)) {
            session_start();
        }
        $user_id = $_SESSION['id'] ?? null;
    }

    if (!$user_id) {
        return false;
    }

    $rbac = new RBAC($db);
    return $rbac->hasPermission($user_id, $permission_name);
}

/**
 * Verificar si el usuario actual tiene un rol
 */
function hasRole($role_name, $user_id = null, $connection = null) {
    global $con;
    $db = $connection ?? $con;

    if ($user_id === null) {
        if (!isset($_SESSION)) {
            session_start();
        }
        $user_id = $_SESSION['id'] ?? null;
    }

    if (!$user_id) {
        return false;
    }

    $rbac = new RBAC($db);
    return $rbac->hasRole($user_id, $role_name);
}

/**
 * Obtener permisos del usuario actual
 */
function getUserPermissions($user_id = null, $connection = null) {
    global $con;
    $db = $connection ?? $con;

    if ($user_id === null) {
        if (!isset($_SESSION)) {
            session_start();
        }
        $user_id = $_SESSION['id'] ?? null;
    }

    if (!$user_id) {
        return [];
    }

    $rbac = new RBAC($db);
    return $rbac->getUserPermissions($user_id);
}

/**
 * Obtener roles del usuario actual
 */
function getUserRoles($user_id = null, $connection = null) {
    global $con;
    $db = $connection ?? $con;

    if ($user_id === null) {
        if (!isset($_SESSION)) {
            session_start();
        }
        $user_id = $_SESSION['id'] ?? null;
    }

    if (!$user_id) {
        return [];
    }

    $rbac = new RBAC($db);
    return $rbac->getUserRoles($user_id);
}

/**
 * Verificar si es Super Admin
 */
function isSuperAdmin($user_id = null, $connection = null) {
    return hasRole('super_admin', $user_id, $connection);
}

/**
 * Verificar si es Admin (Super Admin o Admin)
 */
function isAdmin($user_id = null, $connection = null) {
    return hasRole('super_admin', $user_id, $connection) || hasRole('admin', $user_id, $connection);
}

?>
