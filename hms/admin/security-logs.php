<?php
session_start();
error_reporting(1);
include('include/config.php');
include('include/checklogin.php');
include('../include/rbac-functions.php');

// Verificar acceso administrativo
check_admin_access();

// Verificar permiso específico
if (!hasPermission('view_security_logs')) {
    header("Location: dashboard.php");
    exit();
}

// Obtener logs de intentos fallidos (si existe la tabla)
$failed_logins_sql = "SELECT fl.*,  u.full_name, u.email
                      FROM failed_login_attempts fl
                      LEFT JOIN users u ON fl.user_id = u.id
                      ORDER BY fl.attempt_time DESC
                      LIMIT 100";
$failed_logins_result = @mysqli_query($con, $failed_logins_sql);

// Obtener usuarios bloqueados
$blocked_users_sql = "SELECT id, full_name, email, blocked_at, block_reason, failed_login_attempts
                      FROM users
                      WHERE status = 'blocked'
                      ORDER BY blocked_at DESC";
$blocked_users_result = mysqli_query($con, $blocked_users_sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Admin | Logs de Seguridad</title>
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
                                    <i class="fa fa-list-alt"></i> Logs de Seguridad
                                </h1>
                            </div>
                            <ol class="breadcrumb">
                                <li><span>Admin</span></li>
                                <li><span>Seguridad</span></li>
                                <li class="active"><span>Logs de Seguridad</span></li>
                            </ol>
                        </div>
                    </section>

                    <!-- Contenido Principal -->

                    <!-- Sección 1: Usuarios Bloqueados -->
                    <div class="container-fluid container-fullw bg-white margin-bottom-20">
                        <div class="row">
                            <div class="col-md-12">
                                <h4><i class="fa fa-ban text-danger"></i> Usuarios Bloqueados</h4>
                                <p class="text-muted">Cuentas bloqueadas por múltiples intentos fallidos de inicio de sesión</p>
                                <hr>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr style="background: #f44336; color: white;">
                                                <th>#</th>
                                                <th>Usuario</th>
                                                <th>Email</th>
                                                <th>Fecha de Bloqueo</th>
                                                <th>Razón</th>
                                                <th>Intentos Fallidos</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (mysqli_num_rows($blocked_users_result) > 0):
                                                $cnt = 1;
                                                while ($user = mysqli_fetch_assoc($blocked_users_result)):
                                            ?>
                                            <tr>
                                                <td><?php echo $cnt++; ?></td>
                                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo $user['blocked_at'] ? date('d/m/Y H:i', strtotime($user['blocked_at'])) : 'N/A'; ?></td>
                                                <td><?php echo htmlspecialchars($user['block_reason'] ?? 'Múltiples intentos fallidos'); ?></td>
                                                <td>
                                                    <span class="label label-danger">
                                                        <?php echo $user['failed_login_attempts'] ?? 0; ?> intentos
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="unlock-accounts.php?user_id=<?php echo $user['id']; ?>"
                                                       class="btn btn-xs btn-warning">
                                                        <i class="fa fa-unlock"></i> Desbloquear
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php
                                                endwhile;
                                            else:
                                            ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    <i class="fa fa-check-circle text-success"></i> No hay usuarios bloqueados
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 2: Intentos Fallidos de Inicio de Sesión -->
                    <div class="container-fluid container-fullw bg-white">
                        <div class="row">
                            <div class="col-md-12">
                                <h4><i class="fa fa-exclamation-triangle text-warning"></i> Intentos Fallidos de Inicio de Sesión</h4>
                                <p class="text-muted">Registro de intentos de acceso no autorizados al sistema</p>
                                <hr>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr style="background: #ff9800; color: white;">
                                                <th>#</th>
                                                <th>Fecha/Hora</th>
                                                <th>Email/Usuario</th>
                                                <th>IP</th>
                                                <th>User Agent</th>
                                                <th>Razón</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($failed_logins_result && mysqli_num_rows($failed_logins_result) > 0):
                                                $cnt = 1;
                                                while ($log = mysqli_fetch_assoc($failed_logins_result)):
                                            ?>
                                            <tr>
                                                <td><?php echo $cnt++; ?></td>
                                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['attempt_time'])); ?></td>
                                                <td>
                                                    <?php
                                                    if ($log['full_name']) {
                                                        echo htmlspecialchars($log['full_name']) . '<br>';
                                                        echo '<small class="text-muted">' . htmlspecialchars($log['email']) . '</small>';
                                                    } else {
                                                        echo '<span class="text-muted">Usuario desconocido</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><code><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></code></td>
                                                <td>
                                                    <small><?php echo htmlspecialchars(substr($log['user_agent'] ?? 'N/A', 0, 50)); ?></small>
                                                </td>
                                                <td>
                                                    <span class="label label-warning">
                                                        <?php echo htmlspecialchars($log['failure_reason'] ?? 'Contraseña incorrecta'); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php
                                                endwhile;
                                            else:
                                            ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">
                                                    <i class="fa fa-info-circle"></i>
                                                    <?php if (!$failed_logins_result): ?>
                                                    Funcionalidad de logs de intentos fallidos en desarrollo. Requiere tabla failed_login_attempts.
                                                    <?php else: ?>
                                                    No hay intentos fallidos registrados
                                                    <?php endif; ?>
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
