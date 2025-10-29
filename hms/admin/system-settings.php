<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
include('../include/rbac-functions.php');

// Verificar acceso administrativo
check_admin_access();

// Verificar permiso específico
if (!hasPermission('manage_system_settings')) {
    header("Location: dashboard.php");
    exit();
}

// Variables para mensajes
$success_msg = '';
$error_msg = '';

// Procesar actualización de configuraciones
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    try {
        $user_id = $_SESSION['id'];

        // Guardar configuraciones generales
        setSystemSetting('hospital_name', $_POST['hospital_name'], $user_id);
        setSystemSetting('hospital_address', $_POST['hospital_address'], $user_id);
        setSystemSetting('hospital_phone', $_POST['hospital_phone'], $user_id);
        setSystemSetting('hospital_email', $_POST['hospital_email'], $user_id);

        // Guardar horarios
        setSystemSetting('start_time', $_POST['start_time'], $user_id);
        setSystemSetting('end_time', $_POST['end_time'], $user_id);

        // Guardar notificaciones
        $email_notif = isset($_POST['email_notifications']) ? '1' : '0';
        $sms_notif = isset($_POST['sms_notifications']) ? '1' : '0';
        setSystemSetting('email_notifications', $email_notif, $user_id);
        setSystemSetting('sms_notifications', $sms_notif, $user_id);

        // Guardar configuraciones de email corporativo
        if (isset($_POST['email_domain'])) {
            setSystemSetting('email_domain', $_POST['email_domain'], $user_id);
            setSystemSetting('email_format_template', $_POST['email_format_template'], $user_id);
            $email_auto = isset($_POST['email_auto_generate']) ? '1' : '0';
            $email_custom = isset($_POST['email_allow_custom']) ? '1' : '0';
            setSystemSetting('email_auto_generate', $email_auto, $user_id);
            setSystemSetting('email_allow_custom', $email_custom, $user_id);
        }

        $success_msg = "Configuración actualizada exitosamente";
    } catch (Exception $e) {
        $error_msg = "Error al actualizar la configuración: " . $e->getMessage();
    }
}

// Cargar configuraciones actuales
$hospital_name = getSystemSetting('hospital_name', 'Clínica Dental Muelitas');
$hospital_address = getSystemSetting('hospital_address', '');
$hospital_phone = getSystemSetting('hospital_phone', '');
$hospital_email = getSystemSetting('hospital_email', '');
$start_time = getSystemSetting('start_time', '08:00');
$end_time = getSystemSetting('end_time', '18:00');
$email_notifications = getSystemSetting('email_notifications', '1');
$sms_notifications = getSystemSetting('sms_notifications', '0');

// Configuraciones de email corporativo
$email_domain = getSystemSetting('email_domain', 'clinica.dental.muelitas');
$email_format_template = getSystemSetting('email_format_template', '{firstname}.{lastname_initial}@{domain}');
$email_auto_generate = getSystemSetting('email_auto_generate', '1');
$email_allow_custom = getSystemSetting('email_allow_custom', '0');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Admin | Configuración del Sistema</title>
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
                                    <i class="fa fa-sliders"></i> Configuración del Sistema
                                </h1>
                            </div>
                            <ol class="breadcrumb">
                                <li><span>Admin</span></li>
                                <li><span>Sistema</span></li>
                                <li class="active"><span>Configuración General</span></li>
                            </ol>
                        </div>
                    </section>

                    <!-- Mensajes -->
                    <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fa fa-check-circle"></i> <?php echo $success_msg; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fa fa-times-circle"></i> <?php echo $error_msg; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Contenido Principal -->
                    <div class="container-fluid container-fullw bg-white">
                        <div class="row">
                            <div class="col-md-12">
                                <form method="POST" action="">
                                    <div class="panel-group" id="accordion">

                                        <!-- Panel 1: Información General -->
                                        <div class="panel panel-white">
                                            <div class="panel-heading">
                                                <h4 class="panel-title">
                                                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse1">
                                                        <i class="fa fa-hospital-o"></i> Información General del Hospital
                                                    </a>
                                                </h4>
                                            </div>
                                            <div id="collapse1" class="panel-collapse collapse in">
                                                <div class="panel-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Nombre del Hospital</label>
                                                                <input type="text" class="form-control" name="hospital_name" value="<?php echo htmlspecialchars($hospital_name); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Dirección</label>
                                                                <input type="text" class="form-control" name="hospital_address" value="<?php echo htmlspecialchars($hospital_address); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Teléfono</label>
                                                                <input type="text" class="form-control" name="hospital_phone" value="<?php echo htmlspecialchars($hospital_phone); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Email</label>
                                                                <input type="email" class="form-control" name="hospital_email" value="<?php echo htmlspecialchars($hospital_email); ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Panel 2: Horarios -->
                                        <div class="panel panel-white">
                                            <div class="panel-heading">
                                                <h4 class="panel-title">
                                                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse2" class="collapsed">
                                                        <i class="fa fa-clock-o"></i> Horarios de Atención
                                                    </a>
                                                </h4>
                                            </div>
                                            <div id="collapse2" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Hora de Inicio</label>
                                                                <input type="time" class="form-control" name="start_time" value="<?php echo htmlspecialchars($start_time); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Hora de Cierre</label>
                                                                <input type="time" class="form-control" name="end_time" value="<?php echo htmlspecialchars($end_time); ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Panel 3: Notificaciones -->
                                        <div class="panel panel-white">
                                            <div class="panel-heading">
                                                <h4 class="panel-title">
                                                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse3" class="collapsed">
                                                        <i class="fa fa-bell"></i> Configuración de Notificaciones
                                                    </a>
                                                </h4>
                                            </div>
                                            <div id="collapse3" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    <div class="checkbox">
                                                        <label>
                                                            <input type="checkbox" name="email_notifications" <?php echo ($email_notifications == '1') ? 'checked' : ''; ?>>
                                                            Enviar notificaciones por email
                                                        </label>
                                                    </div>
                                                    <div class="checkbox">
                                                        <label>
                                                            <input type="checkbox" name="sms_notifications" <?php echo ($sms_notifications == '1') ? 'checked' : ''; ?>>
                                                            Enviar notificaciones por SMS
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Panel 4: Configuración de Correos Corporativos -->
                                        <div class="panel panel-white">
                                            <div class="panel-heading">
                                                <h4 class="panel-title">
                                                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse4" class="collapsed">
                                                        <i class="fa fa-envelope"></i> Configuración de Correos Corporativos
                                                    </a>
                                                </h4>
                                            </div>
                                            <div id="collapse4" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="alert alert-info">
                                                                <i class="fa fa-info-circle"></i> Configure el formato de los correos corporativos para todos los usuarios del sistema.
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Dominio Corporativo <span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control" name="email_domain" value="<?php echo htmlspecialchars($email_domain); ?>" placeholder="ej: clinica.dental.muelitas">
                                                                <span class="help-block">El dominio que se usará para todos los correos corporativos</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Formato de Email <span class="text-danger">*</span></label>
                                                                <select class="form-control" name="email_format_template" id="emailFormatSelect">
                                                                    <option value="{firstname}.{lastname_initial}@{domain}" <?php echo ($email_format_template == '{firstname}.{lastname_initial}@{domain}') ? 'selected' : ''; ?>>
                                                                        nombre.a@dominio (ej: juan.p@clinica.dental.muelitas)
                                                                    </option>
                                                                    <option value="{firstname}{lastname_initial}@{domain}" <?php echo ($email_format_template == '{firstname}{lastname_initial}@{domain}') ? 'selected' : ''; ?>>
                                                                        nombrea@dominio (ej: juanp@clinica.dental.muelitas)
                                                                    </option>
                                                                    <option value="{firstname}.{lastname}@{domain}" <?php echo ($email_format_template == '{firstname}.{lastname}@{domain}') ? 'selected' : ''; ?>>
                                                                        nombre.apellido@dominio (ej: juan.perez@clinica.dental.muelitas)
                                                                    </option>
                                                                    <option value="{firstname_initial}{lastname}@{domain}" <?php echo ($email_format_template == '{firstname_initial}{lastname}@{domain}') ? 'selected' : ''; ?>>
                                                                        napellido@dominio (ej: jperez@clinica.dental.muelitas)
                                                                    </option>
                                                                </select>
                                                                <span class="help-block">Seleccione el formato para generar emails automáticamente</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="panel panel-default">
                                                                <div class="panel-body" style="background-color: #f9f9f9;">
                                                                    <strong><i class="fa fa-eye"></i> Vista Previa:</strong>
                                                                    <p class="text-muted" style="margin: 10px 0;">
                                                                        Nombre: <strong>Juan</strong> | Apellido: <strong>Pérez</strong><br>
                                                                        Email generado: <strong id="emailPreview">juan.p@<?php echo htmlspecialchars($email_domain); ?></strong>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="checkbox">
                                                                <label>
                                                                    <input type="checkbox" name="email_auto_generate" <?php echo ($email_auto_generate == '1') ? 'checked' : ''; ?>>
                                                                    <strong>Auto-generar emails al crear usuarios</strong>
                                                                </label>
                                                                <span class="help-block">Los emails se generarán automáticamente según el formato seleccionado</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="checkbox">
                                                                <label>
                                                                    <input type="checkbox" name="email_allow_custom" <?php echo ($email_allow_custom == '1') ? 'checked' : ''; ?>>
                                                                    <strong>Permitir emails personalizados</strong>
                                                                </label>
                                                                <span class="help-block">Permitir que los usuarios usen emails fuera del dominio corporativo</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="text-right">
                                        <button type="submit" name="update_settings" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Guardar Configuración
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
    <script src="assets/js/form-elements.js"></script>
    <script>
        jQuery(document).ready(function() {
            Main.init();
            FormElements.init();

            // Función para actualizar vista previa de email
            function updateEmailPreview() {
                var template = $('#emailFormatSelect').val();
                var domain = $('input[name="email_domain"]').val();

                // Ejemplo con Juan Pérez
                var email = template
                    .replace('{firstname}', 'juan')
                    .replace('{lastname}', 'perez')
                    .replace('{firstname_initial}', 'j')
                    .replace('{lastname_initial}', 'p')
                    .replace('{domain}', domain);

                $('#emailPreview').text(email);
            }

            // Actualizar vista previa cuando cambie el formato o dominio
            $('#emailFormatSelect').on('change', updateEmailPreview);
            $('input[name="email_domain"]').on('keyup', updateEmailPreview);

            // Actualizar al cargar la página
            updateEmailPreview();
        });
    </script>
</body>
</html>
