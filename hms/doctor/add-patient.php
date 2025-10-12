<?php
session_start();
error_reporting(0);
include('include/config.php');

if (isset($_POST['submit'])) {
    $docid = $_SESSION['id'];
    $patname = $_POST['patname'];
    $patcontact = $_POST['patcontact'];
    $patemail = $_POST['patemail'];
    $gender = $_POST['gender'];
    $pataddress = $_POST['pataddress'];
    $patage = $_POST['patage'];
    $medhis = $_POST['medhis'];
    $sql = mysqli_query($con, "INSERT INTO tblpatient(Docid, PatientName, PatientContno, PatientEmail, PatientGender, PatientAdd, PatientAge, PatientMedhis) VALUES ('$docid', '$patname', '$patcontact', '$patemail', '$gender', '$pataddress', '$patage', '$medhis')");
    if ($sql) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                swal({
                    title: '¡Éxito!',
                    text: 'Información del paciente añadida exitosamente',
                    icon: 'success',
                    button: 'OK'
                }).then(function() {
                    window.location.href = 'add-patient.php';
                });
            });
        </script>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Doctor | Añadir Paciente</title>
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
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
    function userAvailability() {
        $("#loaderIcon").show();
        jQuery.ajax({
            url: "check_availability.php",
            data: 'email=' + $("#patemail").val(),
            type: "POST",
            success: function (data) {
                $("#user-availability-status1").html(data);
                $("#loaderIcon").hide();
            },
            error: function () {}
        });
    }
    </script>
</head>
<body>
    <div id="app">        
        <?php include('include/sidebar.php'); ?>
        <div class="app-content">
            <?php include('include/header.php'); ?>
            <div class="main-content">
                <div class="wrap-content container" id="container">
                    <section id="page-title">
                        <div class="row">
                            <div class="col-sm-8">
                                <h1 class="mainTitle">Paciente | Añadir Paciente</h1>
                            </div>
                            <ol class="breadcrumb">
                                <li>
                                    <span>Paciente</span>
                                </li>
                                <li class="active">
                                    <span>Añadir Paciente</span>
                                </li>
                            </ol>
                        </div>
                    </section>
                    <div class="container-fluid container-fullw bg-white">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row margin-top-30">
                                    <div class="col-lg-8 col-md-12">
                                        <div class="panel panel-white">
                                            <div class="panel-heading">
                                                <h5 class="panel-title">Añadir Paciente</h5>
                                            </div>
                                            <div class="panel-body">
                                                <form role="form" name="" method="post">
                                                    <div class="form-group">
                                                        <label for="doctorname">Nombre del Paciente</label>
                                                        <input type="text" name="patname" class="form-control" placeholder="Ingrese el nombre del paciente" required="true">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="fess">Número de Contacto del Paciente</label>
                                                        <input type="text" name="patcontact" class="form-control" placeholder="Ingrese el número de contacto del paciente" required="true" maxlength="10" pattern="[0-9]+">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="fess">Correo Electrónico del Paciente</label>
                                                        <input type="email" id="patemail" name="patemail" class="form-control" placeholder="Ingrese el correo electrónico del paciente" required="true" onBlur="userAvailability()">
                                                        <span id="user-availability-status1" style="font-size:12px;"></span>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="block">Género</label>
                                                        <div class="clip-radio radio-primary">
                                                            <input type="radio" id="rg-female" name="gender" value="female">
                                                            <label for="rg-female">Femenino</label>
                                                            <input type="radio" id="rg-male" name="gender" value="male">
                                                            <label for="rg-male">Masculino</label>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="address">Dirección del Paciente</label>
                                                        <textarea name="pataddress" class="form-control" placeholder="Ingrese la dirección del paciente" required="true"></textarea>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="fess">Edad del Paciente</label>
                                                        <input type="text" name="patage" class="form-control" placeholder="Ingrese la edad del paciente" required="true">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="fess">Historial Médico</label>
                                                        <textarea type="text" name="medhis" class="form-control" placeholder="Ingrese el historial médico del paciente (si lo hay)" required="true"></textarea>
                                                    </div>
                                                    <button type="submit" name="submit" id="submit" class="btn btn-o btn-primary">Añadir</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12">
                                <div class="panel panel-white">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include('include/footer.php'); ?>
        <?php include('include/setting.php'); ?>
    </div>
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
        });
    </script>
</body>
</html>
