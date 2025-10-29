<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
include('../include/rbac-functions.php');
check_login();

// Obtener estadísticas generales con manejo de errores
$stats = [];

// Total de usuarios
$result = mysqli_query($con, "SELECT COUNT(*) as total FROM users");
$stats['total_users'] = ($result && $row = mysqli_fetch_assoc($result)) ? $row['total'] : 0;

// Usuarios activos
$result = mysqli_query($con, "SELECT COUNT(*) as total FROM users WHERE status = 'active'");
$stats['active_users'] = ($result && $row = mysqli_fetch_assoc($result)) ? $row['total'] : 0;

// Usuarios inactivos
$result = mysqli_query($con, "SELECT COUNT(*) as total FROM users WHERE status = 'inactive'");
$stats['inactive_users'] = ($result && $row = mysqli_fetch_assoc($result)) ? $row['total'] : 0;

// Total de doctores (de la tabla users con user_type = 'doctor')
$result = mysqli_query($con, "SELECT COUNT(*) as total FROM users WHERE user_type = 'doctor'");
$stats['total_doctors'] = ($result && $row = mysqli_fetch_assoc($result)) ? $row['total'] : 0;

// Total de pacientes (de la tabla users con user_type = 'patient')
$result = mysqli_query($con, "SELECT COUNT(*) as total FROM users WHERE user_type = 'patient'");
$stats['total_patients'] = ($result && $row = mysqli_fetch_assoc($result)) ? $row['total'] : 0;

// Total de citas
$result = mysqli_query($con, "SELECT COUNT(*) as total FROM appointment");
$stats['total_appointments'] = ($result && $row = mysqli_fetch_assoc($result)) ? $row['total'] : 0;

// Citas de hoy
$result = mysqli_query($con, "SELECT COUNT(*) as total FROM appointment WHERE DATE(appointmentDate) = CURDATE()");
$stats['today_appointments'] = ($result && $row = mysqli_fetch_assoc($result)) ? $row['total'] : 0;

// Consultas nuevas (si la tabla existe)
$result = @mysqli_query($con, "SELECT COUNT(*) as total FROM tblcontactus WHERE IsRead IS NULL");
$stats['new_queries'] = ($result && $row = mysqli_fetch_assoc($result)) ? $row['total'] : 0;

// Usuarios registrados esta semana (con manejo si el campo no existe)
$result = @mysqli_query($con, "SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['users_this_week'] = ($result && $row = mysqli_fetch_assoc($result)) ? $row['total'] : 0;

// Actividad reciente (últimos 5 usuarios registrados)
$recent_users = [];
$result = @mysqli_query($con, "SELECT full_name, email, user_type, created_at FROM users ORDER BY id DESC LIMIT 5");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Admin | Panel de Control</title>
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
    
    <style>
        /* Tarjetas de estadísticas mejoradas */
        .dashboard-stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 25px;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .dashboard-stat-card.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .dashboard-stat-card.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .dashboard-stat-card.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .dashboard-stat-card.purple {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .dashboard-stat-card.red {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .dashboard-stat-card.teal {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        }
        
        .stat-icon-large {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 60px;
            opacity: 0.3;
        }
        
        .stat-value-large {
            font-size: 42px;
            font-weight: bold;
            margin: 10px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .stat-label-large {
            font-size: 16px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-description {
            font-size: 13px;
            opacity: 0.8;
            margin-top: 8px;
        }
        
        /* Panel de actividad reciente */
        .activity-panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .activity-panel h3 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #00a8b3;
            font-size: 20px;
        }
        
        .activity-item {
            padding: 15px;
            border-left: 3px solid #00a8b3;
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .activity-item strong {
            color: #00a8b3;
        }
        
        .activity-item small {
            color: #6c757d;
            display: block;
            margin-top: 5px;
        }
        
        /* Tarjetas de acción rápida */
        .quick-action-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            border-top: 4px solid #00a8b3;
        }
        
        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .quick-action-icon {
            font-size: 48px;
            color: #00a8b3;
            margin-bottom: 15px;
        }
        
        .quick-action-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .quick-action-link {
            color: #00a8b3;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .quick-action-link:hover {
            color: #007a82;
            text-decoration: none;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 12px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            color: white;
            margin: 0;
            font-size: 32px;
        }
        
        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
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
<!-- Header con gradiente -->
<div class="page-header">
    <h1><i class="fa fa-dashboard"></i> Panel de Control</h1>
    <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Administrador'); ?> | <?php echo date('d/m/Y'); ?></p>
</div>

<!-- Tarjetas de estadísticas principales -->
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="dashboard-stat-card blue">
            <div class="stat-icon-large"><i class="fa fa-users"></i></div>
            <div class="stat-label-large">Total Usuarios</div>
            <div class="stat-value-large"><?php echo $stats['total_users']; ?></div>
            <div class="stat-description">
                <i class="fa fa-check-circle"></i> <?php echo $stats['active_users']; ?> activos | 
                <i class="fa fa-times-circle"></i> <?php echo $stats['inactive_users']; ?> inactivos
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6">
        <div class="dashboard-stat-card green">
            <div class="stat-icon-large"><i class="fa fa-user-md"></i></div>
            <div class="stat-label-large">Doctores</div>
            <div class="stat-value-large"><?php echo $stats['total_doctors']; ?></div>
            <div class="stat-description">Médicos registrados en el sistema</div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6">
        <div class="dashboard-stat-card orange">
            <div class="stat-icon-large"><i class="fa fa-wheelchair"></i></div>
            <div class="stat-label-large">Pacientes</div>
            <div class="stat-value-large"><?php echo $stats['total_patients']; ?></div>
            <div class="stat-description">Pacientes registrados</div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6">
        <div class="dashboard-stat-card purple">
            <div class="stat-icon-large"><i class="fa fa-calendar"></i></div>
            <div class="stat-label-large">Citas</div>
            <div class="stat-value-large"><?php echo $stats['total_appointments']; ?></div>
            <div class="stat-description">
                <i class="fa fa-clock-o"></i> Hoy: <?php echo $stats['today_appointments']; ?>
            </div>
        </div>
    </div>
</div>

<!-- Segunda fila de estadísticas -->
<div class="row">
    <div class="col-md-4 col-sm-6">
        <div class="dashboard-stat-card red">
            <div class="stat-icon-large"><i class="fa fa-bell"></i></div>
            <div class="stat-label-large">Consultas Nuevas</div>
            <div class="stat-value-large"><?php echo $stats['new_queries']; ?></div>
            <div class="stat-description">Mensajes sin leer</div>
        </div>
    </div>
    
    <div class="col-md-4 col-sm-6">
        <div class="dashboard-stat-card teal">
            <div class="stat-icon-large"><i class="fa fa-calendar-check-o"></i></div>
            <div class="stat-label-large">Citas Hoy</div>
            <div class="stat-value-large"><?php echo $stats['today_appointments']; ?></div>
            <div class="stat-description">Programadas para hoy</div>
        </div>
    </div>
    
    <div class="col-md-4 col-sm-6">
        <div class="dashboard-stat-card purple">
            <div class="stat-icon-large"><i class="fa fa-user-plus"></i></div>
            <div class="stat-label-large">Esta Semana</div>
            <div class="stat-value-large"><?php echo $stats['users_this_week']; ?></div>
            <div class="stat-description">Nuevos usuarios registrados</div>
        </div>
    </div>
</div>

<!-- Actividad Reciente y Acciones Rápidas -->
<div class="row">
    <!-- Panel de Actividad Reciente -->
    <div class="col-md-6">
        <div class="activity-panel">
            <h3><i class="fa fa-history"></i> Actividad Reciente</h3>
            <?php if (!empty($recent_users)): ?>
                <?php foreach ($recent_users as $user): ?>
                    <div class="activity-item">
                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong> 
                        <span class="label label-info"><?php echo htmlspecialchars($user['user_type']); ?></span>
                        <br>
                        <small>
                            <i class="fa fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                        </small>
                        <small>
                            <i class="fa fa-clock-o"></i> 
                            <?php 
                                if (!empty($user['created_at']) && $user['created_at'] != '0000-00-00 00:00:00') {
                                    echo date('d/m/Y H:i', strtotime($user['created_at']));
                                } else {
                                    echo 'Fecha no disponible';
                                }
                            ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No hay actividad reciente</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Acciones Rápidas -->
    <div class="col-md-6">
        <div class="activity-panel">
            <h3><i class="fa fa-bolt"></i> Acciones Rápidas</h3>
            <div class="row">
                <div class="col-md-6 col-sm-6">
                    <div class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fa fa-user-plus"></i>
                        </div>
                        <div class="quick-action-title">Nuevo Usuario</div>
                        <a href="manage-users.php" class="quick-action-link">
                            Crear Usuario <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-md-6 col-sm-6">
                    <div class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fa fa-shield"></i>
                        </div>
                        <div class="quick-action-title">Roles y Permisos</div>
                        <a href="manage-roles.php" class="quick-action-link">
                            Gestionar Roles <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-md-6 col-sm-6">
                    <div class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fa fa-key"></i>
                        </div>
                        <div class="quick-action-title">Políticas de Contraseña</div>
                        <a href="manage-password-policies.php" class="quick-action-link">
                            Ver Políticas <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-md-6 col-sm-6">
                    <div class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fa fa-lock"></i>
                        </div>
                        <div class="quick-action-title">Seguridad</div>
                        <a href="security-logs.php" class="quick-action-link">
                            Ver Logs <i class="fa fa-arrow-right"></i>
                        </a>
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
<script src="vendor/maskedinput/jquery.maskedinput.min.js"></script>
<script src="vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js"></script>
<script src="vendor/autosize/autosize.min.js"></script>
<script src="vendor/selectFx/classie.js"></script>
<script src="vendor/selectFx/selectFx.js"></script>
<script src="vendor/select2/select2.min.js"></script>
<script src="vendor/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
<script src="vendor/bootstrap-timepicker/bootstrap-timepicker.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/form-elements.js"></script>
<script>
jQuery(document).ready(function() {
Main.init();
FormElements.init();
});
</script>
</body>
</html>
