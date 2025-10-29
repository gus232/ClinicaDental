<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
include('include/rbac-functions.php');
check_login();

$user_id = $_SESSION['id'];
$success_msg = '';
$error_msg = '';

// PROCESAR ACTUALIZACIÓN
if(isset($_POST['submit']))
{
    // Datos básicos del usuario
    $full_name = mysqli_real_escape_string($con, $_POST['full_name']);

    // Datos médicos del paciente
    $date_of_birth = isset($_POST['date_of_birth']) ? mysqli_real_escape_string($con, $_POST['date_of_birth']) : NULL;
    $gender = isset($_POST['gender']) ? mysqli_real_escape_string($con, $_POST['gender']) : NULL;
    $blood_type = isset($_POST['blood_type']) ? mysqli_real_escape_string($con, $_POST['blood_type']) : NULL;
    $height = isset($_POST['height']) ? floatval($_POST['height']) : NULL;
    $weight = isset($_POST['weight']) ? floatval($_POST['weight']) : NULL;
    $allergies = isset($_POST['allergies']) ? mysqli_real_escape_string($con, $_POST['allergies']) : NULL;
    $chronic_conditions = isset($_POST['chronic_conditions']) ? mysqli_real_escape_string($con, $_POST['chronic_conditions']) : NULL;
    $current_medications = isset($_POST['current_medications']) ? mysqli_real_escape_string($con, $_POST['current_medications']) : NULL;
    $past_surgeries = isset($_POST['past_surgeries']) ? mysqli_real_escape_string($con, $_POST['past_surgeries']) : NULL;
    $family_medical_history = isset($_POST['family_medical_history']) ? mysqli_real_escape_string($con, $_POST['family_medical_history']) : NULL;

    // Datos de contacto
    $address = isset($_POST['address']) ? mysqli_real_escape_string($con, $_POST['address']) : NULL;
    $city = isset($_POST['city']) ? mysqli_real_escape_string($con, $_POST['city']) : NULL;
    $state = isset($_POST['state']) ? mysqli_real_escape_string($con, $_POST['state']) : NULL;
    $postal_code = isset($_POST['postal_code']) ? mysqli_real_escape_string($con, $_POST['postal_code']) : NULL;
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($con, $_POST['phone']) : NULL;
    $emergency_contact = isset($_POST['emergency_contact']) ? mysqli_real_escape_string($con, $_POST['emergency_contact']) : NULL;
    $emergency_phone = isset($_POST['emergency_phone']) ? mysqli_real_escape_string($con, $_POST['emergency_phone']) : NULL;

    // Datos de seguro
    $has_insurance = isset($_POST['has_insurance']) ? 1 : 0;
    $insurance_provider = isset($_POST['insurance_provider']) ? mysqli_real_escape_string($con, $_POST['insurance_provider']) : NULL;
    $insurance_number = isset($_POST['insurance_number']) ? mysqli_real_escape_string($con, $_POST['insurance_number']) : NULL;
    $insurance_expiry_date = isset($_POST['insurance_expiry_date']) ? mysqli_real_escape_string($con, $_POST['insurance_expiry_date']) : NULL;

    try {
        // Actualizar tabla users
        $sql_users = "UPDATE users SET full_name = ? WHERE id = ?";
        $stmt = $con->prepare($sql_users);
        $stmt->bind_param("si", $full_name, $user_id);
        $users_updated = $stmt->execute();
        $stmt->close();

        // Actualizar tabla patients
        $sql_patients = "UPDATE patients SET
                        date_of_birth = ?,
                        gender = ?,
                        blood_type = ?,
                        height = ?,
                        weight = ?,
                        allergies = ?,
                        chronic_conditions = ?,
                        current_medications = ?,
                        past_surgeries = ?,
                        family_medical_history = ?,
                        address = ?,
                        city = ?,
                        state = ?,
                        postal_code = ?,
                        phone = ?,
                        emergency_contact = ?,
                        emergency_phone = ?,
                        has_insurance = ?,
                        insurance_provider = ?,
                        insurance_number = ?,
                        insurance_expiry_date = ?
                        WHERE user_id = ?";

        $stmt = $con->prepare($sql_patients);
        $stmt->bind_param("sssddsssssssssssisss",
            $date_of_birth, $gender, $blood_type, $height, $weight,
            $allergies, $chronic_conditions, $current_medications, $past_surgeries, $family_medical_history,
            $address, $city, $state, $postal_code, $phone,
            $emergency_contact, $emergency_phone,
            $has_insurance, $insurance_provider, $insurance_number, $insurance_expiry_date, $user_id
        );

        $patients_updated = $stmt->execute();
        $stmt->close();

        if($users_updated && $patients_updated) {
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
$patient_data = array();

$sql = "SELECT u.id, u.full_name, u.email, u.user_type, u.status, u.created_at,
               p.user_id, p.date_of_birth, p.gender, p.blood_type, p.height, p.weight,
               p.allergies, p.chronic_conditions, p.current_medications, p.past_surgeries,
               p.family_medical_history, p.address, p.city, p.state, p.postal_code, p.phone,
               p.emergency_contact, p.emergency_phone, p.has_insurance, p.insurance_provider,
               p.insurance_number, p.insurance_expiry_date, p.status as patient_status
        FROM users u
        LEFT JOIN patients p ON u.id = p.user_id
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Paciente | Mi Perfil</title>

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
            color: #667eea;
            border-bottom: 3px solid #667eea;
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
            border-bottom: 2px solid #667eea;
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
                                <h1 class="mainTitle">Paciente | Mi Perfil</h1>
                            </div>
                            <ol class="breadcrumb">
                                <li>
                                    <span>Paciente</span>
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
                                                <h4>Perfil de <?php echo htmlentities($user_data['full_name']); ?></h4>
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
                                                            <a href="#medical-info" aria-controls="medical-info" role="tab" data-toggle="tab">
                                                                <i class="fa fa-heartbeat"></i> Información Médica
                                                            </a>
                                                        </li>
                                                        <li role="presentation">
                                                            <a href="#contact-info" aria-controls="contact-info" role="tab" data-toggle="tab">
                                                                <i class="fa fa-phone"></i> Contacto
                                                            </a>
                                                        </li>
                                                        <li role="presentation">
                                                            <a href="#insurance-info" aria-controls="insurance-info" role="tab" data-toggle="tab">
                                                                <i class="fa fa-shield"></i> Seguro Médico
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

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="gender">Género</label>
                                                                    <select name="gender" id="gender" class="form-control">
                                                                        <option value="">-- Seleccionar --</option>
                                                                        <option value="male" <?php if($user_data['gender'] == 'male') echo 'selected'; ?>>Masculino</option>
                                                                        <option value="female" <?php if($user_data['gender'] == 'female') echo 'selected'; ?>>Femenino</option>
                                                                        <option value="other" <?php if($user_data['gender'] == 'other') echo 'selected'; ?>>Otro</option>
                                                                        <option value="prefer_not_to_say" <?php if($user_data['gender'] == 'prefer_not_to_say') echo 'selected'; ?>>Prefiero no especificar</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="date_of_birth">Fecha de Nacimiento</label>
                                                                    <input type="date" name="date_of_birth" id="date_of_birth" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['date_of_birth']); ?>">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- TAB 2: INFORMACIÓN MÉDICA -->
                                                        <div role="tabpanel" class="tab-pane" id="medical-info">
                                                            <h4 class="form-section-title"><i class="fa fa-stethoscope"></i> Información Médica</h4>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="blood_type">Tipo de Sangre</label>
                                                                    <select name="blood_type" id="blood_type" class="form-control">
                                                                        <option value="">-- Seleccionar --</option>
                                                                        <option value="O+" <?php if($user_data['blood_type'] == 'O+') echo 'selected'; ?>>O+</option>
                                                                        <option value="O-" <?php if($user_data['blood_type'] == 'O-') echo 'selected'; ?>>O-</option>
                                                                        <option value="A+" <?php if($user_data['blood_type'] == 'A+') echo 'selected'; ?>>A+</option>
                                                                        <option value="A-" <?php if($user_data['blood_type'] == 'A-') echo 'selected'; ?>>A-</option>
                                                                        <option value="B+" <?php if($user_data['blood_type'] == 'B+') echo 'selected'; ?>>B+</option>
                                                                        <option value="B-" <?php if($user_data['blood_type'] == 'B-') echo 'selected'; ?>>B-</option>
                                                                        <option value="AB+" <?php if($user_data['blood_type'] == 'AB+') echo 'selected'; ?>>AB+</option>
                                                                        <option value="AB-" <?php if($user_data['blood_type'] == 'AB-') echo 'selected'; ?>>AB-</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="height">Altura (cm)</label>
                                                                    <input type="number" name="height" id="height" class="form-control" step="0.01"
                                                                           value="<?php echo htmlentities($user_data['height']); ?>" placeholder="170">
                                                                </div>
                                                            </div>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="weight">Peso (kg)</label>
                                                                    <input type="number" name="weight" id="weight" class="form-control" step="0.1"
                                                                           value="<?php echo htmlentities($user_data['weight']); ?>" placeholder="70">
                                                                </div>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="allergies">Alergias</label>
                                                                <textarea name="allergies" id="allergies" class="form-control" rows="3"
                                                                          placeholder="Listar alergias conocidas..."><?php echo htmlentities($user_data['allergies']); ?></textarea>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="chronic_conditions">Condiciones Crónicas</label>
                                                                <textarea name="chronic_conditions" id="chronic_conditions" class="form-control" rows="3"
                                                                          placeholder="Diabetes, Hipertensión, etc..."><?php echo htmlentities($user_data['chronic_conditions']); ?></textarea>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="current_medications">Medicamentos Actuales</label>
                                                                <textarea name="current_medications" id="current_medications" class="form-control" rows="3"
                                                                          placeholder="Medicamentos que está tomando..."><?php echo htmlentities($user_data['current_medications']); ?></textarea>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="past_surgeries">Cirugías Anteriores</label>
                                                                <textarea name="past_surgeries" id="past_surgeries" class="form-control" rows="3"
                                                                          placeholder="Procedimientos quirúrgicos realizados..."><?php echo htmlentities($user_data['past_surgeries']); ?></textarea>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="family_medical_history">Historial Médico Familiar</label>
                                                                <textarea name="family_medical_history" id="family_medical_history" class="form-control" rows="3"
                                                                          placeholder="Enfermedades hereditarias, antecedentes..."><?php echo htmlentities($user_data['family_medical_history']); ?></textarea>
                                                            </div>
                                                        </div>

                                                        <!-- TAB 3: CONTACTO -->
                                                        <div role="tabpanel" class="tab-pane" id="contact-info">
                                                            <h4 class="form-section-title"><i class="fa fa-map-marker"></i> Información de Contacto</h4>

                                                            <div class="form-group">
                                                                <label for="address">Dirección</label>
                                                                <textarea name="address" id="address" class="form-control" rows="2"><?php echo htmlentities($user_data['address']); ?></textarea>
                                                            </div>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="city">Ciudad</label>
                                                                    <input type="text" name="city" id="city" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['city']); ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="state">Provincia/Estado</label>
                                                                    <input type="text" name="state" id="state" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['state']); ?>">
                                                                </div>
                                                            </div>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="postal_code">Código Postal</label>
                                                                    <input type="text" name="postal_code" id="postal_code" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['postal_code']); ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="phone">Teléfono</label>
                                                                    <input type="tel" name="phone" id="phone" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['phone']); ?>">
                                                                </div>
                                                            </div>

                                                            <h4 class="form-section-title"><i class="fa fa-heart"></i> Contacto de Emergencia</h4>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="emergency_contact">Nombre del Contacto</label>
                                                                    <input type="text" name="emergency_contact" id="emergency_contact" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['emergency_contact']); ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="emergency_phone">Teléfono de Emergencia</label>
                                                                    <input type="tel" name="emergency_phone" id="emergency_phone" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['emergency_phone']); ?>">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- TAB 4: SEGURO -->
                                                        <div role="tabpanel" class="tab-pane" id="insurance-info">
                                                            <h4 class="form-section-title"><i class="fa fa-shield"></i> Información de Seguro Médico</h4>

                                                            <div class="form-group">
                                                                <label>
                                                                    <input type="checkbox" name="has_insurance" value="1"
                                                                           <?php if($user_data['has_insurance'] == 1) echo 'checked'; ?>>
                                                                    Poseo Seguro Médico
                                                                </label>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="insurance_provider">Proveedor de Seguro</label>
                                                                <input type="text" name="insurance_provider" id="insurance_provider" class="form-control"
                                                                       value="<?php echo htmlentities($user_data['insurance_provider']); ?>"
                                                                       placeholder="Nombre de la aseguradora">
                                                            </div>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="insurance_number">Número de Póliza</label>
                                                                    <input type="text" name="insurance_number" id="insurance_number" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['insurance_number']); ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="insurance_expiry_date">Fecha de Vencimiento</label>
                                                                    <input type="date" name="insurance_expiry_date" id="insurance_expiry_date" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['insurance_expiry_date']); ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <hr />
                                                    <button type="submit" name="submit" class="btn btn-o btn-primary btn-lg">
                                                        <i class="fa fa-save"></i> Guardar Cambios
                                                    </button>
                                                    <a href="dashboard1.php" class="btn btn-o btn-default btn-lg">
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
