<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
include('../include/rbac-functions.php');

// Verificar acceso administrativo
check_admin_access();

// Verificar permisos
if (!hasPermission('backup_database') && !hasPermission('restore_database')) {
    header("Location: dashboard.php");
    exit();
}

$canBackup = hasPermission('backup_database');
$canRestore = hasPermission('restore_database');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Admin | Respaldos y Restauración</title>
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
                                    <i class="fa fa-database"></i> Respaldos y Restauración
                                </h1>
                            </div>
                            <ol class="breadcrumb">
                                <li><span>Admin</span></li>
                                <li><span>Sistema</span></li>
                                <li class="active"><span>Respaldos</span></li>
                            </ol>
                        </div>
                    </section>

                    <!-- Contenido Principal -->
                    <div class="row">
                        <!-- Crear Respaldo -->
                        <?php if ($canBackup): ?>
                        <div class="col-md-6">
                            <div class="panel panel-white">
                                <div class="panel-heading" style="background: #00a8b3; color: white;">
                                    <h4 class="panel-title">
                                        <i class="fa fa-download"></i> Crear Respaldo
                                    </h4>
                                </div>
                                <div class="panel-body">
                                    <p class="text-muted">
                                        Genere un respaldo completo de la base de datos del sistema.
                                    </p>
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i>
                                        <strong>Importante:</strong> El respaldo incluirá todas las tablas y datos del sistema.
                                    </div>
                                    <form method="POST" action="">
                                        <div class="form-group">
                                            <label>Nombre del Respaldo</label>
                                            <input type="text" class="form-control" name="backup_name"
                                                   value="backup_<?php echo date('Y-m-d_H-i-s'); ?>.sql" readonly>
                                        </div>
                                        <button type="submit" name="create_backup" class="btn btn-success btn-block">
                                            <i class="fa fa-download"></i> Crear Respaldo Ahora
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Restaurar Respaldo -->
                        <?php if ($canRestore): ?>
                        <div class="col-md-6">
                            <div class="panel panel-white">
                                <div class="panel-heading" style="background: #f44336; color: white;">
                                    <h4 class="panel-title">
                                        <i class="fa fa-upload"></i> Restaurar Respaldo
                                    </h4>
                                </div>
                                <div class="panel-body">
                                    <p class="text-muted">
                                        Restaure la base de datos desde un archivo de respaldo.
                                    </p>
                                    <div class="alert alert-danger">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <strong>Advertencia:</strong> Esta acción reemplazará todos los datos actuales.
                                    </div>
                                    <form method="POST" action="" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <label>Seleccionar Archivo de Respaldo (.sql)</label>
                                            <input type="file" class="form-control" name="backup_file" accept=".sql" required>
                                        </div>
                                        <button type="submit" name="restore_backup" class="btn btn-danger btn-block"
                                                onclick="return confirm('¿Está seguro de restaurar este respaldo? Se perderán todos los datos actuales.')">
                                            <i class="fa fa-upload"></i> Restaurar Respaldo
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Historial de Respaldos -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-white">
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <i class="fa fa-history"></i> Historial de Respaldos
                                    </h4>
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr style="background: #f5f5f5;">
                                                    <th>#</th>
                                                    <th>Nombre del Archivo</th>
                                                    <th>Fecha de Creación</th>
                                                    <th>Tamaño</th>
                                                    <th>Creado Por</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">
                                                        <i class="fa fa-info-circle"></i>
                                                        No hay respaldos registrados. Funcionalidad en desarrollo.
                                                    </td>
                                                </tr>
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
