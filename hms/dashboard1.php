<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
include('include/rbac-functions.php');
check_login();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Usuario | Panel de Control</title>
    
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
        /* Mejoras visuales para el dashboard */
        .dashboard-card {
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        
        .dashboard-card.card-profile {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .dashboard-card.card-appointments {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .dashboard-card.card-book {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .dashboard-card .panel-body {
            padding: 40px 20px;
        }
        
        .dashboard-card .fa-stack {
            margin-bottom: 20px;
        }
        
        .dashboard-card .fa-stack i {
            color: white;
        }
        
        .dashboard-card .fa-stack .fa-circle {
            opacity: 1;
        }
        
        .dashboard-card .icon-circle {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .dashboard-card .icon-circle i {
            font-size: 45px;
            color: inherit;
        }
        
        .dashboard-card.card-profile .icon-circle i {
            color: #667eea;
        }
        
        .dashboard-card.card-appointments .icon-circle i {
            color: #f5576c;
        }
        
        .dashboard-card.card-book .icon-circle i {
            color: #00f2fe;
        }
        
        .dashboard-card h2 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin: 20px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .dashboard-card a {
            color: white;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            padding: 12px 30px;
            background: rgba(255,255,255,0.2);
            border-radius: 25px;
            display: inline-block;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .dashboard-card a:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
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
        
        .dashboard-card:nth-child(3) {
            animation-delay: 0.3s;
        }
    </style>

</head>
<body>
    <div id="app">        
        <?php include('include/sidebar.php');?>
            <div class="app-content">
                
                        <?php include('include/header.php');?>
                        
                <!-- end: TOP NAVBAR -->
                <div class="main-content" >
                    <div class="wrap-content container" id="container">
                        <!-- start: PAGE TITLE -->
                        <section id="page-title">
                            <div class="row">
                                <div class="col-sm-8">
                                    <h1 class="mainTitle">Usuario | Panel de Control</h1>
                                                                    </div>
                                <ol class="breadcrumb">
                                    <li>
                                        <span>Usuario</span>
                                    </li>
                                    <li class="active">
                                        <span>Panel de Control</span>
                                    </li>
                                </ol>
                            </div>
                        </section>
                        <!-- end: PAGE TITLE -->
                        <!-- start: BASIC EXAMPLE -->
                            <div class="container-fluid container-fullw bg-white">
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="panel dashboard-card card-profile no-radius text-center">
                                        <div class="panel-body">
                                            <div class="icon-circle">
                                                <i class="fa fa-user"></i>
                                            </div>
                                            <h2 class="StepTitle">Mi Perfil</h2>
                                            <p class="links cl-effect-1">
                                                <a href="edit-profile.php">
                                                    <i class="fa fa-edit"></i> Actualizar Perfil
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="panel dashboard-card card-appointments no-radius text-center">
                                        <div class="panel-body">
                                            <div class="icon-circle">
                                                <i class="fa fa-calendar"></i>
                                            </div>
                                            <h2 class="StepTitle">Mis Citas</h2>
                                            <p class="cl-effect-1">
                                                <a href="appointment-history.php">
                                                    <i class="fa fa-list"></i> Ver Historial
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="panel dashboard-card card-book no-radius text-center">
                                        <div class="panel-body">
                                            <div class="icon-circle">
                                                <i class="fa fa-plus-square"></i>
                                            </div>
                                            <h2 class="StepTitle">Reservar Cita</h2>
                                            <p class="links cl-effect-1">
                                                <a href="book-appointment.php">
                                                    <i class="fa fa-plus-circle"></i> Nueva Cita
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
            
                    
                    
                        
                        
                    
                        <!-- end: SELECT BOXES -->
                        
                    </div>
                </div>
            </div>
            <!-- start: FOOTER -->
    <?php include('include/footer.php');?>
            <!-- end: FOOTER -->
        
            <!-- start: SETTINGS -->
    <?php include('include/setting.php');?>
            <>
            <!-- end: SETTINGS -->
        </div>
        <!-- start: MAIN JAVASCRIPTS -->
        <script src="vendor/jquery/jquery.min.js"></script>
        <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
        <script src="vendor/modernizr/modernizr.js"></script>
        <script src="vendor/jquery-cookie/jquery.cookie.js"></script>
        <script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
        <script src="vendor/switchery/switchery.min.js"></script>
        <!-- end: MAIN JAVASCRIPTS -->
        <!-- start: JAVASCRIPTS REQUIRED FOR THIS PAGE ONLY -->
        <script src="vendor/maskedinput/jquery.maskedinput.min.js"></script>
        <script src="vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js"></script>
        <script src="vendor/autosize/autosize.min.js"></script>
        <script src="vendor/selectFx/classie.js"></script>
        <script src="vendor/selectFx/selectFx.js"></script>
        <script src="vendor/select2/select2.min.js"></script>
        <script src="vendor/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
        <script src="vendor/bootstrap-timepicker/bootstrap-timepicker.min.js"></script>
        <!-- end: JAVASCRIPTS REQUIRED FOR THIS PAGE ONLY -->
        <!-- start: CLIP-TWO JAVASCRIPTS -->
        <script src="assets/js/main.js"></script>
        <!-- start: JavaScript Event Handlers for this page -->
        <script src="assets/js/form-elements.js"></script>
        <script>
            jQuery(document).ready(function() {
                Main.init();
                FormElements.init();

                // Function to reset the timer
                function resetTimer() {
                    clearTimeout(inactivityTimeout);
                    inactivityTimeout = setTimeout(logout, 90000); // 10 seconds
                }

                // Function to log out
                function logout() {
                    alert('Sesión cerrada por inactividad.');
                    window.location.href = 'login.php';
                }

                // Set the initial timer
                var inactivityTimeout = setTimeout(logout, 10000); // 10 seconds

                // Add event listeners to reset the timer on user activity
                window.addEventListener('mousemove', resetTimer);
                window.addEventListener('keypress', resetTimer);
                window.addEventListener('click', resetTimer);
                window.addEventListener('scroll', resetTimer);
            });
        </script>
        <!-- end: JavaScript Event Handlers for this page -->
        <!-- end: CLIP-TWO JAVASCRIPTS -->
    </body>
</html>

