<?php
session_start();
error_reporting(1);
include('include/config.php');
include('include/checklogin.php');
include('../include/rbac-functions.php');
require_once('../include/SessionManager.php');

// Verificar acceso administrativo
check_admin_access();

// Verificar permisos
if (!hasPermission('manage_security_settings')) {
    header("Location: dashboard.php");
    exit();
}

// Inicializar SessionManager
$sessionManager = new SessionManager($con);

// Cargar configuración actual
$settings = SessionManager::getSettings($con);

// Procesar formulario
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_security'])) {
    // Validar y limpiar datos
    $session_timeout = filter_input(INPUT_POST, 'session_timeout', FILTER_VALIDATE_INT, 
        ['options' => ['min_range' => 5, 'max_range' => 120]]);
    $session_max_duration = filter_input(INPUT_POST, 'session_max_duration', FILTER_VALIDATE_INT, 
        ['options' => ['min_range' => 1, 'max_range' => 24]]);
    $warning_minutes = filter_input(INPUT_POST, 'warning_minutes', FILTER_VALIDATE_INT, 
        ['options' => ['min_range' => 1, 'max_range' => 5]]);
    $remember_me = isset($_POST['remember_me']) ? 1 : 0;
    $remember_days = filter_input(INPUT_POST, 'remember_days', FILTER_VALIDATE_INT, 
        ['options' => ['min_range' => 1, 'max_range' => 90]]);

    if ($session_timeout && $session_max_duration && $warning_minutes && $remember_days) {
        // Actualizar configuración en la base de datos
        $update_sql = "
            INSERT INTO system_settings (setting_key, setting_value, setting_category, description) 
            VALUES 
            ('session_timeout_minutes', ?, 'security', 'Tiempo de inactividad en minutos antes de cerrar sesión'),
            ('session_max_duration_hours', ?, 'security', 'Duración máxima de sesión en horas'),
            ('session_warning_minutes', ?, 'security', 'Minutos antes de mostrar advertencia de expiración'),
            ('remember_me_enabled', ?, 'security', 'Habilitar función Recordarme (1=si, 0=no)'),
            ('remember_me_duration_days', ?, 'security', 'Duración de la cookie Recordarme en días')
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_at = NOW(),
                updated_by = ?
        ";
        
        $stmt = $con->prepare($update_sql);
        $admin_id = $_SESSION['id'];
        $stmt->bind_param('iiiiii', 
            $session_timeout, 
            $session_max_duration, 
            $warning_minutes,
            $remember_me,
            $remember_days,
            $admin_id
        );
        
        if ($stmt->execute()) {
            $success_msg = "Configuración de seguridad actualizada exitosamente";
            // Actualizar configuración en memoria (crear nueva instancia para forzar recarga)
            $sessionManager = new SessionManager($con);
            $settings = SessionManager::getSettings($con);
        } else {
            $error_msg = "Error al actualizar la configuración: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_msg = "Por favor ingrese valores válidos en todos los campos";
    }
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
                    <?php if ($success_msg): ?>
                    <div class="alert alert-success alert-dismissible fade in">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <i class="fa fa-check"></i> <?php echo $success_msg; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error_msg): ?>
                    <div class="alert alert-danger alert-dismissible fade in">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <i class="fa fa-exclamation-triangle"></i> <?php echo $error_msg; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="container-fluid container-fullw bg-white">
                        <div class="row">
                            <div class="col-md-12">
                                <form method="POST" action="" class="form-horizontal">
                                    <input type="hidden" name="update_security" value="1">

                                    <!-- Panel 1: Configuración de Sesiones -->
                                    <div class="panel panel-white">
                                        <div class="panel-heading" style="background: #00a8b3; color: white;">
                                            <h4 class="panel-title">
                                                <i class="fa fa-clock-o"></i> Configuración de Sesiones
                                            </h4>
                                        </div>
                                        <div class="panel-body">
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Tiempo de inactividad (minutos):</label>
                                                <div class="col-sm-9">
                                                    <input type="number" class="form-control" name="session_timeout" 
                                                           min="5" max="120" value="<?php echo $settings['timeout_minutes']; ?>">
                                                    <span class="help-block">
                                                        Tiempo de inactividad antes de cerrar sesión (5-120 minutos)
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Duración máxima (horas):</label>
                                                <div class="col-sm-9">
                                                    <input type="number" class="form-control" name="session_max_duration" 
                                                           min="1" max="24" value="<?php echo $settings['max_duration_hours']; ?>">
                                                    <span class="help-block">
                                                        Duración máxima de la sesión (1-24 horas)
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Advertencia (minutos):</label>
                                                <div class="col-sm-9">
                                                    <input type="number" class="form-control" name="warning_minutes" 
                                                           min="1" max="5" value="<?php echo $settings['warning_minutes']; ?>">
                                                    <span class="help-block">
                                                        Tiempo de advertencia antes de expirar (1-5 minutos)
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Panel 2: Recordarme -->
                                    <div class="panel panel-white">
                                        <div class="panel-heading" style="background: #8e44ad; color: white;">
                                            <h4 class="panel-title">
                                                <i class="fa fa-user-clock"></i> Función "Recordarme"
                                            </h4>
                                        </div>
                                        <div class="panel-body">
                                            <div class="form-group">
                                                <div class="col-sm-offset-3 col-sm-9">
                                                    <div class="checkbox">
                                                        <label>
                                                            <input type="checkbox" name="remember_me" value="1" 
                                                                <?php echo $settings['remember_me_enabled'] ? 'checked' : ''; ?>>
                                                            Habilitar función "Recordarme"
                                                        </label>
                                                    </div>
                                                    <span class="help-block">
                                                        Permite a los usuarios mantener la sesión iniciada
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Duración (días):</label>
                                                <div class="col-sm-9">
                                                    <input type="number" class="form-control" name="remember_days" 
                                                           min="1" max="90" value="<?php echo $settings['remember_me_days']; ?>">
                                                    <span class="help-block">
                                                        Días que durará la sesión recordada (1-90 días)
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Botón de guardar -->
                                    <div class="form-group">
                                        <div class="col-sm-offset-3 col-sm-9">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-save"></i> Guardar Configuración
                                            </button>
                                        </div>
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
