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
        $assigned_by = $_SESSION['id'];

        $sql = "INSERT INTO role_permissions (role_id, permission_id, assigned_by)
                VALUES ($role_id, $perm_id, $assigned_by)";

        if (mysqli_query($con, $sql)) {
            $success_count++;
        }
    }

    // Registrar en auditoría
    $sql = "INSERT INTO audit_role_changes (user_id, role_id, action, performed_by)
            VALUES (0, $role_id, 'permissions_updated', {$_SESSION['id']})";
    mysqli_query($con, $sql);

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

// ============================================================================
// OBTENER DATOS PARA LA VISTA
// ============================================================================

// Obtener todos los roles
$all_roles = $rbac->getAllRoles();

// Obtener todos los permisos agrupados por categoría
$sql = "SELECT p.*
        FROM permissions p
        ORDER BY p.module ASC, p.permission_name ASC";
$all_permissions_result = mysqli_query($con, $sql);

$permissions_by_category = [];
while ($perm = mysqli_fetch_assoc($all_permissions_result)) {
    $category = $perm['module'] ?? 'general';
    $permissions_by_category[$category][] = $perm;
}

// Obtener todos los usuarios
$users_sql = "SELECT id, full_name, email, user_type FROM users WHERE status = 'active' ORDER BY full_name ASC";
$all_users = mysqli_query($con, $users_sql);

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
        .matrix-table {
            width: 100%;
            font-size: 12px;
        }
        .matrix-table th {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            text-align: center;
            padding: 10px 5px;
            background: #f8f9fa;
        }
        .matrix-table td {
            text-align: center;
            padding: 8px;
        }
        .matrix-table .role-name {
            writing-mode: horizontal-tb;
            text-align: left;
            font-weight: bold;
            background: #f8f9fa;
        }
        .checkbox-lg {
            transform: scale(1.3);
            cursor: pointer;
        }
        .perm-category {
            background: #e9ecef;
            font-weight: bold;
            padding: 10px;
            margin-top: 15px;
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
                                        <h4><i class="fa fa-table"></i> Matriz de Accesos (Roles vs Permisos)</h4>
                                        <p class="text-muted">Vista completa de todos los permisos asignados a cada rol</p>
                                        <hr>

                                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                            <table class="matrix-table table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th class="role-name">Permisos / Roles</th>
                                                        <?php foreach ($all_roles as $role): ?>
                                                        <?php if ($role['status'] === 'active'): ?>
                                                        <th><?php echo htmlspecialchars($role['display_name']); ?></th>
                                                        <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($permissions_by_category as $category => $perms): ?>
                                                    <tr>
                                                        <td colspan="<?php echo count($all_roles) + 1; ?>" class="perm-category">
                                                            <?php echo strtoupper($category); ?> (<?php echo count($perms); ?> permisos)
                                                        </td>
                                                    </tr>
                                                    <?php foreach ($perms as $perm): ?>
                                                    <tr>
                                                        <td style="text-align: left; padding-left: 20px;">
                                                            <small><?php echo htmlspecialchars($perm['display_name']); ?></small><br>
                                                            <code style="font-size: 10px;"><?php echo htmlspecialchars($perm['permission_name']); ?></code>
                                                        </td>
                                                        <?php foreach ($all_roles as $role): ?>
                                                        <?php if ($role['status'] === 'active'): ?>
                                                        <?php
                                                        $role_perms = $rbac->getRolePermissions($role['id']);
                                                        $has_perm = in_array($perm['permission_name'], $role_perms);
                                                        ?>
                                                        <td>
                                                            <?php if ($has_perm): ?>
                                                                <i class="fa fa-check-circle" style="color: #4CAF50; font-size: 16px;"></i>
                                                            <?php else: ?>
                                                                <i class="fa fa-times-circle" style="color: #ccc; font-size: 16px;"></i>
                                                            <?php endif; ?>
                                                        </td>
                                                        <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
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
                    <div class="modal-header" style="background: #FF9800; color: white;">
                        <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                        <h4 class="modal-title">
                            <i class="fa fa-key"></i> Gestionar Permisos: <span id="perm_role_name"></span>
                        </h4>
                    </div>
                    <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
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

        // Gestionar permisos de un rol
        function managePermissions(roleId, roleName) {
            $('#perm_role_id').val(roleId);
            $('#perm_role_name').text(roleName);

            // Cargar permisos por categoría
            const permissionsByCategory = <?php echo json_encode($permissions_by_category); ?>;
            const rolePermissions = <?php
                $role_perms_map = [];
                foreach ($all_roles as $r) {
                    $role_perms_map[$r['id']] = $rbac->getRolePermissions($r['id']);
                }
                echo json_encode($role_perms_map);
            ?>;

            const currentPerms = rolePermissions[roleId] || [];

            let html = '';
            for (const [category, perms] of Object.entries(permissionsByCategory)) {
                html += '<div class="panel panel-default">';
                html += '<div class="panel-heading"><strong>' + category.toUpperCase() + '</strong> (' + perms.length + ' permisos)</div>';
                html += '<div class="panel-body">';

                perms.forEach(perm => {
                    const isChecked = currentPerms.includes(perm.permission_name) ? 'checked' : '';
                    html += '<div class="checkbox">';
                    html += '<label>';
                    html += '<input type="checkbox" name="permissions[]" value="' + perm.id + '" ' + isChecked + '> ';
                    html += '<strong>' + perm.display_name + '</strong>';
                    html += '<br><small class="text-muted">' + perm.permission_name + '</small>';
                    if (perm.description) {
                        html += '<br><small>' + perm.description + '</small>';
                    }
                    html += '</label>';
                    html += '</div>';
                });

                html += '</div></div>';
            }

            $('#permissions_list').html(html);
            $('#permissionsModal').modal('show');
        }
    </script>
</body>
</html>
