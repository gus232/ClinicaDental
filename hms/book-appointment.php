<?php
session_start();
//error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
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
        #confirmationBox {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }
        
        .confirmationContent {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 30%;
            text-align: center;
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
                                                    <h5 class="panel-title">Reservar Cita</h5>
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