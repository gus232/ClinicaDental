<?php
/**
 * ============================================================================
 * GESTIÓN DE POLÍTICAS DE CONTRASEÑA - PUNTO 8.3 PROYECTO SIS 321
 * ============================================================================
 *
 * Sistema completo de configuración de políticas de contraseña con:
 * - Configuración de políticas (complejidad, expiración, bloqueo)
 * - Estadísticas del sistema
 * - Auditoría de cambios
 * - Simulador de contraseñas
 *
 * Versión: 1.0.0
 * Fecha: 2025-10-24
 * ============================================================================
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('include/config.php');
require_once('include/checklogin.php');
require_once('../include/permission-check.php');
require_once('../include/password-policy.php');

check_login();

// Protección RBAC - Solo admins pueden gestionar políticas de contraseña
// Si no existe el permiso específico, usar manage_roles como fallback
if (function_exists('hasPermission')) {
    if (!hasPermission('manage_password_policies') && !hasPermission('manage_roles')) {
        header('location: dashboard.php');
        exit();
    }
} else {
    // Fallback: verificar que sea admin
    if ($_SESSION['user_type'] !== 'admin') {
        header('location: dashboard.php');
        exit();
    }
}

// Variables para mensajes
$success_msg = '';
$error_msg = '';

// Inicializar la clase PasswordPolicy
$passwordPolicy = new PasswordPolicy($con);

// ============================================================================
// MANEJO DE ACCIONES
// ============================================================================

// ACTUALIZAR CONFIGURACIÓN DE POLÍTICAS
if (isset($_POST['action']) && $_POST['action'] == 'update_policies') {

    $settings_to_update = [
        'min_length' => $_POST['min_length'] ?? 8,
        'max_length' => $_POST['max_length'] ?? 64,
        'require_uppercase' => isset($_POST['require_uppercase']) ? 1 : 0,
        'require_lowercase' => isset($_POST['require_lowercase']) ? 1 : 0,
        'require_number' => isset($_POST['require_number']) ? 1 : 0,
        'require_special_char' => isset($_POST['require_special_char']) ? 1 : 0,
        'special_chars_allowed' => $_POST['special_chars_allowed'] ?? '@#$%^&*()_+-=[]{}|;:,.<>?',
        'password_expiry_days' => $_POST['password_expiry_days'] ?? 90,
        'password_history_count' => $_POST['password_history_count'] ?? 5,
        'max_failed_attempts' => $_POST['max_failed_attempts'] ?? 3,
        'lockout_duration_minutes' => $_POST['lockout_duration_minutes'] ?? 30,
        'reset_token_expiry_minutes' => $_POST['reset_token_expiry_minutes'] ?? 30,
        'min_password_age_hours' => $_POST['min_password_age_hours'] ?? 1,
        // Bloqueo progresivo
        'progressive_lockout_enabled' => isset($_POST['progressive_lockout_enabled']) ? 1 : 0,
        'lockout_1st_minutes' => $_POST['lockout_1st_minutes'] ?? 30,
        'lockout_2nd_minutes' => $_POST['lockout_2nd_minutes'] ?? 120,
        'lockout_3rd_minutes' => $_POST['lockout_3rd_minutes'] ?? 1440,
        'lockout_permanent_after' => $_POST['lockout_permanent_after'] ?? 4,
        'lockout_reset_days' => $_POST['lockout_reset_days'] ?? 30
    ];

    // Validaciones
    $errors = [];

    if ($settings_to_update['min_length'] < 4 || $settings_to_update['min_length'] > 32) {
        $errors[] = "Longitud mínima debe estar entre 4 y 32 caracteres";
    }

    if ($settings_to_update['max_length'] < $settings_to_update['min_length'] || $settings_to_update['max_length'] > 128) {
        $errors[] = "Longitud máxima debe ser mayor que la mínima y no exceder 128";
    }

    if ($settings_to_update['password_expiry_days'] < 30 || $settings_to_update['password_expiry_days'] > 365) {
        $errors[] = "Días de expiración deben estar entre 30 y 365";
    }

    if ($settings_to_update['max_failed_attempts'] < 3 || $settings_to_update['max_failed_attempts'] > 10) {
        $errors[] = "Intentos fallidos deben estar entre 3 y 10";
    }

    if ($settings_to_update['lockout_duration_minutes'] < 5 || $settings_to_update['lockout_duration_minutes'] > 120) {
        $errors[] = "Duración de bloqueo debe estar entre 5 y 120 minutos";
    }

    // Validaciones para bloqueo progresivo
    if ($settings_to_update['lockout_1st_minutes'] < 5 || $settings_to_update['lockout_1st_minutes'] > 1440) {
        $errors[] = "Primer bloqueo debe estar entre 5 y 1440 minutos (24 horas)";
    }

    if ($settings_to_update['lockout_2nd_minutes'] < 5 || $settings_to_update['lockout_2nd_minutes'] > 2880) {
        $errors[] = "Segundo bloqueo debe estar entre 5 y 2880 minutos (48 horas)";
    }

    if ($settings_to_update['lockout_3rd_minutes'] < 5 || $settings_to_update['lockout_3rd_minutes'] > 10080) {
        $errors[] = "Tercer bloqueo debe estar entre 5 y 10080 minutos (7 días)";
    }

    if ($settings_to_update['lockout_permanent_after'] < 2 || $settings_to_update['lockout_permanent_after'] > 10) {
        $errors[] = "Bloqueo permanente debe ser después de 2 a 10 bloqueos";
    }

    if ($settings_to_update['lockout_reset_days'] < 0 || $settings_to_update['lockout_reset_days'] > 365) {
        $errors[] = "Días de reseteo deben estar entre 0 y 365";
    }

    if (empty($errors)) {
        $updated_count = 0;
        $failed_count = 0;

        foreach ($settings_to_update as $setting_name => $setting_value) {
            $setting_name_escaped = mysqli_real_escape_string($con, $setting_name);
            $setting_value_escaped = mysqli_real_escape_string($con, $setting_value);
            $updated_by = $_SESSION['id'];

            // Obtener valor anterior para auditoría
            $old_value_query = "SELECT setting_value FROM password_policy_config WHERE setting_name = '$setting_name_escaped'";
            $old_value_result = mysqli_query($con, $old_value_query);
            $old_value = '';
            if ($old_row = mysqli_fetch_assoc($old_value_result)) {
                $old_value = $old_row['setting_value'];
            }

            // Actualizar solo si cambió
            if ($old_value != $setting_value) {
                $update_sql = "UPDATE password_policy_config
                              SET setting_value = '$setting_value_escaped',
                                  updated_at = NOW(),
                                  updated_by = $updated_by
                              WHERE setting_name = '$setting_name_escaped'";

                if (mysqli_query($con, $update_sql)) {
                    $updated_count++;

                    // Registrar en auditoría (opcional - crear tabla más tarde)
                    // INSERT INTO audit_password_policy_changes ...
                } else {
                    $failed_count++;
                }
            }
        }

        if ($updated_count > 0) {
            $success_msg = "Se actualizaron $updated_count configuraciones exitosamente";
        } elseif ($failed_count > 0) {
            $error_msg = "Error al actualizar $failed_count configuraciones";
        } else {
            $success_msg = "No se detectaron cambios en las configuraciones";
        }
    } else {
        $error_msg = implode(". ", $errors);
    }
}

// RESTAURAR VALORES POR DEFECTO
if (isset($_POST['action']) && $_POST['action'] == 'restore_defaults') {
    $defaults = [
        'min_length' => 8,
        'max_length' => 64,
        'require_uppercase' => 1,
        'require_lowercase' => 1,
        'require_number' => 1,
        'require_special_char' => 1,
        'special_chars_allowed' => '@#$%^&*()_+-=[]{}|;:,.<>?',
        'password_expiry_days' => 90,
        'password_history_count' => 5,
        'max_failed_attempts' => 3,
        'lockout_duration_minutes' => 30,
        'reset_token_expiry_minutes' => 30,
        'min_password_age_hours' => 1,
        // Bloqueo progresivo
        'progressive_lockout_enabled' => 1,
        'lockout_1st_minutes' => 30,
        'lockout_2nd_minutes' => 120,
        'lockout_3rd_minutes' => 1440,
        'lockout_permanent_after' => 4,
        'lockout_reset_days' => 30
    ];

    $updated_count = 0;
    foreach ($defaults as $setting_name => $setting_value) {
        $setting_name_escaped = mysqli_real_escape_string($con, $setting_name);
        $setting_value_escaped = mysqli_real_escape_string($con, $setting_value);
        $updated_by = $_SESSION['id'];

        $sql = "UPDATE password_policy_config
                SET setting_value = '$setting_value_escaped',
                    updated_at = NOW(),
                    updated_by = $updated_by
                WHERE setting_name = '$setting_name_escaped'";

        if (mysqli_query($con, $sql)) {
            $updated_count++;
        }
    }

    $success_msg = "Se restauraron los valores por defecto ($updated_count configuraciones)";
}

// ============================================================================
// OBTENER DATOS PARA LA VISTA
// ============================================================================

// Obtener configuración actual
$current_config = $passwordPolicy->getConfig();

// Obtener estadísticas
$stats = [];

// 1. Contraseñas próximas a expirar (< 7 días)
$expiring_soon_query = "SELECT COUNT(*) as count FROM users
                        WHERE password_expires_at IS NOT NULL
                        AND password_expires_at > NOW()
                        AND DATEDIFF(password_expires_at, NOW()) <= 7";
$result = mysqli_query($con, $expiring_soon_query);
$stats['expiring_soon'] = mysqli_fetch_assoc($result)['count'];

// 2. Contraseñas ya expiradas
$expired_query = "SELECT COUNT(*) as count FROM users
                  WHERE password_expires_at IS NOT NULL
                  AND password_expires_at < NOW()";
$result = mysqli_query($con, $expired_query);
$stats['expired'] = mysqli_fetch_assoc($result)['count'];

// 3. Cuentas bloqueadas actualmente
$locked_query = "SELECT COUNT(*) as count FROM users
                 WHERE account_locked_until IS NOT NULL
                 AND account_locked_until > NOW()";
$result = mysqli_query($con, $locked_query);
$stats['locked_accounts'] = mysqli_fetch_assoc($result)['count'];

// 4. Intentos fallidos en las últimas 24h
$failed_attempts_query = "SELECT COUNT(*) as count FROM login_attempts
                          WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                          AND attempt_result != 'success'";
$result = mysqli_query($con, $failed_attempts_query);
$stats['failed_attempts_24h'] = mysqli_fetch_assoc($result)['count'];

// 5. Total de usuarios
$total_users_query = "SELECT COUNT(*) as count FROM users WHERE status = 'active'";
$result = mysqli_query($con, $total_users_query);
$stats['total_users'] = mysqli_fetch_assoc($result)['count'];

// 6. Contraseñas en historial
$history_count_query = "SELECT COUNT(*) as count FROM password_history";
$result = mysqli_query($con, $history_count_query);
$stats['history_count'] = mysqli_fetch_assoc($result)['count'];

// Obtener cambios recientes en configuración (últimos 20)
$changes_query = "SELECT
                    ppc.setting_name,
                    ppc.setting_value,
                    ppc.updated_at,
                    u.full_name as updated_by_name,
                    u.email as updated_by_email
                  FROM password_policy_config ppc
                  LEFT JOIN users u ON ppc.updated_by = u.id
                  WHERE ppc.updated_at IS NOT NULL
                  ORDER BY ppc.updated_at DESC
                  LIMIT 20";
$recent_changes = mysqli_query($con, $changes_query);

// Obtener usuarios con contraseñas próximas a expirar
$expiring_users_query = "SELECT
                          id,
                          full_name,
                          email,
                          user_type,
                          password_expires_at,
                          DATEDIFF(password_expires_at, NOW()) as days_left
                        FROM users
                        WHERE password_expires_at IS NOT NULL
                        AND password_expires_at > NOW()
                        AND DATEDIFF(password_expires_at, NOW()) <= 7
                        ORDER BY password_expires_at ASC
                        LIMIT 10";
$expiring_users = mysqli_query($con, $expiring_users_query);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Admin | Políticas de Contraseña</title>
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

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        .nav-tabs {
            margin-bottom: 20px;
            border-bottom: 2px solid #00a8b3;
        }
        .nav-tabs > li > a {
            color: #666;
        }
        .nav-tabs > li.active > a,
        .nav-tabs > li.active > a:hover,
        .nav-tabs > li.active > a:focus {
            background: #00a8b3;
            color: white;
            border: none;
        }

        .policy-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .policy-section {
            margin-bottom: 30px;
        }

        .policy-section-title {
            font-size: 18px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .policy-section-title i {
            margin-right: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: #555;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .stat-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card.danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .password-strength-meter {
            height: 8px;
            border-radius: 4px;
            background: #e0e0e0;
            margin-top: 10px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            transition: all 0.3s;
        }

        .strength-weak { background: #f44336; }
        .strength-medium { background: #ff9800; }
        .strength-strong { background: #4caf50; }

        .requirement-list {
            list-style: none;
            padding: 0;
        }

        .requirement-list li {
            padding: 8px 0;
            color: #666;
        }

        .requirement-list li i {
            margin-right: 8px;
        }

        .requirement-met {
            color: #4caf50;
        }

        .requirement-not-met {
            color: #f44336;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
            color: white;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .password-test-input {
            font-family: monospace;
            font-size: 16px;
        }

        .alert-info {
            background-color: #e3f2fd;
            border-color: #2196f3;
            color: #1976d2;
        }
    </style>
</head>

<body>
    <div id="app">
        <?php include('include/sidebar.php'); ?>
        <div class="app-content">
            <?php include('include/header.php'); ?>

            <div class="main-content">
                <div class="wrap-content container" id="container">

                    <!-- Breadcrumb -->
                    <section id="page-title">
                        <div class="row">
                            <div class="col-sm-8">
                                <h1 class="mainTitle"><i class="fa fa-key"></i> Gestión de Políticas de Contraseña</h1>
                            </div>
                            <ol class="breadcrumb">
                                <li><span>Admin</span></li>
                                <li><span>Seguridad</span></li>
                                <li class="active"><span>Políticas de Contraseña</span></li>
                            </ol>
                        </div>
                    </section>

                    <!-- Mensajes -->
                    <?php if ($success_msg): ?>
                        <div class="alert alert-success alert-dismissible fade in">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <i class="fa fa-check-circle"></i> <strong>Éxito!</strong> <?php echo $success_msg; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger alert-dismissible fade in">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <i class="fa fa-exclamation-circle"></i> <strong>Error!</strong> <?php echo $error_msg; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Tabs Navigation -->
                    <div class="container-fluid container-fullw bg-white">
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#tab-config" aria-controls="tab-config" role="tab" data-toggle="tab">
                                    <i class="fa fa-cog"></i> Configuración
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#tab-stats" aria-controls="tab-stats" role="tab" data-toggle="tab">
                                    <i class="fa fa-bar-chart"></i> Estadísticas
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#tab-audit" aria-controls="tab-audit" role="tab" data-toggle="tab">
                                    <i class="fa fa-history"></i> Auditoría
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#tab-simulator" aria-controls="tab-simulator" role="tab" data-toggle="tab">
                                    <i class="fa fa-flask"></i> Simulador
                                </a>
                            </li>
                        </ul>

                        <!-- Tabs Content -->
                        <div class="tab-content">

                            <!-- ============================================ -->
                            <!-- TAB 1: CONFIGURACIÓN DE POLÍTICAS -->
                            <!-- ============================================ -->
                            <div role="tabpanel" class="tab-pane active" id="tab-config">
                                <div class="row">
                                    <div class="col-md-12">

                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> Configure las políticas de seguridad de contraseñas para todos los usuarios del sistema.
                                            Los cambios se aplicarán inmediatamente.
                                        </div>

                                        <form method="POST" action="" id="policiesForm">
                                            <input type="hidden" name="action" value="update_policies">

                                            <!-- SECCIÓN 1: COMPLEJIDAD -->
                                            <div class="policy-card">
                                                <div class="policy-section">
                                                    <h3 class="policy-section-title">
                                                        <i class="fa fa-lock"></i> Complejidad de Contraseña
                                                    </h3>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="min_length">Longitud Mínima</label>
                                                                <input type="number" class="form-control" id="min_length" name="min_length"
                                                                       value="<?php echo $current_config['min_length']; ?>"
                                                                       min="4" max="32" required>
                                                                <small class="text-muted">Entre 4 y 32 caracteres</small>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="max_length">Longitud Máxima</label>
                                                                <input type="number" class="form-control" id="max_length" name="max_length"
                                                                       value="<?php echo $current_config['max_length']; ?>"
                                                                       min="8" max="128" required>
                                                                <small class="text-muted">Entre 8 y 128 caracteres</small>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="checkbox">
                                                                <label>
                                                                    <input type="checkbox" name="require_uppercase" value="1"
                                                                           <?php echo $current_config['require_uppercase'] == 1 ? 'checked' : ''; ?>>
                                                                    Requiere al menos una letra mayúscula (A-Z)
                                                                </label>
                                                            </div>

                                                            <div class="checkbox">
                                                                <label>
                                                                    <input type="checkbox" name="require_lowercase" value="1"
                                                                           <?php echo $current_config['require_lowercase'] == 1 ? 'checked' : ''; ?>>
                                                                    Requiere al menos una letra minúscula (a-z)
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="checkbox">
                                                                <label>
                                                                    <input type="checkbox" name="require_number" value="1"
                                                                           <?php echo $current_config['require_number'] == 1 ? 'checked' : ''; ?>>
                                                                    Requiere al menos un número (0-9)
                                                                </label>
                                                            </div>

                                                            <div class="checkbox">
                                                                <label>
                                                                    <input type="checkbox" name="require_special_char" value="1"
                                                                           <?php echo $current_config['require_special_char'] == 1 ? 'checked' : ''; ?>>
                                                                    Requiere al menos un carácter especial
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label for="special_chars_allowed">Caracteres Especiales Permitidos</label>
                                                        <input type="text" class="form-control" id="special_chars_allowed" name="special_chars_allowed"
                                                               value="<?php echo htmlspecialchars($current_config['special_chars_allowed']); ?>"
                                                               required>
                                                        <small class="text-muted">Caracteres que se considerarán válidos como especiales</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- SECCIÓN 2: EXPIRACIÓN -->
                                            <div class="policy-card">
                                                <div class="policy-section">
                                                    <h3 class="policy-section-title">
                                                        <i class="fa fa-calendar"></i> Expiración y Renovación
                                                    </h3>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="password_expiry_days">Días Hasta Expiración</label>
                                                                <input type="number" class="form-control" id="password_expiry_days" name="password_expiry_days"
                                                                       value="<?php echo $current_config['password_expiry_days']; ?>"
                                                                       min="30" max="365" required>
                                                                <small class="text-muted">Entre 30 y 365 días</small>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="min_password_age_hours">Edad Mínima de Contraseña (horas)</label>
                                                                <input type="number" class="form-control" id="min_password_age_hours" name="min_password_age_hours"
                                                                       value="<?php echo $current_config['min_password_age_hours']; ?>"
                                                                       min="0" max="72" required>
                                                                <small class="text-muted">Tiempo mínimo entre cambios (0 = sin restricción)</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- SECCIÓN 3: HISTORIAL -->
                                            <div class="policy-card">
                                                <div class="policy-section">
                                                    <h3 class="policy-section-title">
                                                        <i class="fa fa-history"></i> Historial de Contraseñas
                                                    </h3>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="password_history_count">Contraseñas No Reutilizables</label>
                                                                <input type="number" class="form-control" id="password_history_count" name="password_history_count"
                                                                       value="<?php echo $current_config['password_history_count']; ?>"
                                                                       min="0" max="24" required>
                                                                <small class="text-muted">Número de contraseñas anteriores que no se pueden reutilizar (0 = sin historial)</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- SECCIÓN 4: BLOQUEO DE CUENTA -->
                                            <div class="policy-card">
                                                <div class="policy-section">
                                                    <h3 class="policy-section-title">
                                                        <i class="fa fa-ban"></i> Bloqueo de Cuenta
                                                    </h3>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="max_failed_attempts">Intentos Fallidos Permitidos</label>
                                                                <input type="number" class="form-control" id="max_failed_attempts" name="max_failed_attempts"
                                                                       value="<?php echo $current_config['max_failed_attempts']; ?>"
                                                                       min="3" max="10" required>
                                                                <small class="text-muted">Entre 3 y 10 intentos</small>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="lockout_duration_minutes">Duración del Bloqueo (minutos)</label>
                                                                <input type="number" class="form-control" id="lockout_duration_minutes" name="lockout_duration_minutes"
                                                                       value="<?php echo $current_config['lockout_duration_minutes']; ?>"
                                                                       min="5" max="120" required>
                                                                <small class="text-muted">Entre 5 y 120 minutos</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- SECCIÓN 5: BLOQUEO PROGRESIVO -->
                                            <div class="policy-card">
                                                <div class="policy-section">
                                                    <h3 class="policy-section-title">
                                                        <i class="fa fa-shield"></i> Bloqueo Progresivo de Cuenta
                                                    </h3>

                                                    <div class="alert alert-info">
                                                        <i class="fa fa-info-circle"></i> El sistema de bloqueo progresivo aumenta automáticamente el tiempo de bloqueo con cada reincidencia. Al alcanzar el límite configurado, la cuenta se bloqueará permanentemente y requerirá intervención del administrador.
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="checkbox">
                                                                <label>
                                                                    <input type="checkbox" name="progressive_lockout_enabled" value="1"
                                                                           <?php echo (isset($current_config['progressive_lockout_enabled']) && $current_config['progressive_lockout_enabled'] == 1) ? 'checked' : ''; ?>>
                                                                    <strong>Habilitar Bloqueo Progresivo</strong> (Si se desactiva, se usará duración fija de bloqueo)
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row" style="margin-top: 15px;">
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label for="lockout_1st_minutes">
                                                                    <i class="fa fa-clock-o"></i> Primer Bloqueo (minutos)
                                                                </label>
                                                                <input type="number" class="form-control" id="lockout_1st_minutes" name="lockout_1st_minutes"
                                                                       value="<?php echo isset($current_config['lockout_1st_minutes']) ? $current_config['lockout_1st_minutes'] : 30; ?>"
                                                                       min="5" max="1440" required>
                                                                <small class="text-muted">Entre 5 y 1440 min (24h)</small>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label for="lockout_2nd_minutes">
                                                                    <i class="fa fa-clock-o"></i> Segundo Bloqueo (minutos)
                                                                </label>
                                                                <input type="number" class="form-control" id="lockout_2nd_minutes" name="lockout_2nd_minutes"
                                                                       value="<?php echo isset($current_config['lockout_2nd_minutes']) ? $current_config['lockout_2nd_minutes'] : 120; ?>"
                                                                       min="5" max="2880" required>
                                                                <small class="text-muted">Entre 5 y 2880 min (48h)</small>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label for="lockout_3rd_minutes">
                                                                    <i class="fa fa-clock-o"></i> Tercer Bloqueo (minutos)
                                                                </label>
                                                                <input type="number" class="form-control" id="lockout_3rd_minutes" name="lockout_3rd_minutes"
                                                                       value="<?php echo isset($current_config['lockout_3rd_minutes']) ? $current_config['lockout_3rd_minutes'] : 1440; ?>"
                                                                       min="5" max="10080" required>
                                                                <small class="text-muted">Entre 5 y 10080 min (7 días)</small>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="lockout_permanent_after">
                                                                    <i class="fa fa-ban"></i> Bloqueo Permanente Después de
                                                                </label>
                                                                <input type="number" class="form-control" id="lockout_permanent_after" name="lockout_permanent_after"
                                                                       value="<?php echo isset($current_config['lockout_permanent_after']) ? $current_config['lockout_permanent_after'] : 4; ?>"
                                                                       min="2" max="10" required>
                                                                <small class="text-muted">Número de bloqueos antes del bloqueo permanente (2-10)</small>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="lockout_reset_days">
                                                                    <i class="fa fa-refresh"></i> Resetear Contador Después de (días)
                                                                </label>
                                                                <input type="number" class="form-control" id="lockout_reset_days" name="lockout_reset_days"
                                                                       value="<?php echo isset($current_config['lockout_reset_days']) ? $current_config['lockout_reset_days'] : 30; ?>"
                                                                       min="0" max="365" required>
                                                                <small class="text-muted">Días sin incidentes para resetear (0 = nunca resetear automáticamente)</small>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="alert alert-warning">
                                                        <strong><i class="fa fa-exclamation-triangle"></i> Ejemplo de escalamiento:</strong><br>
                                                        1er bloqueo → <span id="display_1st">30</span> min |
                                                        2do bloqueo → <span id="display_2nd">120</span> min |
                                                        3er bloqueo → <span id="display_3rd">1440</span> min |
                                                        <span id="display_permanent">4</span>to bloqueo → <strong>PERMANENTE</strong>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- SECCIÓN 6: TOKENS DE RECUPERACIÓN -->
                                            <div class="policy-card">
                                                <div class="policy-section">
                                                    <h3 class="policy-section-title">
                                                        <i class="fa fa-envelope"></i> Tokens de Recuperación
                                                    </h3>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="reset_token_expiry_minutes">Expiración del Token (minutos)</label>
                                                                <input type="number" class="form-control" id="reset_token_expiry_minutes" name="reset_token_expiry_minutes"
                                                                       value="<?php echo $current_config['reset_token_expiry_minutes']; ?>"
                                                                       min="10" max="120" required>
                                                                <small class="text-muted">Entre 10 y 120 minutos</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- BOTONES DE ACCIÓN -->
                                            <div class="text-center" style="margin-top: 30px; margin-bottom: 20px;">
                                                <button type="submit" class="btn btn-primary btn-lg">
                                                    <i class="fa fa-save"></i> Guardar Cambios
                                                </button>
                                                <button type="button" class="btn btn-warning btn-lg" onclick="restoreDefaults()">
                                                    <i class="fa fa-refresh"></i> Restaurar Valores por Defecto
                                                </button>
                                                <a href="dashboard.php" class="btn btn-default btn-lg">
                                                    <i class="fa fa-times"></i> Cancelar
                                                </a>
                                            </div>
                                        </form>

                                    </div>
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- TAB 2: ESTADÍSTICAS -->
                            <!-- ============================================ -->
                            <div role="tabpanel" class="tab-pane" id="tab-stats">
                                <div class="row">

                                    <!-- Tarjetas de Estadísticas -->
                                    <div class="col-md-3">
                                        <div class="stat-card">
                                            <div class="stat-label">
                                                <i class="fa fa-users"></i> Total de Usuarios
                                            </div>
                                            <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="stat-card warning">
                                            <div class="stat-label">
                                                <i class="fa fa-exclamation-triangle"></i> Próximas a Expirar
                                            </div>
                                            <div class="stat-number"><?php echo $stats['expiring_soon']; ?></div>
                                            <small>En los próximos 7 días</small>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="stat-card danger">
                                            <div class="stat-label">
                                                <i class="fa fa-lock"></i> Cuentas Bloqueadas
                                            </div>
                                            <div class="stat-number"><?php echo $stats['locked_accounts']; ?></div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="stat-card success">
                                            <div class="stat-label">
                                                <i class="fa fa-database"></i> Historial
                                            </div>
                                            <div class="stat-number"><?php echo $stats['history_count']; ?></div>
                                            <small>Contraseñas en historial</small>
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="policy-card">
                                            <h4><i class="fa fa-pie-chart"></i> Resumen del Sistema</h4>
                                            <hr>
                                            <table class="table">
                                                <tr>
                                                    <td><strong>Contraseñas Expiradas:</strong></td>
                                                    <td><span class="badge badge-danger"><?php echo $stats['expired']; ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Próximas a Expirar (7 días):</strong></td>
                                                    <td><span class="badge badge-warning"><?php echo $stats['expiring_soon']; ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Intentos Fallidos (24h):</strong></td>
                                                    <td><span class="badge badge-info"><?php echo $stats['failed_attempts_24h']; ?></span></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Cuentas Bloqueadas:</strong></td>
                                                    <td><span class="badge badge-danger"><?php echo $stats['locked_accounts']; ?></span></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="policy-card">
                                            <h4><i class="fa fa-cog"></i> Configuración Actual</h4>
                                            <hr>
                                            <table class="table table-sm">
                                                <tr>
                                                    <td><strong>Longitud Mínima:</strong></td>
                                                    <td><?php echo $current_config['min_length']; ?> caracteres</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Días de Expiración:</strong></td>
                                                    <td><?php echo $current_config['password_expiry_days']; ?> días</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Historial:</strong></td>
                                                    <td><?php echo $current_config['password_history_count']; ?> contraseñas</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Intentos Permitidos:</strong></td>
                                                    <td><?php echo $current_config['max_failed_attempts']; ?> intentos</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Duración Bloqueo:</strong></td>
                                                    <td><?php echo $current_config['lockout_duration_minutes']; ?> minutos</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Lista de usuarios con contraseñas próximas a expirar -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="policy-card">
                                            <h4><i class="fa fa-warning"></i> Usuarios con Contraseñas Próximas a Expirar</h4>
                                            <hr>

                                            <?php if (mysqli_num_rows($expiring_users) > 0): ?>
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Usuario</th>
                                                            <th>Email</th>
                                                            <th>Tipo</th>
                                                            <th>Expira</th>
                                                            <th>Días Restantes</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while ($user = mysqli_fetch_assoc($expiring_users)): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                            <td>
                                                                <span class="badge badge-info">
                                                                    <?php echo ucfirst($user['user_type']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('d/m/Y', strtotime($user['password_expires_at'])); ?></td>
                                                            <td>
                                                                <span class="badge <?php echo $user['days_left'] <= 3 ? 'badge-danger' : 'badge-warning'; ?>">
                                                                    <?php echo $user['days_left']; ?> días
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            <?php else: ?>
                                                <div class="alert alert-success">
                                                    <i class="fa fa-check-circle"></i> No hay usuarios con contraseñas próximas a expirar.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- ============================================ -->
                            <!-- TAB 3: AUDITORÍA -->
                            <!-- ============================================ -->
                            <div role="tabpanel" class="tab-pane" id="tab-audit">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="policy-card">
                                            <h4><i class="fa fa-history"></i> Historial de Cambios en Configuración</h4>
                                            <hr>

                                            <?php if (mysqli_num_rows($recent_changes) > 0): ?>
                                                <table class="table table-hover table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Configuración</th>
                                                            <th>Valor Actual</th>
                                                            <th>Modificado Por</th>
                                                            <th>Fecha de Cambio</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while ($change = mysqli_fetch_assoc($recent_changes)): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($change['setting_name']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($change['setting_value']); ?></code>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                if ($change['updated_by_name']) {
                                                                    echo htmlspecialchars($change['updated_by_name']);
                                                                    echo '<br><small class="text-muted">' . htmlspecialchars($change['updated_by_email']) . '</small>';
                                                                } else {
                                                                    echo '<span class="text-muted">Sistema</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                if ($change['updated_at']) {
                                                                    echo date('d/m/Y H:i:s', strtotime($change['updated_at']));
                                                                } else {
                                                                    echo '<span class="text-muted">-</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                        </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            <?php else: ?>
                                                <div class="alert alert-info">
                                                    <i class="fa fa-info-circle"></i> No hay cambios registrados en la configuración.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- TAB 4: SIMULADOR DE CONTRASEÑAS -->
                            <!-- ============================================ -->
                            <div role="tabpanel" class="tab-pane" id="tab-simulator">
                                <div class="row">
                                    <div class="col-md-8 col-md-offset-2">
                                        <div class="policy-card">
                                            <h4><i class="fa fa-flask"></i> Simulador de Contraseñas</h4>
                                            <p class="text-muted">Prueba una contraseña contra las políticas actuales del sistema</p>
                                            <hr>

                                            <div class="form-group">
                                                <label for="test_password">Ingrese una contraseña para probar:</label>
                                                <input type="password" class="form-control password-test-input" id="test_password"
                                                       placeholder="Escriba una contraseña aquí...">
                                                <div class="checkbox" style="margin-top: 10px;">
                                                    <label>
                                                        <input type="checkbox" id="show_password"> Mostrar contraseña
                                                    </label>
                                                </div>
                                            </div>

                                            <div id="password_feedback" style="margin-top: 20px;">
                                                <!-- El feedback se mostrará aquí dinámicamente -->
                                            </div>

                                            <div class="password-strength-meter">
                                                <div class="password-strength-bar" id="strength_bar" style="width: 0%;"></div>
                                            </div>
                                            <p id="strength_text" style="margin-top: 10px; font-weight: bold;"></p>

                                            <hr>

                                            <h5>Requisitos de Contraseña Actuales:</h5>
                                            <ul class="requirement-list" id="requirements_list">
                                                <li>
                                                    <i class="fa fa-circle-o"></i>
                                                    Longitud entre <?php echo $current_config['min_length']; ?> y <?php echo $current_config['max_length']; ?> caracteres
                                                </li>
                                                <?php if ($current_config['require_uppercase'] == 1): ?>
                                                <li>
                                                    <i class="fa fa-circle-o"></i>
                                                    Al menos una letra mayúscula (A-Z)
                                                </li>
                                                <?php endif; ?>
                                                <?php if ($current_config['require_lowercase'] == 1): ?>
                                                <li>
                                                    <i class="fa fa-circle-o"></i>
                                                    Al menos una letra minúscula (a-z)
                                                </li>
                                                <?php endif; ?>
                                                <?php if ($current_config['require_number'] == 1): ?>
                                                <li>
                                                    <i class="fa fa-circle-o"></i>
                                                    Al menos un número (0-9)
                                                </li>
                                                <?php endif; ?>
                                                <?php if ($current_config['require_special_char'] == 1): ?>
                                                <li>
                                                    <i class="fa fa-circle-o"></i>
                                                    Al menos un carácter especial (<?php echo htmlspecialchars($current_config['special_chars_allowed']); ?>)
                                                </li>
                                                <?php endif; ?>
                                                <li>
                                                    <i class="fa fa-circle-o"></i>
                                                    No puede contener espacios en blanco
                                                </li>
                                            </ul>
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

    <!-- Scripts -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/modernizr/modernizr.js"></script>
    <script src="vendor/jquery-cookie/jquery.cookie.js"></script>
    <script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="vendor/switchery/switchery.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/form-elements.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ============================================
        // RESTAURAR VALORES POR DEFECTO
        // ============================================
        function restoreDefaults() {
            Swal.fire({
                title: '¿Restaurar valores por defecto?',
                text: "Se perderán todas las configuraciones personalizadas",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, restaurar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Crear formulario y enviarlo
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';

                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'action';
                    input.value = 'restore_defaults';

                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // ============================================
        // VALIDACIÓN DE FORMULARIO
        // ============================================
        document.getElementById('policiesForm').addEventListener('submit', function(e) {
            var minLength = parseInt(document.getElementById('min_length').value);
            var maxLength = parseInt(document.getElementById('max_length').value);

            if (minLength > maxLength) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Validación',
                    text: 'La longitud mínima no puede ser mayor que la máxima'
                });
                return false;
            }
        });

        // ============================================
        // ACTUALIZAR EJEMPLO DE BLOQUEO PROGRESIVO
        // ============================================
        function updateLockoutExample() {
            var first = document.getElementById('lockout_1st_minutes').value;
            var second = document.getElementById('lockout_2nd_minutes').value;
            var third = document.getElementById('lockout_3rd_minutes').value;
            var permanent = document.getElementById('lockout_permanent_after').value;

            document.getElementById('display_1st').textContent = first;
            document.getElementById('display_2nd').textContent = second;
            document.getElementById('display_3rd').textContent = third;
            document.getElementById('display_permanent').textContent = permanent;
        }

        // Actualizar ejemplo cuando cambian los valores
        document.getElementById('lockout_1st_minutes').addEventListener('input', updateLockoutExample);
        document.getElementById('lockout_2nd_minutes').addEventListener('input', updateLockoutExample);
        document.getElementById('lockout_3rd_minutes').addEventListener('input', updateLockoutExample);
        document.getElementById('lockout_permanent_after').addEventListener('input', updateLockoutExample);

        // ============================================
        // SIMULADOR DE CONTRASEÑAS
        // ============================================

        // Configuración actual desde PHP
        var config = {
            min_length: <?php echo $current_config['min_length']; ?>,
            max_length: <?php echo $current_config['max_length']; ?>,
            require_uppercase: <?php echo $current_config['require_uppercase']; ?>,
            require_lowercase: <?php echo $current_config['require_lowercase']; ?>,
            require_number: <?php echo $current_config['require_number']; ?>,
            require_special_char: <?php echo $current_config['require_special_char']; ?>,
            special_chars_allowed: '<?php echo addslashes($current_config['special_chars_allowed']); ?>'
        };

        // Mostrar/ocultar contraseña
        document.getElementById('show_password').addEventListener('change', function() {
            var input = document.getElementById('test_password');
            input.type = this.checked ? 'text' : 'password';
        });

        // Validar contraseña en tiempo real
        document.getElementById('test_password').addEventListener('input', function() {
            var password = this.value;
            validatePasswordRealTime(password);
        });

        function validatePasswordRealTime(password) {
            var errors = [];
            var checks = [];

            // 1. Longitud
            if (password.length < config.min_length) {
                errors.push('Muy corta (mínimo ' + config.min_length + ' caracteres)');
                checks.push(false);
            } else if (password.length > config.max_length) {
                errors.push('Muy larga (máximo ' + config.max_length + ' caracteres)');
                checks.push(false);
            } else {
                checks.push(true);
            }

            // 2. Mayúscula
            if (config.require_uppercase == 1) {
                if (!/[A-Z]/.test(password)) {
                    errors.push('Falta letra mayúscula');
                    checks.push(false);
                } else {
                    checks.push(true);
                }
            }

            // 3. Minúscula
            if (config.require_lowercase == 1) {
                if (!/[a-z]/.test(password)) {
                    errors.push('Falta letra minúscula');
                    checks.push(false);
                } else {
                    checks.push(true);
                }
            }

            // 4. Número
            if (config.require_number == 1) {
                if (!/[0-9]/.test(password)) {
                    errors.push('Falta número');
                    checks.push(false);
                } else {
                    checks.push(true);
                }
            }

            // 5. Carácter especial
            if (config.require_special_char == 1) {
                var specialChars = config.special_chars_allowed.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                var regex = new RegExp('[' + specialChars + ']');
                if (!regex.test(password)) {
                    errors.push('Falta carácter especial');
                    checks.push(false);
                } else {
                    checks.push(true);
                }
            }

            // 6. Espacios
            if (/\s/.test(password)) {
                errors.push('No puede contener espacios');
                checks.push(false);
            } else {
                checks.push(true);
            }

            // Calcular fortaleza
            var strength = 0;
            if (password.length >= config.min_length) strength += 20;
            if (password.length >= config.min_length + 4) strength += 20;
            if (/[A-Z]/.test(password)) strength += 15;
            if (/[a-z]/.test(password)) strength += 15;
            if (/[0-9]/.test(password)) strength += 15;
            var specialCharsRegex = new RegExp('[' + config.special_chars_allowed.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ']');
            if (specialCharsRegex.test(password)) strength += 15;

            // Actualizar barra de fortaleza
            var strengthBar = document.getElementById('strength_bar');
            var strengthText = document.getElementById('strength_text');

            strengthBar.style.width = strength + '%';

            if (strength < 40) {
                strengthBar.className = 'password-strength-bar strength-weak';
                strengthText.textContent = 'Débil';
                strengthText.style.color = '#f44336';
            } else if (strength < 70) {
                strengthBar.className = 'password-strength-bar strength-medium';
                strengthText.textContent = 'Media';
                strengthText.style.color = '#ff9800';
            } else {
                strengthBar.className = 'password-strength-bar strength-strong';
                strengthText.textContent = 'Fuerte';
                strengthText.style.color = '#4caf50';
            }

            // Mostrar feedback
            var feedback = document.getElementById('password_feedback');

            if (password.length === 0) {
                feedback.innerHTML = '';
                strengthText.textContent = '';
                strengthBar.style.width = '0%';
                return;
            }

            if (errors.length === 0) {
                feedback.innerHTML = '<div class="alert alert-success"><i class="fa fa-check-circle"></i> <strong>¡Contraseña válida!</strong> Cumple con todas las políticas de seguridad.</div>';
            } else {
                var errorList = errors.map(function(err) {
                    return '<li>' + err + '</li>';
                }).join('');

                feedback.innerHTML = '<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <strong>Contraseña inválida:</strong><ul>' + errorList + '</ul></div>';
            }
        }

        // Auto-desaparecer alertas después de 5 segundos
        setTimeout(function() {
            $('.alert-dismissible').fadeOut('slow');
        }, 5000);
    </script>
</body>
</html>
