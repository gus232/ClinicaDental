<?php
/**
 * ============================================================================
 * MIDDLEWARE: VERIFICACIÓN DE PERMISOS
 * ============================================================================
 *
 * Descripción: Middleware para proteger páginas con permisos RBAC
 *
 * Uso:
 *   require_once('include/permission-check.php');
 *   requirePermission('view_patients');
 *   requireRole('admin');
 *   requireAnyRole(['admin', 'doctor']);
 *
 * Proyecto: SIS 321 - Seguridad de Sistemas
 * Versión: 2.2.0
 * Fecha: 2025-10-20
 * ============================================================================
 */

// Incluir funciones RBAC
require_once(dirname(__FILE__) . '/rbac-functions.php');

/**
 * ============================================================================
 * FUNCIÓN: requirePermission
 * ============================================================================
 * Verifica que el usuario tenga un permiso específico.
 * Si no lo tiene, redirecciona a página de error.
 *
 * @param string $permission_name - Nombre del permiso requerido
 * @param string $redirect_url - URL a redireccionar si no tiene permiso (opcional)
 * @param bool $die - Si es true, detiene ejecución; si es false, solo retorna false
 */
function requirePermission($permission_name, $redirect_url = null, $die = true) {
    // Verificar sesión
    if (!isset($_SESSION)) {
        session_start();
    }

    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['id'])) {
        if ($die) {
            header("Location: login.php");
            exit();
        }
        return false;
    }

    $user_id = $_SESSION['id'];

    // Verificar permiso
    if (!hasPermission($permission_name, $user_id)) {
        // Log del intento de acceso no autorizado
        logUnauthorizedAccess($user_id, $permission_name, 'permission');

        if ($die) {
            // Redireccionar a página de error
            if ($redirect_url) {
                header("Location: $redirect_url");
            } else {
                header("Location: access-denied.php?permission=" . urlencode($permission_name));
            }
            exit();
        }

        return false;
    }

    return true;
}

/**
 * ============================================================================
 * FUNCIÓN: requireRole
 * ============================================================================
 * Verifica que el usuario tenga un rol específico.
 *
 * @param string $role_name - Nombre del rol requerido
 * @param string $redirect_url - URL a redireccionar si no tiene el rol (opcional)
 * @param bool $die - Si es true, detiene ejecución
 */
function requireRole($role_name, $redirect_url = null, $die = true) {
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!isset($_SESSION['id'])) {
        if ($die) {
            header("Location: login.php");
            exit();
        }
        return false;
    }

    $user_id = $_SESSION['id'];

    if (!hasRole($role_name, $user_id)) {
        logUnauthorizedAccess($user_id, $role_name, 'role');

        if ($die) {
            if ($redirect_url) {
                header("Location: $redirect_url");
            } else {
                header("Location: access-denied.php?role=" . urlencode($role_name));
            }
            exit();
        }

        return false;
    }

    return true;
}

/**
 * ============================================================================
 * FUNCIÓN: requireAnyRole
 * ============================================================================
 * Verifica que el usuario tenga AL MENOS UNO de los roles especificados.
 *
 * @param array $role_names - Array de nombres de roles
 * @param string $redirect_url
 * @param bool $die
 */
function requireAnyRole($role_names, $redirect_url = null, $die = true) {
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!isset($_SESSION['id'])) {
        if ($die) {
            header("Location: login.php");
            exit();
        }
        return false;
    }

    $user_id = $_SESSION['id'];
    global $con;
    $rbac = new RBAC($con);

    if (!$rbac->hasAnyRole($user_id, $role_names)) {
        logUnauthorizedAccess($user_id, implode(',', $role_names), 'any_role');

        if ($die) {
            if ($redirect_url) {
                header("Location: $redirect_url");
            } else {
                header("Location: access-denied.php?roles=" . urlencode(implode(',', $role_names)));
            }
            exit();
        }

        return false;
    }

    return true;
}

/**
 * ============================================================================
 * FUNCIÓN: requireAllRoles
 * ============================================================================
 * Verifica que el usuario tenga TODOS los roles especificados.
 */
function requireAllRoles($role_names, $redirect_url = null, $die = true) {
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!isset($_SESSION['id'])) {
        if ($die) {
            header("Location: login.php");
            exit();
        }
        return false;
    }

    $user_id = $_SESSION['id'];
    global $con;
    $rbac = new RBAC($con);

    if (!$rbac->hasAllRoles($user_id, $role_names)) {
        logUnauthorizedAccess($user_id, implode(',', $role_names), 'all_roles');

        if ($die) {
            if ($redirect_url) {
                header("Location: $redirect_url");
            } else {
                header("Location: access-denied.php?roles=" . urlencode(implode(',', $role_names)));
            }
            exit();
        }

        return false;
    }

    return true;
}

/**
 * ============================================================================
 * FUNCIÓN: requireLogin
 * ============================================================================
 * Solo verifica que el usuario esté logueado (sin verificar permisos)
 */
function requireLogin($redirect_url = 'login.php') {
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!isset($_SESSION['id'])) {
        header("Location: $redirect_url");
        exit();
    }

    return true;
}

/**
 * ============================================================================
 * FUNCIÓN: canAccessOwnDataOnly
 * ============================================================================
 * Verifica si el usuario solo puede acceder a sus propios datos
 * Útil para pacientes que intentan acceder a datos de otros pacientes
 *
 * @param int $resource_owner_id - ID del dueño del recurso
 * @param string $permission_override - Permiso que permite acceder a datos de otros
 */
function canAccessOwnDataOnly($resource_owner_id, $permission_override = null) {
    if (!isset($_SESSION)) {
        session_start();
    }

    $current_user_id = $_SESSION['id'] ?? null;

    if (!$current_user_id) {
        return false;
    }

    // Si es el mismo usuario, permitir acceso
    if ($current_user_id == $resource_owner_id) {
        return true;
    }

    // Si tiene un permiso especial (ej: 'view_all_patients'), permitir acceso
    if ($permission_override && hasPermission($permission_override, $current_user_id)) {
        return true;
    }

    return false;
}

/**
 * ============================================================================
 * FUNCIÓN: requireOwnDataOrPermission
 * ============================================================================
 * Middleware que verifica acceso a datos propios o con permiso
 */
function requireOwnDataOrPermission($resource_owner_id, $permission_override, $redirect_url = null) {
    if (!canAccessOwnDataOnly($resource_owner_id, $permission_override)) {
        logUnauthorizedAccess($_SESSION['id'] ?? null, "access_resource_owner:$resource_owner_id", 'data_access');

        if ($redirect_url) {
            header("Location: $redirect_url");
        } else {
            header("Location: access-denied.php?reason=data_access");
        }
        exit();
    }

    return true;
}

/**
 * ============================================================================
 * FUNCIÓN: logUnauthorizedAccess
 * ============================================================================
 * Registra intentos de acceso no autorizados
 */
function logUnauthorizedAccess($user_id, $required_permission, $access_type) {
    global $con;

    if (!$con) {
        return;
    }

    $ip = getClientIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $requested_page = $_SERVER['REQUEST_URI'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'] ?? '';

    // Tabla de logs de seguridad (debe existir)
    $query = "INSERT INTO security_logs (user_id, event_type, event_description, ip_address, user_agent, additional_data)
              VALUES (?, 'unauthorized_access', ?, ?, ?, ?)";

    $description = "Intento de acceso no autorizado - Tipo: $access_type, Requerido: $required_permission";
    $additional_data = json_encode([
        'access_type' => $access_type,
        'required' => $required_permission,
        'page' => $requested_page,
        'method' => $method
    ]);

    $stmt = mysqli_prepare($con, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "issss", $user_id, $description, $ip, $user_agent, $additional_data);
        mysqli_stmt_execute($stmt);
    }
}

/**
 * ============================================================================
 * FUNCIÓN AUXILIAR: getClientIP
 * ============================================================================
 */
function getClientIP() {
    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }

    return '0.0.0.0';
}

/**
 * ============================================================================
 * FUNCIÓN: checkPermission (Alias de requirePermission que no redirige)
 * ============================================================================
 * Retorna true/false sin redireccionar
 */
function checkPermission($permission_name, $user_id = null) {
    return requirePermission($permission_name, null, false);
}

/**
 * ============================================================================
 * FUNCIÓN: checkRole (Alias de requireRole que no redirige)
 * ============================================================================
 */
function checkRole($role_name, $user_id = null) {
    return requireRole($role_name, null, false);
}

/**
 * ============================================================================
 * HELPERS PARA VISTAS
 * ============================================================================
 */

/**
 * Mostrar elemento HTML solo si tiene permiso
 * Uso: <?php showIfHasPermission('edit_users', '<button>Editar</button>'); ?>
 */
function showIfHasPermission($permission_name, $html) {
    if (checkPermission($permission_name)) {
        echo $html;
    }
}

/**
 * Mostrar elemento HTML solo si tiene rol
 */
function showIfHasRole($role_name, $html) {
    if (checkRole($role_name)) {
        echo $html;
    }
}

/**
 * Deshabilitar elemento HTML si no tiene permiso
 * Útil para formularios
 */
function disableIfNoPermission($permission_name) {
    if (!checkPermission($permission_name)) {
        echo 'disabled';
    }
}

/**
 * Agregar clase CSS si tiene permiso
 */
function classIfHasPermission($permission_name, $class_name) {
    if (checkPermission($permission_name)) {
        echo $class_name;
    }
}

?>
