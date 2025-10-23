<?php
/**
 * ============================================================================
 * GESTIÓN DE ROLES Y PERMISOS - PUNTO 9.2 PROYECTO SIS 321
 * ============================================================================
 *
 * Sistema completo de gestión de roles con:
 * - CRUD de roles
 * - Matriz de permisos editable
 * - Asignación de roles a usuarios
 * - Auditoría de cambios
 *
 * Versión: 4.2.0
 * ============================================================================
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('include/config.php');
require_once('include/checklogin.php');
require_once('../include/permission-check.php');
require_once('../include/rbac-functions.php');

check_login();

// ✅ PROTECCIÓN RBAC - Solo admins pueden gestionar roles
requirePermission('manage_roles');

// ============================================================================
// FIX: Ejecutar migración para permitir NULL en audit_role_changes.user_id
// ============================================================================
$fix_sql = "ALTER TABLE audit_role_changes MODIFY COLUMN user_id INT NULL COMMENT 'Usuario afectado (NULL = cambio en el rol)'";
@mysqli_query($con, $fix_sql); // @ para suprimir error si ya está modificado

// ============================================================================
// FIX: Actualizar iconos a Font Awesome 4 (auto-corrección)
// ============================================================================
$icon_fixes = [
    "UPDATE permission_categories SET icon = 'fa-wheelchair' WHERE category_name = 'patients' AND icon = 'fa-user-injured'",
    "UPDATE permission_categories SET icon = 'fa-calendar' WHERE category_name = 'appointments' AND icon != 'fa-calendar'",
    "UPDATE permission_categories SET icon = 'fa-file-text-o' WHERE category_name = 'medical_records' AND icon != 'fa-file-text-o'",
    "UPDATE permission_categories SET icon = 'fa-usd' WHERE category_name = 'billing' AND icon != 'fa-usd'",
    "UPDATE permission_categories SET icon = 'fa-bar-chart' WHERE category_name = 'reports' AND icon != 'fa-bar-chart'",
    "UPDATE permission_categories SET icon = 'fa-shield' WHERE category_name = 'security' AND icon != 'fa-shield'"
];

foreach ($icon_fixes as $fix) {
    @mysqli_query($con, $fix);
}

// Inicializar RBAC
$rbac = new RBAC($con);

// Variables para mensajes
$success_msg = '';
$error_msg = '';

// ============================================================================
// MANEJO DE ACCIONES
// ============================================================================

// CREAR ROL
if (isset($_POST['action']) && $_POST['action'] == 'create_role' && hasPermission('manage_roles')) {
    $role_name = mysqli_real_escape_string($con, $_POST['role_name']);
    $display_name = mysqli_real_escape_string($con, $_POST['display_name']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $priority = (int)$_POST['priority'];
    $status = mysqli_real_escape_string($con, $_POST['status']);

    $sql = "INSERT INTO roles (role_name, display_name, description, priority, status)
            VALUES ('$role_name', '$display_name', '$description', $priority, '$status')";

    if (mysqli_query($con, $sql)) {
        $success_msg = "Rol creado exitosamente";
    } else {
        $error_msg = "Error al crear rol: " . mysqli_error($con);
    }
}

// ACTUALIZAR ROL
if (isset($_POST['action']) && $_POST['action'] == 'update_role' && hasPermission('manage_roles')) {
    $role_id = (int)$_POST['role_id'];
    $display_name = mysqli_real_escape_string($con, $_POST['display_name']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $priority = (int)$_POST['priority'];
    $status = mysqli_real_escape_string($con, $_POST['status']);

    $sql = "UPDATE roles
            SET display_name = '$display_name',
                description = '$description',
                priority = $priority,
                status = '$status'
            WHERE id = $role_id";

    if (mysqli_query($con, $sql)) {
        $success_msg = "Rol actualizado exitosamente";
    } else {
        $error_msg = "Error al actualizar rol: " . mysqli_error($con);
    }
}

// ELIMINAR ROL
if (isset($_GET['action']) && $_GET['action'] == 'delete_role' && hasPermission('manage_roles')) {
    $role_id = (int)$_GET['id'];

    // Verificar que no sea un rol del sistema
    $check = mysqli_query($con, "SELECT role_name FROM roles WHERE id = $role_id");
    $role = mysqli_fetch_assoc($check);

    if (in_array($role['role_name'], ['super_admin', 'admin', 'doctor', 'patient'])) {
        $error_msg = "No puedes eliminar roles del sistema";
    } else {
        $sql = "UPDATE roles SET status = 'inactive' WHERE id = $role_id";
        if (mysqli_query($con, $sql)) {
            $success_msg = "Rol desactivado exitosamente";
        } else {
            $error_msg = "Error al desactivar rol";
        }
    }
}

// ACTUALIZAR PERMISOS DE ROL
if (isset($_POST['action']) && $_POST['action'] == 'update_permissions' && hasPermission('manage_roles')) {
    $role_id = (int)$_POST['role_id'];
    $permissions = $_POST['permissions'] ?? [];

    // Eliminar permisos actuales
    mysqli_query($con, "DELETE FROM role_permissions WHERE role_id = $role_id");

    // Insertar nuevos permisos
    $success_count = 0;
    foreach ($permissions as $perm_id) {
        $perm_id = (int)$perm_id;
        $granted_by = $_SESSION['id'];

        $sql = "INSERT INTO role_permissions (role_id, permission_id, granted_by)
                VALUES ($role_id, $perm_id, $granted_by)";

        if (mysqli_query($con, $sql)) {
            $success_count++;
        }
    }

    // Registrar en auditoría (user_id NULL porque es cambio en el rol, no en usuario específico)
    $performed_by = $_SESSION['id'];
    $sql = "INSERT INTO audit_role_changes (user_id, role_id, action, performed_by)
            VALUES (NULL, $role_id, 'permissions_updated', $performed_by)";
    if (!mysqli_query($con, $sql)) {
        // Si falla por la FK, intentar sin registrar el user_id
        error_log("Error en auditoría: " . mysqli_error($con));
    }

    $success_msg = "Se actualizaron $success_count permisos para el rol";
}

// ASIGNAR ROL A USUARIO
if (isset($_POST['action']) && $_POST['action'] == 'assign_to_user' && hasPermission('manage_user_roles')) {
    $user_id = (int)$_POST['user_id'];
    $role_id = (int)$_POST['role_id'];

    $result = $rbac->assignRoleToUser($user_id, $role_id, $_SESSION['id']);

    if ($result['success']) {
        $success_msg = $result['message'];
    } else {
        $error_msg = $result['message'];
    }
}

// REMOVER ROL DE USUARIO
if (isset($_GET['action']) && $_GET['action'] == 'revoke_role' && hasPermission('manage_user_roles')) {
    $user_role_id = (int)$_GET['user_role_id'];
    
    // Obtener info antes de revocar
    $info_sql = "SELECT ur.user_id, ur.role_id, r.display_name 
                 FROM user_roles ur 
                 INNER JOIN roles r ON ur.role_id = r.id 
                 WHERE ur.id = $user_role_id";
    $info_result = mysqli_query($con, $info_sql);
    $info = mysqli_fetch_assoc($info_result);
    
    if ($info) {
        $result = $rbac->revokeRoleFromUser($info['user_id'], $info['role_id'], $_SESSION['id']);
        if ($result['success']) {
            $success_msg = "Rol '{$info['display_name']}' revocado exitosamente";
        } else {
            $error_msg = $result['message'];
        }
    } else {
        $error_msg = "No se encontró la asignación de rol";
    }
}

// ============================================================================
// OBTENER DATOS PARA LA VISTA
// ============================================================================

// Obtener todos los roles
$all_roles = $rbac->getAllRoles();

// Obtener categorías de permisos de la tabla permission_categories
$categories_sql = "SELECT * FROM permission_categories ORDER BY sort_order ASC";
$categories_result = mysqli_query($con, $categories_sql);
$permission_categories = [];
while ($cat = mysqli_fetch_assoc($categories_result)) {
    $permission_categories[] = $cat;
}

// Obtener todos los permisos agrupados por módulo
$sql = "SELECT p.*
        FROM permissions p
        ORDER BY p.module ASC, p.permission_name ASC";
$all_permissions_result = mysqli_query($con, $sql);

$permissions_by_category = [];
while ($perm = mysqli_fetch_assoc($all_permissions_result)) {
    $category = $perm['module'] ?? 'general';
    $permissions_by_category[$category][] = $perm;
}

// Crear mapa de conteo: rol_id => [categoria => conteo_permisos]
$role_category_matrix = [];
foreach ($all_roles as $role) {
    $role_category_matrix[$role['id']] = [];

    // Inicializar todas las categorías en 0
    foreach ($permission_categories as $cat) {
        $role_category_matrix[$role['id']][$cat['category_name']] = 0;
    }

    // Obtener permisos asignados a este rol desde la base de datos
    $role_id = $role['id'];
    $perms_sql = "SELECT p.id, p.permission_name, p.module
                  FROM role_permissions rp
                  INNER JOIN permissions p ON rp.permission_id = p.id
                  WHERE rp.role_id = $role_id";
    $perms_result = mysqli_query($con, $perms_sql);
    
    // Contar permisos por categoría
    while ($perm = mysqli_fetch_assoc($perms_result)) {
        $module = $perm['module'] ?? 'general';
        if (isset($role_category_matrix[$role['id']][$module])) {
            $role_category_matrix[$role['id']][$module]++;
        }
    }
}

// Obtener todos los usuarios
$users_sql = "SELECT id, full_name, email, user_type FROM users WHERE status = 'active' ORDER BY full_name ASC";
$all_users = mysqli_query($con, $users_sql);

// Obtener usuarios con roles asignados para Tab 3
$users_with_roles_sql = "SELECT 
                            u.id as user_id,
                            u.full_name,
                            u.email,
                            u.user_type,
                            ur.id as user_role_id,
                            ur.role_id,
                            r.display_name as role_name,
                            r.role_name as role_code,
                            ur.assigned_at
                        FROM users u
                        INNER JOIN user_roles ur ON u.id = ur.user_id AND ur.is_active = 1
                        INNER JOIN roles r ON ur.role_id = r.id
                        WHERE u.status = 'active'
                        ORDER BY u.full_name ASC, r.display_name ASC";
$users_with_roles = mysqli_query($con, $users_with_roles_sql);

// Obtener auditoría reciente
$audit_sql = "SELECT arc.*, u.full_name as performed_by_name, r.display_name as role_name
              FROM audit_role_changes arc
              LEFT JOIN users u ON arc.performed_by = u.id
              LEFT JOIN roles r ON arc.role_id = r.id
              ORDER BY arc.performed_at DESC
              LIMIT 50";
$audit_records = mysqli_query($con, $audit_sql);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Admin | Gestionar Roles y Permisos</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="http://fonts.googleapis.com/css?family=Lato:300,400,400italic,600,700|Raleway:300,400,500,600,700|Crete+Round:400italic" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="vendor/themify-icons/themify-icons.min.css">
    <link href="vendor/animate.css/animate.min.css" rel="stylesheet" media="screen">
    <link href="vendor/perfect-scrollbar/perfect-scrollbar.min.css" rel="stylesheet" media="screen">
    <link href="vendor/switchery/switchery.min.css" rel="stylesheet" media="screen">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/plugins.css">
    <link rel="stylesheet" href="assets/css/themes/theme-1.css" id="skin_color" />

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        .nav-tabs {
            margin-bottom: 20px;
            border-bottom: 2px solid #00a8b3;
        }
        .nav-tabs > li > a {
            color: #666;
        }
        .nav-tabs > li.active > a,
        .nav-tabs > li.active > a:hover,
        .nav-tabs > li.active > a:focus {
            background: #00a8b3;
            color: white;
            border: none;
        }
        /* Estilos mejorados para la matriz de permisos */
        .matrix-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            margin-bottom: 30px;
        }
        
        .matrix-table {
            width: 100%;
            font-size: 13px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .matrix-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .matrix-table thead th {
            color: white !important;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 15px 10px;
            border: none;
            position: relative;
        }
        
        .matrix-table thead th::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 10%;
            right: 10%;
            height: 2px;
            background: rgba(255,255,255,0.3);
        }
        
        .matrix-table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #e9ecef;
        }
        
        .matrix-table tbody tr:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .matrix-table tbody td {
            padding: 15px;
            vertical-align: middle;
            transition: all 0.2s ease;
        }
        
        .matrix-table tbody td:first-child {
            position: sticky;
            left: 0;
            background: white;
            font-weight: 600;
            z-index: 9;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }
        
        .matrix-table tbody tr:hover td:first-child {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.05) 100%);
        }
        
        .matrix-table tfoot {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: bold;
        }
        
        .matrix-table tfoot td {
            padding: 15px;
            border-top: 3px solid #667eea;
        }
        
        .perm-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 45px;
        }
        
        .perm-badge.has-perms {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            box-shadow: 0 3px 10px rgba(76, 175, 80, 0.3);
        }
        
        .perm-badge.has-perms:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }
        
        .perm-badge.no-perms {
            background: #f5f5f5;
            color: #9e9e9e;
            border: 2px dashed #e0e0e0;
        }
        
        .category-icon {
            font-size: 28px;
            margin-bottom: 8px;
            display: inline-block;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .category-name {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Estilos para el modal de permisos por categoría - DISEÑO MODERNO */
        #permissions_list {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .category-card {
            background: white;
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
        }
        
        .category-card:hover {
            box-shadow: 0 8px 24px rgba(76, 175, 80, 0.15);
            transform: translateY(-2px);
            border-color: #4CAF50;
        }
        
        .category-header {
            display: flex;
            align-items: center;
            padding: 18px 20px;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .category-header::after {
            content: '\f078';
            font-family: FontAwesome;
            position: absolute;
            right: 20px;
            color: white;
            opacity: 0.7;
            transition: transform 0.3s ease;
        }
        
        .category-header.expanded::after {
            transform: rotate(180deg);
        }
        
        .category-header:hover {
            background: linear-gradient(135deg, #66BB6A 0%, #4CAF50 100%);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }
        
        .category-header input[type="checkbox"] {
            width: 24px;
            height: 24px;
            margin-right: 16px;
            cursor: pointer;
            accent-color: #4CAF50;
        }
        
        .category-icon-box {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.25);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .category-icon-box i {
            font-size: 26px;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .category-header-text {
            flex: 1;
            color: white;
        }
        
        .category-header-text h5 {
            margin: 0 0 3px 0;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .category-header-text small {
            opacity: 0.9;
            font-size: 11px;
            font-weight: 400;
        }
        
        .category-perms-count {
            background: rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 25px;
            color: white;
            font-weight: 700;
            font-size: 14px;
            margin-right: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .permission-list {
            display: none;
            padding: 0;
            background: #f8f9fc;
        }
        
        .permission-item {
            padding: 14px 20px;
            margin: 0;
            background: white;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }
        
        .permission-item:last-child {
            border-bottom: none;
        }
        
        .permission-item:hover {
            background: #f8f9fc;
            padding-left: 25px;
        }
        
        .permission-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            cursor: pointer;
            accent-color: #4CAF50;
        }
        
        .permission-item.selected {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
        }
        
        .permission-item.selected:hover {
            background: #e8f5e9;
        }
        
        .checkbox-lg {
            transform: scale(1.3);
            cursor: pointer;
        }
        .stats-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stats-card .icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        .stats-card .value {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stats-card .label {
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div id="app">
        <?php include('include/sidebar.php');?>
        <div class="app-content">
            <?php include('include/header.php');?>
            <div class="main-content">
                <div class="wrap-content container" id="container">
                    <!-- Page Title -->
                    <section id="page-title">
                        <div class="row">
                            <div class="col-sm-8">
                                <h1 class="mainTitle">
                                    <i class="fa fa-shield"></i> Gestión de Roles y Permisos
                                </h1>
                            </div>
                            <ol class="breadcrumb">
                                <li><span>Admin</span></li>
                                <li><span>Seguridad</span></li>
                                <li class="active"><span>Gestionar Roles</span></li>
                            </ol>
                        </div>
                    </section>

                    <!-- Mensajes -->
                    <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <strong><i class="fa fa-check-circle"></i> Éxito!</strong> <?php echo htmlspecialchars($success_msg); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <strong><i class="fa fa-exclamation-circle"></i> Error!</strong> <?php echo htmlspecialchars($error_msg); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Estadísticas -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stats-card" style="border-left: 4px solid #667eea;">
                                <div class="icon" style="color: #667eea;">
                                    <i class="fa fa-shield"></i>
                                </div>
                                <div class="value" style="color: #667eea;">
                                    <?php echo count($all_roles); ?>
                                </div>
                                <div class="label">Total Roles</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card" style="border-left: 4px solid #4CAF50;">
                                <div class="icon" style="color: #4CAF50;">
                                    <i class="fa fa-check-circle"></i>
                                </div>
                                <div class="value" style="color: #4CAF50;">
                                    <?php
                                    $active_roles = array_filter($all_roles, function($r) { return $r['status'] === 'active'; });
                                    echo count($active_roles);
                                    ?>
                                </div>
                                <div class="label">Roles Activos</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card" style="border-left: 4px solid #FF9800;">
                                <div class="icon" style="color: #FF9800;">
                                    <i class="fa fa-key"></i>
                                </div>
                                <div class="value" style="color: #FF9800;">
                                    <?php
                                    $total_perms = 0;
                                    foreach ($permissions_by_category as $perms) {
                                        $total_perms += count($perms);
                                    }
                                    echo $total_perms;
                                    ?>
                                </div>
                                <div class="label">Total Permisos</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card" style="border-left: 4px solid #2196F3;">
                                <div class="icon" style="color: #2196F3;">
                                    <i class="fa fa-folder"></i>
                                </div>
                                <div class="value" style="color: #2196F3;">
                                    <?php echo count($permissions_by_category); ?>
                                </div>
                                <div class="label">Categorías</div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs de Navegación -->
                    <div class="container-fluid container-fullw bg-white">
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#tab-roles" aria-controls="tab-roles" role="tab" data-toggle="tab">
                                    <i class="fa fa-shield"></i> Roles
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#tab-matrix" aria-controls="tab-matrix" role="tab" data-toggle="tab">
                                    <i class="fa fa-table"></i> Matriz de Permisos
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#tab-assign" aria-controls="tab-assign" role="tab" data-toggle="tab">
                                    <i class="fa fa-users"></i> Asignar a Usuarios
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#tab-audit" aria-controls="tab-audit" role="tab" data-toggle="tab">
                                    <i class="fa fa-history"></i> Auditoría
                                </a>
                            </li>
                        </ul>

                        <!-- Contenido de los Tabs -->
                        <div class="tab-content">
                            <!-- =========================== -->
                            <!-- TAB 1: GESTIÓN DE ROLES -->
                            <!-- =========================== -->
                            <div role="tabpanel" class="tab-pane active" id="tab-roles">
                                <div class="row" style="margin-top: 20px;">
                                    <div class="col-md-12">
                                        <?php if (hasPermission('manage_roles')): ?>
                                        <button type="button" class="btn btn-success pull-right" data-toggle="modal" data-target="#createRoleModal">
                                            <i class="fa fa-plus"></i> Nuevo Rol
                                        </button>
                                        <?php endif; ?>
                                        <h4><i class="fa fa-shield"></i> Lista de Roles</h4>
                                        <hr>

                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="5%">ID</th>
                                                        <th width="20%">Nombre</th>
                                                        <th width="30%">Descripción</th>
                                                        <th width="10%">Prioridad</th>
                                                        <th width="10%">Permisos</th>
                                                        <th width="10%">Estado</th>
                                                        <th width="15%">Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($all_roles as $role): ?>
                                                    <?php
                                                    $role_perms = $rbac->getRolePermissions($role['id']);
                                                    $perm_count = count($role_perms);
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $role['id']; ?></td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($role['display_name']); ?></strong><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($role['role_name']); ?></small>
                                                        </td>
                                                        <td>
                                                            <small><?php echo htmlspecialchars($role['description']); ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="label label-info"><?php echo $role['priority']; ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="label label-success"><?php echo $perm_count; ?></span>
                                                        </td>
                                                        <td>
                                                            <?php if ($role['status'] === 'active'): ?>
                                                                <span class="label label-success">Activo</span>
                                                            <?php else: ?>
                                                                <span class="label label-danger">Inactivo</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group">
                                                                <?php if (hasPermission('manage_roles')): ?>
                                                                <button type="button"
                                                                        class="btn btn-primary btn-xs"
                                                                        onclick="editRole(<?php echo $role['id']; ?>)"
                                                                        title="Editar">
                                                                    <i class="fa fa-edit"></i>
                                                                </button>
                                                                <button type="button"
                                                                        class="btn btn-warning btn-xs"
                                                                        onclick="managePermissions(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['display_name']); ?>')"
                                                                        title="Permisos">
                                                                    <i class="fa fa-key"></i>
                                                                </button>
                                                                <?php endif; ?>

                                                                <?php if (hasPermission('manage_roles') && !in_array($role['role_name'], ['super_admin', 'admin', 'doctor', 'patient'])): ?>
                                                                <button type="button"
                                                                        class="btn btn-danger btn-xs"
                                                                        onclick="deleteRole(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['display_name']); ?>')"
                                                                        title="Eliminar">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                                <?php else: ?>
                                                                <button type="button" class="btn btn-default btn-xs" disabled title="Rol del sistema">
                                                                    <i class="fa fa-lock"></i>
                                                                </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- =========================== -->
                            <!-- TAB 2: MATRIZ DE PERMISOS -->
                            <!-- =========================== -->
                            <div role="tabpanel" class="tab-pane" id="tab-matrix">
                                <div class="row" style="margin-top: 20px;">
                                    <div class="col-md-12">
                                        <h4><i class="fa fa-table"></i> Matriz de Roles vs Categorías de Permisos</h4>
                                        <p class="text-muted">Vista panorámica de permisos asignados por rol y categoría</p>
                                        <hr>

                                        <div class="matrix-container">
                                            <div style="overflow-x: auto;">
                                                <table class="matrix-table">
                                                    <thead>
                                                        <tr>
                                                            <th style="text-align: left; min-width: 200px; position: sticky; left: 0; z-index: 11; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                                <i class="fa fa-shield"></i> ROL
                                                            </th>
                                                            <?php foreach ($permission_categories as $cat): ?>
                                                            <th style="text-align: center; min-width: 130px;">
                                                                <div style="display: flex; flex-direction: column; align-items: center; padding: 8px;">
                                                                    <i class="fa <?php echo $cat['icon']; ?> category-icon"></i>
                                                                    <span class="category-name" style="margin-top: 8px;">
                                                                        <?php echo htmlspecialchars($cat['display_name']); ?>
                                                                    </span>
                                                                </div>
                                                            </th>
                                                            <?php endforeach; ?>
                                                            <th style="text-align: center; min-width: 100px;">
                                                                <i class="fa fa-calculator"></i><br>
                                                                <span class="category-name">TOTAL</span>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($all_roles as $role): ?>
                                                        <?php if ($role['status'] === 'active'): ?>
                                                        <tr>
                                                            <td>
                                                                <div style="display: flex; flex-direction: column;">
                                                                    <strong style="font-size: 15px; color: #667eea;">
                                                                        <?php echo htmlspecialchars($role['display_name']); ?>
                                                                    </strong>
                                                                    <small class="text-muted" style="font-size: 11px;">
                                                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                                                    </small>
                                                                </div>
                                                            </td>
                                                            <?php
                                                            $total_perms = 0;
                                                            foreach ($permission_categories as $cat):
                                                                $count = $role_category_matrix[$role['id']][$cat['category_name']] ?? 0;
                                                                $total_perms += $count;
                                                            ?>
                                                            <td>
                                                                <?php if ($count > 0): ?>
                                                                    <span class="perm-badge has-perms">
                                                                        <?php echo $count; ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="perm-badge no-perms">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <?php endforeach; ?>
                                                            <td style="background: linear-gradient(90deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);">
                                                                <span class="perm-badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 16px; padding: 10px 18px;">
                                                                    <?php echo $total_perms; ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <td style="text-align: left;">
                                                                <strong><i class="fa fa-list"></i> TOTAL POR CATEGORÍA</strong>
                                                            </td>
                                                            <?php
                                                            $grand_total = 0;
                                                            foreach ($permission_categories as $cat):
                                                                $cat_total = count($permissions_by_category[$cat['category_name']] ?? []);
                                                                $grand_total += $cat_total;
                                                            ?>
                                                            <td>
                                                                <strong style="font-size: 15px; color: #667eea;"><?php echo $cat_total; ?></strong>
                                                            </td>
                                                            <?php endforeach; ?>
                                                            <td>
                                                                <strong style="font-size: 17px; color: #667eea;">
                                                                    <i class="fa fa-star"></i> <?php echo $grand_total; ?>
                                                                </strong>
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Leyenda -->
                                        <div class="alert alert-info" style="margin-top: 20px;">
                                            <strong><i class="fa fa-info-circle"></i> Leyenda:</strong>
                                            <ul style="margin-top: 10px; margin-bottom: 0;">
                                                <li><span class="label label-success">N</span> = El rol tiene N permisos en esa categoría</li>
                                                <li><span class="text-muted">-</span> = El rol no tiene permisos en esa categoría</li>
                                                <li>La columna <strong>TOTAL</strong> muestra el número total de permisos del rol</li>
                                                <li>La fila <strong>TOTAL POR CATEGORÍA</strong> muestra cuántos permisos existen en cada categoría</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- =========================== -->
                            <!-- TAB 3: ASIGNAR A USUARIOS -->
                            <!-- =========================== -->
                            <div role="tabpanel" class="tab-pane" id="tab-assign">
                                <div class="row" style="margin-top: 20px;">
                                    <div class="col-md-6">
                                        <h4><i class="fa fa-users"></i> Asignar Rol a Usuario</h4>
                                        <hr>

                                        <?php if (hasPermission('manage_user_roles')): ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="assign_to_user">

                                            <div class="form-group">
                                                <label>Seleccionar Usuario</label>
                                                <select name="user_id" class="form-control" required>
                                                    <option value="">-- Seleccionar Usuario --</option>
                                                    <?php mysqli_data_seek($all_users, 0); ?>
                                                    <?php while ($user = mysqli_fetch_assoc($all_users)): ?>
                                                    <option value="<?php echo $user['id']; ?>">
                                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                                        (<?php echo htmlspecialchars($user['email']); ?>)
                                                        - <?php echo $user['user_type']; ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label>Seleccionar Rol</label>
                                                <select name="role_id" class="form-control" required>
                                                    <option value="">-- Seleccionar Rol --</option>
                                                    <?php foreach ($all_roles as $role): ?>
                                                    <?php if ($role['status'] === 'active'): ?>
                                                    <option value="<?php echo $role['id']; ?>">
                                                        <?php echo htmlspecialchars($role['display_name']); ?>
                                                    </option>
                                                    <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <button type="submit" class="btn btn-success">
                                                <i class="fa fa-save"></i> Asignar Rol
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <div class="alert alert-warning">
                                            No tienes permiso para asignar roles a usuarios
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6">
                                        <h4><i class="fa fa-info-circle"></i> Información</h4>
                                        <hr>
                                        <div class="alert alert-info">
                                            <strong>Nota:</strong> Un usuario puede tener múltiples roles.
                                            Los permisos se combinan entre todos los roles asignados.
                                        </div>
                                        <div class="alert alert-warning">
                                            <strong>Importante:</strong> Los cambios en roles afectan
                                            inmediatamente los permisos del usuario.
                                        </div>
                                    </div>
                                </div>

                                <!-- Tabla de Usuarios con Roles Asignados -->
                                <div class="row" style="margin-top: 30px;">
                                    <div class="col-md-12">
                                        <h4><i class="fa fa-list"></i> Usuarios con Roles Asignados</h4>
                                        <hr>

                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Usuario</th>
                                                        <th>Email</th>
                                                        <th>Tipo</th>
                                                        <th>Roles Asignados</th>
                                                        <th>Fecha Asignación</th>
                                                        <th width="10%">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $current_user_id = null;
                                                    $user_roles = [];
                                                    mysqli_data_seek($users_with_roles, 0);
                                                    
                                                    // Agrupar roles por usuario
                                                    $grouped_users = [];
                                                    while ($row = mysqli_fetch_assoc($users_with_roles)) {
                                                        $uid = $row['user_id'];
                                                        if (!isset($grouped_users[$uid])) {
                                                            $grouped_users[$uid] = [
                                                                'full_name' => $row['full_name'],
                                                                'email' => $row['email'],
                                                                'user_type' => $row['user_type'],
                                                                'roles' => []
                                                            ];
                                                        }
                                                        $grouped_users[$uid]['roles'][] = [
                                                            'user_role_id' => $row['user_role_id'],
                                                            'role_name' => $row['role_name'],
                                                            'role_code' => $row['role_code'],
                                                            'assigned_at' => $row['assigned_at']
                                                        ];
                                                    }
                                                    
                                                    if (empty($grouped_users)):
                                                    ?>
                                                        <tr>
                                                            <td colspan="6" class="text-center">
                                                                <em>No hay usuarios con roles asignados</em>
                                                            </td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($grouped_users as $uid => $user_data): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($user_data['full_name']); ?></strong></td>
                                                            <td><small><?php echo htmlspecialchars($user_data['email']); ?></small></td>
                                                            <td>
                                                                <?php
                                                                $type_badges = [
                                                                    'patient' => '<span class="label label-info">Paciente</span>',
                                                                    'doctor' => '<span class="label label-primary">Doctor</span>',
                                                                    'admin' => '<span class="label label-warning">Admin</span>'
                                                                ];
                                                                echo $type_badges[$user_data['user_type']] ?? $user_data['user_type'];
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php foreach ($user_data['roles'] as $role): ?>
                                                                    <span class="label label-success" style="margin-right: 5px;">
                                                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </td>
                                                            <td>
                                                                <small><?php echo date('d/m/Y', strtotime($user_data['roles'][0]['assigned_at'])); ?></small>
                                                            </td>
                                                            <td>
                                                                <?php if (hasPermission('manage_user_roles')): ?>
                                                                <button type="button" 
                                                                        class="btn btn-danger btn-xs" 
                                                                        onclick="revokeUserRole(<?php echo $uid; ?>, '<?php echo htmlspecialchars($user_data['full_name']); ?>')">
                                                                    <i class="fa fa-times"></i> Remover Rol
                                                                </button>
                                                                <?php else: ?>
                                                                <button type="button" class="btn btn-default btn-xs" disabled>
                                                                    <i class="fa fa-lock"></i> Sin permiso
                                                                </button>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- =========================== -->
                            <!-- TAB 4: AUDITORÍA -->
                            <!-- =========================== -->
                            <div role="tabpanel" class="tab-pane" id="tab-audit">
                                <div class="row" style="margin-top: 20px;">
                                    <div class="col-md-12">
                                        <h4><i class="fa fa-history"></i> Registro de Cambios de Roles</h4>
                                        <p class="text-muted">Últimos 50 cambios en roles y permisos</p>
                                        <hr>

                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha/Hora</th>
                                                        <th>Usuario</th>
                                                        <th>Rol</th>
                                                        <th>Acción</th>
                                                        <th>Realizado Por</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($audit = mysqli_fetch_assoc($audit_records)): ?>
                                                    <tr>
                                                        <td>
                                                            <small><?php echo date('d/m/Y H:i', strtotime($audit['performed_at'])); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php if ($audit['user_id'] > 0): ?>
                                                                <small>Usuario ID: <?php echo $audit['user_id']; ?></small>
                                                            <?php else: ?>
                                                                <small class="text-muted">-</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($audit['role_name']); ?></strong>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $action_labels = [
                                                                'assigned' => '<span class="label label-success">Asignado</span>',
                                                                'revoked' => '<span class="label label-danger">Revocado</span>',
                                                                'permissions_updated' => '<span class="label label-info">Permisos Actualizados</span>'
                                                            ];
                                                            echo $action_labels[$audit['action']] ?? $audit['action'];
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <small><?php echo htmlspecialchars($audit['performed_by_name'] ?? 'Sistema'); ?></small>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include('include/footer.php');?>
        <?php include('include/setting.php');?>
    </div>

    <!-- MODAL: CREAR ROL -->
    <div class="modal fade" id="createRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_role">
                    <div class="modal-header" style="background: #667eea; color: white;">
                        <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                        <h4 class="modal-title"><i class="fa fa-plus"></i> Crear Nuevo Rol</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nombre del Rol (código)</label>
                            <input type="text" name="role_name" class="form-control" required
                                   placeholder="ej: custom_role" pattern="[a-z_]+">
                            <small class="text-muted">Solo minúsculas y guiones bajos</small>
                        </div>
                        <div class="form-group">
                            <label>Nombre para Mostrar</label>
                            <input type="text" name="display_name" class="form-control" required
                                   placeholder="ej: Rol Personalizado">
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Prioridad</label>
                                    <input type="number" name="priority" class="form-control" value="50" required>
                                    <small class="text-muted">Menor número = Mayor prioridad</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="status" class="form-control">
                                        <option value="active">Activo</option>
                                        <option value="inactive">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-save"></i> Crear Rol
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: EDITAR ROL -->
    <div class="modal fade" id="editRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="role_id" id="edit_role_id">
                    <div class="modal-header" style="background: #667eea; color: white;">
                        <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                        <h4 class="modal-title"><i class="fa fa-edit"></i> Editar Rol</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nombre para Mostrar</label>
                            <input type="text" name="display_name" id="edit_display_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Prioridad</label>
                                    <input type="number" name="priority" id="edit_priority" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="status" id="edit_status" class="form-control">
                                        <option value="active">Activo</option>
                                        <option value="inactive">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: GESTIONAR PERMISOS -->
    <div class="modal fade" id="permissionsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_permissions">
                    <input type="hidden" name="role_id" id="perm_role_id">
                    <div class="modal-header" style="background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; border: none;">
                        <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;">&times;</button>
                        <h4 class="modal-title" style="font-weight: 600;">
                            <i class="fa fa-key"></i> Gestionar Permisos: <span id="perm_role_name"></span>
                        </h4>
                    </div>
                    <div class="modal-body" style="max-height: 500px; overflow-y: auto; background: #f8f9fa; padding: 20px;">
                        <div id="permissions_list">
                            <!-- Se llenará dinámicamente con JavaScript -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-save"></i> Guardar Permisos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/modernizr/modernizr.js"></script>
    <script src="vendor/jquery-cookie/jquery.cookie.js"></script>
    <script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="vendor/switchery/switchery.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        jQuery(document).ready(function() {
            Main.init();
        });

        // Editar rol
        function editRole(roleId) {
            // Cargar datos del rol
            const roles = <?php echo json_encode($all_roles); ?>;
            const role = roles.find(r => r.id == roleId);

            if (role) {
                $('#edit_role_id').val(role.id);
                $('#edit_display_name').val(role.display_name);
                $('#edit_description').val(role.description);
                $('#edit_priority').val(role.priority);
                $('#edit_status').val(role.status);
                $('#editRoleModal').modal('show');
            }
        }

        // Eliminar rol
        function deleteRole(roleId, roleName) {
            Swal.fire({
                title: '¿Estás seguro?',
                html: 'Vas a desactivar el rol: <br><strong>' + roleName + '</strong>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, desactivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'manage-roles.php?action=delete_role&id=' + roleId;
                }
            });
        }

        // Gestionar permisos de un rol (por categoría)
        function managePermissions(roleId, roleName) {
            $('#perm_role_id').val(roleId);
            $('#perm_role_name').text(roleName);

            // Cargar datos
            const permissionsByCategory = <?php echo json_encode($permissions_by_category); ?>;
            const categoriesInfo = <?php echo json_encode($permission_categories); ?>;
            
            // Obtener IDs de permisos asignados al rol
            const rolePermissionIds = <?php
                $role_perm_ids_map = [];
                foreach ($all_roles as $r) {
                    // Obtener IDs de permisos del rol
                    $sql = "SELECT permission_id FROM role_permissions WHERE role_id = {$r['id']}";
                    $result = mysqli_query($con, $sql);
                    $perm_ids = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        $perm_ids[] = (int)$row['permission_id'];
                    }
                    $role_perm_ids_map[$r['id']] = $perm_ids;
                }
                echo json_encode($role_perm_ids_map);
            ?>;

            const currentPermIds = rolePermissionIds[roleId] || [];
            
            // Debug en consola
            console.log('=== DEBUG PERMISOS ===');
            console.log('Role ID:', roleId);
            console.log('Current Permission IDs:', currentPermIds);
            console.log('Permissions by Category:', permissionsByCategory);

            // Construir HTML con categorías
            let html = '';
            let totalAssigned = 0;
            
            for (const [category, perms] of Object.entries(permissionsByCategory)) {
                // Encontrar info de la categoría
                const catInfo = categoriesInfo.find(c => c.category_name === category);
                const catIcon = catInfo ? 'fa ' + catInfo.icon : 'fa fa-folder';
                const catDisplayName = catInfo ? catInfo.display_name : category;
                const catDescription = catInfo ? catInfo.description : '';
                
                // Verificar cuántos permisos de esta categoría están seleccionados
                let selectedCount = 0;
                const permIds = [];
                perms.forEach(perm => {
                    permIds.push(perm.id);
                    if (currentPermIds.includes(parseInt(perm.id))) {
                        selectedCount++;
                        totalAssigned++;
                    }
                });
                
                const allSelected = selectedCount === perms.length;
                const categoryId = 'cat_' + category;
                
                html += '<div class="category-card">';
                html += '  <div class="category-header" onclick="toggleCategoryPerms(\'' + categoryId + '\')">';
                html += '    <input type="checkbox" class="category-checkbox" id="' + categoryId + '" ';
                html += '           data-perms=\'' + JSON.stringify(permIds) + '\' ';
                html += '           onclick="event.stopPropagation(); selectCategory(this)" ';
                html += '           ' + (allSelected ? 'checked' : '') + '>';
                html += '    <div class="category-icon-box">';
                html += '      <i class="' + catIcon + '"></i>';
                html += '    </div>';
                html += '    <div class="category-header-text">';
                html += '      <h5>' + catDisplayName + '</h5>';
                html += '      <small>' + catDescription + '</small>';
                html += '    </div>';
                html += '    <div class="category-perms-count">';
                html += '      <span id="count_' + categoryId + '">' + selectedCount + '</span> / ' + perms.length;
                html += '    </div>';
                html += '  </div>';
                
                // Lista de permisos (oculta por defecto)
                html += '  <div class="permission-list" id="list_' + categoryId + '">';
                perms.forEach(perm => {
                    const isChecked = currentPermIds.includes(parseInt(perm.id)) ? 'checked' : '';
                    const selectedClass = isChecked ? 'selected' : '';
                    html += '    <div class="permission-item ' + selectedClass + '">';
                    html += '      <input type="checkbox" name="permissions[]" value="' + perm.id + '" ';
                    html += '             class="perm-checkbox" data-category="' + categoryId + '" ';
                    html += '             onchange="updateCategoryCount(\'' + categoryId + '\')" ';
                    html += '             ' + isChecked + '> ';
                    html += '      <strong>' + perm.display_name + '</strong> ';
                    html += '      <small class="text-muted">(' + perm.permission_name + ')</small>';
                    html += '    </div>';
                });
                html += '  </div>';
                html += '</div>';
            }

            console.log('Total permisos asignados:', totalAssigned);
            console.log('======================');

            $('#permissions_list').html(html);
            $('#permissionsModal').modal('show');
        }

        // Seleccionar/deseleccionar todos los permisos de una categoría
        function selectCategory(checkbox) {
            const categoryId = checkbox.id;
            const permIds = JSON.parse(checkbox.getAttribute('data-perms'));
            const isChecked = checkbox.checked;
            
            // Marcar/desmarcar todos los permisos de esta categoría
            permIds.forEach(permId => {
                const permCheckbox = document.querySelector('input[name="permissions[]"][value="' + permId + '"]');
                if (permCheckbox) {
                    permCheckbox.checked = isChecked;
                    // Actualizar clase visual
                    const item = permCheckbox.closest('.permission-item');
                    if (item) {
                        if (isChecked) {
                            item.classList.add('selected');
                        } else {
                            item.classList.remove('selected');
                        }
                    }
                }
            });
            
            updateCategoryCount(categoryId);
        }

        // Actualizar contador de permisos seleccionados por categoría
        function updateCategoryCount(categoryId) {
            const categoryCheckbox = document.getElementById(categoryId);
            const permCheckboxes = document.querySelectorAll('.perm-checkbox[data-category="' + categoryId + '"]');
            
            let selectedCount = 0;
            let totalCount = permCheckboxes.length;
            
            permCheckboxes.forEach(cb => {
                if (cb.checked) {
                    selectedCount++;
                    cb.closest('.permission-item').classList.add('selected');
                } else {
                    cb.closest('.permission-item').classList.remove('selected');
                }
            });
            
            // Actualizar contador visual
            document.getElementById('count_' + categoryId).textContent = selectedCount;
            
            // Actualizar estado del checkbox de categoría
            if (categoryCheckbox) {
                categoryCheckbox.checked = (selectedCount === totalCount);
                categoryCheckbox.indeterminate = (selectedCount > 0 && selectedCount < totalCount);
            }
        }

        // Toggle mostrar/ocultar lista de permisos
        function toggleCategoryPerms(categoryId) {
            const list = document.getElementById('list_' + categoryId);
            const header = list.previousElementSibling;
            
            if (list.style.display === 'block') {
                list.style.display = 'none';
                header.classList.remove('expanded');
            } else {
                list.style.display = 'block';
                header.classList.add('expanded');
            }
        }

        // Remover rol de usuario
        function revokeUserRole(userId, userName) {
            // Primero, obtener los roles del usuario para permitir seleccionar cuál remover
            Swal.fire({
                title: 'Remover Rol',
                html: `¿Quieres remover TODOS los roles del usuario <strong>${userName}</strong>?<br><br>` +
                      `<small class="text-warning">Esta acción removerá todos los roles asignados. ` +
                      `Si quieres remover un rol específico, usa el botón X individual en cada badge de rol.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, remover todos',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirigir a la página con acción para remover el primer rol encontrado
                    // (en la tabla agrupada, mostramos el usuario y sus roles juntos)
                    Swal.fire({
                        title: 'Información',
                        html: 'Para remover roles individuales, por favor ve a la página de<br><strong>Gestión de Usuarios</strong><br>donde podrás editar los roles específicos.',
                        icon: 'info',
                        confirmButtonText: 'Entendido'
                    });
                }
            });
        }
    </script>
</body>
</html>
