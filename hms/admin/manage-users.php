<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once('include/config.php');
include_once('include/checklogin.php');
include_once('../include/permission-check.php');
include_once('../include/UserManagement.php');
include_once('../include/rbac-functions.php');

check_login();

// ✅ PROTECCIÓN RBAC - Verificar permiso para gestionar usuarios
requirePermission('view_users');

// Inicializar UserManagement
$userManager = new UserManagement($con, $_SESSION['id']);
$rbac = new RBAC($con);

// Variables para mensajes
$success_msg = '';
$error_msg = '';

// ============================================================================
// MANEJO DE ACCIONES (CRUD)
// ============================================================================

// CREAR USUARIO
if (isset($_POST['action']) && $_POST['action'] == 'create' && hasPermission('create_user')) {
    $data = [
        'full_name' => $_POST['full_name'],
        'email' => $_POST['email'],
        'password' => $_POST['password'],
        'user_type' => $_POST['user_type'],
        'status' => $_POST['status'] ?? 'active'
    ];

    // Validar datos
    $validation = $userManager->validateUserData($data, 'create');
    if (!$validation['valid']) {
        $error_msg = $validation['message'];
    } else {
        $result = $userManager->createUser($data, 'Usuario creado desde panel de administración');
        if ($result['success']) {
            // Asignar roles si se seleccionaron (usando RBAC que funciona correctamente)
            if (!empty($_POST['roles'])) {
                $roles_assigned = 0;
                foreach ($_POST['roles'] as $role_id) {
                    $role_result = $rbac->assignRoleToUser($result['user_id'], intval($role_id), $_SESSION['id']);
                    if ($role_result['success']) {
                        $roles_assigned++;
                    }
                }
                if ($roles_assigned > 0) {
                    $success_msg = $result['message'] . " y se asignaron $roles_assigned rol(es)";
                } else {
                    $success_msg = $result['message'] . ' (sin roles asignados)';
                }
            } else {
                $success_msg = $result['message'];
            }
        } else {
            $error_msg = $result['message'];
        }
    }
}

// ACTUALIZAR USUARIO
if (isset($_POST['action']) && $_POST['action'] == 'update' && hasPermission('edit_user')) {
    $user_id = $_POST['user_id'];
    $data = [
        'full_name' => $_POST['full_name'],
        'email' => $_POST['email'],
        'status' => $_POST['status']
    ];

    // Determinar si hay cambios reales
    $has_data_changes = false;
    $current_user_data = $userManager->getUserById($user_id);
    
    if ($current_user_data) {
        if ($current_user_data['full_name'] != $data['full_name'] ||
            $current_user_data['email'] != $data['email'] ||
            $current_user_data['status'] != $data['status']) {
            $has_data_changes = true;
        }
    }

    // Actualizar datos básicos solo si hay cambios
    if ($has_data_changes) {
        $result = $userManager->updateUser($user_id, $data, 'Usuario actualizado desde panel de administración');
        if (!$result['success']) {
            $error_msg = $result['message'];
        }
    }

    // Actualizar roles si se enviaron (usando RBAC que funciona correctamente)
    $roles_updated = false;
    if (isset($_POST['roles'])) {
        $new_role_ids = !empty($_POST['roles']) ? array_map('intval', $_POST['roles']) : [];
        $current_roles = $userManager->getUserRoles($user_id);
        $current_role_ids = !empty($current_roles) ? array_map('intval', array_column($current_roles, 'id')) : [];

        // ✅ PROTECCIÓN AUTO-MODIFICACIÓN: Un admin no puede quitarse su propio rol de admin
        if ($user_id == $_SESSION['id']) {
            // Verificar si se está intentando quitar el rol de administrador
            $admin_role_query = "SELECT id FROM roles WHERE role_name IN ('admin', 'super_admin') AND status = 'active'";
            $admin_role_result = mysqli_query($con, $admin_role_query);
            $admin_role_ids = [];
            while ($row = mysqli_fetch_assoc($admin_role_result)) {
                $admin_role_ids[] = intval($row['id']);
            }
            
            // Verificar si el usuario actual tiene rol de admin
            $user_has_admin = !empty(array_intersect($current_role_ids, $admin_role_ids));
            $new_has_admin = !empty(array_intersect($new_role_ids, $admin_role_ids));
            
            if ($user_has_admin && !$new_has_admin) {
                $error_msg = 'No puedes quitar tu propio rol de administrador. Contacta a otro administrador.';
            } else {
                // Proceder normalmente si no se está quitando el rol de admin
                $roles_to_add = array_diff($new_role_ids, $current_role_ids);
                $roles_to_remove = array_diff($current_role_ids, $new_role_ids);

                // Aplicar cambios de roles
                if (!empty($roles_to_remove)) {
                    foreach ($roles_to_remove as $role_id) {
                        $rbac->revokeRoleFromUser($user_id, $role_id, $_SESSION['id']);
                    }
                    $roles_updated = true;
                }
                if (!empty($roles_to_add)) {
                    foreach ($roles_to_add as $role_id) {
                        $rbac->assignRoleToUser($user_id, $role_id, $_SESSION['id']);
                    }
                    $roles_updated = true;
                }
            }
        } else {
            // Para otros usuarios, proceder normalmente
            $roles_to_add = array_diff($new_role_ids, $current_role_ids);
            $roles_to_remove = array_diff($current_role_ids, $new_role_ids);

            // Aplicar cambios de roles
            if (!empty($roles_to_remove)) {
                foreach ($roles_to_remove as $role_id) {
                    $rbac->revokeRoleFromUser($user_id, $role_id, $_SESSION['id']);
                }
                $roles_updated = true;
            }
            if (!empty($roles_to_add)) {
                foreach ($roles_to_add as $role_id) {
                    $rbac->assignRoleToUser($user_id, $role_id, $_SESSION['id']);
                }
                $roles_updated = true;
            }
        }
    }

    // Mensaje final
    if ($has_data_changes && $roles_updated) {
        $success_msg = 'Usuario y roles actualizados exitosamente';
    } elseif ($has_data_changes) {
        $success_msg = 'Usuario actualizado exitosamente';
    } elseif ($roles_updated) {
        $success_msg = 'Roles actualizados exitosamente';
    } else {
        $error_msg = 'No se realizaron cambios';
    }
}

// ELIMINAR USUARIO (SOFT DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && hasPermission('delete_user')) {
    $user_id = $_GET['id'];
    
    // ✅ PROTECCIÓN AUTO-ELIMINACIÓN: Un admin no puede eliminarse a sí mismo
    if ($user_id == $_SESSION['id']) {
        $error_msg = 'No puedes eliminar tu propia cuenta. Contacta a otro administrador.';
    } else {
        $result = $userManager->deleteUser($user_id, 'Usuario eliminado desde panel de administración');
        if ($result['success']) {
            $success_msg = $result['message'];
            
            // ✅ CERRAR SESIÓN DEL USUARIO ELIMINADO (si está activo)
            $session_file = session_save_path() . '/sess_' . session_id();
            if (file_exists($session_file)) {
                // Buscar y eliminar sesiones del usuario eliminado
                $sessions_dir = session_save_path();
                if ($handle = opendir($sessions_dir)) {
                    while (false !== ($file = readdir($handle))) {
                        if (strpos($file, 'sess_') === 0) {
                            $session_data = @file_get_contents($sessions_dir . '/' . $file);
                            if ($session_data && strpos($session_data, 'id|i:' . $user_id . ';') !== false) {
                                @unlink($sessions_dir . '/' . $file);
                            }
                        }
                    }
                    closedir($handle);
                }
            }
        } else {
            $error_msg = $result['message'];
        }
    }
}

// ============================================================================
// OBTENER DATOS PARA LA VISTA
// ============================================================================

// Búsqueda y filtros
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_type = $_GET['type'] ?? '';

// Parámetros de ordenamiento
$sort_by = $_GET['sort_by'] ?? 'full_name';
$sort_order = $_GET['sort_order'] ?? 'ASC';

// Validar columnas permitidas para ordenar
$allowed_sort_columns = ['full_name', 'email', 'user_type', 'status', 'last_login'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'full_name';
}

// Validar dirección de ordenamiento
$sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

// Obtener usuarios con filtros
if (!empty($search) || !empty($filter_status) || !empty($filter_type)) {
    // Construir filtros solo con valores no vacíos
    $filters = ['limit' => 100, 'sort_by' => $sort_by, 'sort_order' => $sort_order];
    if (!empty($filter_status)) {
        $filters['status'] = $filter_status;
    }
    if (!empty($filter_type)) {
        $filters['user_type'] = $filter_type;
    }
    $users = $userManager->searchUsers($search, $filters);
} else {
    // Si no hay filtros, obtener todos los usuarios
    $users = $userManager->getAllUsers(100, $sort_by, $sort_order);
}

// Obtener estadísticas
$stats = $userManager->getStatistics();

// Obtener todos los roles disponibles para asignación
$all_roles = $rbac->getAllRoles();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Admin | Gestionar Usuarios</title>
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

    <!-- SweetAlert2 para confirmaciones -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        .stats-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .stats-card .stat-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        .stats-card .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stats-card .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        .badge-status {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-active {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
        }
        .badge-inactive {
            background: linear-gradient(135deg, #9E9E9E, #757575);
            color: white;
            box-shadow: 0 2px 4px rgba(158, 158, 158, 0.3);
            opacity: 0.8;
        }
        .badge-blocked {
            background: linear-gradient(135deg, #F44336, #d32f2f);
            color: white;
            box-shadow: 0 2px 4px rgba(244, 67, 54, 0.3);
        }
        
        /* ✅ Efectos visuales para usuarios inactivos */
        .user-row-inactive {
            opacity: 0.6;
            background-color: #f9f9f9 !important;
        }
        .user-row-inactive td {
            color: #666 !important;
        }
        .user-row-inactive .user-name {
            text-decoration: line-through;
            color: #999 !important;
        }
        
        /* Iconos en badges */
        .badge-status::before {
            font-family: 'FontAwesome';
            margin-right: 3px;
        }
        .badge-active::before {
            content: '\f00c'; /* check */
        }
        .badge-inactive::before {
            content: '\f00d'; /* times */
        }
        .badge-blocked::before {
            content: '\f023'; /* lock */
        }
        .modal-header .close {
            color: white;
            opacity: 0.8;
        }
        .required::after {
            content: " *";
            color: red;
        }
        .sortable-header {
            cursor: pointer;
            user-select: none;
            position: relative;
            padding-right: 20px;
        }
        .sortable-header:hover {
            background-color: #f0f0f0;
        }
        .sort-icon {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.3;
            font-size: 12px;
        }
        .sortable-header.active .sort-icon {
            opacity: 1;
            color: #00a8b3;
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
                                    <i class="fa fa-users"></i> Gestión de Usuarios
                                </h1>
                            </div>
                            <ol class="breadcrumb">
                                <li><span>Admin</span></li>
                                <li class="active"><span>Gestionar Usuarios</span></li>
                            </ol>
                        </div>
                    </section>

                    <!-- Mensajes de éxito/error -->
                    <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <strong><i class="fa fa-check-circle"></i> Éxito!</strong> <?php echo htmlspecialchars($success_msg); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <strong><i class="fa fa-exclamation-circle"></i> Error!</strong> <?php echo htmlspecialchars($error_msg); ?>
                    </div>
                    <?php endif; ?>

                    <!-- ============================================ -->
                    <!-- TARJETAS DE ESTADÍSTICAS -->
                    <!-- ============================================ -->
                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card text-center" style="border-left: 4px solid #00a8b3;">
                                <div class="stat-icon" style="color: #00a8b3;">
                                    <i class="fa fa-users"></i>
                                </div>
                                <div class="stat-value" style="color: #00a8b3;">
                                    <?php echo $stats['total_users'] ?? 0; ?>
                                </div>
                                <div class="stat-label">Total Usuarios</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card text-center" style="border-left: 4px solid #4CAF50;">
                                <div class="stat-icon" style="color: #4CAF50;">
                                    <i class="fa fa-check-circle"></i>
                                </div>
                                <div class="stat-value" style="color: #4CAF50;">
                                    <?php echo $stats['active_users'] ?? 0; ?>
                                </div>
                                <div class="stat-label">Activos</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card text-center" style="border-left: 4px solid #9E9E9E;">
                                <div class="stat-icon" style="color: #9E9E9E;">
                                    <i class="fa fa-user-times"></i>
                                </div>
                                <div class="stat-value" style="color: #9E9E9E;">
                                    <?php echo $stats['inactive_users'] ?? 0; ?>
                                </div>
                                <div class="stat-label">Inactivos</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card text-center" style="border-left: 4px solid #F44336;">
                                <div class="stat-icon" style="color: #F44336;">
                                    <i class="fa fa-ban"></i>
                                </div>
                                <div class="stat-value" style="color: #F44336;">
                                    <?php echo $stats['blocked_users'] ?? 0; ?>
                                </div>
                                <div class="stat-label">Bloqueados</div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- BARRA DE BÚSQUEDA Y ACCIONES -->
                    <!-- ============================================ -->
                    <div class="container-fluid container-fullw bg-white">
                        <div class="row search-box">
                            <div class="col-md-4">
                                <div class="input-group" style="width: 100%;">
                                    <input type="text"
                                           name="search"
                                           id="searchInput"
                                           class="form-control"
                                           placeholder="Buscar por nombre o email..."
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <span class="input-group-btn">
                                        <button class="btn btn-primary" type="button" onclick="applyFilter()">
                                            <i class="fa fa-search"></i> Buscar
                                        </button>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="filterStatus" onchange="applyFilter()">
                                    <option value="">-- Todos los estados --</option>
                                    <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Activos</option>
                                    <option value="inactive" <?php echo $filter_status == 'inactive' ? 'selected' : ''; ?>>Inactivos</option>
                                    <option value="blocked" <?php echo $filter_status == 'blocked' ? 'selected' : ''; ?>>Bloqueados</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="filterType" onchange="applyFilter()">
                                    <option value="">-- Todos los tipos --</option>
                                    <option value="patient" <?php echo $filter_type == 'patient' ? 'selected' : ''; ?>>Pacientes</option>
                                    <option value="doctor" <?php echo $filter_type == 'doctor' ? 'selected' : ''; ?>>Doctores</option>
                                    <option value="admin" <?php echo $filter_type == 'admin' ? 'selected' : ''; ?>>Administradores</option>
                                </select>
                            </div>
                            <div class="col-md-2 text-right">
                                <?php if (hasPermission('create_user')): ?>
                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#createUserModal">
                                    <i class="fa fa-plus"></i> Nuevo Usuario
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- ============================================ -->
                        <!-- TABLA DE USUARIOS -->
                        <!-- ============================================ -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped" id="usersTable">
                                        <thead>
                                            <tr>
                                                <th width="5%">#</th>
                                                <th width="20%" class="sortable-header <?php echo ($sort_by === 'full_name') ? 'active' : ''; ?>" onclick="sortTable('full_name')">
                                                    Nombre Completo
                                                    <i class="fa <?php echo ($sort_by === 'full_name' && $sort_order === 'ASC') ? 'fa-sort-asc' : 'fa-sort-desc'; ?> sort-icon"></i>
                                                </th>
                                                <th width="20%" class="sortable-header <?php echo ($sort_by === 'email') ? 'active' : ''; ?>" onclick="sortTable('email')">
                                                    Email
                                                    <i class="fa <?php echo ($sort_by === 'email' && $sort_order === 'ASC') ? 'fa-sort-asc' : 'fa-sort-desc'; ?> sort-icon"></i>
                                                </th>
                                                <th width="10%" class="sortable-header <?php echo ($sort_by === 'user_type') ? 'active' : ''; ?>" onclick="sortTable('user_type')">
                                                    Tipo
                                                    <i class="fa <?php echo ($sort_by === 'user_type' && $sort_order === 'ASC') ? 'fa-sort-asc' : 'fa-sort-desc'; ?> sort-icon"></i>
                                                </th>
                                                <th width="15%">Roles</th>
                                                <th width="10%" class="sortable-header <?php echo ($sort_by === 'status') ? 'active' : ''; ?>" onclick="sortTable('status')">
                                                    Estado
                                                    <i class="fa <?php echo ($sort_by === 'status' && $sort_order === 'ASC') ? 'fa-sort-asc' : 'fa-sort-desc'; ?> sort-icon"></i>
                                                </th>
                                                <th width="10%" class="sortable-header <?php echo ($sort_by === 'last_login') ? 'active' : ''; ?>" onclick="sortTable('last_login')">
                                                    Último Login
                                                    <i class="fa <?php echo ($sort_by === 'last_login' && $sort_order === 'ASC') ? 'fa-sort-asc' : 'fa-sort-desc'; ?> sort-icon"></i>
                                                </th>
                                                <th width="10%">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($users)): ?>
                                                <?php $cnt = 1; foreach ($users as $user): ?>
                                                <tr class="<?php echo ($user['status'] === 'inactive') ? 'user-row-inactive' : ''; ?>">
                                                    <td><?php echo $cnt++; ?></td>
                                                    <td><strong class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <?php
                                                        $type_badges = [
                                                            'patient' => '<span class="label label-info">Paciente</span>',
                                                            'doctor' => '<span class="label label-primary">Doctor</span>',
                                                            'admin' => '<span class="label label-warning">Admin</span>'
                                                        ];
                                                        echo $type_badges[$user['user_type']] ?? $user['user_type'];
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <small><?php echo htmlspecialchars($user['roles'] ?? 'Sin roles'); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status_class = [
                                                            'active' => 'badge-active',
                                                            'inactive' => 'badge-inactive',
                                                            'blocked' => 'badge-blocked'
                                                        ];
                                                        $status_text = [
                                                            'active' => 'Activo',
                                                            'inactive' => 'Inactivo',
                                                            'blocked' => 'Bloqueado'
                                                        ];
                                                        $badge = $status_class[$user['status']] ?? 'badge-inactive';
                                                        $text = $status_text[$user['status']] ?? $user['status'];
                                                        ?>
                                                        <span class="badge-status <?php echo $badge; ?>">
                                                            <?php echo $text; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            <?php
                                                            if (!empty($user['last_login'])) {
                                                                echo date('d/m/Y H:i', strtotime($user['last_login']));
                                                            } else {
                                                                echo '<em>Nunca</em>';
                                                            }
                                                            ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <?php if (hasPermission('edit_user')): ?>
                                                            <button type="button"
                                                                    class="btn btn-primary btn-xs"
                                                                    onclick="editUser(<?php echo $user['id']; ?>)"
                                                                    title="Editar">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <?php else: ?>
                                                            <button type="button" class="btn btn-default btn-xs" disabled title="Sin permiso">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <?php endif; ?>

                                                            <?php if (hasPermission('delete_user')): ?>
                                                            <button type="button"
                                                                    class="btn btn-danger btn-xs"
                                                                    onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')"
                                                                    title="Eliminar">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                            <?php else: ?>
                                                            <button type="button" class="btn btn-default btn-xs" disabled title="Sin permiso">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">
                                                        <em>No se encontraron usuarios</em>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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

    <!-- ============================================ -->
    <!-- MODAL: CREAR USUARIO -->
    <!-- ============================================ -->
    <div class="modal fade" id="createUserModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="" id="createUserForm">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"><i class="fa fa-user-plus"></i> Crear Nuevo Usuario</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Nombre Completo</label>
                                    <input type="text" name="full_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Contraseña</label>
                                    <input type="password" name="password" id="create_password" class="form-control" required minlength="8">
                                    <small class="text-muted">Mínimo 8 caracteres, incluir mayúsculas, minúsculas, números y símbolos</small>
                                    <div id="password_strength" style="margin-top: 5px;"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Tipo de Usuario</label>
                                    <select name="user_type" class="form-control" required>
                                        <option value="">-- Seleccionar --</option>
                                        <option value="patient">Paciente</option>
                                        <option value="doctor">Doctor</option>
                                        <option value="admin">Administrador</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="status" class="form-control">
                                        <option value="active">Activo</option>
                                        <option value="inactive">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Asignar Roles</label>
                                    <select name="roles[]" class="form-control" multiple size="4">
                                        <?php foreach ($all_roles as $role): ?>
                                        <option value="<?php echo $role['id']; ?>">
                                            <?php echo htmlspecialchars($role['display_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Mantén Ctrl para seleccionar múltiples roles</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-save"></i> Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- MODAL: EDITAR USUARIO -->
    <!-- ============================================ -->
    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="" id="editUserForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"><i class="fa fa-edit"></i> Editar Usuario</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Nombre Completo</label>
                                    <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Email</label>
                                    <input type="email" name="email" id="edit_email" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="status" id="edit_status" class="form-control">
                                        <option value="active">Activo</option>
                                        <option value="inactive">Inactivo</option>
                                        <option value="blocked">Bloqueado</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Roles Asignados</label>
                                    <select name="roles[]" id="edit_roles" class="form-control" multiple size="4">
                                        <?php foreach ($all_roles as $role): ?>
                                        <option value="<?php echo $role['id']; ?>">
                                            <?php echo htmlspecialchars($role['display_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Mantén Ctrl para seleccionar múltiples roles</small>
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

            // Validación de contraseña en tiempo real
            $('#create_password').on('keyup', function() {
                validatePassword($(this).val());
            });

            // Validar antes de enviar formulario
            $('#createUserForm').on('submit', function(e) {
                var password = $('#create_password').val();
                if (!isPasswordValid(password)) {
                    e.preventDefault();
                    Swal.fire('Error', 'La contraseña no cumple con las políticas de seguridad', 'error');
                    return false;
                }
            });
        });

        function validatePassword(password) {
            var strength = 0;
            var feedback = [];

            // Verificar longitud mínima
            if (password.length >= 8) {
                strength += 20;
            } else {
                feedback.push('Mínimo 8 caracteres');
            }

            // Verificar mayúsculas
            if (/[A-Z]/.test(password)) {
                strength += 20;
            } else {
                feedback.push('Al menos una mayúscula');
            }

            // Verificar minúsculas
            if (/[a-z]/.test(password)) {
                strength += 20;
            } else {
                feedback.push('Al menos una minúscula');
            }

            // Verificar números
            if (/[0-9]/.test(password)) {
                strength += 20;
            } else {
                feedback.push('Al menos un número');
            }

            // Verificar símbolos
            if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
                strength += 20;
            } else {
                feedback.push('Al menos un símbolo (!@#$...)');
            }

            // Mostrar retroalimentación
            var html = '';
            if (strength < 100) {
                html = '<small class="text-danger">Falta: ' + feedback.join(', ') + '</small>';
            } else {
                html = '<small class="text-success"><i class="fa fa-check"></i> Contraseña válida</small>';
            }
            $('#password_strength').html(html);

            return strength === 100;
        }

        function isPasswordValid(password) {
            return password.length >= 8 &&
                   /[A-Z]/.test(password) &&
                   /[a-z]/.test(password) &&
                   /[0-9]/.test(password) &&
                   /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
        }

        // Función para aplicar filtros
        function applyFilter() {
            var search = document.getElementById('searchInput').value;
            var status = document.getElementById('filterStatus').value;
            var type = document.getElementById('filterType').value;

            var params = [];
            if (search) params.push('search=' + encodeURIComponent(search));
            if (status) params.push('status=' + status);
            if (type) params.push('type=' + type);

            var url = 'manage-users.php';
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            window.location.href = url;
        }

        // Función para ordenar tabla
        function sortTable(column) {
            // Obtener parámetros actuales de la URL
            var urlParams = new URLSearchParams(window.location.search);
            
            var currentSortBy = urlParams.get('sort_by') || 'full_name';
            var currentSortOrder = urlParams.get('sort_order') || 'ASC';
            
            // Si se hace clic en la misma columna, invertir el orden
            var newSortOrder = 'ASC';
            if (column === currentSortBy) {
                newSortOrder = currentSortOrder === 'ASC' ? 'DESC' : 'ASC';
            }
            
            // Establecer nuevos parámetros
            urlParams.set('sort_by', column);
            urlParams.set('sort_order', newSortOrder);
            
            // Redirigir con nuevos parámetros
            window.location.href = 'manage-users.php?' + urlParams.toString();
        }

        // Función para editar usuario
        function editUser(userId) {
            // Cargar datos del usuario mediante AJAX
            $.ajax({
                url: 'api/users-api.php',
                method: 'GET',
                data: { action: 'get', id: userId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var user = response.data;
                        $('#edit_user_id').val(user.id);
                        $('#edit_full_name').val(user.full_name);
                        $('#edit_email').val(user.email);
                        $('#edit_status').val(user.status);

                        // Marcar roles asignados
                        $('#edit_roles option').prop('selected', false);
                        if (user.role_ids) {
                            var roleIds = user.role_ids.split(',');
                            roleIds.forEach(function(roleId) {
                                $('#edit_roles option[value="' + roleId + '"]').prop('selected', true);
                            });
                        }

                        $('#editUserModal').modal('show');
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'No se pudo cargar los datos del usuario', 'error');
                }
            });
        }

        // Función para eliminar usuario
        function deleteUser(userId, userName) {
            Swal.fire({
                title: '¿Estás seguro?',
                html: 'Vas a eliminar al usuario: <br><strong>' + userName + '</strong>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'manage-users.php?action=delete&id=' + userId;
                }
            });
        }
    </script>
</body>
</html>
