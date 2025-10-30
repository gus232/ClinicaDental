<?php
session_start();
error_reporting(1);
include('include/config.php');
include('include/checklogin.php');
include('../include/rbac-functions.php');
check_login();

// Obtener estadísticas del doctor
$doctor_user_id = $_SESSION['id'];
$stats = [];

// Verificar si el doctor existe en la tabla doctors
$doctor_info = null;
$doctor_id = $doctor_user_id; // Por defecto usar el user_id

$result = mysqli_query($con, "SELECT d.*, ds.specilization FROM doctors d LEFT JOIN doctorspecilization ds ON d.specialization_id = ds.id WHERE d.user_id = '$doctor_user_id'");
if ($result && $row = mysqli_fetch_assoc($result)) {
    $doctor_info = $row;
    // En la tabla appointment, doctorId puede referirse al user_id o al id de doctors
    // Vamos a probar ambos para ver cuál tiene datos
    $test_user_id = mysqli_query($con, "SELECT COUNT(*) as count FROM appointment WHERE doctorId = '$doctor_user_id'");
    $test_doctor_id = mysqli_query($con, "SELECT COUNT(*) as count FROM appointment WHERE doctorId = '".$row['id']."'");
    
    $user_id_count = ($test_user_id && $r = mysqli_fetch_assoc($test_user_id)) ? $r['count'] : 0;
    $doctor_id_count = ($test_doctor_id && $r = mysqli_fetch_assoc($test_doctor_id)) ? $r['count'] : 0;
    
    // Usar el ID que tenga más citas registradas
    if ($doctor_id_count > $user_id_count) {
        $doctor_id = $row['id'];
    }
}

// Total de pacientes que han tenido citas con este doctor
$result = mysqli_query($con, "SELECT COUNT(DISTINCT userId) as total FROM appointment WHERE doctorId = '$doctor_id'");
$stats['total_patients'] = ($result && $row = mysqli_fetch_assoc($result)) ? $row['total'] : 0;

// Citas del doctor
$result = mysqli_query($con, "SELECT COUNT(*) as total FROM appointment WHERE doctorId = '$doctor_id'");
$stats['total_appointments'] = ($result && $row = mysqli_fetch_assoc($result)) ? $row['total'] : 0;

// Citas de hoy (appointmentDate se almacena como varchar, necesitamos convertir)
$today = date('Y-m-d');
$result = mysqli_query($con, "SELECT COUNT(*) as total FROM appointment WHERE doctorId = '$doctor_id' AND appointmentDate = '$today'");
$stats['today_appointments'] = ($result && $row = mysqli_fetch_assoc($result)) ? $row['total'] : 0;

// Citas pendientes (donde userStatus y doctorStatus son NULL o 1, indicando que están activas)
$result = mysqli_query($con, "SELECT COUNT(*) as total FROM appointment WHERE doctorId = '$doctor_id' AND (userStatus IS NULL OR userStatus = 1) AND (doctorStatus IS NULL OR doctorStatus = 1)");
$stats['pending_appointments'] = ($result && $row = mysqli_fetch_assoc($result)) ? $row['total'] : 0;

// Últimas citas
$recent_appointments = [];
$result = mysqli_query($con, "SELECT a.*, u.full_name as patient_name FROM appointment a LEFT JOIN users u ON a.userId = u.id WHERE a.doctorId = '$doctor_id' ORDER BY a.postingDate DESC LIMIT 5");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_appointments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Doctor | Panel de Control</title>
    <link href="http://fonts.googleapis.com/css?family=Lato:300,400,400italic,600,700|Raleway:300,400,500,600,700|Crete+Round:400italic" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="vendor/themify-icons/themify-icons.min.css">
    <link href="vendor/animate.css/animate.min.css" rel="stylesheet" media="screen">
    <link href="vendor/perfect-scrollbar/perfect-scrollbar.min.css" rel="stylesheet" media="screen">
    <link href="vendor/switchery/switchery.min.css" rel="stylesheet" media="screen">
    <link href="vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css" rel="stylesheet" media="screen">
    <link href="vendor/select2/select2.min.css" rel="stylesheet" media="screen">
    <link href="vendor/bootstrap-datepicker/bootstrap-datepicker3.standalone.min.css" rel="stylesheet" media="screen">
    <link href="vendor/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet" media="screen">
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
        
        .mainTitle {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        #page-title {
            margin-bottom: 40px;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
        }
        
        .container-fullw {
            padding: 30px;
        }
        
        /* Panel de configuración funcional - FORZAR VISIBILIDAD */
        #settings {
            position: fixed !important;
            right: 0 !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 9999 !important;
            transition: right 0.3s ease !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        #settings.active {
            right: 0 !important;
        }
        
        #settings:not(.active) {
            right: -250px !important;
        }
        
        /* Asegurar que el botón de la ruedita sea visible */
        #settings button {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Remover cualquier ocultación por responsive */
        .hidden-xs, .hidden-sm {
            display: block !important;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dashboard-card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .dashboard-card:nth-child(1) {
            animation-delay: 0.1s;
        }
        
        .dashboard-card:nth-child(2) {
            animation-delay: 0.2s;
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
                    <h1><i class="fa fa-user-md"></i> Panel de Doctor</h1>
                    <p>Bienvenido, Dr. <?php echo htmlspecialchars($_SESSION['name'] ?? 'Doctor'); ?> | <?php echo date('d/m/Y'); ?></p>
                </div>

                <!-- Tarjetas de estadísticas principales -->
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="dashboard-stat-card blue">
                            <div class="stat-icon-large"><i class="fa fa-users"></i></div>
                            <div class="stat-label-large">Mis Pacientes</div>
                            <div class="stat-value-large"><?php echo $stats['total_patients']; ?></div>
                            <div class="stat-description">Pacientes bajo mi cuidado</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="dashboard-stat-card green">
                            <div class="stat-icon-large"><i class="fa fa-calendar"></i></div>
                            <div class="stat-label-large">Total Citas</div>
                            <div class="stat-value-large"><?php echo $stats['total_appointments']; ?></div>
                            <div class="stat-description">Citas programadas</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="dashboard-stat-card orange">
                            <div class="stat-icon-large"><i class="fa fa-clock-o"></i></div>
                            <div class="stat-label-large">Citas Hoy</div>
                            <div class="stat-value-large"><?php echo $stats['today_appointments']; ?></div>
                            <div class="stat-description">Programadas para hoy</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="dashboard-stat-card purple">
                            <div class="stat-icon-large"><i class="fa fa-hourglass-half"></i></div>
                            <div class="stat-label-large">Pendientes</div>
                            <div class="stat-value-large"><?php echo $stats['pending_appointments']; ?></div>
                            <div class="stat-description">Citas por confirmar</div>
                        </div>
                    </div>
                </div>

                <!-- Actividad Reciente y Acciones Rápidas -->
                <div class="row">
                    <!-- Panel de Citas Recientes -->
                    <div class="col-md-6">
                        <div class="activity-panel">
                            <h3><i class="fa fa-history"></i> Citas Recientes</h3>
                            <?php if (!empty($recent_appointments)): ?>
                                <?php foreach ($recent_appointments as $appointment): ?>
                                    <div class="activity-item">
                                        <strong><?php echo htmlspecialchars($appointment['patient_name'] ?? 'Paciente'); ?></strong> 
                                        <span class="label label-info"><?php echo htmlspecialchars($appointment['appointmentTime']); ?></span>
                                        <br>
                                        <small>
                                            <i class="fa fa-calendar"></i> 
                                            <?php 
                                                // Manejar diferentes formatos de fecha
                                                $date = $appointment['appointmentDate'];
                                                if (!empty($date) && $date != '0000-00-00') {
                                                    $timestamp = strtotime($date);
                                                    if ($timestamp !== false) {
                                                        echo date('d/m/Y', $timestamp);
                                                    } else {
                                                        echo htmlspecialchars($date);
                                                    }
                                                } else {
                                                    echo 'Fecha no disponible';
                                                }
                                            ?>
                                        </small>
                                        <small>
                                            <i class="fa fa-info-circle"></i> 
                                            <?php 
                                                $status = 'Pendiente';
                                                if ($appointment['userStatus'] == 1 && $appointment['doctorStatus'] == 1) {
                                                    $status = 'Confirmada';
                                                } elseif ($appointment['userStatus'] == 0) {
                                                    $status = 'Cancelada por paciente';
                                                } elseif ($appointment['doctorStatus'] == 0) {
                                                    $status = 'Cancelada por doctor';
                                                }
                                                echo $status;
                                            ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No hay citas recientes</p>
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
                                        <div class="quick-action-title">Nuevo Paciente</div>
                                        <a href="add-patient.php" class="quick-action-link">
                                            Agregar Paciente <i class="fa fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 col-sm-6">
                                    <div class="quick-action-card">
                                        <div class="quick-action-icon">
                                            <i class="fa fa-calendar"></i>
                                        </div>
                                        <div class="quick-action-title">Ver Citas</div>
                                        <a href="appointment-history.php" class="quick-action-link">
                                            Historial de Citas <i class="fa fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-6">
                                    <div class="quick-action-card">
                                        <div class="quick-action-icon">
                                            <i class="fa fa-search"></i>
                                        </div>
                                        <div class="quick-action-title">Buscar Paciente</div>
                                        <a href="search.php" class="quick-action-link">
                                            Buscar <i class="fa fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-6">
                                    <div class="quick-action-card">
                                        <div class="quick-action-icon">
                                            <i class="fa fa-user"></i>
                                        </div>
                                        <div class="quick-action-title">Mi Perfil</div>
                                        <a href="edit-profile.php" class="quick-action-link">
                                            Editar Perfil <i class="fa fa-arrow-right"></i>
                                        </a>
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
