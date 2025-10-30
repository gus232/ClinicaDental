<?php
session_start();
error_reporting(1);
include('include/config.php');
include('include/checklogin.php');
include('../include/rbac-functions.php');
check_login();

$user_id = $_SESSION['id'];
$success_msg = '';
$error_msg = '';

// PROCESAR ACTUALIZACIÓN
if(isset($_POST['submit']))
{
    // Datos básicos del usuario
    $full_name = mysqli_real_escape_string($con, $_POST['full_name']);

    // Datos profesionales del admin
    $department = isset($_POST['department']) ? mysqli_real_escape_string($con, $_POST['department']) : NULL;
    $job_title = isset($_POST['job_title']) ? mysqli_real_escape_string($con, $_POST['job_title']) : NULL;
    $technical_area = isset($_POST['technical_area']) ? mysqli_real_escape_string($con, $_POST['technical_area']) : NULL;
    $certifications = isset($_POST['certifications']) ? mysqli_real_escape_string($con, $_POST['certifications']) : NULL;
    $specialization = isset($_POST['specialization']) ? mysqli_real_escape_string($con, $_POST['specialization']) : NULL;
    $years_of_experience = isset($_POST['years_of_experience']) ? intval($_POST['years_of_experience']) : NULL;
    $education_level = isset($_POST['education_level']) ? mysqli_real_escape_string($con, $_POST['education_level']) : NULL;

    // Datos de contacto
    $office_phone = isset($_POST['office_phone']) ? mysqli_real_escape_string($con, $_POST['office_phone']) : NULL;
    $extension = isset($_POST['extension']) ? mysqli_real_escape_string($con, $_POST['extension']) : NULL;
    $office_location = isset($_POST['office_location']) ? mysqli_real_escape_string($con, $_POST['office_location']) : NULL;
    $reports_to = isset($_POST['reports_to']) ? mysqli_real_escape_string($con, $_POST['reports_to']) : NULL;

    // Acceso
    $admin_level = isset($_POST['admin_level']) ? mysqli_real_escape_string($con, $_POST['admin_level']) : NULL;
    $clearance_level = isset($_POST['clearance_level']) ? mysqli_real_escape_string($con, $_POST['clearance_level']) : NULL;
    $can_access_production = isset($_POST['can_access_production']) ? 1 : 0;
    $can_modify_security = isset($_POST['can_modify_security']) ? 1 : 0;

    // Asignaciones
    $main_responsibilities = isset($_POST['main_responsibilities']) ? mysqli_real_escape_string($con, $_POST['main_responsibilities']) : NULL;
    $assigned_systems = isset($_POST['assigned_systems']) ? mysqli_real_escape_string($con, $_POST['assigned_systems']) : NULL;
    $current_projects = isset($_POST['current_projects']) ? mysqli_real_escape_string($con, $_POST['current_projects']) : NULL;

    try {
        // Actualizar tabla users
        $sql_users = "UPDATE users SET full_name = ? WHERE id = ?";
        $stmt = $con->prepare($sql_users);
        $stmt->bind_param("si", $full_name, $user_id);
        $users_updated = $stmt->execute();
        $stmt->close();

        // Actualizar tabla admins
        $sql_admins = "UPDATE admins SET
                        department = ?,
                        job_title = ?,
                        technical_area = ?,
                        certifications = ?,
                        specialization = ?,
                        years_of_experience = ?,
                        education_level = ?,
                        office_phone = ?,
                        extension = ?,
                        office_location = ?,
                        reports_to = ?,
                        admin_level = ?,
                        clearance_level = ?,
                        can_access_production = ?,
                        can_modify_security = ?,
                        main_responsibilities = ?,
                        assigned_systems = ?,
                        current_projects = ?
                        WHERE user_id = ?";

        $stmt = $con->prepare($sql_admins);
        $stmt->bind_param("sssssiissssssiisss",
            $department, $job_title, $technical_area, $certifications, $specialization,
            $years_of_experience, $education_level, $office_phone, $extension, $office_location,
            $reports_to, $admin_level, $clearance_level, $can_access_production, $can_modify_security,
            $main_responsibilities, $assigned_systems, $current_projects, $user_id
        );

        $admins_updated = $stmt->execute();
        $stmt->close();

        if($users_updated && $admins_updated) {
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
               a.user_id, a.employee_id, a.department, a.job_title, a.technical_area, a.certifications,
               a.specialization, a.years_of_experience, a.education_level,
               a.office_phone, a.extension, a.office_location, a.reports_to,
               a.admin_level, a.clearance_level, a.can_access_production, a.can_modify_security,
               a.main_responsibilities, a.assigned_systems, a.current_projects,
               a.total_incidents_resolved, a.average_resolution_time, a.performance_rating,
               a.last_performance_review, a.last_security_training, a.security_training_expiry,
               a.status as admin_status
        FROM users u
        LEFT JOIN admins a ON u.id = a.user_id
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
    <title>Admin | Mi Perfil</title>

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
        .stat-box {
            background: #f0f5ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .checkbox-group {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
            margin-bottom: 15px;
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
                                <h1 class="mainTitle">Administrador | Mi Perfil</h1>
                            </div>
                            <ol class="breadcrumb">
                                <li>
                                    <span>Administrador</span>
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
                                                <p><b>ID de Empleado: </b><?php echo htmlentities($user_data['employee_id']); ?></p>
                                                <p><b>Miembro desde: </b><?php echo htmlentities($user_data['created_at']); ?></p>
                                                <hr />

                                                <form role="form" name="editProfile" method="post">
                                                    <!-- TABS -->
                                                    <ul class="nav nav-tabs profile-tabs" role="tablist">
                                                        <li role="presentation" class="active">
                                                            <a href="#personal-info" aria-controls="personal-info" role="tab" data-toggle="tab">
                                                                <i class="fa fa-user"></i> Personal
                                                            </a>
                                                        </li>
                                                        <li role="presentation">
                                                            <a href="#professional-info" aria-controls="professional-info" role="tab" data-toggle="tab">
                                                                <i class="fa fa-briefcase"></i> Profesional
                                                            </a>
                                                        </li>
                                                        <li role="presentation">
                                                            <a href="#access-info" aria-controls="access-info" role="tab" data-toggle="tab">
                                                                <i class="fa fa-lock"></i> Acceso
                                                            </a>
                                                        </li>
                                                        <li role="presentation">
                                                            <a href="#assignment-info" aria-controls="assignment-info" role="tab" data-toggle="tab">
                                                                <i class="fa fa-tasks"></i> Asignaciones
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

                                                            <h4 class="form-section-title"><i class="fa fa-phone"></i> Contacto Laboral</h4>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="office_phone">Teléfono de Oficina</label>
                                                                    <input type="tel" name="office_phone" id="office_phone" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['office_phone']); ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="extension">Extensión</label>
                                                                    <input type="text" name="extension" id="extension" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['extension']); ?>"
                                                                           placeholder="200">
                                                                </div>
                                                            </div>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="office_location">Ubicación de Oficina</label>
                                                                    <input type="text" name="office_location" id="office_location" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['office_location']); ?>"
                                                                           placeholder="Piso 2, Ala B">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="reports_to">Reporta a</label>
                                                                    <input type="text" name="reports_to" id="reports_to" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['reports_to']); ?>"
                                                                           placeholder="Nombre del supervisor">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- TAB 2: INFORMACIÓN PROFESIONAL -->
                                                        <div role="tabpanel" class="tab-pane" id="professional-info">
                                                            <h4 class="form-section-title"><i class="fa fa-briefcase"></i> Información Profesional</h4>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="department">Departamento *</label>
                                                                    <select name="department" id="department" class="form-control" required>
                                                                        <option value="">-- Seleccionar --</option>
                                                                        <option value="operations" <?php if($user_data['department'] == 'operations') echo 'selected'; ?>>Operaciones</option>
                                                                        <option value="it" <?php if($user_data['department'] == 'it') echo 'selected'; ?>>IT / Sistemas</option>
                                                                        <option value="security" <?php if($user_data['department'] == 'security') echo 'selected'; ?>>Seguridad</option>
                                                                        <option value="finance" <?php if($user_data['department'] == 'finance') echo 'selected'; ?>>Finanzas</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="job_title">Título de Trabajo</label>
                                                                    <input type="text" name="job_title" id="job_title" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['job_title']); ?>"
                                                                           placeholder="Ej: Analista de Sistemas">
                                                                </div>
                                                            </div>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="technical_area">Área Técnica</label>
                                                                    <input type="text" name="technical_area" id="technical_area" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['technical_area']); ?>"
                                                                           placeholder="Ej: Seguridad de Sistemas, Base de Datos">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="years_of_experience">Años de Experiencia IT</label>
                                                                    <input type="number" name="years_of_experience" id="years_of_experience"
                                                                           class="form-control" min="0" max="70"
                                                                           value="<?php echo htmlentities($user_data['years_of_experience']); ?>">
                                                                </div>
                                                            </div>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="education_level">Nivel de Educación</label>
                                                                    <select name="education_level" id="education_level" class="form-control">
                                                                        <option value="">-- Seleccionar --</option>
                                                                        <option value="Bachelor" <?php if($user_data['education_level'] == 'Bachelor') echo 'selected'; ?>>Licenciatura</option>
                                                                        <option value="Master" <?php if($user_data['education_level'] == 'Master') echo 'selected'; ?>>Maestría</option>
                                                                        <option value="PhD" <?php if($user_data['education_level'] == 'PhD') echo 'selected'; ?>>Doctorado</option>
                                                                        <option value="Technical" <?php if($user_data['education_level'] == 'Technical') echo 'selected'; ?>>Técnico</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="specialization">Especialización</label>
                                                                    <input type="text" name="specialization" id="specialization" class="form-control"
                                                                           value="<?php echo htmlentities($user_data['specialization']); ?>"
                                                                           placeholder="Ej: Cloud Computing, DevOps">
                                                                </div>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="certifications">Certificaciones</label>
                                                                <textarea name="certifications" id="certifications" class="form-control" rows="3"
                                                                          placeholder="Listar certificaciones (separadas por comas)..."><?php echo htmlentities($user_data['certifications']); ?></textarea>
                                                            </div>
                                                        </div>

                                                        <!-- TAB 3: ACCESO Y PERMISOS -->
                                                        <div role="tabpanel" class="tab-pane" id="access-info">
                                                            <h4 class="form-section-title"><i class="fa fa-lock"></i> Configuración de Acceso</h4>

                                                            <div class="form-row">
                                                                <div class="form-group">
                                                                    <label for="admin_level">Nivel Administrativo *</label>
                                                                    <select name="admin_level" id="admin_level" class="form-control" required>
                                                                        <option value="">-- Seleccionar --</option>
                                                                        <option value="operational" <?php if($user_data['admin_level'] == 'operational') echo 'selected'; ?>>Operacional</option>
                                                                        <option value="supervisor" <?php if($user_data['admin_level'] == 'supervisor') echo 'selected'; ?>>Supervisor</option>
                                                                        <option value="manager" <?php if($user_data['admin_level'] == 'manager') echo 'selected'; ?>>Manager</option>
                                                                        <option value="director" <?php if($user_data['admin_level'] == 'director') echo 'selected'; ?>>Director</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="clearance_level">Nivel de Acceso</label>
                                                                    <select name="clearance_level" id="clearance_level" class="form-control">
                                                                        <option value="">-- Seleccionar --</option>
                                                                        <option value="level_1" <?php if($user_data['clearance_level'] == 'level_1') echo 'selected'; ?>>Nivel 1 (Limitado)</option>
                                                                        <option value="level_2" <?php if($user_data['clearance_level'] == 'level_2') echo 'selected'; ?>>Nivel 2 (Estándar)</option>
                                                                        <option value="level_3" <?php if($user_data['clearance_level'] == 'level_3') echo 'selected'; ?>>Nivel 3 (Avanzado)</option>
                                                                        <option value="level_4" <?php if($user_data['clearance_level'] == 'level_4') echo 'selected'; ?>>Nivel 4 (Administrador)</option>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <h4 class="form-section-title" style="margin-top: 30px;"><i class="fa fa-shield"></i> Permisos Especiales</h4>

                                                            <div class="checkbox-group">
                                                                <label>
                                                                    <input type="checkbox" name="can_access_production" value="1"
                                                                           <?php if($user_data['can_access_production'] == 1) echo 'checked'; ?>>
                                                                    Acceso a Producción
                                                                </label>
                                                                <small class="help-block">Permite acceder y modificar datos en ambiente de producción</small>
                                                            </div>

                                                            <div class="checkbox-group">
                                                                <label>
                                                                    <input type="checkbox" name="can_modify_security" value="1"
                                                                           <?php if($user_data['can_modify_security'] == 1) echo 'checked'; ?>>
                                                                    Puede Modificar Configuración de Seguridad
                                                                </label>
                                                                <small class="help-block">Permite cambiar políticas y configuraciones de seguridad</small>
                                                            </div>
                                                        </div>

                                                        <!-- TAB 4: ASIGNACIONES -->
                                                        <div role="tabpanel" class="tab-pane" id="assignment-info">
                                                            <h4 class="form-section-title"><i class="fa fa-tasks"></i> Asignaciones y Responsabilidades</h4>

                                                            <div class="form-group">
                                                                <label for="main_responsibilities">Responsabilidades Principales</label>
                                                                <textarea name="main_responsibilities" id="main_responsibilities" class="form-control" rows="4"
                                                                          placeholder="Describir las responsabilidades principales..."><?php echo htmlentities($user_data['main_responsibilities']); ?></textarea>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="assigned_systems">Sistemas Asignados</label>
                                                                <textarea name="assigned_systems" id="assigned_systems" class="form-control" rows="3"
                                                                          placeholder="Sistemas que administra (separados por comas)..."><?php echo htmlentities($user_data['assigned_systems']); ?></textarea>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="current_projects">Proyectos Actuales</label>
                                                                <textarea name="current_projects" id="current_projects" class="form-control" rows="3"
                                                                          placeholder="Proyectos en los que participa..."><?php echo htmlentities($user_data['current_projects']); ?></textarea>
                                                            </div>
                                                        </div>

                                                        <!-- TAB 5: ESTADÍSTICAS -->
                                                        <div role="tabpanel" class="tab-pane" id="statistics-info">
                                                            <h4 class="form-section-title"><i class="fa fa-bar-chart"></i> Estadísticas y Desempeño</h4>

                                                            <div class="stat-box">
                                                                <p>
                                                                    <strong>Incidentes Resueltos:</strong>
                                                                    <span class="label label-info"><?php echo htmlentities($user_data['total_incidents_resolved'] ?? 0); ?></span>
                                                                </p>
                                                            </div>

                                                            <div class="stat-box">
                                                                <p>
                                                                    <strong>Tiempo Promedio de Resolución:</strong>
                                                                    <span class="label label-warning"><?php echo htmlentities($user_data['average_resolution_time'] ?? '0'); ?> hrs</span>
                                                                </p>
                                                            </div>

                                                            <div class="stat-box">
                                                                <p>
                                                                    <strong>Calificación de Desempeño:</strong>
                                                                    <span class="label label-success">
                                                                        <?php echo htmlentities($user_data['performance_rating'] ?? '0.0'); ?> / 10.0
                                                                    </span>
                                                                </p>
                                                            </div>

                                                            <div class="stat-box">
                                                                <p>
                                                                    <strong>Última Revisión de Desempeño:</strong>
                                                                    <span><?php echo htmlentities($user_data['last_performance_review'] ?? 'No disponible'); ?></span>
                                                                </p>
                                                            </div>

                                                            <div class="stat-box">
                                                                <p>
                                                                    <strong>Última Capacitación de Seguridad:</strong>
                                                                    <span><?php echo htmlentities($user_data['last_security_training'] ?? 'No disponible'); ?></span>
                                                                </p>
                                                            </div>

                                                            <div class="stat-box">
                                                                <p>
                                                                    <strong>Vencimiento de Capacitación:</strong>
                                                                    <span><?php echo htmlentities($user_data['security_training_expiry'] ?? 'No disponible'); ?></span>
                                                                </p>
                                                            </div>

                                                            <div class="alert alert-info">
                                                                <p><strong>Nota:</strong> Las estadísticas se actualizan automáticamente según la actividad registrada.</p>
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
