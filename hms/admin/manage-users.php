<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once('include/config.php');
include_once('include/checklogin.php');
include_once('../include/permission-check.php');
include_once('../include/UserManagement.php');
include_once('../include/rbac-functions.php');

check_login();

// ✅ PROTECCIÓN RBAC - Verificar permiso para gestionar usuarios
requirePermission('view_users');

// Inicializar UserManagement
$userManager = new UserManagement($con, $_SESSION['id']);
$rbac = new RBAC($con);

// Variables para mensajes
$success_msg = '';
$error_msg = '';

// ============================================================================
// MANEJO DE ACCIONES (CRUD)
// ============================================================================

// CREAR USUARIO
if (isset($_POST['action']) && $_POST['action'] == 'create' && hasPermission('create_user')) {
    $data = [
        'full_name' => $_POST['full_name'],
        'email' => $_POST['email'],
        'password' => $_POST['password'],
        'user_type' => $_POST['user_type'],
        'status' => $_POST['status'] ?? 'active'
    ];

    // Validar formato de correo corporativo
    if (!validateCorporateEmail($data['email'])) {
        $error_msg = "El correo no cumple con el formato corporativo requerido";
    }
    // Validar políticas de contraseña
    elseif (!empty($data['password'])) {
        $password_validation = validatePasswordAgainstPolicies($data['password']);
        if (!$password_validation['valid']) {
            $error_msg = "Contraseña no válida: " . implode(', ', $password_validation['errors']);
        }
    }

    // Validar datos básicos
    if (empty($error_msg)) {
        $validation = $userManager->validateUserData($data, 'create');
        if (!$validation['valid']) {
            $error_msg = $validation['message'];
        }
    }

    if (empty($error_msg)) {
        $result = $userManager->createUser($data, 'Usuario creado desde panel de administración');
        if ($result['success']) {
            $new_user_id = $result['user_id'];

            // ✅ CREAR REGISTRO ESPECÍFICO SEGÚN TIPO DE USUARIO
            try {
                switch ($data['user_type']) {
                    case 'doctor':
                        // Obtener datos específicos del doctor del formulario
                        $spec_id = !empty($_POST['doctor_specialization']) ? intval($_POST['doctor_specialization']) : NULL;
                        $consultation_fee = !empty($_POST['doctor_consultation_fee']) ? floatval($_POST['doctor_consultation_fee']) : 0.00;

                        // Crear registro en tabla doctors con datos del formulario
                        $stmt = $con->prepare("
                            INSERT INTO doctors (user_id, specialization_id, consultation_fee, status)
                            VALUES (?, ?, ?, 'active')
                        ");
                        $stmt->bind_param("idi", $new_user_id, $spec_id, $consultation_fee);
                        if (!$stmt->execute()) {
                            error_log("Error al crear doctor: " . $stmt->error);
                        }
                        $stmt->close();
                        break;

                    case 'patient':
                        // Obtener datos específicos del paciente del formulario
                        $dob = !empty($_POST['patient_date_of_birth']) ? $_POST['patient_date_of_birth'] : NULL;
                        $gender = !empty($_POST['patient_gender']) ? $_POST['patient_gender'] : NULL;

                        // Crear registro en tabla patients con datos del formulario
                        $stmt = $con->prepare("
                            INSERT INTO patients (user_id, date_of_birth, gender, status)
                            VALUES (?, ?, ?, 'active')
                        ");
                        $stmt->bind_param("iss", $new_user_id, $dob, $gender);
                        if (!$stmt->execute()) {
                            error_log("Error al crear patient: " . $stmt->error);
                        }
                        $stmt->close();
                        break;

                    case 'admin':
                        // Obtener datos específicos del admin del formulario
                        $department = !empty($_POST['admin_department']) ? $_POST['admin_department'] : 'operations';
                        $admin_level = !empty($_POST['admin_level']) ? $_POST['admin_level'] : 'operational';
                        $employee_id = 'EMP' . str_pad($new_user_id, 5, '0', STR_PAD_LEFT);

                        // Crear registro en tabla admins con datos del formulario
                        $stmt = $con->prepare("
                            INSERT INTO admins (user_id, employee_id, department, admin_level, status)
                            VALUES (?, ?, ?, ?, 'active')
                        ");
                        $stmt->bind_param("isss", $new_user_id, $employee_id, $department, $admin_level);
                        if (!$stmt->execute()) {
                            error_log("Error al crear admin: " . $stmt->error);
                        }
                        $stmt->close();
                        break;
                }
            } catch (Exception $e) {
                error_log("Error al crear registro específico: " . $e->getMessage());
                // No detenemos el proceso, el usuario ya fue creado
            }

            // Asignar roles si se seleccionaron (usando RBAC que funciona correctamente)
            if (!empty($_POST['roles'])) {
                $roles_assigned = 0;
                foreach ($_POST['roles'] as $role_id) {
                    $role_result = $rbac->assignRoleToUser($result['user_id'], intval($role_id), $_SESSION['id']);
                    if ($role_result['success']) {
                        $roles_assigned++;
                    }
                }
                if ($roles_assigned > 0) {
                    $success_msg = $result['message'] . " y se asignaron $roles_assigned rol(es)";
                } else {
                    $success_msg = $result['message'] . ' (sin roles asignados)';
                }
            } else {
                $success_msg = $result['message'];
            }
        } else {
            $error_msg = $result['message'];
        }
    }
}

// ACTUALIZAR USUARIO
if (isset($_POST['action']) && $_POST['action'] == 'update' && hasPermission('edit_user')) {
    $user_id = $_POST['user_id'];
    $data = [
        'full_name' => $_POST['full_name'],
        'email' => $_POST['email'],
        'status' => $_POST['status']
    ];

    // Validar formato de correo corporativo
    if (!validateCorporateEmail($data['email'])) {
        $error_msg = "El correo no cumple con el formato corporativo requerido";
    }

    // Determinar si hay cambios reales
    $has_data_changes = false;
    $current_user_data = $userManager->getUserById($user_id);

    if ($current_user_data) {
        if ($current_user_data['full_name'] != $data['full_name'] ||
            $current_user_data['email'] != $data['email'] ||
            $current_user_data['status'] != $data['status']) {
            $has_data_changes = true;
        }
    }

    // Actualizar datos básicos solo si hay cambios y no hay errores
    if ($has_data_changes && empty($error_msg)) {
        $result = $userManager->updateUser($user_id, $data, 'Usuario actualizado desde panel de administración');
        if (!$result['success']) {
            $error_msg = $result['message'];
        }
    }

    // Actualizar roles si se enviaron (usando RBAC que funciona correctamente)
    $roles_updated = false;
    if (isset($_POST['roles'])) {
        $new_role_ids = !empty($_POST['roles']) ? array_map('intval', $_POST['roles']) : [];
        $current_roles = $userManager->getUserRoles($user_id);
        $current_role_ids = !empty($current_roles) ? array_map('intval', array_column($current_roles, 'id')) : [];

        // ✅ PROTECCIÓN AUTO-MODIFICACIÓN: Un admin no puede quitarse su propio rol de admin
        if ($user_id == $_SESSION['id']) {
            // Verificar si se está intentando quitar el rol de administrador
            $admin_role_query = "SELECT id FROM roles WHERE role_name IN ('admin', 'super_admin') AND status = 'active'";
            $admin_role_result = mysqli_query($con, $admin_role_query);
            $admin_role_ids = [];
            while ($row = mysqli_fetch_assoc($admin_role_result)) {
                $admin_role_ids[] = intval($row['id']);
            }
            
            // Verificar si el usuario actual tiene rol de admin
            $user_has_admin = !empty(array_intersect($current_role_ids, $admin_role_ids));
            $new_has_admin = !empty(array_intersect($new_role_ids, $admin_role_ids));
            
            if ($user_has_admin && !$new_has_admin) {
                $error_msg = 'No puedes quitar tu propio rol de administrador. Contacta a otro administrador.';
            } else {
                // Proceder normalmente si no se está quitando el rol de admin
                $roles_to_add = array_diff($new_role_ids, $current_role_ids);
                $roles_to_remove = array_diff($current_role_ids, $new_role_ids);

                // Aplicar cambios de roles
                if (!empty($roles_to_remove)) {
                    foreach ($roles_to_remove as $role_id) {
                        $rbac->revokeRoleFromUser($user_id, $role_id, $_SESSION['id']);
                    }
                    $roles_updated = true;
                }
                if (!empty($roles_to_add)) {
                    foreach ($roles_to_add as $role_id) {
                        $rbac->assignRoleToUser($user_id, $role_id, $_SESSION['id']);
                    }
                    $roles_updated = true;
                }
            }
        } else {
            // Para otros usuarios, proceder normalmente
            $roles_to_add = array_diff($new_role_ids, $current_role_ids);
            $roles_to_remove = array_diff($current_role_ids, $new_role_ids);

            // Aplicar cambios de roles
            if (!empty($roles_to_remove)) {
                foreach ($roles_to_remove as $role_id) {
                    $rbac->revokeRoleFromUser($user_id, $role_id, $_SESSION['id']);
                }
                $roles_updated = true;
            }
            if (!empty($roles_to_add)) {
                foreach ($roles_to_add as $role_id) {
                    $rbac->assignRoleToUser($user_id, $role_id, $_SESSION['id']);
                }
                $roles_updated = true;
            }
        }
    }

    // ✅ ACTUALIZAR DATOS ESPECÍFICOS SEGÚN TIPO DE USUARIO
    $specific_data_updated = false;
    $current_user = $userManager->getUserById($user_id);

    if ($current_user) {
        try {
            switch ($current_user['user_type']) {
                case 'doctor':
                    // Actualizar datos específicos del doctor
                    $update_fields = [];
                    $update_params = [];
                    $update_types = '';

                    if (!empty($_POST['doctor_specialization'])) {
                        $update_fields[] = "specialization_id = ?";
                        $update_params[] = intval($_POST['doctor_specialization']);
                        $update_types .= 'i';
                    }
                    if (!empty($_POST['doctor_consultation_fee'])) {
                        $update_fields[] = "consultation_fee = ?";
                        $update_params[] = floatval($_POST['doctor_consultation_fee']);
                        $update_types .= 'd';
                    }
                    if (!empty($_POST['doctor_years_of_experience'])) {
                        $update_fields[] = "years_of_experience = ?";
                        $update_params[] = intval($_POST['doctor_years_of_experience']);
                        $update_types .= 'i';
                    }
                    if (!empty($_POST['doctor_bio'])) {
                        $update_fields[] = "bio = ?";
                        $update_params[] = $_POST['doctor_bio'];
                        $update_types .= 's';
                    }

                    if (!empty($update_fields)) {
                        $update_params[] = $user_id;
                        $update_types .= 'i';
                        $sql = "UPDATE doctors SET " . implode(", ", $update_fields) . " WHERE user_id = ?";
                        $stmt = $con->prepare($sql);
                        $stmt->bind_param($update_types, ...$update_params);
                        if ($stmt->execute()) {
                            $specific_data_updated = true;
                        }
                        $stmt->close();
                    }
                    break;

                case 'patient':
                    // Actualizar datos específicos del paciente
                    $update_fields = [];
                    $update_params = [];
                    $update_types = '';

                    if (isset($_POST['patient_date_of_birth'])) {
                        $update_fields[] = "date_of_birth = ?";
                        $update_params[] = !empty($_POST['patient_date_of_birth']) ? $_POST['patient_date_of_birth'] : NULL;
                        $update_types .= 's';
                    }
                    if (isset($_POST['patient_gender'])) {
                        $update_fields[] = "gender = ?";
                        $update_params[] = !empty($_POST['patient_gender']) ? $_POST['patient_gender'] : NULL;
                        $update_types .= 's';
                    }
                    if (isset($_POST['patient_blood_type'])) {
                        $update_fields[] = "blood_type = ?";
                        $update_params[] = !empty($_POST['patient_blood_type']) ? $_POST['patient_blood_type'] : NULL;
                        $update_types .= 's';
                    }
                    if (isset($_POST['patient_address'])) {
                        $update_fields[] = "address = ?";
                        $update_params[] = !empty($_POST['patient_address']) ? $_POST['patient_address'] : NULL;
                        $update_types .= 's';
                    }
                    if (isset($_POST['patient_city'])) {
                        $update_fields[] = "city = ?";
                        $update_params[] = !empty($_POST['patient_city']) ? $_POST['patient_city'] : NULL;
                        $update_types .= 's';
                    }
                    if (isset($_POST['patient_phone'])) {
                        $update_fields[] = "phone = ?";
                        $update_params[] = !empty($_POST['patient_phone']) ? $_POST['patient_phone'] : NULL;
                        $update_types .= 's';
                    }
                    if (isset($_POST['patient_allergies'])) {
                        $update_fields[] = "allergies = ?";
                        $update_params[] = !empty($_POST['patient_allergies']) ? $_POST['patient_allergies'] : NULL;
                        $update_types .= 's';
                    }
                    if (isset($_POST['patient_chronic_conditions'])) {
                        $update_fields[] = "chronic_conditions = ?";
                        $update_params[] = !empty($_POST['patient_chronic_conditions']) ? $_POST['patient_chronic_conditions'] : NULL;
                        $update_types .= 's';
                    }

                    if (!empty($update_fields)) {
                        $update_params[] = $user_id;
                        $update_types .= 'i';
                        $sql = "UPDATE patients SET " . implode(", ", $update_fields) . " WHERE user_id = ?";
                        $stmt = $con->prepare($sql);
                        $stmt->bind_param($update_types, ...$update_params);
                        if ($stmt->execute()) {
                            $specific_data_updated = true;
                        }
                        $stmt->close();
                    }
                    break;

                case 'admin':
                    // Actualizar datos específicos del admin
                    $update_fields = [];
                    $update_params = [];
                    $update_types = '';

                    if (!empty($_POST['admin_department'])) {
                        $update_fields[] = "department = ?";
                        $update_params[] = $_POST['admin_department'];
                        $update_types .= 's';
                    }
                    if (!empty($_POST['admin_level'])) {
                        $update_fields[] = "admin_level = ?";
                        $update_params[] = $_POST['admin_level'];
                        $update_types .= 's';
                    }
                    if (!empty($_POST['admin_technical_area'])) {
                        $update_fields[] = "technical_area = ?";
                        $update_params[] = $_POST['admin_technical_area'];
                        $update_types .= 's';
                    }
                    if (!empty($_POST['admin_certifications'])) {
                        $update_fields[] = "certifications = ?";
                        $update_params[] = $_POST['admin_certifications'];
                        $update_types .= 's';
                    }
                    if (!empty($_POST['admin_years_experience'])) {
                        $update_fields[] = "years_of_experience = ?";
                        $update_params[] = intval($_POST['admin_years_experience']);
                        $update_types .= 'i';
                    }
                    if (!empty($_POST['admin_clearance_level'])) {
                        $update_fields[] = "clearance_level = ?";
                        $update_params[] = $_POST['admin_clearance_level'];
                        $update_types .= 's';
                    }
                    if (isset($_POST['admin_can_access_production'])) {
                        $update_fields[] = "can_access_production = ?";
                        $update_params[] = isset($_POST['admin_can_access_production']) ? 1 : 0;
                        $update_types .= 'i';
                    }
                    if (isset($_POST['admin_can_modify_security'])) {
                        $update_fields[] = "can_modify_security = ?";
                        $update_params[] = isset($_POST['admin_can_modify_security']) ? 1 : 0;
                        $update_types .= 'i';
                    }

                    if (!empty($update_fields)) {
                        $update_params[] = $user_id;
                        $update_types .= 'i';
                        $sql = "UPDATE admins SET " . implode(", ", $update_fields) . " WHERE user_id = ?";
                        $stmt = $con->prepare($sql);
                        $stmt->bind_param($update_types, ...$update_params);
                        if ($stmt->execute()) {
                            $specific_data_updated = true;
                        }
                        $stmt->close();
                    }
                    break;
            }
        } catch (Exception $e) {
            error_log("Error al actualizar datos específicos: " . $e->getMessage());
        }
    }

    // Mensaje final
    if ($has_data_changes && $roles_updated && $specific_data_updated) {
        $success_msg = 'Usuario, roles e información específica actualizados exitosamente';
    } elseif ($has_data_changes && $roles_updated) {
        $success_msg = 'Usuario y roles actualizados exitosamente';
    } elseif ($has_data_changes && $specific_data_updated) {
        $success_msg = 'Usuario e información específica actualizados exitosamente';
    } elseif ($roles_updated && $specific_data_updated) {
        $success_msg = 'Roles e información específica actualizados exitosamente';
    } elseif ($has_data_changes) {
        $success_msg = 'Usuario actualizado exitosamente';
    } elseif ($roles_updated) {
        $success_msg = 'Roles actualizados exitosamente';
    } elseif ($specific_data_updated) {
        $success_msg = 'Información específica actualizada exitosamente';
    } else {
        $error_msg = 'No se realizaron cambios';
    }
}

// ELIMINAR USUARIO (SOFT DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && hasPermission('delete_user')) {
    $user_id = $_GET['id'];
    
    // ✅ PROTECCIÓN AUTO-ELIMINACIÓN: Un admin no puede eliminarse a sí mismo
    if ($user_id == $_SESSION['id']) {
        $error_msg = 'No puedes eliminar tu propia cuenta. Contacta a otro administrador.';
    } else {
        $result = $userManager->deleteUser($user_id, 'Usuario eliminado desde panel de administración');
        if ($result['success']) {
            $success_msg = $result['message'];
            
            // ✅ CERRAR SESIÓN DEL USUARIO ELIMINADO (si está activo)
            $session_file = session_save_path() . '/sess_' . session_id();
            if (file_exists($session_file)) {
                // Buscar y eliminar sesiones del usuario eliminado
                $sessions_dir = session_save_path();
                if ($handle = opendir($sessions_dir)) {
                    while (false !== ($file = readdir($handle))) {
                        if (strpos($file, 'sess_') === 0) {
                            $session_data = @file_get_contents($sessions_dir . '/' . $file);
                            if ($session_data && strpos($session_data, 'id|i:' . $user_id . ';') !== false) {
                                @unlink($sessions_dir . '/' . $file);
                            }
                        }
                    }
                    closedir($handle);
                }
            }
        } else {
            $error_msg = $result['message'];
        }
    }
}

// ============================================================================
// OBTENER DATOS PARA LA VISTA
// ============================================================================

// Búsqueda y filtros
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_type = $_GET['type'] ?? '';

// Parámetros de ordenamiento
$sort_by = $_GET['sort_by'] ?? 'full_name';
$sort_order = $_GET['sort_order'] ?? 'ASC';

// Validar columnas permitidas para ordenar
$allowed_sort_columns = ['full_name', 'email', 'user_type', 'status', 'last_login'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'full_name';
}

// Validar dirección de ordenamiento
$sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

// Obtener usuarios con filtros
if (!empty($search) || !empty($filter_status) || !empty($filter_type)) {
    // Construir filtros solo con valores no vacíos
    $filters = ['limit' => 100, 'sort_by' => $sort_by, 'sort_order' => $sort_order];
    if (!empty($filter_status)) {
        $filters['status'] = $filter_status;
    }
    if (!empty($filter_type)) {
        $filters['user_type'] = $filter_type;
    }
    $users = $userManager->searchUsers($search, $filters);
} else {
    // Si no hay filtros, obtener todos los usuarios
    $users = $userManager->getAllUsers(100, $sort_by, $sort_order);
}

// Obtener estadísticas
$stats = $userManager->getStatistics();

// Obtener todos los roles disponibles para asignación
$all_roles = $rbac->getAllRoles();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Admin | Gestionar Usuarios</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="http://fonts.googleapis.com/css?family=Lato:300,400,400italic,600,700|Raleway:300,400,500,600,700|Crete+Round:400italic" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="vendor/themify-icons/themify-icons.min.css">
    <link href="vendor/animate.css/animate.min.css" rel="stylesheet" media="screen">
    <link href="vendor/perfect-scrollbar/perfect-scrollbar.min.css" rel="stylesheet" media="screen">
    <link href="vendor/switchery/switchery.min.css" rel="stylesheet" media="screen">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/plugins.css">
    <link rel="stylesheet" href="assets/css/themes/theme-1.css" id="skin_color" />
    <link rel="stylesheet" href="assets/css/modals-improved.css">

    <!-- SweetAlert2 para confirmaciones -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        .stats-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .stats-card .stat-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        .stats-card .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stats-card .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        .badge-status {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-active {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
        }
        .badge-inactive {
            background: linear-gradient(135deg, #9E9E9E, #757575);
            color: white;
            box-shadow: 0 2px 4px rgba(158, 158, 158, 0.3);
            opacity: 0.8;
        }
        .badge-blocked {
            background: linear-gradient(135deg, #F44336, #d32f2f);
            color: white;
            box-shadow: 0 2px 4px rgba(244, 67, 54, 0.3);
        }
        
        /* ✅ Efectos visuales para usuarios inactivos */
        .user-row-inactive {
            opacity: 0.6;
            background-color: #f9f9f9 !important;
        }
        .user-row-inactive td {
            color: #666 !important;
        }
        .user-row-inactive .user-name {
            text-decoration: line-through;
            color: #999 !important;
        }
        
        /* Iconos en badges */
        .badge-status::before {
            font-family: 'FontAwesome';
            margin-right: 3px;
        }
        .badge-active::before {
            content: '\f00c'; /* check */
        }
        .badge-inactive::before {
            content: '\f00d'; /* times */
        }
        .badge-blocked::before {
            content: '\f023'; /* lock */
        }
        .modal-header .close {
            color: white;
            opacity: 0.8;
        }
        .required::after {
            content: " *";
            color: red;
        }
        .sortable-header {
            cursor: pointer;
            user-select: none;
            position: relative;
            padding-right: 20px;
        }
        .sortable-header:hover {
            background-color: #f0f0f0;
        }
        .sort-icon {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.3;
            font-size: 12px;
        }
        .sortable-header.active .sort-icon {
            opacity: 1;
            color: #00a8b3;
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
                    <!-- Page Title -->
                    <section id="page-title">
                        <div class="row">
                            <div class="col-sm-8">
                                <h1 class="mainTitle">
                                    <i class="fa fa-users"></i> Gestión de Usuarios
                                </h1>
                            </div>
                            <ol class="breadcrumb">
                                <li><span>Admin</span></li>
                                <li class="active"><span>Gestionar Usuarios</span></li>
                            </ol>
                        </div>
                    </section>

                    <!-- Mensajes de éxito/error -->
                    <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <strong><i class="fa fa-check-circle"></i> Éxito!</strong> <?php echo htmlspecialchars($success_msg); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <strong><i class="fa fa-exclamation-circle"></i> Error!</strong> <?php echo htmlspecialchars($error_msg); ?>
                    </div>
                    <?php endif; ?>

                    <!-- ============================================ -->
                    <!-- TABS DE NAVEGACIÓN -->
                    <!-- ============================================ -->
                    <?php
                    // Verificar permiso para ver logs de actividad
                    $canViewActivityLogs = hasPermission('view_user_activity');
                    // Obtener tab activo (por defecto: listado)
                    $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'listado';
                    ?>

                    <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 20px;">
                        <li role="presentation" class="<?php echo ($activeTab == 'listado' || !isset($_GET['tab'])) ? 'active' : ''; ?>">
                            <a href="?tab=listado">
                                <i class="fa fa-users"></i> Listado de Usuarios
                            </a>
                        </li>
                        <?php if ($canViewActivityLogs): ?>
                        <li role="presentation" class="<?php echo $activeTab == 'logs' ? 'active' : ''; ?>">
                            <a href="?tab=logs">
                                <i class="fa fa-list-alt"></i> Logs de Actividad
                            </a>
                        </li>
                        <?php endif; ?>
                        <li role="presentation" class="<?php echo $activeTab == 'stats' ? 'active' : ''; ?>">
                            <a href="?tab=stats">
                                <i class="fa fa-bar-chart"></i> Estadísticas
                            </a>
                        </li>
                    </ul>

                    <!-- ============================================ -->
                    <!-- TAB 1: LISTADO DE USUARIOS -->
                    <!-- ============================================ -->
                    <?php if ($activeTab == 'listado' || !isset($_GET['tab'])): ?>

                    <!-- ============================================ -->
                    <!-- TARJETAS DE ESTADÍSTICAS -->
                    <!-- ============================================ -->
                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card text-center" style="border-left: 4px solid #00a8b3;">
                                <div class="stat-icon" style="color: #00a8b3;">
                                    <i class="fa fa-users"></i>
                                </div>
                                <div class="stat-value" style="color: #00a8b3;">
                                    <?php echo $stats['total_users'] ?? 0; ?>
                                </div>
                                <div class="stat-label">Total Usuarios</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card text-center" style="border-left: 4px solid #4CAF50;">
                                <div class="stat-icon" style="color: #4CAF50;">
                                    <i class="fa fa-check-circle"></i>
                                </div>
                                <div class="stat-value" style="color: #4CAF50;">
                                    <?php echo $stats['active_users'] ?? 0; ?>
                                </div>
                                <div class="stat-label">Activos</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card text-center" style="border-left: 4px solid #9E9E9E;">
                                <div class="stat-icon" style="color: #9E9E9E;">
                                    <i class="fa fa-user-times"></i>
                                </div>
                                <div class="stat-value" style="color: #9E9E9E;">
                                    <?php echo $stats['inactive_users'] ?? 0; ?>
                                </div>
                                <div class="stat-label">Inactivos</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-card text-center" style="border-left: 4px solid #F44336;">
                                <div class="stat-icon" style="color: #F44336;">
                                    <i class="fa fa-ban"></i>
                                </div>
                                <div class="stat-value" style="color: #F44336;">
                                    <?php echo $stats['blocked_users'] ?? 0; ?>
                                </div>
                                <div class="stat-label">Bloqueados</div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- BARRA DE BÚSQUEDA Y ACCIONES -->
                    <!-- ============================================ -->
                    <div class="container-fluid container-fullw bg-white">
                        <div class="row search-box">
                            <div class="col-md-4">
                                <div class="input-group" style="width: 100%;">
                                    <input type="text"
                                           name="search"
                                           id="searchInput"
                                           class="form-control"
                                           placeholder="Buscar por nombre o email..."
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <span class="input-group-btn">
                                        <button class="btn btn-primary" type="button" onclick="applyFilter()">
                                            <i class="fa fa-search"></i> Buscar
                                        </button>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="filterStatus" onchange="applyFilter()">
                                    <option value="">-- Todos los estados --</option>
                                    <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Activos</option>
                                    <option value="inactive" <?php echo $filter_status == 'inactive' ? 'selected' : ''; ?>>Inactivos</option>
                                    <option value="blocked" <?php echo $filter_status == 'blocked' ? 'selected' : ''; ?>>Bloqueados</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="filterType" onchange="applyFilter()">
                                    <option value="">-- Todos los tipos --</option>
                                    <option value="patient" <?php echo $filter_type == 'patient' ? 'selected' : ''; ?>>Pacientes</option>
                                    <option value="doctor" <?php echo $filter_type == 'doctor' ? 'selected' : ''; ?>>Doctores</option>
                                    <option value="admin" <?php echo $filter_type == 'admin' ? 'selected' : ''; ?>>Administradores</option>
                                </select>
                            </div>
                            <div class="col-md-2 text-right">
                                <?php if (hasPermission('create_user')): ?>
                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#createUserModal">
                                    <i class="fa fa-plus"></i> Nuevo Usuario
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- ============================================ -->
                        <!-- TABLA DE USUARIOS -->
                        <!-- ============================================ -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped" id="usersTable">
                                        <thead>
                                            <tr>
                                                <th width="5%">#</th>
                                                <th width="20%" class="sortable-header <?php echo ($sort_by === 'full_name') ? 'active' : ''; ?>" onclick="sortTable('full_name')">
                                                    Nombre Completo
                                                    <i class="fa <?php echo ($sort_by === 'full_name' && $sort_order === 'ASC') ? 'fa-sort-asc' : 'fa-sort-desc'; ?> sort-icon"></i>
                                                </th>
                                                <th width="20%" class="sortable-header <?php echo ($sort_by === 'email') ? 'active' : ''; ?>" onclick="sortTable('email')">
                                                    Email
                                                    <i class="fa <?php echo ($sort_by === 'email' && $sort_order === 'ASC') ? 'fa-sort-asc' : 'fa-sort-desc'; ?> sort-icon"></i>
                                                </th>
                                                <th width="10%" class="sortable-header <?php echo ($sort_by === 'user_type') ? 'active' : ''; ?>" onclick="sortTable('user_type')">
                                                    Tipo
                                                    <i class="fa <?php echo ($sort_by === 'user_type' && $sort_order === 'ASC') ? 'fa-sort-asc' : 'fa-sort-desc'; ?> sort-icon"></i>
                                                </th>
                                                <th width="15%">Roles</th>
                                                <th width="10%" class="sortable-header <?php echo ($sort_by === 'status') ? 'active' : ''; ?>" onclick="sortTable('status')">
                                                    Estado
                                                    <i class="fa <?php echo ($sort_by === 'status' && $sort_order === 'ASC') ? 'fa-sort-asc' : 'fa-sort-desc'; ?> sort-icon"></i>
                                                </th>
                                                <th width="10%" class="sortable-header <?php echo ($sort_by === 'last_login') ? 'active' : ''; ?>" onclick="sortTable('last_login')">
                                                    Último Login
                                                    <i class="fa <?php echo ($sort_by === 'last_login' && $sort_order === 'ASC') ? 'fa-sort-asc' : 'fa-sort-desc'; ?> sort-icon"></i>
                                                </th>
                                                <th width="10%">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($users)): ?>
                                                <?php $cnt = 1; foreach ($users as $user): ?>
                                                <tr class="<?php echo ($user['status'] === 'inactive') ? 'user-row-inactive' : ''; ?>">
                                                    <td><?php echo $cnt++; ?></td>
                                                    <td><strong class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <?php
                                                        $type_badges = [
                                                            'patient' => '<span class="label label-info">Paciente</span>',
                                                            'doctor' => '<span class="label label-primary">Doctor</span>',
                                                            'admin' => '<span class="label label-warning">Admin</span>'
                                                        ];
                                                        echo $type_badges[$user['user_type']] ?? $user['user_type'];
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <small><?php echo htmlspecialchars($user['roles'] ?? 'Sin roles'); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status_class = [
                                                            'active' => 'badge-active',
                                                            'inactive' => 'badge-inactive',
                                                            'blocked' => 'badge-blocked'
                                                        ];
                                                        $status_text = [
                                                            'active' => 'Activo',
                                                            'inactive' => 'Inactivo',
                                                            'blocked' => 'Bloqueado'
                                                        ];
                                                        $badge = $status_class[$user['status']] ?? 'badge-inactive';
                                                        $text = $status_text[$user['status']] ?? $user['status'];
                                                        ?>
                                                        <span class="badge-status <?php echo $badge; ?>">
                                                            <?php echo $text; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            <?php
                                                            if (!empty($user['last_login'])) {
                                                                echo date('d/m/Y H:i', strtotime($user['last_login']));
                                                            } else {
                                                                echo '<em>Nunca</em>';
                                                            }
                                                            ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <?php if (hasPermission('edit_user')): ?>
                                                            <button type="button"
                                                                    class="btn btn-primary btn-xs"
                                                                    onclick="editUser(<?php echo $user['id']; ?>)"
                                                                    title="Editar">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <?php else: ?>
                                                            <button type="button" class="btn btn-default btn-xs" disabled title="Sin permiso">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <?php endif; ?>

                                                            <?php if (hasPermission('delete_user')): ?>
                                                            <button type="button"
                                                                    class="btn btn-danger btn-xs"
                                                                    onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')"
                                                                    title="Eliminar">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                            <?php else: ?>
                                                            <button type="button" class="btn btn-default btn-xs" disabled title="Sin permiso">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">
                                                        <em>No se encontraron usuarios</em>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; // Fin del tab listado ?>

        <!-- ============================================ -->
        <!-- TAB 2: LOGS DE ACTIVIDAD -->
        <!-- ============================================ -->
        <?php if ($activeTab == 'logs' && $canViewActivityLogs): ?>
        <div class="container-fluid container-fullw bg-white">
            <div class="row">
                <div class="col-md-12">
                    <h4><i class="fa fa-list-alt"></i> Logs de Actividad de Usuarios</h4>
                    <p class="text-muted">Historial de sesiones de usuarios, doctores y pacientes del sistema</p>
                    <hr>

                    <?php
                    // Obtener logs de sesiones de usuarios y doctores
                    $logs_sql = "SELECT ul.*, u.full_name, u.email, u.user_type
                                FROM userlog ul
                                LEFT JOIN users u ON ul.uid = u.id
                                ORDER BY ul.loginTime DESC
                                LIMIT 100";
                    $logs_result = mysqli_query($con, $logs_sql);
                    ?>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="logsTable">
                            <thead>
                                <tr style="background: #00a8b3; color: white;">
                                    <th>#</th>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Tipo</th>
                                    <th>IP</th>
                                    <th>Fecha de Ingreso</th>
                                    <th>Fecha de Salida</th>
                                    <th>Duración</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $cnt = 1;
                                while ($log = mysqli_fetch_assoc($logs_result)):
                                    // Calcular duración de sesión
                                    $login = strtotime($log['loginTime']);
                                    $logout = $log['logout'] ? strtotime($log['logout']) : null;
                                    $duration = $logout ? ($logout - $login) : null;

                                    // Formato de duración
                                    if ($duration) {
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        $duration_text = sprintf("%dh %dm", $hours, $minutes);
                                    } else {
                                        $duration_text = '<span class="label label-success">Activo</span>';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $cnt++; ?></td>
                                    <td><?php echo htmlspecialchars($log['full_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($log['email'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        $type = $log['user_type'] ?? 'usuario';
                                        $badge_class = 'default';
                                        if ($type == 'admin') $badge_class = 'danger';
                                        elseif ($type == 'doctor') $badge_class = 'primary';
                                        elseif ($type == 'patient') $badge_class = 'info';
                                        echo '<span class="label label-' . $badge_class . '">' . ucfirst($type) . '</span>';
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['userip']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($log['loginTime'])); ?></td>
                                    <td>
                                        <?php
                                        if ($log['logout']) {
                                            echo date('d/m/Y H:i', strtotime($log['logout']));
                                        } else {
                                            echo '<span class="label label-success">En sesión</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $duration_text; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; // Fin del tab logs ?>

        <!-- ============================================ -->
        <!-- TAB 3: ESTADÍSTICAS -->
        <!-- ============================================ -->
        <?php if ($activeTab == 'stats'): ?>
        <div class="container-fluid container-fullw bg-white">
            <div class="row">
                <div class="col-md-12">
                    <h4><i class="fa fa-bar-chart"></i> Estadísticas de Usuarios</h4>
                    <p class="text-muted">Resumen estadístico del sistema de usuarios</p>
                    <hr>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="panel panel-white">
                                <div class="panel-body text-center">
                                    <h1 class="text-info"><?php echo $stats['total_users'] ?? 0; ?></h1>
                                    <p>Total Usuarios</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-white">
                                <div class="panel-body text-center">
                                    <h1 class="text-success"><?php echo $stats['active_users'] ?? 0; ?></h1>
                                    <p>Usuarios Activos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-white">
                                <div class="panel-body text-center">
                                    <h1 class="text-warning"><?php echo $stats['inactive_users'] ?? 0; ?></h1>
                                    <p>Usuarios Inactivos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-white">
                                <div class="panel-body text-center">
                                    <h1 class="text-danger"><?php echo $stats['blocked_users'] ?? 0; ?></h1>
                                    <p>Usuarios Bloqueados</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico de usuarios por tipo (placeholder) -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-white">
                                <div class="panel-heading">
                                    <h4>Distribución de Usuarios por Tipo</h4>
                                </div>
                                <div class="panel-body">
                                    <p class="text-muted">
                                        <i class="fa fa-info-circle"></i>
                                        Funcionalidad de gráficos en desarrollo. Próximamente se mostrarán visualizaciones interactivas.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; // Fin del tab stats ?>

        <?php include('include/footer.php');?>
        <?php include('include/setting.php');?>
    </div>

    <!-- ============================================ -->
    <!-- MODAL: CREAR USUARIO (MEJORADO) -->
    <!-- ============================================ -->
    <div class="modal fade" id="createUserModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-content-improved">
                <form method="POST" action="" id="createUserForm">
                    <input type="hidden" name="action" value="create">

                    <!-- Modal Header -->
                    <div class="modal-header modal-header-improved">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"><i class="fa fa-user-plus"></i> Crear Nuevo Usuario</h4>
                    </div>

                    <!-- Modal Body -->
                    <div class="modal-body modal-body-improved">

                        <!-- SECCIÓN 1: INFORMACIÓN BÁSICA -->
                        <div class="form-section">
                            <h5 class="form-section-title">
                                <i class="fa fa-id-card"></i> Información Básica
                            </h5>

                            <div class="form-row-2cols">
                                <div class="form-group-improved">
                                    <label>
                                        Nombre <span class="required">*</span>
                                    </label>
                                    <input type="text" name="firstname" id="create_firstname" class="form-control" required>
                                    <input type="hidden" name="full_name" id="create_full_name">
                                </div>
                                <div class="form-group-improved">
                                    <label>
                                        Apellido <span class="required">*</span>
                                    </label>
                                    <input type="text" name="lastname" id="create_lastname" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group-improved">
                                <label>
                                    Email Corporativo <span class="required">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="email" name="email" id="create_email" class="form-control" required>
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-primary" id="generateEmailBtn" title="Generar email automáticamente">
                                            <i class="fa fa-magic"></i>
                                        </button>
                                    </span>
                                </div>
                                <small class="text-muted" id="email_hint">El email se generará automáticamente</small>
                            </div>

                            <div class="form-group-improved">
                                <label>
                                    Contraseña <span class="required">*</span>
                                </label>
                                <input type="password" name="password" id="create_password" class="form-control" required>
                                <small class="text-muted" id="password_requirements">Cargando requisitos...</small>
                                <div id="password_strength" style="margin-top: 5px;"></div>
                            </div>
                        </div>

                        <!-- SECCIÓN 2: TIPO DE USUARIO -->
                        <div class="form-section">
                            <h5 class="form-section-title">
                                <i class="fa fa-user-tag"></i> Tipo de Usuario
                            </h5>

                            <div class="user-type-selector">
                                <div class="user-type-option">
                                    <input type="radio" id="usertype_patient" name="user_type" value="patient" required>
                                    <label class="user-type-label" for="usertype_patient">
                                        <i class="fa fa-user"></i>
                                        <span>Paciente</span>
                                    </label>
                                </div>

                                <div class="user-type-option">
                                    <input type="radio" id="usertype_doctor" name="user_type" value="doctor">
                                    <label class="user-type-label" for="usertype_doctor">
                                        <i class="fa fa-stethoscope"></i>
                                        <span>Doctor</span>
                                    </label>
                                </div>

                                <div class="user-type-option">
                                    <input type="radio" id="usertype_admin" name="user_type" value="admin">
                                    <label class="user-type-label" for="usertype_admin">
                                        <i class="fa fa-shield"></i>
                                        <span>Administrador</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 3: ROLES (Auto-seleccionables) -->
                        <div class="form-section">
                            <h5 class="form-section-title">
                                <i class="fa fa-lock"></i> Roles Asignados
                            </h5>

                            <div class="roles-container">
                                <?php foreach ($all_roles as $role): ?>
                                <div class="role-checkbox-wrapper">
                                    <input type="checkbox"
                                           name="roles[]"
                                           id="role_<?php echo $role['id']; ?>"
                                           value="<?php echo $role['id']; ?>"
                                           class="role-checkbox"
                                           data-category="<?php echo $role['category'] ?? ''; ?>">
                                    <label for="role_<?php echo $role['id']; ?>">
                                        <span class="role-badge"><?php echo htmlspecialchars($role['category'] ?? 'N/A'); ?></span>
                                        <?php echo htmlspecialchars($role['display_name']); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- SECCIÓN 4: CAMPOS ESPECÍFICOS POR TIPO (Se muestran/ocultan dinámicamente) -->

                        <!-- Doctor Specific Fields -->
                        <div class="form-section doctor-color specific-fields" id="doctor_fields">
                            <h5 class="form-section-title">
                                <i class="fa fa-stethoscope"></i> Información Médica
                            </h5>

                            <div class="form-group-improved">
                                <label>Especialización</label>
                                <select name="doctor_specialization" id="doctor_specialization" class="form-control">
                                    <option value="">-- Seleccionar especialización --</option>
                                    <?php
                                    $spec_query = "SELECT id, specilization FROM doctorspecilization";
                                    $spec_result = $con->query($spec_query);
                                    while ($spec = $spec_result->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $spec['id']; ?>">
                                        <?php echo htmlspecialchars($spec['specilization']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group-improved">
                                <label>Honorarios de Consulta (Opcional)</label>
                                <input type="number" name="doctor_consultation_fee" id="doctor_consultation_fee"
                                       class="form-control" step="0.01" min="0" placeholder="0.00">
                                <small class="text-muted">Monto base por consulta</small>
                            </div>
                        </div>

                        <!-- Patient Specific Fields -->
                        <div class="form-section patient-color specific-fields" id="patient_fields">
                            <h5 class="form-section-title">
                                <i class="fa fa-user-medical"></i> Información Médica
                            </h5>

                            <div class="form-row-2cols">
                                <div class="form-group-improved">
                                    <label>Fecha de Nacimiento</label>
                                    <input type="date" name="patient_date_of_birth" id="patient_date_of_birth" class="form-control">
                                </div>
                                <div class="form-group-improved">
                                    <label>Género</label>
                                    <select name="patient_gender" id="patient_gender" class="form-control">
                                        <option value="">-- Seleccionar --</option>
                                        <option value="male">Masculino</option>
                                        <option value="female">Femenino</option>
                                        <option value="other">Otro</option>
                                        <option value="prefer_not_to_say">Prefiero no especificar</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Specific Fields -->
                        <div class="form-section admin-color specific-fields" id="admin_fields">
                            <h5 class="form-section-title">
                                <i class="fa fa-shield"></i> Información Técnica
                            </h5>

                            <div class="form-row-2cols">
                                <div class="form-group-improved">
                                    <label>Departamento</label>
                                    <select name="admin_department" id="admin_department" class="form-control">
                                        <option value="operations">Operaciones</option>
                                        <option value="it">IT / Sistemas</option>
                                        <option value="security">Seguridad</option>
                                        <option value="finance">Finanzas</option>
                                    </select>
                                </div>
                                <div class="form-group-improved">
                                    <label>Nivel Administrativo</label>
                                    <select name="admin_level" id="admin_level" class="form-control">
                                        <option value="operational">Operacional</option>
                                        <option value="supervisor">Supervisor</option>
                                        <option value="manager">Manager</option>
                                        <option value="director">Director</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 5: ESTADO -->
                        <div class="form-section">
                            <h5 class="form-section-title">
                                <i class="fa fa-toggle-on"></i> Estado
                            </h5>

                            <div class="form-group-improved">
                                <label>Estado de Cuenta</label>
                                <select name="status" class="form-control">
                                    <option value="active">Activo</option>
                                    <option value="inactive">Inactivo</option>
                                </select>
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer modal-footer-improved">
                        <button type="button" class="btn btn-cancel" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-create-user">
                            <i class="fa fa-save"></i> Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- MODAL: EDITAR USUARIO (MEJORADO) -->
    <!-- ============================================ -->
    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-content-improved">
                <form method="POST" action="" id="editUserForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="edit_user_id">

                    <!-- Modal Header -->
                    <div class="modal-header modal-header-improved">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"><i class="fa fa-edit"></i> Editar Usuario</h4>
                    </div>

                    <!-- Modal Body con Tabs -->
                    <div class="modal-body modal-body-improved">

                        <!-- Navigation Tabs -->
                        <ul class="nav nav-tabs nav-tabs-improved" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#edit-basic" aria-controls="edit-basic" role="tab" data-toggle="tab" class="nav-link active">
                                    <i class="fa fa-user"></i> Información Básica
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#edit-roles" aria-controls="edit-roles" role="tab" data-toggle="tab" class="nav-link">
                                    <i class="fa fa-lock"></i> Roles y Permisos
                                </a>
                            </li>
                            <li role="presentation" class="edit-specific-tab" style="display: none;">
                                <a href="#edit-specific" aria-controls="edit-specific" role="tab" data-toggle="tab" class="nav-link">
                                    <i class="fa fa-info-circle"></i> Información Específica
                                </a>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content tab-content-improved">

                            <!-- TAB 1: INFORMACIÓN BÁSICA -->
                            <div role="tabpanel" class="tab-pane-improved active" id="edit-basic">

                                <div class="form-section">
                                    <h5 class="form-section-title">
                                        <i class="fa fa-id-card"></i> Datos Personales
                                    </h5>

                                    <div class="form-row-2cols">
                                        <div class="form-group-improved">
                                            <label>
                                                Nombre Completo <span class="required">*</span>
                                            </label>
                                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                                        </div>
                                        <div class="form-group-improved">
                                            <label>
                                                Email <span class="required">*</span>
                                            </label>
                                            <input type="email" name="email" id="edit_email" class="form-control" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <h5 class="form-section-title">
                                        <i class="fa fa-toggle-on"></i> Estado
                                    </h5>

                                    <div class="form-group-improved">
                                        <label>Estado de Cuenta</label>
                                        <select name="status" id="edit_status" class="form-control">
                                            <option value="active">Activo</option>
                                            <option value="inactive">Inactivo</option>
                                            <option value="blocked">Bloqueado</option>
                                        </select>
                                    </div>
                                </div>

                            </div>

                            <!-- TAB 2: ROLES Y PERMISOS -->
                            <div role="tabpanel" class="tab-pane-improved" id="edit-roles">

                                <div class="form-section">
                                    <h5 class="form-section-title">
                                        <i class="fa fa-lock"></i> Roles Asignados
                                    </h5>

                                    <div class="roles-container">
                                        <?php foreach ($all_roles as $role): ?>
                                        <div class="role-checkbox-wrapper">
                                            <input type="checkbox"
                                                   name="roles[]"
                                                   id="edit_role_<?php echo $role['id']; ?>"
                                                   value="<?php echo $role['id']; ?>"
                                                   class="role-checkbox">
                                            <label for="edit_role_<?php echo $role['id']; ?>">
                                                <span class="role-badge"><?php echo htmlspecialchars($role['category'] ?? 'N/A'); ?></span>
                                                <?php echo htmlspecialchars($role['display_name']); ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            </div>

                            <!-- TAB 3: INFORMACIÓN ESPECÍFICA (Doctor/Patient/Admin) -->
                            <div role="tabpanel" class="tab-pane-improved" id="edit-specific">

                                <!-- Doctor Specific Fields -->
                                <div class="form-section doctor-color edit-specific-fields" id="edit_doctor_fields" style="display: none;">
                                    <h5 class="form-section-title">
                                        <i class="fa fa-stethoscope"></i> Información Médica
                                    </h5>

                                    <div class="form-group-improved">
                                        <label>Licencia Médica</label>
                                        <input type="text" name="doctor_license_number" id="edit_doctor_license" class="form-control" placeholder="LIC-XXXXX">
                                        <small class="text-muted">Número único de licencia médica</small>
                                    </div>

                                    <div class="form-row-2cols">
                                        <div class="form-group-improved">
                                            <label>Especialización</label>
                                            <select name="doctor_specialization" id="edit_doctor_specialization" class="form-control">
                                                <option value="">-- Seleccionar especialización --</option>
                                                <?php
                                                $spec_query = "SELECT id, specilization FROM doctorspecilization";
                                                $spec_result = $con->query($spec_query);
                                                while ($spec = $spec_result->fetch_assoc()):
                                                ?>
                                                <option value="<?php echo $spec['id']; ?>">
                                                    <?php echo htmlspecialchars($spec['specilization']); ?>
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="form-group-improved">
                                            <label>Años de Experiencia</label>
                                            <input type="number" name="doctor_years_of_experience" id="edit_doctor_experience"
                                                   class="form-control" min="0" max="70" placeholder="0">
                                        </div>
                                    </div>

                                    <div class="form-row-2cols">
                                        <div class="form-group-improved">
                                            <label>Honorarios de Consulta</label>
                                            <input type="number" name="doctor_consultation_fee" id="edit_doctor_consultation_fee"
                                                   class="form-control" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                        <div class="form-group-improved">
                                            <label>Calificación Promedio</label>
                                            <input type="number" name="doctor_rating" id="edit_doctor_rating"
                                                   class="form-control" step="0.1" min="0" max="5" placeholder="0.0" readonly>
                                            <small class="text-muted">Calculado automáticamente</small>
                                        </div>
                                    </div>

                                    <div class="form-group-improved">
                                        <label>Biografía Profesional</label>
                                        <textarea name="doctor_bio" id="edit_doctor_bio" class="form-control" rows="3"
                                                  placeholder="Información profesional y educativa..."></textarea>
                                    </div>
                                </div>

                                <!-- Patient Specific Fields -->
                                <div class="form-section patient-color edit-specific-fields" id="edit_patient_fields" style="display: none;">
                                    <h5 class="form-section-title">
                                        <i class="fa fa-user-medical"></i> Información Médica
                                    </h5>

                                    <div class="form-row-2cols">
                                        <div class="form-group-improved">
                                            <label>Fecha de Nacimiento</label>
                                            <input type="date" name="patient_date_of_birth" id="edit_patient_dob" class="form-control">
                                        </div>
                                        <div class="form-group-improved">
                                            <label>Tipo de Sangre</label>
                                            <select name="patient_blood_type" id="edit_patient_blood_type" class="form-control">
                                                <option value="">-- Seleccionar --</option>
                                                <option value="O+">O+</option>
                                                <option value="O-">O-</option>
                                                <option value="A+">A+</option>
                                                <option value="A-">A-</option>
                                                <option value="B+">B+</option>
                                                <option value="B-">B-</option>
                                                <option value="AB+">AB+</option>
                                                <option value="AB-">AB-</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-row-2cols">
                                        <div class="form-group-improved">
                                            <label>Género</label>
                                            <select name="patient_gender" id="edit_patient_gender" class="form-control">
                                                <option value="">-- Seleccionar --</option>
                                                <option value="male">Masculino</option>
                                                <option value="female">Femenino</option>
                                                <option value="other">Otro</option>
                                                <option value="prefer_not_to_say">Prefiero no especificar</option>
                                            </select>
                                        </div>
                                        <div class="form-group-improved">
                                            <label>Estado Civil</label>
                                            <select name="patient_marital_status" id="edit_patient_marital" class="form-control">
                                                <option value="">-- Seleccionar --</option>
                                                <option value="single">Soltero/a</option>
                                                <option value="married">Casado/a</option>
                                                <option value="divorced">Divorciado/a</option>
                                                <option value="widowed">Viudo/a</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group-improved">
                                        <label>Dirección</label>
                                        <input type="text" name="patient_address" id="edit_patient_address" class="form-control" placeholder="Calle y número">
                                    </div>

                                    <div class="form-row-2cols">
                                        <div class="form-group-improved">
                                            <label>Ciudad</label>
                                            <input type="text" name="patient_city" id="edit_patient_city" class="form-control">
                                        </div>
                                        <div class="form-group-improved">
                                            <label>Teléfono</label>
                                            <input type="tel" name="patient_phone" id="edit_patient_phone" class="form-control">
                                        </div>
                                    </div>

                                    <div class="form-group-improved">
                                        <label>Alergias Conocidas</label>
                                        <textarea name="patient_allergies" id="edit_patient_allergies" class="form-control" rows="2"
                                                  placeholder="Listar alergias separadas por comas..."></textarea>
                                    </div>

                                    <div class="form-group-improved">
                                        <label>Condiciones Crónicas</label>
                                        <textarea name="patient_chronic_conditions" id="edit_patient_chronic" class="form-control" rows="2"
                                                  placeholder="Listar condiciones crónicas..."></textarea>
                                    </div>
                                </div>

                                <!-- Admin Specific Fields -->
                                <div class="form-section admin-color edit-specific-fields" id="edit_admin_fields" style="display: none;">
                                    <h5 class="form-section-title">
                                        <i class="fa fa-shield"></i> Información Técnica
                                    </h5>

                                    <div class="form-row-2cols">
                                        <div class="form-group-improved">
                                            <label>ID de Empleado</label>
                                            <input type="text" name="admin_employee_id" id="edit_admin_employee_id" class="form-control" readonly>
                                            <small class="text-muted">Generado automáticamente</small>
                                        </div>
                                        <div class="form-group-improved">
                                            <label>Departamento</label>
                                            <select name="admin_department" id="edit_admin_department" class="form-control">
                                                <option value="operations">Operaciones</option>
                                                <option value="it">IT / Sistemas</option>
                                                <option value="security">Seguridad</option>
                                                <option value="finance">Finanzas</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-row-2cols">
                                        <div class="form-group-improved">
                                            <label>Nivel Administrativo</label>
                                            <select name="admin_level" id="edit_admin_level" class="form-control">
                                                <option value="operational">Operacional</option>
                                                <option value="supervisor">Supervisor</option>
                                                <option value="manager">Manager</option>
                                                <option value="director">Director</option>
                                            </select>
                                        </div>
                                        <div class="form-group-improved">
                                            <label>Área Técnica</label>
                                            <input type="text" name="admin_technical_area" id="edit_admin_technical_area" class="form-control"
                                                   placeholder="Ej: Seguridad de Sistemas, Base de Datos...">
                                        </div>
                                    </div>

                                    <div class="form-group-improved">
                                        <label>Certificaciones</label>
                                        <input type="text" name="admin_certifications" id="edit_admin_certifications" class="form-control"
                                               placeholder="Ej: CISSP, CEH, AWS (separadas por comas)">
                                    </div>

                                    <div class="form-row-2cols">
                                        <div class="form-group-improved">
                                            <label>Años de Experiencia IT</label>
                                            <input type="number" name="admin_years_experience" id="edit_admin_years_exp"
                                                   class="form-control" min="0" max="70" placeholder="0">
                                        </div>
                                        <div class="form-group-improved">
                                            <label>Nivel de Acceso</label>
                                            <select name="admin_clearance_level" id="edit_admin_clearance" class="form-control">
                                                <option value="level_1">Nivel 1 (Limitado)</option>
                                                <option value="level_2">Nivel 2 (Estándar)</option>
                                                <option value="level_3">Nivel 3 (Avanzado)</option>
                                                <option value="level_4">Nivel 4 (Administrador)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group-improved">
                                        <label>
                                            <input type="checkbox" name="admin_can_access_production" id="edit_admin_prod_access">
                                            Acceso a Producción
                                        </label>
                                    </div>

                                    <div class="form-group-improved">
                                        <label>
                                            <input type="checkbox" name="admin_can_modify_security" id="edit_admin_security_modify">
                                            Puede Modificar Configuración de Seguridad
                                        </label>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer modal-footer-improved">
                        <button type="button" class="btn btn-cancel" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-save-changes">
                            <i class="fa fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <?php echo getEmailConfigForJS(); ?>
    <?php echo getPasswordPoliciesForJS(); ?>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/modernizr/modernizr.js"></script>
    <script src="vendor/jquery-cookie/jquery.cookie.js"></script>
    <script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="vendor/switchery/switchery.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/email-validation.js"></script>

    <script>
        jQuery(document).ready(function() {
            Main.init();

            // Actualizar requisitos de contraseña dinámicamente desde las políticas de la BD
            var minLength = typeof PASSWORD_MIN_LENGTH !== 'undefined' ? PASSWORD_MIN_LENGTH : 8;
            $('#create_password').attr('minlength', minLength);
            $('#password_requirements').text('Mínimo ' + minLength + ' caracteres, incluir mayúsculas, minúsculas, números y símbolos');

            // Validación de contraseña en tiempo real
            $('#create_password').on('keyup', function() {
                validatePassword($(this).val());
            });

            // Validar antes de enviar formulario
            $('#createUserForm').on('submit', function(e) {
                var password = $('#create_password').val();
                if (!isPasswordValid(password)) {
                    e.preventDefault();
                    Swal.fire('Error', 'La contraseña no cumple con las políticas de seguridad', 'error');
                    return false;
                }

                // Actualizar full_name antes de enviar
                var firstname = $('#create_firstname').val();
                var lastname = $('#create_lastname').val();
                $('#create_full_name').val(firstname + ' ' + lastname);
            });

            // Generar email automáticamente cuando se completen nombre y apellido
            $('#create_firstname, #create_lastname').on('blur', function() {
                var firstname = $('#create_firstname').val().trim();
                var lastname = $('#create_lastname').val().trim();

                if (firstname && lastname) {
                    generateEmail(firstname, lastname);
                }
            });

            // Botón manual para generar email
            $('#generateEmailBtn').on('click', function() {
                var firstname = $('#create_firstname').val().trim();
                var lastname = $('#create_lastname').val().trim();

                if (!firstname || !lastname) {
                    Swal.fire('Atención', 'Por favor ingrese nombre y apellido primero', 'warning');
                    return;
                }

                generateEmail(firstname, lastname);
            });

            // Función para generar email vía API
            function generateEmail(firstname, lastname) {
                $('#email_hint').html('<i class="fa fa-spinner fa-spin"></i> Generando email...');

                $.ajax({
                    url: 'api/generate-email.php',
                    type: 'POST',
                    data: {
                        firstname: firstname,
                        lastname: lastname
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#create_email').val(response.email);
                            $('#email_hint').html('<i class="fa fa-check text-success"></i> Email generado: ' + response.email);
                            // Marcar como válido
                            $('#create_email').removeClass('is-invalid').addClass('is-valid');
                        } else {
                            $('#email_hint').html('<i class="fa fa-times text-danger"></i> ' + response.error);
                        }
                    },
                    error: function() {
                        $('#email_hint').html('<i class="fa fa-times text-danger"></i> Error al generar email');
                    }
                });
            }

            // Validar email en tiempo real cuando se cambia manualmente
            $('#create_email').on('blur change', function() {
                var email = $(this).val().trim();

                if (!email) {
                    $(this).removeClass('is-valid is-invalid');
                    $('#email_hint').html('El email se generará automáticamente');
                    return;
                }

                if (validateCorporateEmailFormat(email)) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                    $('#email_hint').html('<i class="fa fa-check text-success"></i> Email válido');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                    $('#email_hint').html('<i class="fa fa-times text-danger"></i> ' + getEmailValidationError(email));
                }
            });

            // Validar antes de enviar el formulario
            $('#createUserForm').on('submit', function(e) {
                var email = $('#create_email').val().trim();
                var password = $('#create_password').val();

                // Validar email
                if (!validateCorporateEmailFormat(email)) {
                    e.preventDefault();
                    Swal.fire('Error', getEmailValidationError(email), 'error');
                    $('#create_email').focus();
                    return false;
                }

                // Validar contraseña (ya existente)
                if (!isPasswordValid(password)) {
                    e.preventDefault();
                    Swal.fire('Error', 'La contraseña no cumple con las políticas de seguridad', 'error');
                    return false;
                }

                // Actualizar full_name antes de enviar
                var firstname = $('#create_firstname').val();
                var lastname = $('#create_lastname').val();
                $('#create_full_name').val(firstname + ' ' + lastname);
            });
        });

        function validatePassword(password) {
            var strength = 0;
            var feedback = [];
            var minLength = typeof PASSWORD_MIN_LENGTH !== 'undefined' ? PASSWORD_MIN_LENGTH : 8;

            // Verificar longitud mínima
            if (password.length >= minLength) {
                strength += 20;
            } else {
                feedback.push('Mínimo ' + minLength + ' caracteres');
            }

            // Verificar mayúsculas
            if (/[A-Z]/.test(password)) {
                strength += 20;
            } else {
                feedback.push('Al menos una mayúscula');
            }

            // Verificar minúsculas
            if (/[a-z]/.test(password)) {
                strength += 20;
            } else {
                feedback.push('Al menos una minúscula');
            }

            // Verificar números
            if (/[0-9]/.test(password)) {
                strength += 20;
            } else {
                feedback.push('Al menos un número');
            }

            // Verificar símbolos
            if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
                strength += 20;
            } else {
                feedback.push('Al menos un símbolo (!@#$...)');
            }

            // Mostrar retroalimentación
            var html = '';
            if (strength < 100) {
                html = '<small class="text-danger">Falta: ' + feedback.join(', ') + '</small>';
            } else {
                html = '<small class="text-success"><i class="fa fa-check"></i> Contraseña válida</small>';
            }
            $('#password_strength').html(html);

            return strength === 100;
        }

        function isPasswordValid(password) {
            var minLength = typeof PASSWORD_MIN_LENGTH !== 'undefined' ? PASSWORD_MIN_LENGTH : 8;
            return password.length >= minLength &&
                   /[A-Z]/.test(password) &&
                   /[a-z]/.test(password) &&
                   /[0-9]/.test(password) &&
                   /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
        }

        // Función para aplicar filtros
        function applyFilter() {
            var search = document.getElementById('searchInput').value;
            var status = document.getElementById('filterStatus').value;
            var type = document.getElementById('filterType').value;

            var params = [];
            if (search) params.push('search=' + encodeURIComponent(search));
            if (status) params.push('status=' + status);
            if (type) params.push('type=' + type);

            var url = 'manage-users.php';
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            window.location.href = url;
        }

        // Función para ordenar tabla
        function sortTable(column) {
            // Obtener parámetros actuales de la URL
            var urlParams = new URLSearchParams(window.location.search);
            
            var currentSortBy = urlParams.get('sort_by') || 'full_name';
            var currentSortOrder = urlParams.get('sort_order') || 'ASC';
            
            // Si se hace clic en la misma columna, invertir el orden
            var newSortOrder = 'ASC';
            if (column === currentSortBy) {
                newSortOrder = currentSortOrder === 'ASC' ? 'DESC' : 'ASC';
            }
            
            // Establecer nuevos parámetros
            urlParams.set('sort_by', column);
            urlParams.set('sort_order', newSortOrder);
            
            // Redirigir con nuevos parámetros
            window.location.href = 'manage-users.php?' + urlParams.toString();
        }

        // Función para editar usuario
        function editUser(userId) {
            // Cargar datos del usuario mediante AJAX
            $.ajax({
                url: 'api/users-api.php',
                method: 'GET',
                data: { action: 'get', id: userId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var user = response.data;
                        $('#edit_user_id').val(user.id);
                        $('#edit_full_name').val(user.full_name);
                        $('#edit_email').val(user.email);
                        $('#edit_status').val(user.status);

                        // Marcar roles asignados
                        $('#edit_roles option').prop('selected', false);
                        if (user.role_ids) {
                            var roleIds = user.role_ids.split(',');
                            roleIds.forEach(function(roleId) {
                                $('#edit_roles option[value="' + roleId + '"]').prop('selected', true);
                            });
                        }

                        $('#editUserModal').modal('show');
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'No se pudo cargar los datos del usuario', 'error');
                }
            });
        }

        // Función para eliminar usuario
        function deleteUser(userId, userName) {
            Swal.fire({
                title: '¿Estás seguro?',
                html: 'Vas a eliminar al usuario: <br><strong>' + userName + '</strong>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'manage-users.php?action=delete&id=' + userId;
                }
            });
        }

        // ============================================================================
        // FUNCIONALIDAD DINÁMICA DEL MODAL: CREATE USER
        // ============================================================================

        // Mapeo dinámico de roles por tipo de usuario - basado en nombres de roles
        var rolesByUserType = {};

        // Cargar mapeo de roles dinámicamente desde los checkboxes existentes
        function initializeRoleMapping() {
            rolesByUserType = {
                'patient': [],
                'doctor': [],
                'admin': []
            };

            $('.role-checkbox').each(function() {
                var roleId = $(this).attr('id').replace('role_', '');
                var roleLabel = $(this).siblings('label').text().trim().toLowerCase();

                // Mapear por nombre de rol
                if (roleLabel.includes('paciente') || roleLabel.includes('patient')) {
                    rolesByUserType['patient'].push(roleId);
                } else if (roleLabel.includes('doctor') || roleLabel.includes('médico')) {
                    rolesByUserType['doctor'].push(roleId);
                } else if (roleLabel.includes('admin') || roleLabel.includes('administrador')) {
                    rolesByUserType['admin'].push(roleId);
                }
            });
        }

        // Inicializar mapeo cuando carga el documento
        initializeRoleMapping();

        // Cuando cambia el tipo de usuario en el modal CREATE
        $('input[name="user_type"]').on('change', function() {
            var userType = $(this).val();

            // Mostrar/ocultar campos específicos
            $('.specific-fields').removeClass('active');
            if (userType === 'doctor') {
                $('#doctor_fields').addClass('active');
            } else if (userType === 'patient') {
                $('#patient_fields').addClass('active');
            } else if (userType === 'admin') {
                $('#admin_fields').addClass('active');
            }

            // Auto-seleccionar roles según tipo de usuario
            $('.role-checkbox').prop('checked', false);

            if (userType && rolesByUserType[userType]) {
                rolesByUserType[userType].forEach(function(roleId) {
                    $('#role_' + roleId).prop('checked', true);
                });
            }
        });

        // ============================================================================
        // FUNCIONALIDAD DINÁMICA DEL MODAL: EDIT USER
        // ============================================================================

        // Cuando se abre el modal EDIT, detectar el tipo de usuario y mostrar la pestaña correcta
        $('#editUserModal').on('show.bs.modal', function() {
            // Esperamos a que los datos se carguen vía AJAX en editUser()
            // Luego detectamos el tipo de usuario
        });

        // Modificar la función editUser para que cargue también los campos específicos
        var originalEditUser = window.editUser;
        window.editUser = function(userId) {
            $.ajax({
                url: 'api/users-api.php',
                method: 'GET',
                data: { action: 'get', id: userId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var user = response.data;
                        $('#edit_user_id').val(user.id);
                        $('#edit_full_name').val(user.full_name);
                        $('#edit_email').val(user.email);
                        $('#edit_status').val(user.status);

                        // Marcar roles asignados (usando checkboxes ahora)
                        $('.role-checkbox').prop('checked', false);
                        if (user.role_ids) {
                            var roleIds = user.role_ids.split(',');
                            roleIds.forEach(function(roleId) {
                                $('#edit_role_' + roleId).prop('checked', true);
                            });
                        }

                        // Detectar tipo de usuario y mostrar los campos específicos
                        var userType = user.user_type;

                        // Mostrar/ocultar pestaña de información específica
                        if (userType === 'patient' || userType === 'doctor' || userType === 'admin') {
                            $('.edit-specific-tab').show();
                        } else {
                            $('.edit-specific-tab').hide();
                        }

                        // Mostrar/ocultar campos específicos
                        $('.edit-specific-fields').hide();

                        if (userType === 'doctor') {
                            $('#edit_doctor_fields').show();
                            // Cargar datos específicos del doctor si existen
                            if (user.doctor_data) {
                                $('#edit_doctor_license').val(user.doctor_data.license_number || '');
                                $('#edit_doctor_specialization').val(user.doctor_data.specialization_id || '');
                                $('#edit_doctor_experience').val(user.doctor_data.years_of_experience || '');
                                $('#edit_doctor_consultation_fee').val(user.doctor_data.consultation_fee || '0.00');
                                $('#edit_doctor_rating').val(user.doctor_data.rating || '0.0');
                                $('#edit_doctor_bio').val(user.doctor_data.bio || '');
                            }
                        } else if (userType === 'patient') {
                            $('#edit_patient_fields').show();
                            // Cargar datos específicos del patient si existen
                            if (user.patient_data) {
                                $('#edit_patient_dob').val(user.patient_data.date_of_birth || '');
                                $('#edit_patient_blood_type').val(user.patient_data.blood_type || '');
                                $('#edit_patient_gender').val(user.patient_data.gender || '');
                                $('#edit_patient_address').val(user.patient_data.address || '');
                                $('#edit_patient_city').val(user.patient_data.city || '');
                                $('#edit_patient_phone').val(user.patient_data.phone || '');
                                $('#edit_patient_allergies').val(user.patient_data.allergies || '');
                                $('#edit_patient_chronic').val(user.patient_data.chronic_conditions || '');
                            }
                        } else if (userType === 'admin') {
                            $('#edit_admin_fields').show();
                            // Cargar datos específicos del admin si existen
                            if (user.admin_data) {
                                $('#edit_admin_employee_id').val(user.admin_data.employee_id || '');
                                $('#edit_admin_department').val(user.admin_data.department || 'operations');
                                $('#edit_admin_level').val(user.admin_data.admin_level || 'operational');
                                $('#edit_admin_technical_area').val(user.admin_data.technical_area || '');
                                $('#edit_admin_certifications').val(user.admin_data.certifications || '');
                                $('#edit_admin_years_exp').val(user.admin_data.years_of_experience || '0');
                                $('#edit_admin_clearance').val(user.admin_data.clearance_level || 'level_2');
                                $('#edit_admin_prod_access').prop('checked', user.admin_data.can_access_production === 1);
                                $('#edit_admin_security_modify').prop('checked', user.admin_data.can_modify_security === 1);
                            }
                        }

                        $('#editUserModal').modal('show');
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'No se pudo cargar los datos del usuario', 'error');
                }
            });
        };

    </script>
</body>
</html>
