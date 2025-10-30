<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug paso a paso
echo "<!-- DEBUG: Iniciando script -->";
try {
    include('include/config.php');
    echo "<!-- DEBUG: Config OK -->";
    
    include('include/checklogin.php');
    echo "<!-- DEBUG: Checklogin OK -->";
    
    include('../include/rbac-functions.php');
    echo "<!-- DEBUG: RBAC OK -->";
    
    check_login();
    echo "<!-- DEBUG: Login verificado -->";
    
} catch (Exception $e) {
    die("ERROR CRÍTICO: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Doctor | Gestionar Pacientes</title>
    
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
        /* Mejoras visuales para gestionar pacientes */
        .mainTitle {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        #page-title {
            margin-bottom: 30px;
        }
        
        .over-title {
            font-size: 18px;
            color: #7f8c8d;
            margin-bottom: 20px;
        }
        
        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        /* Ajuste del panel de configuración: cuadrado pegado arriba a la derecha */
        .settings.panel.panel-default {
            position: fixed;
            right: 0;
            top: 120px;
            z-index: 9999;
            background: transparent;
            border: none;
            box-shadow: none;
        }
        .settings button {
            position: relative;
            border-radius: 4px;
            padding: 8px 12px;
        }
        .settings .panel-body {
            display: none;
            position: absolute;
            right: 100%;
            top: 0;
            width: 250px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .settings.active .panel-body { display: block; }
        
        .table thead {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .table thead th {
            border: none;
            padding: 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            color: #2c3e50;
        }
        
        .table tbody td a {
            color: #11998e;
            font-size: 18px;
            margin: 0 5px;
            transition: all 0.3s ease;
        }
        
        .table tbody td a:hover {
            color: #38ef7d;
            transform: scale(1.2);
        }
        
        .container-fullw {
            padding: 30px;
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
                        <!-- start: TÍTULO DE LA PÁGINA -->
<section id="page-title">
<div class="row">
<div class="col-sm-8">
<h1 class="mainTitle">Doctor | Gestionar Pacientes</h1>
</div>
<ol class="breadcrumb">
<li>
<span>Doctor</span>
</li>
<li class="active">
<span>Gestionar Pacientes</span>
</li>
</ol>
</div>
</section>
<div class="container-fluid container-fullw bg-white">
<div class="row">
<div class="col-md-12">
<h5 class="over-title margin-bottom-15">Gestionar <span class="text-bold">Pacientes</span></h5>
    
<table class="table table-hover" id="sample-table-1">
<thead>
<tr>
<th class="center">#</th>
<th>Nombre del Paciente</th>
<th>Número de Contacto del Paciente</th>
<th>Género del Paciente </th>
<th>Fecha de Creación </th>
<th>Fecha de Actualización </th>
<th>Acción</th>
</tr>
</thead>
<tbody>
<?php
echo "<!-- DEBUG: Iniciando consulta SQL -->";
$docid = $_SESSION['id'];
echo "<!-- DEBUG: Doctor ID = $docid -->";

// Consulta súper simple para evitar errores
$sql = false;
$patients = array();

// Intentar consulta básica
try {
    echo "<!-- DEBUG: Probando consulta básica -->";
    $query = "SELECT id, fullName FROM users LIMIT 5";
    $test = mysqli_query($con, $query);
    if ($test) {
        echo "<!-- DEBUG: Consulta básica OK -->";
        // Si funciona, intentar la consulta real
        $real_query = "SELECT DISTINCT u.id, u.fullName, u.address, u.gender 
                       FROM users u 
                       WHERE u.id IN (SELECT userId FROM appointment WHERE doctorId = '$docid')";
        $sql = mysqli_query($con, $real_query);
        if (!$sql) {
            echo "<!-- DEBUG: Error en consulta real: " . mysqli_error($con) . " -->";
        } else {
            echo "<!-- DEBUG: Consulta real OK -->";
        }
    } else {
        echo "<!-- DEBUG: Error en consulta básica: " . mysqli_error($con) . " -->";
    }
} catch (Exception $e) {
    echo "<!-- DEBUG: Excepción en SQL: " . $e->getMessage() . " -->";
}
echo "<!-- DEBUG: Mostrando resultados -->";
if ($sql && mysqli_num_rows($sql) > 0) {
    $cnt = 1;
    while($row = mysqli_fetch_array($sql)) {
        echo "<!-- DEBUG: Procesando fila $cnt -->";
?>
<tr>
<td class="center"><?php echo $cnt;?>.</td>
<td class="hidden-xs"><?php echo isset($row['fullName']) ? $row['fullName'] : 'Sin nombre';?></td>
<td><?php echo isset($row['address']) ? $row['address'] : 'No disponible';?></td>
<td><?php echo isset($row['gender']) ? $row['gender'] : 'No especificado';?></td>
<td>Fecha creación</td>
<td>Fecha actualización</td>
<td>
<a href="#"><i class="fa fa-edit"></i></a> || 
<a href="#"><i class="fa fa-eye"></i></a>
</td>
</tr>
<?php 
        $cnt++;
    }
} else {
    echo "<!-- DEBUG: No hay resultados o error en SQL -->";
?>
<tr>
<td colspan="7" class="text-center">
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i> No hay pacientes registrados para este doctor.
        <br><small>Doctor ID: <?php echo $docid; ?></small>
    </div>
</td>
</tr>
<?php 
}
echo "<!-- DEBUG: Tabla completada -->";
?>
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
