<?php
// Iniciar sesión y cargar configuraciones necesarias
session_start();
error_reporting(0);
include("include/config.php");
include("include/rbac-functions.php");
include("include/recaptcha-config.php");

// Variables para mensajes
$error_message = '';
$warning_message = '';
$success_message = '';

// Función para obtener IP del cliente
function getClientIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

// Función para registrar intento de login
function logLoginAttempt($con, $email, $user_id, $result, $ip, $user_agent) {
    $sql = "INSERT INTO login_attempts (email, user_id, ip_address, user_agent, attempt_result)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "sisss", $email, $user_id, $ip, $user_agent, $result);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Función para verificar reCAPTCHA v2
function verifyRecaptcha($token, $ip) {
    // Si reCAPTCHA está desactivado, retornar éxito
    if (!RECAPTCHA_ENABLED) {
        return ['success' => true, 'message' => 'reCAPTCHA desactivado'];
    }

    // Validar que el token no esté vacío
    if (empty($token)) {
        return ['success' => false, 'message' => 'Por favor, complete la verificación de reCAPTCHA'];
    }

    // Preparar datos para enviar a Google
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $ip
    ];

    // Configurar opciones de cURL
    $options = [
        CURLOPT_URL => RECAPTCHA_VERIFY_URL,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 10
    ];

    // Hacer petición a Google reCAPTCHA API
    $curl = curl_init();
    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    $curl_error = curl_error($curl);
    curl_close($curl);

    // Verificar si hubo error en la petición
    if ($curl_error) {
        return ['success' => false, 'message' => 'Error de conexión: ' . $curl_error];
    }

    // Decodificar respuesta JSON
    $result = json_decode($response, true);

    // Verificar respuesta válida
    if (!$result) {
        return ['success' => false, 'message' => 'Respuesta inválida de reCAPTCHA'];
    }

    // En reCAPTCHA v2, solo verificamos el campo 'success'
    if (isset($result['success']) && $result['success'] === true) {
        return [
            'success' => true,
            'message' => 'Verificación exitosa',
            'challenge_ts' => $result['challenge_ts'] ?? '',
            'hostname' => $result['hostname'] ?? ''
        ];
    }

    // Si llegamos aquí, la verificación falló
    return [
        'success' => false,
        'message' => 'Verificación de reCAPTCHA fallida',
        'error_codes' => $result['error-codes'] ?? []
    ];
}

// Función para calcular duración de bloqueo progresivo
function getProgressiveLockoutDuration($con, $lockout_count, $last_lockout_date) {
    // Obtener configuración
    $config_sql = "SELECT setting_name, setting_value FROM password_policy_config 
                   WHERE setting_name IN ('progressive_lockout_enabled', 'lockout_1st_minutes', 
                   'lockout_2nd_minutes', 'lockout_3rd_minutes', 'lockout_permanent_after', 'lockout_reset_days')";
    $config_result = mysqli_query($con, $config_sql);
    $config = [];
    while ($row = mysqli_fetch_assoc($config_result)) {
        $config[$row['setting_name']] = $row['setting_value'];
    }
    
    // Si no está habilitado, usar duración fija
    if (!isset($config['progressive_lockout_enabled']) || $config['progressive_lockout_enabled'] != '1') {
        return ['duration' => 30, 'is_permanent' => false, 'lockout_number' => 1];
    }
    
    // Verificar si debe resetear contador por tiempo transcurrido
    $reset_days = isset($config['lockout_reset_days']) ? (int)$config['lockout_reset_days'] : 30;
    if ($reset_days > 0 && $last_lockout_date && 
        strtotime($last_lockout_date) < strtotime("-{$reset_days} days")) {
        $lockout_count = 0; // Resetear
    }
    
    $lockout_count++; // Incrementar para este bloqueo
    
    // Determinar duración según número de bloqueo
    $permanent_after = isset($config['lockout_permanent_after']) ? (int)$config['lockout_permanent_after'] : 4;
    
    if ($lockout_count >= $permanent_after) {
        return ['duration' => 0, 'is_permanent' => true, 'lockout_number' => $lockout_count];
    }
    
    // Calcular duración progresiva
    $durations = [
        1 => isset($config['lockout_1st_minutes']) ? (int)$config['lockout_1st_minutes'] : 30,
        2 => isset($config['lockout_2nd_minutes']) ? (int)$config['lockout_2nd_minutes'] : 120,
        3 => isset($config['lockout_3rd_minutes']) ? (int)$config['lockout_3rd_minutes'] : 1440,
    ];
    
    $duration = isset($durations[$lockout_count]) ? $durations[$lockout_count] : $durations[3];
    
    return ['duration' => $duration, 'is_permanent' => false, 'lockout_number' => $lockout_count];
}

// ========== PROCESAR FORMULARIO DE LOGIN ==========
if (isset($_POST['submit'])) {
    $email = mysqli_real_escape_string($con, trim($_POST['email']));
    $password = $_POST['password'];
    $ip_address = getClientIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    // ========== VALIDACIÓN DE reCAPTCHA v2 ==========
    // En v2, el token viene en 'g-recaptcha-response'
    $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
    $recaptcha_result = verifyRecaptcha($recaptcha_token, $ip_address);

    if (!$recaptcha_result['success']) {
        // reCAPTCHA falló - posible bot o usuario no completó el captcha
        $error_message = $recaptcha_result['message'] ?? "Verificación de seguridad fallida. Por favor, intente nuevamente.";

        // Registrar intento bloqueado por captcha
        logLoginAttempt($con, $email, null, 'captcha_failed', $ip_address, $user_agent);
    }
    // Continuar solo si reCAPTCHA pasó
    elseif (empty($email) || empty($password)) {
        $error_message = "Por favor ingrese email y contraseña";
    }
    // Validar formato de correo corporativo
    elseif (!validateCorporateEmail($email)) {
        $error_message = "El correo electrónico no cumple con el formato corporativo requerido. Contacte al administrador.";
        logLoginAttempt($con, $email, null, 'invalid_email_format', $ip_address, $user_agent);
    }
    else {
        // Buscar usuario en la tabla unificada
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            // Usuario no existe
            $error_message = "Email o contraseña incorrectos";
            logLoginAttempt($con, $email, null, 'failed_user_not_found', $ip_address, $user_agent);
        } else {
            // Usuario existe, verificar estado de la cuenta

            // 1. Verificar si la cuenta está bloqueada
            if ($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
                $minutes_remaining = ceil((strtotime($user['account_locked_until']) - time()) / 60);
                $error_message = "Cuenta bloqueada por múltiples intentos fallidos. Inténtelo nuevamente en {$minutes_remaining} minuto(s).";
                logLoginAttempt($con, $email, $user['id'], 'account_locked', $ip_address, $user_agent);
            }
            // 2. Verificar si la cuenta está inactiva
            elseif ($user['status'] !== 'active') {
                $error_message = "Su cuenta está inactiva. Contacte al administrador.";
                logLoginAttempt($con, $email, $user['id'], 'account_inactive', $ip_address, $user_agent);
            }
            // 3. Verificar contraseña
            elseif (password_verify($password, $user['password'])) {
                // ========== LOGIN EXITOSO ==========

                // Verificar si debe cambiar contraseña
                if ($user['force_password_change'] == 1) {
                    $_SESSION['must_change_password'] = true;
                    $_SESSION['temp_user_id'] = $user['id'];
                    $_SESSION['temp_email'] = $user['email'];
                    header("location:change-password.php?force=1");
                    exit();
                }

                // Verificar si la contraseña está expirada
                if ($user['password_expires_at'] && strtotime($user['password_expires_at']) < time()) {
                    $warning_message = "Su contraseña ha expirado. Por favor, cámbiela.";
                    $_SESSION['password_expired'] = true;
                    $_SESSION['temp_user_id'] = $user['id'];
                    $_SESSION['temp_email'] = $user['email'];
                    header("location:change-password.php?expired=1");
                    exit();
                }

                // Verificar si está próxima a expirar (7 días)
                if ($user['password_expires_at']) {
                    $days_until_expiry = floor((strtotime($user['password_expires_at']) - time()) / 86400);
                    if ($days_until_expiry <= 7 && $days_until_expiry > 0) {
                        $_SESSION['password_expiry_warning'] = "Su contraseña expirará en {$days_until_expiry} día(s). Por favor, considere cambiarla.";
                    }
                }

                // Establecer sesión
                $_SESSION['login'] = $user['email'];
                $_SESSION['id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['full_name'] = $user['full_name'];

                // Actualizar información de login y resetear intentos fallidos
                $update_sql = "UPDATE users SET
                               last_login = NOW(),
                               last_login_ip = ?,
                               failed_login_attempts = 0,
                               lockout_count = 0,
                               last_lockout_date = NULL,
                               account_locked_until = NULL
                               WHERE id = ?";
                $update_stmt = mysqli_prepare($con, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $ip_address, $user['id']);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);

                // Registrar login exitoso
                logLoginAttempt($con, $email, $user['id'], 'success', $ip_address, $user_agent);

                // Redirigir según tipo de usuario
                switch ($user['user_type']) {
                    case 'patient':
                        header("location:dashboard1.php");
                        break;
                    case 'doctor':
                        header("location:doctor/dashboard.php");
                        break;
                    case 'admin':
                        header("location:admin/dashboard.php");
                        break;
                }
                exit();
            } else {
                // ========== CONTRASEÑA INCORRECTA ==========

                // Incrementar contador de intentos fallidos
                $failed_attempts = $user['failed_login_attempts'] + 1;

                // Obtener configuración de bloqueo
                $config_sql = "SELECT setting_value FROM password_policy_config
                               WHERE setting_name IN ('max_failed_attempts', 'lockout_duration_minutes')";
                $config_result = mysqli_query($con, $config_sql);
                $config = [];
                while ($row = mysqli_fetch_assoc($config_result)) {
                    $config[$row['setting_name']] = $row['setting_value'];
                }

                $max_attempts = isset($config['max_failed_attempts']) ? (int)$config['max_failed_attempts'] : 3;
                $lockout_minutes = isset($config['lockout_duration_minutes']) ? (int)$config['lockout_duration_minutes'] : 30;

                $attempts_remaining = $max_attempts - $failed_attempts;

                if ($failed_attempts >= $max_attempts) {
                    // BLOQUEAR CUENTA CON SISTEMA PROGRESIVO
                    
                    $lockout_info = getProgressiveLockoutDuration($con, $user['lockout_count'], $user['last_lockout_date']);
                    
                    if ($lockout_info['is_permanent']) {
                        // BLOQUEO PERMANENTE
                        $update_sql = "UPDATE users SET
                                       failed_login_attempts = ?,
                                       lockout_count = ?,
                                       last_lockout_date = NOW(),
                                       status = 'blocked'
                                       WHERE id = ?";
                        $update_stmt = mysqli_prepare($con, $update_sql);
                        mysqli_stmt_bind_param($update_stmt, "iii", $failed_attempts, $lockout_info['lockout_number'], $user['id']);
                        mysqli_stmt_execute($update_stmt);
                        mysqli_stmt_close($update_stmt);
                        
                        $error_message = "Su cuenta ha sido bloqueada permanentemente por múltiples intentos de acceso no autorizado. Contacte al administrador del sistema.";
                        logLoginAttempt($con, $email, $user['id'], 'permanently_blocked', $ip_address, $user_agent);
                    } else {
                        // BLOQUEO TEMPORAL PROGRESIVO
                        $lockout_until = date('Y-m-d H:i:s', strtotime("+{$lockout_info['duration']} minutes"));
                        
                        $update_sql = "UPDATE users SET
                                       failed_login_attempts = ?,
                                       account_locked_until = ?,
                                       lockout_count = ?,
                                       last_lockout_date = NOW()
                                       WHERE id = ?";
                        $update_stmt = mysqli_prepare($con, $update_sql);
                        mysqli_stmt_bind_param($update_stmt, "isii", $failed_attempts, $lockout_until, $lockout_info['lockout_number'], $user['id']);
                        mysqli_stmt_execute($update_stmt);
                        mysqli_stmt_close($update_stmt);
                        
                        $hours = floor($lockout_info['duration'] / 60);
                        $minutes = $lockout_info['duration'] % 60;
                        $time_text = $hours > 0 ? "{$hours} hora(s)" : "{$minutes} minuto(s)";
                        
                        $error_message = "Cuenta bloqueada por múltiples intentos fallidos (Bloqueo #{$lockout_info['lockout_number']}). Inténtelo nuevamente en {$time_text}.";
                        logLoginAttempt($con, $email, $user['id'], 'account_locked_progressive', $ip_address, $user_agent);
                    }
                } else {
                    // Actualizar contador
                    $update_sql = "UPDATE users SET failed_login_attempts = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($con, $update_sql);
                    mysqli_stmt_bind_param($update_stmt, "ii", $failed_attempts, $user['id']);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);

                    if ($attempts_remaining == 1) {
                        $error_message = "Email o contraseña incorrectos. ADVERTENCIA: Le queda {$attempts_remaining} intento antes de que su cuenta sea bloqueada.";
                    } else {
                        $error_message = "Email o contraseña incorrectos. Le quedan {$attempts_remaining} intentos.";
                    }

                    logLoginAttempt($con, $email, $user['id'], 'failed_password', $ip_address, $user_agent);
                }
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hospital Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Google reCAPTCHA v2 -->
    <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_ENABLED): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            max-width: 450px;
            margin: 0 auto;
            padding: 20px;
        }
        .login-card {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .login-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .login-body {
            padding: 40px;
        }
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .form-control {
            height: 50px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding: 10px 15px;
            font-size: 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
        /* Eliminado: estilos de badges de tipo de usuario (ya no se usan) */
        .recaptcha-container {
            display: flex;
            justify-content: center;
            margin: 20px 0;
            transform: scale(0.95);
            transform-origin: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <i class="fas fa-hospital fa-3x mb-3"></i>
                    <h2>Clínica Dental Muelitas</h2>
                    <p>Ingrese sus credenciales para continuar</p>
                </div>
                <div class="login-body">

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($warning_message): ?>
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $warning_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="loginForm">
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email Corporativo</label>
                            <input type="email" class="form-control" id="login_email" name="email" placeholder="Ingrese su email corporativo" required autofocus>
                            <small class="form-text text-danger" id="email_error" style="display:none;"></small>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Contraseña</label>
                            <input type="password" class="form-control" name="password" placeholder="Ingrese su contraseña" required>
                        </div>

                        <!-- reCAPTCHA v2 Visual Checkbox -->
                        <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_ENABLED): ?>
                        <div class="recaptcha-container">
                            <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                        </div>
                        <?php endif; ?>

                        <button type="submit" name="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </button>
                    </form>

                    <div class="login-footer">
                        <p class="mt-3">
                            <a href="forgot-password.php"><i class="fas fa-question-circle"></i> ¿Olvidó su contraseña?</a>
                        </p>
                        <p>¿No tiene cuenta? <a href="registration.php">Regístrese aquí</a></p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <small class="text-white">&copy; <?php echo date('Y'); ?> HMS. Todos los derechos reservados.</small>
            </div>
        </div>
    </div>

    <?php echo getEmailConfigForJS(); ?>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="assets/js/email-validation.js"></script>
    <script>
        $(document).ready(function() {
            // Validar email en tiempo real
            $('#login_email').on('blur change', function() {
                var email = $(this).val().trim();

                if (!email) {
                    $(this).removeClass('is-invalid is-valid');
                    $('#email_error').hide();
                    return;
                }

                if (validateCorporateEmailFormat(email)) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                    $('#email_error').hide();
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                    $('#email_error').text(getEmailValidationError(email)).show();
                }
            });

            // Validar antes de enviar el formulario
            $('#loginForm').on('submit', function(e) {
                var email = $('#login_email').val().trim();

                // Validar email corporativo
                if (!validateCorporateEmailFormat(email)) {
                    e.preventDefault();
                    $('#login_email').removeClass('is-valid').addClass('is-invalid');
                    $('#email_error').text(getEmailValidationError(email)).show();
                    $('#login_email').focus();
                    return false;
                }

                <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_ENABLED): ?>
                // Validar que el usuario haya completado el reCAPTCHA
                var recaptchaResponse = grecaptcha.getResponse();

                if (recaptchaResponse.length === 0) {
                    e.preventDefault();
                    alert('Por favor, complete la verificación de reCAPTCHA (marque la casilla "No soy un robot").');
                    return false;
                }
                <?php endif; ?>

                // Si todas las validaciones pasaron, permitir el envío del formulario
                return true;
            });
        });
    </script>
</body>
</html>
