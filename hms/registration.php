<?php
session_start();
include_once('include/config.php');
require_once('include/csrf-protection.php');
require_once('include/password-policy.php');
require_once('include/rbac-functions.php');

$form_error = '';
$form_success = '';
$field_errors = [];

// Preservar valores del formulario
$form_data = [
    'full_name' => $_POST['full_name'] ?? '',
    'address' => $_POST['address'] ?? '',
    'city' => $_POST['city'] ?? '',
    'gender' => $_POST['gender'] ?? '',
    'email' => $_POST['email'] ?? ''
];

if (isset($_POST['submit'])) {
    if (!csrf_validate()) {
        $form_error = 'Solicitud inválida. Actualiza la página e inténtalo de nuevo.';
    } else {
        $fname = mysqli_real_escape_string($con, trim($_POST['full_name'] ?? ''));
        $address = mysqli_real_escape_string($con, trim($_POST['address'] ?? ''));
        $city = mysqli_real_escape_string($con, trim($_POST['city'] ?? ''));
        $gender = mysqli_real_escape_string($con, trim($_POST['gender'] ?? ''));
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $password_again = $_POST['password_again'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $form_error = 'El correo electrónico no es válido.';
            $field_errors['email'] = true;
        }

        if (!$form_error && $password !== $password_again) {
            $form_error = 'La contraseña y su confirmación no coinciden.';
            $field_errors['password'] = true;
            $field_errors['password_again'] = true;
        }

        if (!$form_error) {
            $pwdValidation = validate_password_simple($password, $con);
            if (!$pwdValidation['valid']) {
                $form_error = implode('<br>', $pwdValidation['errors']);
                $field_errors['password'] = true;
            }
        }

        if (!$form_error) {
            $check_sql = "SELECT id FROM users WHERE email = ?";
            $check_stmt = mysqli_prepare($con, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "s", $email);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $form_error = 'El correo ya está registrado. Usa uno diferente.';
                $field_errors['email'] = true;
            }
            mysqli_stmt_close($check_stmt);
        }

        if (!$form_error) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            mysqli_begin_transaction($con);
            try {
                $user_sql = "INSERT INTO users (email, password, user_type, full_name, status, created_at) VALUES (?, ?, 'patient', ?, 'active', NOW())";
                $user_stmt = mysqli_prepare($con, $user_sql);
                mysqli_stmt_bind_param($user_stmt, "sss", $email, $hashed_password, $fname);
                if (!mysqli_stmt_execute($user_stmt)) {
                    throw new Exception('Error al insertar usuario');
                }
                $user_id = mysqli_insert_id($con);
                mysqli_stmt_close($user_stmt);

                $patient_sql = "INSERT INTO patients (user_id, address, city, gender) VALUES (?, ?, ?, ?)";
                $patient_stmt = mysqli_prepare($con, $patient_sql);
                mysqli_stmt_bind_param($patient_stmt, "isss", $user_id, $address, $city, $gender);
                if (!mysqli_stmt_execute($patient_stmt)) {
                    throw new Exception('Error al insertar paciente');
                }
                mysqli_stmt_close($patient_stmt);

                // ✅ ASIGNAR AUTOMÁTICAMENTE EL ROL DE PACIENTE
                $rbac = new RBAC($con);
                $role_assigned = false;
                
                // Obtener el ID del rol "patient"
                $role_query = "SELECT id FROM roles WHERE role_name = 'patient' AND status = 'active' LIMIT 1";
                $role_result = mysqli_query($con, $role_query);
                
                if ($role_result && $role_row = mysqli_fetch_assoc($role_result)) {
                    $patient_role_id = $role_row['id'];
                    
                    // Asignar el rol usando RBAC (que ya sabemos que funciona)
                    $role_assignment = $rbac->assignRoleToUser($user_id, $patient_role_id, $user_id);
                    
                    if ($role_assignment['success']) {
                        $role_assigned = true;
                    } else {
                        // Log del error pero no fallar el registro completo
                        error_log("No se pudo asignar rol de paciente al usuario $user_id: " . $role_assignment['message']);
                    }
                }

                mysqli_commit($con);
                
                if ($role_assigned) {
                    $form_success = '✅ Registro exitoso con rol de Paciente asignado. Ahora puedes iniciar sesión.';
                } else {
                    $form_success = 'Registro exitoso. Ahora puedes iniciar sesión.';
                }
                // Limpiar datos del formulario solo si es exitoso
                $form_data = ['full_name' => '', 'address' => '', 'city' => '', 'gender' => '', 'email' => ''];
                echo "<script>setTimeout(function(){ window.location.href='login.php'; }, 1500);</script>";
            } catch (Exception $e) {
                mysqli_rollback($con);
                $form_error = 'No se pudo completar el registro.';
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

    <head>
        <title>Registro de Usuario | HMS</title>
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
        <style>
            body.login {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 40px 0;
            }
            .main-login {
                animation: slideInUp 0.5s ease;
                margin-top: 20px;
                margin-bottom: 20px;
            }
            @keyframes slideInUp {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .box-register {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                padding: 30px 40px;
                backdrop-filter: blur(10px);
                max-width: 100%;
            }
            .logo h2 {
                background: linear-gradient(135deg, #1b53a8ff 0%, #764ba2 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                font-weight: 700;
            }
            .form-control {
                border-radius: 10px;
                border: 2px solid #e0e0e0;
                padding: 12px 16px;
                transition: all 0.3s ease;
            }
            .form-control:focus {
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            }
            .form-control.error {
                border-color: #f44336;
                background-color: #ffebee;
            }
            .form-control.success {
                border-color: #4caf50;
                background-color: #f1f8f4;
            }
            .input-icon {
                position: relative;
            }
            .input-icon i {
                position: absolute;
                right: 16px;
                top: 50%;
                transform: translateY(-50%);
                color: #999;
            }
            .btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                border-radius: 10px;
                padding: 12px 32px;
                font-weight: 600;
                transition: all 0.3s ease;
                color: white !important;
            }
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(102,126,234,0.4);
                background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            }
            .btn-block {
                width: 100%;
                display: block;
            }
            .form-actions {
                margin-top: 25px;
                padding-top: 20px;
                border-top: 1px solid #e0e0e0;
            }
            .password-strength {
                height: 4px;
                border-radius: 2px;
                margin-top: 8px;
                transition: all 0.3s ease;
                background: #e0e0e0;
            }
            .password-strength.weak { background: #f44336; width: 33%; }
            .password-strength.medium { background: #ff9800; width: 66%; }
            .password-strength.strong { background: #4caf50; width: 100%; }
            .password-feedback {
                font-size: 12px;
                margin-top: 4px;
                min-height: 18px;
            }
            .password-feedback.weak { color: #f44336; }
            .password-feedback.medium { color: #ff9800; }
            .password-feedback.strong { color: #4caf50; }
            .requirements-list {
                background: #f5f5f5;
                border-radius: 8px;
                padding: 12px;
                margin-top: 8px;
            }
            .requirements-list small {
                display: block;
                margin: 4px 0;
                color: #666;
                font-size: 11px;
            }
            .requirements-list small.met {
                color: #4caf50;
            }
            .requirements-list small.met:before {
                content: '✓ ';
                font-weight: bold;
            }
            .requirements-list small.unmet:before {
                content: '✗ ';
                font-weight: bold;
                color: #f44336;
            }
        </style>
        
        <script type="text/javascript">
function valid()
{
 if(document.registration.password.value!= document.registration.password_again.value)
{
alert("Password and Confirm Password Field do not match  !!");
document.registration.password_again.focus();
return false;
}
return true;
}
</script>
        

    </head>

    <body class="login">
        <!-- start: REGISTRATION -->
        <div class="row">
            <div class="main-login col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3">
                <div class="logo margin-top-30">
                <a href="../index.html"><h2>HMS | Registro de paciente</h2></a>
                </div>
                <!-- start: REGISTER BOX -->
                <div class="box-register">
                    <?php if (!empty($form_error)): ?>
                        <div class="alert alert-danger" role="alert" style="margin-bottom:15px;">
                            <?php echo $form_error; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($form_success)): ?>
                        <div class="alert alert-success" role="alert" style="margin-bottom:15px;">
                            <?php echo $form_success; ?>
                        </div>
                    <?php endif; ?>
                    <form name="registration" id="registration"  method="post" onSubmit="return valid();">
                        <fieldset>
                            <legend>
                                Sign Up
                            </legend>
                            <p>
                                Ponga su información personal:
                            </p>
                            <div class="form-group">
                                <input type="text" class="form-control <?php echo isset($field_errors['full_name']) ? 'error' : ''; ?>" name="full_name" placeholder="Nombre Completo" value="<?php echo htmlspecialchars($form_data['full_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control <?php echo isset($field_errors['address']) ? 'error' : ''; ?>" name="address" placeholder="Dirección" value="<?php echo htmlspecialchars($form_data['address']); ?>" required>
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control <?php echo isset($field_errors['city']) ? 'error' : ''; ?>" name="city" placeholder="Ciudad" value="<?php echo htmlspecialchars($form_data['city']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="block">
                                    Género
                                </label>
                                <div class="clip-radio radio-primary">
                                    <input type="radio" id="rg-female" name="gender" value="female" <?php echo ($form_data['gender'] == 'female') ? 'checked' : ''; ?>>
                                    <label for="rg-female">
                                        Femenino
                                    </label>
                                    <input type="radio" id="rg-male" name="gender" value="male" <?php echo ($form_data['gender'] == 'male') ? 'checked' : ''; ?>>
                                    <label for="rg-male">
                                        Masculino
                                    </label>
                                </div>
                            </div>
                            <p>
                                Ponga su información de cuenta:
                            </p>
                            <div class="form-group">
                                <span class="input-icon">
                                    <input type="email" class="form-control <?php echo isset($field_errors['email']) ? 'error' : ''; ?>" name="email" id="email" onBlur="userAvailability()" placeholder="Correo electrónico" value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
                                    <i class="fa fa-envelope"></i> </span>
                                     <span id="user-availability-status1" style="font-size:12px;"></span>
                            </div>
                            <div class="form-group">
                                <span class="input-icon">
                                    <input type="password" class="form-control <?php echo isset($field_errors['password']) ? 'error' : ''; ?>" id="password" name="password" placeholder="Contraseña" required>
                                    <i class="fa fa-lock"></i>
                                </span>
                                <div class="password-strength" id="strengthBar"></div>
                                <div class="password-feedback" id="strengthText"></div>
                            </div>
                            <div class="form-group">
                                <span class="input-icon">
                                    <input type="password" class="form-control <?php echo isset($field_errors['password_again']) ? 'error' : ''; ?>" id="password_again" name="password_again" placeholder="Confirmar contraseña" required>
                                    <i class="fa fa-lock"></i> </span>
                            </div>
                            <div class="form-group">
                                <div class="requirements-list">
                                    <small id="req-length" class="unmet">Mínimo 8 caracteres</small>
                                    <small id="req-upper" class="unmet">Una letra mayúscula</small>
                                    <small id="req-lower" class="unmet">Una letra minúscula</small>
                                    <small id="req-number" class="unmet">Un número</small>
                                    <small id="req-special" class="unmet">Un carácter especial</small>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox clip-check check-primary">
                                    <input type="checkbox" id="agree" value="agree" checked="true" readonly=" true">
                                    <label for="agree">
                                        Acepto los términos y condiciones
                                    </label>
                                </div>
                            </div>
                            <div class="form-actions" style="margin-top: 20px;">
                                <?php echo csrf_token_field(); ?>
                                <button type="submit" class="btn btn-primary btn-block" id="submit" name="submit" value="1" style="padding: 14px; font-size: 16px; font-weight: 600; margin-bottom: 15px;">
                                    <i class="fa fa-user-plus"></i> Registrarse
                                </button>
                                <p style="text-align: center; margin: 0;">
                                    ¿Ya tienes una cuenta?
                                    <a href="login.php" style="font-weight: 600; color: #667eea;">
                                        Inicia sesión aquí
                                    </a>
                                </p>
                            </div>
                        </fieldset>
                    </form>

                    <div class="copyright">
                        &copy; <span class="current-year"></span><span class="text-bold text-uppercase"> HMS</span>. <span>All rights reserved</span>
                    </div>

                </div>

			</div>
		</div>
		<script src="vendor/jquery/jquery.min.js"></script>
		<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
		<script src="vendor/modernizr/modernizr.js"></script>
		<script src="vendor/jquery-cookie/jquery.cookie.js"></script>
		<script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
		<script src="vendor/switchery/switchery.min.js"></script>
		<script src="vendor/jquery-validation/jquery.validate.min.js"></script>
		<script src="assets/js/main.js"></script>
		<script src="assets/js/login.js"></script>
		<script>
			jQuery(document).ready(function() {
				Main.init();
				Login.init();
			});
		</script>
		
	<script>
function userAvailability() {
$("#loaderIcon").show();
jQuery.ajax({
url: "check_availability.php",
data:'email='+$("#email").val(),
type: "POST",
success:function(data){
$("#user-availability-status1").html(data);
$("#loaderIcon").hide();
},
error:function (){}
});
}

// Validación en tiempo real de contraseña
jQuery(document).ready(function() {
    $('#password').on('input', function() {
        var password = $(this).val();
        var strengthBar = $('#strengthBar');
        var strengthText = $('#strengthText');
        
        // Verificar requisitos
        var hasLength = password.length >= 8;
        var hasUpper = /[A-Z]/.test(password);
        var hasLower = /[a-z]/.test(password);
        var hasNumber = /[0-9]/.test(password);
        var hasSpecial = /[@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(password);
        
        // Actualizar indicadores visuales
        $('#req-length').toggleClass('met', hasLength).toggleClass('unmet', !hasLength);
        $('#req-upper').toggleClass('met', hasUpper).toggleClass('unmet', !hasUpper);
        $('#req-lower').toggleClass('met', hasLower).toggleClass('unmet', !hasLower);
        $('#req-number').toggleClass('met', hasNumber).toggleClass('unmet', !hasNumber);
        $('#req-special').toggleClass('met', hasSpecial).toggleClass('unmet', !hasSpecial);
        
        // Calcular fortaleza
        var strength = 0;
        if (hasLength) strength++;
        if (hasUpper) strength++;
        if (hasLower) strength++;
        if (hasNumber) strength++;
        if (hasSpecial) strength++;
        
        // Actualizar barra y texto de fortaleza
        strengthBar.removeClass('weak medium strong');
        strengthText.removeClass('weak medium strong');
        
        if (password.length === 0) {
            strengthBar.removeClass('weak medium strong');
            strengthText.text('');
        } else if (strength < 3) {
            strengthBar.addClass('weak');
            strengthText.addClass('weak').text('Débil');
        } else if (strength < 5) {
            strengthBar.addClass('medium');
            strengthText.addClass('medium').text('Media');
        } else {
            strengthBar.addClass('strong');
            strengthText.addClass('strong').text('Fuerte');
            $('#password').removeClass('error').addClass('success');
        }
    });
    
    // Validar confirmación de contraseña
    $('#password_again').on('input', function() {
        var password = $('#password').val();
        var confirm = $(this).val();
        
        if (confirm.length > 0) {
            if (password === confirm) {
                $(this).removeClass('error').addClass('success');
            } else {
                $(this).removeClass('success').addClass('error');
            }
        }
    });
});
</script>	
		
	</body>
	<!-- end: BODY -->
</html>