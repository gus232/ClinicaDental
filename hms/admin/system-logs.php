<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
include('../include/rbac-functions.php');

// Verificar acceso administrativo
check_admin_access();

// Verificar permiso específico
if (!hasPermission('view_system_logs')) {
    header("Location: dashboard.php");
    exit();
}

// Obtener logs de cambios de roles (auditoría)
$audit_sql = "SELECT arc.*,
              u_affected.full_name as user_name, u_affected.email as user_email,
              u_performed.full_name as performed_by_name,
              r.display_name as role_name
              FROM audit_role_changes arc
              LEFT JOIN users u_affected ON arc.user_id = u_affected.id
              LEFT JOIN users u_performed ON arc.performed_by = u_performed.id
              LEFT JOIN roles r ON arc.role_id = r.id
              ORDER BY arc.performed_at DESC
              LIMIT 100";
$audit_result = mysqli_query($con, $audit_sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Admin | Logs del Sistema</title>
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
                                    <i class="fa fa-file-text-o"></i> Logs del Sistema
                                </h1>
                            </div>
                            <ol class="breadcrumb">
                                <li><span>Admin</span></li>
                                <li><span>Sistema</span></li>
                                <li class="active"><span>Logs del Sistema</span></li>
                            </ol>
                        </div>
                    </section>

                    <!-- Contenido Principal -->
                    <div class="container-fluid container-fullw bg-white">
                        <div class="row">
                            <div class="col-md-12">
                                <h4><i class="fa fa-list-alt"></i> Auditoría de Cambios de Roles y Permisos</h4>
                                <p class="text-muted">Registro de todas las modificaciones realizadas en roles de usuarios</p>
                                <hr>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr style="background: #00a8b3; color: white;">
                                                <th>#</th>
                                                <th>Fecha/Hora</th>
                                                <th>Usuario Afectado</th>
                                                <th>Rol</th>
                                                <th>Acción</th>
                                                <th>Realizado Por</th>
                                                <th>IP</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (mysqli_num_rows($audit_result) > 0):
                                                $cnt = 1;
                                                while ($log = mysqli_fetch_assoc($audit_result)):
                                                    // Determinar color según acción
                                                    $action_class = 'default';
                                                    $action_icon = 'fa-info';
                                                    if ($log['action'] == 'assigned') {
                                                        $action_class = 'success';
                                                        $action_icon = 'fa-plus-circle';
                                                    } elseif ($log['action'] == 'revoked') {
                                                        $action_class = 'danger';
                                                        $action_icon = 'fa-minus-circle';
                                                    }
                                            ?>
                                            <tr>
                                                <td><?php echo $cnt++; ?></td>
                                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['performed_at'])); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($log['user_name'] ?? 'N/A'); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($log['user_email'] ?? ''); ?></small>
                                                </td>
                                                <td>
                                                    <span class="label label-primary">
                                                        <?php echo htmlspecialchars($log['role_name'] ?? 'N/A'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="label label-<?php echo $action_class; ?>">
                                                        <i class="fa <?php echo $action_icon; ?>"></i>
                                                        <?php echo ucfirst($log['action']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($log['performed_by_name'] ?? 'Sistema'); ?></td>
                                                <td><code><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></code></td>
                                            </tr>
                                            <?php
                                                endwhile;
                                            else:
                                            ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    <i class="fa fa-info-circle"></i> No hay registros de auditoría
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <hr>

                                <!-- Sección de Logs Técnicos (Placeholder) -->
                                <h4 class="margin-top-20"><i class="fa fa-bug"></i> Logs Técnicos del Sistema</h4>
                                <p class="text-muted">Errores, advertencias y eventos del sistema</p>

                                <div class="panel panel-white">
                                    <div class="panel-body">
                                        <div class="alert alert-info">
                                            <i class="fa fa-wrench"></i>
                                            <strong>Funcionalidad en desarrollo</strong><br>
                                            Próximamente se mostrarán logs técnicos de PHP, MySQL y eventos del sistema.
                                        </div>
                                        <pre style="background: #263238; color: #aed581; padding: 15px; border-radius: 4px; max-height: 300px; overflow-y: auto;">
[2025-10-27 10:30:15] INFO: Sistema iniciado correctamente
[2025-10-27 10:31:42] INFO: Usuario admin@hospital.com inició sesión
[2025-10-27 10:32:10] INFO: Rol 'Admin Técnico' asignado a usuario ID:5
[2025-10-27 10:35:22] WARNING: Intento de acceso denegado desde IP 192.168.1.100
[2025-10-27 10:40:05] INFO: Respaldo de base de datos creado exitosamente

--- Logs en tiempo real próximamente ---
                                        </pre>
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

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/modernizr/modernizr.js"></script>
    <script src="vendor/jquery-cookie/jquery.cookie.js"></script>
    <script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="vendor/switchery/switchery.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        jQuery(document).ready(function() {
            Main.init();
        });
    </script>
</body>
</html>
