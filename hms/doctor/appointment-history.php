<?php
session_start();
error_reporting(0);
include('include/config.php');
include('../include/rbac-functions.php');

if(isset($_GET['cancel']))
{
    mysqli_query($con,"update appointment set doctorStatus='0' where id ='".$_GET['id']."'");
    $_SESSION['msg']="Cita cancelada !!";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Doctor | Historial de Citas</title>
    
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
        /* Mejoras visuales para historial de citas de doctores */
        .mainTitle {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        #page-title {
            margin-bottom: 30px;
        }
        
        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .table thead {
            background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
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
        
        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            display: inline-block;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled-patient {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-cancelled-doctor {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-transparent {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-transparent:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
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
                <!-- end: TOP NAVBAR -->
                <div class="main-content" >
                    <div class="wrap-content container" id="container">
                        <!-- start: PAGE TITLE -->
                        <section id="page-title">
                            <div class="row">
                                <div class="col-sm-8">
                                    <h1 class="mainTitle">Doctor  | Historial de Citas</h1>
                                                                    </div>
                                <ol class="breadcrumb">
                                    <li>
                                        <span>Doctor </span>
                                    </li>
                                    <li class="active">
                                        <span>Historial de Citas</span>
                                    </li>
                                </ol>
                            </div>
                        </section>
                        <!-- end: PAGE TITLE -->
                        <!-- start: BASIC EXAMPLE -->
                        <div class="container-fluid container-fullw bg-white">
                        

                                    <div class="row">
                                <div class="col-md-12">
                                    
                                    <p style="color:red;"><?php echo htmlentities($_SESSION['msg']);?>
                                <?php echo htmlentities($_SESSION['msg']="");?></p>    
                                    <table class="table table-hover" id="sample-table-1">
                                        <thead>
                                            <tr>
                                                <th class="center">#</th>
                                                <th class="hidden-xs">Nombre del Paciente</th>
                                                <th>Especialización</th>
                                                <th>Consultoría</th>
                                                <th>Fecha / Hora de la Cita</th>
                                                <th>Fecha de Creación de la Cita</th>
                                                <th>Estado Actual</th>
                                                <th>Acción</th>
                                                
                                            </tr>
                                        </thead>
                                        <tbody>
<?php
$sql=mysqli_query($con,"select users.fullName as fname,appointment.*  from appointment join users on users.id=appointment.userId where appointment.doctorId='".$_SESSION['id']."'");
$cnt=1;
while($row=mysqli_fetch_array($sql))
{
?>

                                            <tr>
                                                <td class="center"><?php echo $cnt;?>.</td>
                                                <td class="hidden-xs"><?php echo $row['fname'];?></td>
                                                <td><?php echo $row['doctorSpecialization'];?></td>
                                                <td><?php echo $row['consultancyFees'];?></td>
                                                <td><?php echo $row['appointmentDate'];?> / <?php echo
                                                 $row['appointmentTime'];?>
                                                </td>
                                                <td><?php echo $row['postingDate'];?></td>
                                                <td>
<?php if(($row['userStatus']==1) && ($row['doctorStatus']==1))  
{
    echo '<span class="status-badge status-active"><i class="fa fa-check-circle"></i> Activa</span>';
}
if(($row['userStatus']==0) && ($row['doctorStatus']==1))  
{
    echo '<span class="status-badge status-cancelled-patient"><i class="fa fa-times-circle"></i> Cancelada por el Paciente</span>';
}

if(($row['userStatus']==1) && ($row['doctorStatus']==0))  
{
    echo '<span class="status-badge status-cancelled-doctor"><i class="fa fa-ban"></i> Cancelada por ti</span>';
}



                                                ?></td>
                                                <td >
                                                <div class="visible-md visible-lg hidden-sm hidden-xs">
                            <?php if(($row['userStatus']==1) && ($row['doctorStatus']==1))  
{ ?>

                                                    
    <a href="appointment-history.php?id=<?php echo $row['id']?>&cancel=update" onClick="return confirm('¿Estás seguro de que deseas cancelar esta cita?')"class="btn btn-transparent btn-xs tooltips" title="Cancelar Cita" tooltip-placement="top" tooltip="Eliminar">Cancelar</a>
    <?php } else {

        echo "Cancelada";
        } ?>
                                                </div>
                                                </td>
                                            </tr>
                                            
                                            <?php 
$cnt=$cnt+1;
                                             }?>
                                            
                                            
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                                </div>
                        
                        <!-- end: BASIC EXAMPLE -->
                        <!-- end: SELECT BOXES -->
                        
                    </div>
                </div>
            </div>
            <!-- start: FOOTER -->
    <?php include('include/footer.php');?>
            <!-- end: FOOTER -->
        
            <!-- start: SETTINGS -->
    <?php include('include/setting.php');?>
            
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
            });
        </script>
        <!-- end: JavaScript Event Handlers for this page -->
        <!-- end: CLIP-TWO JAVASCRIPTS -->
    </body>
</html>
