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
            // Asignar roles si se seleccionaron
            if (!empty($_POST['roles'])) {
                $userManager->assignRoles($result['user_id'], $_POST['roles']);
            }
            $success_msg = $result['message'];
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

    $result = $userManager->updateUser($user_id, $data, 'Usuario actualizado desde panel de administración');
    if ($result['success']) {
        // Actualizar roles si se modificaron
        if (isset($_POST['roles'])) {
            // Primero revocar todos los roles actuales
            $current_roles = $userManager->getUserRoles($user_id);
            $current_role_ids = array_column($current_roles, 'id');
            if (!empty($current_role_ids)) {
                $userManager->revokeRoles($user_id, $current_role_ids);
            }
            // Asignar nuevos roles
            if (!empty($_POST['roles'])) {
                $userManager->assignRoles($user_id, $_POST['roles']);
            }
        }
        $success_msg = $result['message'];
    } else {
        $error_msg = $result['message'];
    }
}

// ELIMINAR USUARIO (SOFT DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && hasPermission('delete_user')) {
    $user_id = $_GET['id'];
    $result = $userManager->deleteUser($user_id, 'Usuario eliminado desde panel de administración');
    if ($result['success']) {
        $success_msg = $result['message'];
    } else {
        $error_msg = $result['message'];
    }
}

// ============================================================================
// OBTENER DATOS PARA LA VISTA
// ============================================================================

// Búsqueda y filtros
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_type = $_GET['type'] ?? '';

// Obtener usuarios
if (!empty($search) || !empty($filter_status) || !empty($filter_type)) {
    $users = $userManager->searchUsers($search, [
        'status' => $filter_status,
        'user_type' => $filter_type,
        'limit' => 100
    ]);
} else {
    $users = $userManager->getAllUsers();
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
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-active {
            background: #4CAF50;
            color: white;
        }
        .badge-inactive {
            background: #9E9E9E;
            color: white;
        }
        .badge-blocked {
            background: #F44336;
            color: white;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .modal-header {
            background: #00a8b3;
            color: white;
        }
        .modal-header .close {
            color: white;
            opacity: 0.8;
        }
        .required::after {
            content: " *";
            color: red;
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
                                    <i class="fa fa-pause-circle"></i>
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
                                <form method="GET" class="form-inline">
                                    <div class="input-group" style="width: 100%;">
                                        <input type="text"
                                               name="search"
                                               class="form-control"
                                               placeholder="Buscar por nombre o email..."
                                               value="<?php echo htmlspecialchars($search); ?>">
                                        <span class="input-group-btn">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fa fa-search"></i> Buscar
                                            </button>
                                        </span>
                                    </div>
                                </form>
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
                                                <th width="20%">Nombre Completo</th>
                                                <th width="20%">Email</th>
                                                <th width="10%">Tipo</th>
                                                <th width="15%">Roles</th>
                                                <th width="10%">Estado</th>
                                                <th width="10%">Último Login</th>
                                                <th width="10%">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($users)): ?>
                                                <?php $cnt = 1; foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo $cnt++; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
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
                                    <input type="password" name="password" class="form-control" required minlength="8">
                                    <small class="text-muted">Mínimo 8 caracteres, incluir mayúsculas, minúsculas, números y símbolos</small>
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
        });

        // Función para aplicar filtros
        function applyFilter() {
            var status = document.getElementById('filterStatus').value;
            var type = document.getElementById('filterType').value;
            var url = 'manage-users.php?';
            if (status) url += 'status=' + status + '&';
            if (type) url += 'type=' + type;
            window.location.href = url;
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
