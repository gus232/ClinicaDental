<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
include('../include/rbac-functions.php');

// Verificar acceso administrativo
check_admin_access();

// Verificar permisos
if (!hasPermission('manage_security_settings')) {
    header("Location: dashboard.php");
    exit();
}

// Valores por defecto (deberían venir de una tabla de configuración)
$session_timeout = 1800; // 30 minutos
$max_login_attempts = 5;
$lockout_duration = 900; // 15 minutos
$password_expiry_days = 90;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_security'])) {
    // Aquí iría la lógica para guardar en base de datos
    $success_msg = "Configuración de seguridad actualizada exitosamente";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Admin | Configuración de Seguridad</title>
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
                                    <i class="fa fa-lock"></i> Configuración de Seguridad
                                </h1>
                            </div>
                            <ol class="breadcrumb">
                                <li><span>Admin</span></li>
                                <li><span>Seguridad</span></li>
                                <li class="active"><span>Configuración</span></li>
                            </ol>
                        </div>
                    </section>

                    <!-- Mensajes -->
                    <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fa fa-check-circle"></i> <?php echo $success_msg; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Contenido Principal -->
                    <div class="container-fluid container-fullw bg-white">
                        <div class="row">
                            <div class="col-md-12">
                                <form method="POST" action="">

                                    <!-- Panel 1: Sesiones -->
                                    <div class="panel panel-white">
                                        <div class="panel-heading" style="background: #00a8b3; color: white;">
                                            <h4 class="panel-title">
                                                <i class="fa fa-clock-o"></i> Configuración de Sesiones
                                            </h4>
                                        </div>
                                        <div class="panel-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Tiempo de Inactividad (segundos)</label>
                                                        <input type="number" class="form-control" name="session_timeout"
                                                               value="<?php echo $session_timeout; ?>" min="300" max="7200">
                                                        <small class="text-muted">
                                                            Tiempo máximo de inactividad antes de cerrar sesión automáticamente (300-7200 segundos)
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Duración Máxima de Sesión (horas)</label>
                                                        <input type="number" class="form-control" name="max_session_duration"
                                                               value="8" min="1" max="24">
                                                        <small class="text-muted">
                                                            Tiempo máximo que una sesión puede estar activa
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="remember_me_enabled" checked>
                                                    Permitir función "Recordarme" en login
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Panel 2: Intentos de Login -->
                                    <div class="panel panel-white">
                                        <div class="panel-heading" style="background: #ff9800; color: white;">
                                            <h4 class="panel-title">
                                                <i class="fa fa-exclamation-triangle"></i> Protección contra Intentos Fallidos
                                            </h4>
                                        </div>
                                        <div class="panel-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Máximo de Intentos Fallidos</label>
                                                        <input type="number" class="form-control" name="max_login_attempts"
                                                               value="<?php echo $max_login_attempts; ?>" min="3" max="10">
                                                        <small class="text-muted">Número de intentos antes de bloquear</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Duración del Bloqueo (segundos)</label>
                                                        <input type="number" class="form-control" name="lockout_duration"
                                                               value="<?php echo $lockout_duration; ?>" min="300" max="3600">
                                                        <small class="text-muted">Tiempo que la cuenta permanece bloqueada</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Ventana de Tiempo (segundos)</label>
                                                        <input type="number" class="form-control" name="attempt_window"
                                                               value="300" min="60" max="900">
                                                        <small class="text-muted">Ventana de tiempo para contar intentos</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="notify_admin_on_lockout" checked>
                                                    Notificar a administradores cuando se bloquea una cuenta
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Panel 3: Políticas Adicionales -->
                                    <div class="panel panel-white">
                                        <div class="panel-heading" style="background: #673ab7; color: white;">
                                            <h4 class="panel-title">
                                                <i class="fa fa-shield"></i> Políticas de Seguridad Adicionales
                                            </h4>
                                        </div>
                                        <div class="panel-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Expiración de Contraseñas (días)</label>
                                                        <input type="number" class="form-control" name="password_expiry_days"
                                                               value="<?php echo $password_expiry_days; ?>" min="0" max="365">
                                                        <small class="text-muted">0 = sin expiración</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Historial de Contraseñas</label>
                                                        <input type="number" class="form-control" name="password_history"
                                                               value="5" min="0" max="10">
                                                        <small class="text-muted">No permitir reutilizar las últimas N contraseñas</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="require_2fa" checked>
                                                    Requerir autenticación de dos factores (2FA) para administradores
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="ip_whitelist_enabled">
                                                    Habilitar lista blanca de IPs para acceso administrativo
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="log_all_access" checked>
                                                    Registrar todos los accesos al sistema (éxitos y fallos)
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <button type="submit" name="update_security" class="btn btn-primary btn-lg">
                                            <i class="fa fa-save"></i> Guardar Configuración de Seguridad
                                        </button>
                                    </div>
                                </form>
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
