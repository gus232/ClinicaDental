<?php
/**
 * ============================================================================
 * USERS API - REST Endpoint
 * ============================================================================
 * API REST para operaciones de usuarios vía AJAX
 *
 * Endpoints disponibles (vía POST action parameter):
 * - get_all_users: Lista todos los usuarios
 * - get_user: Obtiene un usuario por ID
 * - create_user: Crea nuevo usuario
 * - update_user: Actualiza usuario existente
 * - delete_user: Elimina (desactiva) usuario
 * - search_users: Búsqueda avanzada
 * - assign_roles: Asignar roles a usuario
 * - revoke_roles: Revocar roles de usuario
 * - get_user_roles: Obtiene roles de usuario
 * - get_user_history: Obtiene historial de cambios
 * - get_statistics: Obtiene estadísticas generales
 *
 * @version 2.3.0
 * @package HMS
 * @subpackage API
 * ============================================================================
 */

// Inicio de sesión y configuración
session_start();
error_reporting(0);

// Includes necesarios
include('../../include/config.php');
include('../../include/csrf-protection.php');
include('../../include/UserManagement.php');
include('../../include/rbac-functions.php');

// Headers para JSON
header('Content-Type: application/json');

// ============================================================================
// VERIFICACIÓN DE AUTENTICACIÓN Y PERMISOS
// ============================================================================

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Debe iniciar sesión.'
    ]);
    exit();
}

// Verificar permiso de gestión de usuarios
if (!hasPermission('view_users')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para gestionar usuarios'
    ]);
    exit();
}

// ============================================================================
// VALIDACIÓN CSRF para operaciones POST/PUT/DELETE
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Operaciones que requieren CSRF validation
    $csrf_required_actions = [
        'create_user', 'update_user', 'delete_user',
        'assign_roles', 'revoke_roles'
    ];

    if (in_array($action, $csrf_required_actions)) {
        if (!csrf_validate()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Token CSRF inválido',
                'error' => 'CSRF_VALIDATION_FAILED'
            ]);
            exit();
        }
    }
}

// ============================================================================
// INICIALIZAR CLASE DE GESTIÓN
// ============================================================================

try {
    $userManager = new UserManagement($con, $_SESSION['id']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al inicializar sistema de usuarios',
        'error' => $e->getMessage()
    ]);
    exit();
}

// ============================================================================
// ROUTER - Procesar acción solicitada
// ============================================================================

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ========================================================================
    // GET ALL USERS
    // ========================================================================
    case 'get_all_users':
        try {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'gender' => $_GET['gender'] ?? null,
                'city' => $_GET['city'] ?? null
            ];

            $users = $userManager->getAllUsers($filters);

            echo json_encode([
                'success' => true,
                'data' => $users,
                'count' => count($users)
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener usuarios',
                'error' => $e->getMessage()
            ]);
        }
        break;

    // ========================================================================
    // GET USER BY ID
    // ========================================================================
    case 'get':
    case 'get_user':
        try {
            $user_id = intval($_GET['id'] ?? $_GET['user_id'] ?? $_POST['user_id'] ?? 0);

            if ($user_id <= 0) {
                throw new Exception('ID de usuario inválido');
            }

            $user = $userManager->getUserById($user_id);

            if ($user) {
                // Obtener roles del usuario
                $roles = $userManager->getUserRoles($user_id);
                $role_ids = array_column($roles, 'id');

                $user['role_ids'] = implode(',', $role_ids);
                $user['roles'] = $roles;

                echo json_encode([
                    'success' => true,
                    'data' => $user
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ]);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener usuario',
                'error' => $e->getMessage()
            ]);
        }
        break;

    // ========================================================================
    // CREATE USER
    // ========================================================================
    case 'create_user':
        try {
            // Validar datos requeridos
            $required_fields = ['full_name', 'email', 'password'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("El campo '$field' es requerido");
                }
            }

            // Validar políticas de contraseña (FASE 1)
            if (class_exists('PasswordPolicy')) {
                $passwordPolicy = new PasswordPolicy($con);
                $validation = $passwordPolicy->validatePassword($_POST['password']);

                if (!$validation['valid']) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'La contraseña no cumple con las políticas',
                        'errors' => $validation['errors']
                    ]);
                    exit();
                }
            }

            // Preparar datos
            $data = [
                'full_name' => trim($_POST['full_name']),
                'email' => trim($_POST['email']),
                'password' => $_POST['password'],
                'address' => trim($_POST['address'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'gender' => $_POST['gender'] ?? 'Male',
                'contactno' => trim($_POST['contactno'] ?? '')
            ];

            $reason = $_POST['reason'] ?? 'Usuario creado desde panel de administración';

            // Crear usuario
            $result = $userManager->createUser($data, $reason);

            if ($result['success']) {
                // Si se especificaron roles, asignarlos
                if (!empty($_POST['roles']) && is_array($_POST['roles'])) {
                    $userManager->assignRoles(
                        $result['user_id'],
                        $_POST['roles'],
                        'Roles asignados al crear usuario'
                    );
                }

                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al crear usuario',
                'error' => $e->getMessage()
            ]);
        }
        break;

    // ========================================================================
    // UPDATE USER
    // ========================================================================
    case 'update_user':
        try {
            $user_id = intval($_POST['user_id'] ?? 0);

            if ($user_id <= 0) {
                throw new Exception('ID de usuario inválido');
            }

            // Preparar datos (solo los campos presentes)
            $data = [];

            if (isset($_POST['full_name'])) $data['full_name'] = trim($_POST['full_name']);
            if (isset($_POST['email'])) $data['email'] = trim($_POST['email']);
            if (isset($_POST['address'])) $data['address'] = trim($_POST['address']);
            if (isset($_POST['city'])) $data['city'] = trim($_POST['city']);
            if (isset($_POST['gender'])) $data['gender'] = $_POST['gender'];
            if (isset($_POST['contactno'])) $data['contactno'] = trim($_POST['contactno']);
            if (isset($_POST['status'])) $data['status'] = intval($_POST['status']);

            $reason = $_POST['reason'] ?? 'Actualización de información de usuario';

            // Actualizar usuario
            $result = $userManager->updateUser($user_id, $data, $reason);

            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar usuario',
                'error' => $e->getMessage()
            ]);
        }
        break;

    // ========================================================================
    // DELETE USER (SOFT DELETE)
    // ========================================================================
    case 'delete_user':
        try {
            $user_id = intval($_POST['user_id'] ?? 0);

            if ($user_id <= 0) {
                throw new Exception('ID de usuario inválido');
            }

            $reason = $_POST['reason'] ?? 'Usuario eliminado desde panel de administración';

            // Eliminar usuario
            $result = $userManager->deleteUser($user_id, $reason);

            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar usuario',
                'error' => $e->getMessage()
            ]);
        }
        break;

    // ========================================================================
    // SEARCH USERS
    // ========================================================================
    case 'search_users':
        try {
            $search_term = $_GET['search'] ?? $_POST['search'] ?? '';

            $filters = [
                'role_id' => isset($_GET['role_id']) ? intval($_GET['role_id']) : null,
                'status' => isset($_GET['status']) ? intval($_GET['status']) : null,
                'gender' => $_GET['gender'] ?? null,
                'city' => $_GET['city'] ?? null,
                'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : 50,
                'offset' => isset($_GET['offset']) ? intval($_GET['offset']) : 0
            ];

            $users = $userManager->searchUsers($search_term, $filters);

            echo json_encode([
                'success' => true,
                'data' => $users,
                'count' => count($users)
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error en búsqueda',
                'error' => $e->getMessage()
            ]);
        }
        break;

    // ========================================================================
    // ASSIGN ROLES
    // ========================================================================
    case 'assign_roles':
        try {
            $user_id = intval($_POST['user_id'] ?? 0);
            $role_ids = $_POST['role_ids'] ?? [];

            if ($user_id <= 0) {
                throw new Exception('ID de usuario inválido');
            }

            if (!is_array($role_ids) || empty($role_ids)) {
                throw new Exception('Debe seleccionar al menos un rol');
            }

            $reason = $_POST['reason'] ?? 'Roles asignados desde panel de administración';
            $expires_at = $_POST['expires_at'] ?? null;

            $result = $userManager->assignRoles($user_id, $role_ids, $reason, $expires_at);

            echo json_encode($result);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al asignar roles',
                'error' => $e->getMessage()
            ]);
        }
        break;

    // ========================================================================
    // REVOKE ROLES
    // ========================================================================
    case 'revoke_roles':
        try {
            $user_id = intval($_POST['user_id'] ?? 0);
            $role_ids = $_POST['role_ids'] ?? [];

            if ($user_id <= 0) {
                throw new Exception('ID de usuario inválido');
            }

            if (!is_array($role_ids) || empty($role_ids)) {
                throw new Exception('Debe seleccionar al menos un rol');
            }

            $reason = $_POST['reason'] ?? 'Roles revocados desde panel de administración';

            $result = $userManager->revokeRoles($user_id, $role_ids, $reason);

            echo json_encode($result);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al revocar roles',
                'error' => $e->getMessage()
            ]);
        }
        break;

    // ========================================================================
    // GET USER ROLES
    // ========================================================================
    case 'get_user_roles':
        try {
            $user_id = intval($_GET['user_id'] ?? $_POST['user_id'] ?? 0);

            if ($user_id <= 0) {
                throw new Exception('ID de usuario inválido');
            }

            $roles = $userManager->getUserRoles($user_id);

            echo json_encode([
                'success' => true,
                'data' => $roles,
                'count' => count($roles)
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener roles',
                'error' => $e->getMessage()
            ]);
        }
        break;

    // ========================================================================
    // GET USER HISTORY
    // ========================================================================
    case 'get_user_history':
        try {
            $user_id = intval($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
            $limit = intval($_GET['limit'] ?? 50);

            if ($user_id <= 0) {
                throw new Exception('ID de usuario inválido');
            }

            $history = $userManager->getUserHistory($user_id, $limit);

            echo json_encode([
                'success' => true,
                'data' => $history,
                'count' => count($history)
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener historial',
                'error' => $e->getMessage()
            ]);
        }
        break;

    // ========================================================================
    // GET STATISTICS
    // ========================================================================
    case 'get_statistics':
        try {
            $stats = $userManager->getStatistics();

            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ]);
        }
        break;

    // ========================================================================
    // GET ALL AVAILABLE ROLES
    // ========================================================================
    case 'get_all_roles':
        try {
            $sql = "SELECT id, role_name, display_name, description, priority, status
                    FROM roles
                    WHERE status = 'active'
                    ORDER BY priority ASC";

            $stmt = $con->query($sql);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $roles,
                'count' => count($roles)
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener roles',
                'error' => $e->getMessage()
            ]);
        }
        break;

    // ========================================================================
    // ACCIÓN NO VÁLIDA
    // ========================================================================
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida',
            'action_received' => $action
        ]);
        break;
}

?>
