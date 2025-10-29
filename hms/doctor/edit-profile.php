<?php
session_start();
error_reporting(1);
include('../include/config.php');
include('include/checklogin.php');
include('../../include/rbac-functions.php');
check_login();

$user_id = $_SESSION['id'];
$success_msg = '';
$error_msg = '';

// PROCESAR ACTUALIZACIÓN
if(isset($_POST['submit']))
{
    // Datos básicos del usuario
    $full_name = mysqli_real_escape_string($con, $_POST['full_name']);

    // Datos profesionales del doctor
    $license_number = isset($_POST['license_number']) ? mysqli_real_escape_string($con, $_POST['license_number']) : NULL;
    $specialization_id = isset($_POST['specialization_id']) ? intval($_POST['specialization_id']) : NULL;
    $years_of_experience = isset($_POST['years_of_experience']) ? intval($_POST['years_of_experience']) : NULL;
    $bio = isset($_POST['bio']) ? mysqli_real_escape_string($con, $_POST['bio']) : NULL;
    $languages = isset($_POST['languages']) ? mysqli_real_escape_string($con, $_POST['languages']) : NULL;

    // Datos de consulta
    $consultation_fee = isset($_POST['consultation_fee']) ? floatval($_POST['consultation_fee']) : NULL;
    $consultation_duration = isset($_POST['consultation_duration']) ? intval($_POST['consultation_duration']) : NULL;
    $max_daily_appointments = isset($_POST['max_daily_appointments']) ? intval($_POST['max_daily_appointments']) : NULL;

    try {
        // Actualizar tabla users
        $sql_users = "UPDATE users SET full_name = ? WHERE id = ?";
        $stmt = $con->prepare($sql_users);
        $stmt->bind_param("si", $full_name, $user_id);
        $users_updated = $stmt->execute();
        $stmt->close();

        // Actualizar tabla doctors
        $sql_doctors = "UPDATE doctors SET
                        license_number = ?,
                        specialization_id = ?,
                        years_of_experience = ?,
                        bio = ?,
                        languages = ?,
                        consultation_fee = ?,
                        consultation_duration = ?,
                        max_daily_appointments = ?
                        WHERE user_id = ?";

        $stmt = $con->prepare($sql_doctors);
        $stmt->bind_param("siissddi",
            $license_number, $specialization_id, $years_of_experience,
            $bio, $languages,
            $consultation_fee, $consultation_duration, $max_daily_appointments, $user_id
        );

        $doctors_updated = $stmt->execute();
        $stmt->close();

        if($users_updated && $doctors_updated) {
            $success_msg = "Tu perfil se ha actualizado correctamente";
        } else {
            $error_msg = "Error al actualizar el perfil";
        }
    } catch(Exception $e) {
        $error_msg = "Error: " . $e->getMessage();
    }
}

// OBTENER DATOS ACTUALES
$user_data = array();

$sql = "SELECT u.id, u.full_name, u.email, u.user_type, u.status, u.created_at,
               d.user_id, d.license_number, d.specialization_id, d.years_of_experience,
               d.bio, d.languages,
               d.consultation_fee, d.consultation_duration, d.max_daily_appointments,
               d.rating, d.total_ratings, d.total_appointments, d.completed_appointments,
               d.cancelled_appointments, d.status as doctor_status
        FROM users u
        LEFT JOIN doctors d ON u.id = d.user_id
        WHERE u.id = ?";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $user_data = $row;
}
$stmt->close();

// Obtener especializaciones
$specializations = array();
$spec_query = "SELECT id, specilization FROM doctorspecilization ORDER BY specilization ASC";
$spec_result = mysqli_query($con, $spec_query);
while($spec_row = mysqli_fetch_assoc($spec_result)) {
    $specializations[] = $spec_row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Médico | Mi Perfil</title>

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
        .profile-tabs {
            margin-top: 20px;
        }
        .nav-tabs > li > a {
            color: #666;
            border-radius: 4px 4px 0 0;
        }
        .nav-tabs > li.active > a {
            background-color: #f5f5f5;
            color: #11998e;
            border-bottom: 3px solid #11998e;
        }
        .tab-pane {
            padding: 30px 20px;
        }
        .form-section-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #11998e;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        .readonly-field {
            background-color: #f5f5f5 !important;
            cursor: not-allowed;
        }
        .alert-success, .alert-danger {
            margin-bottom: 20px;
        }
        .stat-box {
            background: #f0f8f7;
            border-left: 4px solid #11998e;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
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
                    <section id="page-title">
                        <div class="row">
                            <div class="col-sm-8">
                                <h1 class="mainTitle">Médico | Mi Perfil</h1>
                            </div>
                            <ol class="breadcrumb">
                                <li>
                                    <span>Médico</span>
                                </li>
                                <li class="active">
                                    <span>Mi Perfil</span>
                                </li>
                            </ol>
                        </div>
                    </section>

                    <div class="container-fluid container-fullw bg-white">
                        <div class="row">
                            <div class="col-md-12">
                                <!-- Mensajes -->
                                <?php if($success_msg): ?>
                                <div class="alert alert-success alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <strong>Éxito!</strong> <?php echo htmlentities($success_msg); ?>
                                </div>
                                <?php endif; ?>

                                <?php if($error_msg): ?>
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <strong>Error!</strong> <?php echo htmlentities($error_msg); ?>
                                </div>
                                <?php endif; ?>

                                <div class="row margin-top-30">
                                    <div class="col-lg-10 col-md-12">
                                        <div class="panel panel-white">
                                            <div class="panel-heading">
                                                <h5 class="panel-title">Editar Mi Perfil</h5>
                                            </div>
                                            <div class="panel-body">
                                                <?php if(!empty($user_data)): ?>
                                                <h4>Perfil de Dr(a). <?php echo htmlentities($user_data['full_name']); ?></h4>
                                                <p><b>Miembro desde: </b><?php echo htmlentities($user_data['created_at']); ?></p>
                                                <hr />

                                                <form role="form" name="editProfile" method="post">
                                                    <!-- TABS -->
                                                    <ul class="nav nav-tabs profile-tabs" role="tablist">
                                                        <li role="presentation" class="active">
                                                            <a href="#personal-info" aria-controls="personal-info" role="tab" data-toggle="tab">
                                                                <i class="fa fa-user"></i> Información Personal
                                                            </a>
                                                        </li>
                                                        <li role="presentation">
                                                            <a href="#professional-info" aria-controls="professional-info" role="tab" data-toggle="tab">
                                                                <i class="fa fa-stethoscope"></i> Información Profesional
                                                            </a>
                                                        </li>
                                                        <li role="presentation">
                                                            <a href="#consultation-info" aria-controls="consultation-info" role="tab" data-toggle="tab">
                                                                <i class="fa fa-calendar"></i> Consultas
                                                            </a>
                                                        </li>
                                                        <li role="presentation">
                                                            <a href="#statistics-info" aria-controls="statistics-info" role="tab" data-toggle="tab">
                                                                <i class="fa fa-bar-chart"></i> Estadísticas
                                                            </a>
                                                        </li>
                                                    </ul>

                                                    <!-- TAB CONTENT -->
                                                    <div class="tab-content">
                                                        <!-- TAB 1: INFORMACIÓN PERSONAL -->
                                                        <div role="tabpanel" class="tab-pane active" id="personal-info">
                                                            <h4 class="form-section-title"><i class="fa fa-id-card"></i> Datos Personales</h4>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="full_name">Nombre Completo *</label>
                                                                    <input type="text" name="full_name" id="full_name" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['full_name']); ?>" required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="email">Email *</label>
                                                                    <input type="email" name="email" id="email" class="form-control readonly-field"
                                                                           value="<?php echo htmlentities($user_data['email']); ?>" readonly>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- TAB 2: INFORMACIÓN PROFESIONAL -->
                                                        <div role="tabpanel" class="tab-pane" id="professional-info">
                                                            <h4 class="form-section-title"><i class="fa fa-stethoscope"></i> Información Profesional</h4>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="license_number">Número de Licencia Médica</label>
                                                                    <input type="text" name="license_number" id="license_number" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['license_number']); ?>"
                                                                           placeholder="LIC-XXXXX">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="specialization_id">Especialización *</label>
                                                                    <select name="specialization_id" id="specialization_id" class="form-control" required>
                                                                        <option value="">-- Seleccionar --</option>
                                                                        <?php foreach($specializations as $spec): ?>
                                                                        <option value="<?php echo $spec['id']; ?>"
                                                                                <?php if($user_data['specialization_id'] == $spec['id']) echo 'selected'; ?>>
                                                                            <?php echo htmlentities($spec['specilization']); ?>
                                                                        </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="years_of_experience">Años de Experiencia</label>
                                                                <input type="number" name="years_of_experience" id="years_of_experience"
                                                                       class="form-control" min="0" max="70"
                                                                       value="<?php echo htmlentities($user_data['years_of_experience']); ?>">
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="languages">Idiomas</label>
                                                                <textarea name="languages" id="languages" class="form-control" rows="2"
                                                                          placeholder="Idiomas que habla (separados por comas)..."><?php echo htmlentities($user_data['languages']); ?></textarea>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="bio">Biografía Profesional</label>
                                                                <textarea name="bio" id="bio" class="form-control" rows="4"
                                                                          placeholder="Información sobre tu experiencia y especialidad..."><?php echo htmlentities($user_data['bio']); ?></textarea>
                                                            </div>
                                                        </div>

                                                        <!-- TAB 3: CONSULTAS -->
                                                        <div role="tabpanel" class="tab-pane" id="consultation-info">
                                                            <h4 class="form-section-title"><i class="fa fa-calendar-check-o"></i> Configuración de Consultas</h4>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="consultation_fee">Honorarios por Consulta ($)</label>
                                                                    <input type="number" name="consultation_fee" id="consultation_fee"
                                                                           class="form-control" step="0.01" min="0"
                                                                           value="<?php echo htmlentities($user_data['consultation_fee']); ?>"
                                                                           placeholder="100.00">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="consultation_duration">Duración de Consulta (minutos)</label>
                                                                    <input type="number" name="consultation_duration" id="consultation_duration"
                                                                           class="form-control" min="15" max="120"
                                                                           value="<?php echo htmlentities($user_data['consultation_duration']); ?>"
                                                                           placeholder="30">
                                                                </div>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="max_daily_appointments">Máximo de Citas Diarias</label>
                                                                <input type="number" name="max_daily_appointments" id="max_daily_appointments"
                                                                       class="form-control" min="1" max="50"
                                                                       value="<?php echo htmlentities($user_data['max_daily_appointments']); ?>"
                                                                       placeholder="10">
                                                            </div>
                                                        </div>

                                                        <!-- TAB 4: ESTADÍSTICAS -->
                                                        <div role="tabpanel" class="tab-pane" id="statistics-info">
                                                            <h4 class="form-section-title"><i class="fa fa-bar-chart"></i> Tus Estadísticas</h4>

                                                            <div class="stat-box">
                                                                <p>
                                                                    <strong>Total de Citas:</strong>
                                                                    <span class="label label-info"><?php echo htmlentities($user_data['total_appointments'] ?? 0); ?></span>
                                                                </p>
                                                            </div>

                                                            <div class="stat-box">
                                                                <p>
                                                                    <strong>Citas Completadas:</strong>
                                                                    <span class="label label-success"><?php echo htmlentities($user_data['completed_appointments'] ?? 0); ?></span>
                                                                </p>
                                                            </div>

                                                            <div class="stat-box">
                                                                <p>
                                                                    <strong>Citas Canceladas:</strong>
                                                                    <span class="label label-danger"><?php echo htmlentities($user_data['cancelled_appointments'] ?? 0); ?></span>
                                                                </p>
                                                            </div>

                                                            <div class="stat-box">
                                                                <p>
                                                                    <strong>Calificación Promedio:</strong>
                                                                    <span class="label label-warning">
                                                                        <?php echo htmlentities($user_data['rating'] ?? '0.0'); ?> / 5.0
                                                                    </span>
                                                                    <small>(basada en <?php echo htmlentities($user_data['total_ratings'] ?? 0); ?> calificaciones)</small>
                                                                </p>
                                                            </div>

                                                            <div class="alert alert-info">
                                                                <p><strong>Nota:</strong> Las estadísticas se actualizan automáticamente según tus citas.</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <hr />
                                                    <button type="submit" name="submit" class="btn btn-o btn-primary btn-lg">
                                                        <i class="fa fa-save"></i> Guardar Cambios
                                                    </button>
                                                    <a href="dashboard.php" class="btn btn-o btn-default btn-lg">
                                                        <i class="fa fa-times"></i> Cancelar
                                                    </a>
                                                </form>
                                                <?php else: ?>
                                                <p class="alert alert-warning">No se pudo cargar la información del perfil.</p>
                                                <?php endif; ?>
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
