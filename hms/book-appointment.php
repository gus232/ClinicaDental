<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
include('include/rbac-functions.php');
check_login();

if(isset($_POST['submit']))
{
    $especializacion=$_POST['Doctorspecialization'];
    $idDoctor=$_POST['doctor'];
    $idUsuario=$_SESSION['id'];
    $honorarios=$_POST['fees'];
    $fechaCita=$_POST['appdate'];
    $hora=$_POST['apptime'];
    $estadoUsuario=1;
    $estadoDoctor=1;
    $query=mysqli_query($con,"insert into appointment(doctorSpecialization,doctorId,userId,consultancyFees,appointmentDate,appointmentTime,userStatus,doctorStatus) values('$especializacion','$idDoctor','$idUsuario','$honorarios','$fechaCita','$hora','$estadoUsuario','$estadoDoctor')");
    if($query)
    {
        echo '<script>showConfirmation();</script>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Usuario | Reservar Cita</title>
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
    <script>
        function obtenerDoctor(val) {
            $.ajax({
                type: "POST",
                url: "get_doctor.php",
                data:'specilizationid='+val,
                success: function(data){
                    $("#doctor").html(data);
                }
            });
        }
    </script>   

    <script>
        function obtenerTarifa(val) {
            $.ajax({
                type: "POST",
                url: "get_doctor.php",
                data:'doctor='+val,
                success: function(data){
                    $("#fees").html(data);
                }
            });
        }
    </script>   
    
    <script>
        function showConfirmation() {
            var confirmationBox = document.getElementById('confirmationBox');
            confirmationBox.style.display = 'block';
        }
        
        function hideConfirmation() {
            var confirmationBox = document.getElementById('confirmationBox');
            confirmationBox.style.display = 'none';
        }
    </script>
    
    <style>
        /* Mejoras visuales para el formulario de reservar citas */
        .panel-white {
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            border: none;
            overflow: hidden;
        }
        
        .panel-heading {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
        
        .panel-heading h5 {
            color: white;
            font-size: 22px;
            font-weight: 700;
            margin: 0;
        }
        
        .panel-body {
            padding: 30px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 40px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .mainTitle {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        #page-title {
            margin-bottom: 30px;
        }
        
        #confirmationBox {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
        }
        
        .confirmationContent {
            background: white;
            margin: 10% auto;
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideIn 0.4s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .confirmationContent h3 {
            color: #27ae60;
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .confirmationContent h3:before {
            content: "✓";
            display: block;
            font-size: 60px;
            color: #27ae60;
            margin-bottom: 15px;
            background: #d4edda;
            width: 100px;
            height: 100px;
            line-height: 100px;
            border-radius: 50%;
            margin: 0 auto 20px;
        }
        
        .confirmationContent button {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            border: none;
            padding: 12px 40px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .confirmationContent button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
        }
    </style>
</head>
<body>
    <div id="app">     
        <?php include('include/sidebar.php');?>
        <div class="app-content">
            <?php include('include/header.php');?>
            <div class="main-content" >
                <div class="wrap-content container" id="container">
                    <section id="page-title">
                        <div class="row">
                            <div class="col-sm-8">
                                <h1 class="mainTitle">Usuario | Reservar Cita</h1>
                            </div>
                            <ol class="breadcrumb">
                                <li>
                                    <span>Usuario</span>
                                </li>
                                <li class="active">
                                    <span>Reservar Cita</span>
                                </li>
                            </ol>
                        </section>
                        <div class="container-fluid container-fullw bg-white">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row margin-top-30">
                                        <div class="col-lg-8 col-md-12">
                                            <div class="panel panel-white">
                                                <div class="panel-heading">
                                                    <h5 class="panel-title"><i class="fa fa-calendar-plus-o"></i> Reservar Cita</h5>
                                                </div>
                                                <div class="panel-body">
                                                    <p style="color:red;"><?php echo htmlentities($_SESSION['msg1']);?>
                                                        <?php echo htmlentities($_SESSION['msg1']="");?></p>  
                                                    <form role="form" name="book" method="post" >
                                                        <div class="form-group">
                                                            <label for="DoctorSpecialization">
                                                                Especialización Médica
                                                            </label>
                                                            <select name="Doctorspecialization" class="form-control" onChange="obtenerDoctor(this.value);" required="required">
                                                                <option value="">Seleccionar Especialización</option>
                                                                <?php $ret=mysqli_query($con,"select * from doctorspecilization");
                                                                while($row=mysqli_fetch_array($ret))
                                                                {
                                                                ?>
                                                                <option value="<?php echo htmlentities($row['specilization']);?>">
                                                                    <?php echo htmlentities($row['specilization']);?>
                                                                </option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="doctor">
                                                                Doctor
                                                            </label>
                                                            <select name="doctor" class="form-control" id="doctor" onChange="obtenerTarifa(this.value);" required="required">
                                                                <option value="">Seleccionar Doctor</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="consultancyfees">
                                                                Tarifa de Consulta
                                                            </label>
                                                            <select name="fees" class="form-control" id="fees"  readonly>
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="AppointmentDate">
                                                                Fecha
                                                            </label>
                                                            <input class="form-control datepicker" name="appdate"  required="required" data-date-format="yyyy-mm-dd">
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="Appointmenttime">
                                                                Hora
                                                            </label>
                                                            <input class="form-control" name="apptime" id="timepicker1" required="required">Ej: 10:00 PM
                                                        </div>
                                                        <button type="submit" name="submit" class="btn btn-o btn-primary">
                                                            Enviar
                                                        </button>
                                                    </form>
                                                </div>
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
        
        <!-- Ventana de confirmación -->
        <div id="confirmationBox">
            <div class="confirmationContent">
                <h3>¡Tu cita se ha reservado exitosamente!</h3>
                <p style="color: #7f8c8d; font-size: 16px; margin: 10px 0;">Recibirás una confirmación pronto</p>
                <button onclick="hideConfirmation()">Aceptar</button>
            </div>
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
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                startDate: '-3d'
            });
            $('#timepicker1').timepicker();
        </script>
    </body>
</html>