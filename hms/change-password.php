<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
include('include/password-policy.php');

check_login();

$success_message = '';
$error_message = '';
$warning_message = '';
$info_message = '';

// Verificar si es cambio forzado o por expiración
$is_forced = isset($_GET['force']) && $_GET['force'] == 1;
$is_expired = isset($_GET['expired']) && $_GET['expired'] == 1;

// Instanciar clase de políticas
$policy = new PasswordPolicy($con);

// Obtener requisitos para mostrar en UI
$requirements = $policy->getPasswordRequirements();

// Verificar si debe cambiar contraseña por expiración
if ($is_expired || $is_forced) {
    $warning_message = $is_expired ?
        "Su contraseña ha expirado. Debe cambiarla para continuar." :
        "El administrador requiere que cambie su contraseña.";
}

// Mostrar advertencia si la contraseña expirará pronto
if (isset($_SESSION['password_expiry_warning'])) {
    $info_message = $_SESSION['password_expiry_warning'];
    unset($_SESSION['password_expiry_warning']);
}

if (isset($_POST['submit'])) {
    $user_id = $_SESSION['id'];
    $current_password = $_POST['cpass'];
    $new_password = $_POST['npass'];
    $confirm_password = $_POST['cfpass'];

    // Validaciones básicas
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Todos los campos son obligatorios";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "La nueva contraseña y la confirmación no coinciden";
    } else {
        // Verificar contraseña actual
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            $error_message = "Usuario no encontrado";
        } elseif (!password_verify($current_password, $user['password'])) {
            $error_message = "La contraseña actual es incorrecta";
        } else {
            // Usar la clase PasswordPolicy para cambiar la contraseña
            $change_result = $policy->changePassword($user_id, $new_password, $user_id);

            if ($change_result['success']) {
                $success_message = $change_result['message'];

                // Limpiar sesiones temporales si existían
                unset($_SESSION['must_change_password']);
                unset($_SESSION['password_expired']);
                unset($_SESSION['temp_user_id']);
                unset($_SESSION['temp_email']);

                // Redirigir después de 2 segundos
                header("refresh:2;url=dashboard1.php");
            } else {
                $error_message = $change_result['message'];
            }
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Cambiar Contraseña - HMS</title>

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
        .password-requirements {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 20px;
        }
        .password-requirements ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .password-requirements li {
            margin-bottom: 5px;
            font-size: 13px;
        }
        .password-strength {
            height: 5px;
            background: #e0e0e0;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            transition: all 0.3s;
        }
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
        .requirement-check {
            font-size: 12px;
            margin-top: 10px;
        }
        .requirement-check .valid {
            color: #28a745;
        }
        .requirement-check .invalid {
            color: #dc3545;
        }
    </style>

    <script type="text/javascript">
        function validatePassword() {
            var currentPass = document.chngpwd.cpass.value;
            var newPass = document.chngpwd.npass.value;
            var confirmPass = document.chngpwd.cfpass.value;

            if (currentPass == "") {
                alert("Por favor ingrese su contraseña actual");
                document.chngpwd.cpass.focus();
                return false;
            }

            if (newPass == "") {
                alert("Por favor ingrese la nueva contraseña");
                document.chngpwd.npass.focus();
                return false;
            }

            if (confirmPass == "") {
                alert("Por favor confirme la nueva contraseña");
                document.chngpwd.cfpass.focus();
                return false;
            }

            if (newPass !== confirmPass) {
                alert("La nueva contraseña y la confirmación no coinciden");
                document.chngpwd.cfpass.focus();
                return false;
            }

            if (newPass.length < 8) {
                alert("La contraseña debe tener al menos 8 caracteres");
                document.chngpwd.npass.focus();
                return false;
            }

            return true;
        }

        function checkPasswordStrength() {
            var password = document.getElementById('npass').value;
            var strengthBar = document.getElementById('strength-bar');
            var strengthText = document.getElementById('strength-text');

            var strength = 0;
            var checks = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(password)
            };

            // Actualizar checks visuales
            document.getElementById('check-length').className = checks.length ? 'valid' : 'invalid';
            document.getElementById('check-uppercase').className = checks.uppercase ? 'valid' : 'invalid';
            document.getElementById('check-lowercase').className = checks.lowercase ? 'valid' : 'invalid';
            document.getElementById('check-number').className = checks.number ? 'valid' : 'invalid';
            document.getElementById('check-special').className = checks.special ? 'valid' : 'invalid';

            // Calcular fortaleza
            if (checks.length) strength++;
            if (checks.uppercase) strength++;
            if (checks.lowercase) strength++;
            if (checks.number) strength++;
            if (checks.special) strength++;

            // Actualizar barra de fortaleza
            strengthBar.className = 'password-strength-bar';
            if (strength < 3) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Débil';
                strengthText.style.color = '#dc3545';
            } else if (strength < 5) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Media';
                strengthText.style.color = '#ffc107';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Fuerte';
                strengthText.style.color = '#28a745';
            }
        }

        function togglePasswordVisibility(fieldId, iconId) {
            var field = document.getElementById(fieldId);
            var icon = document.getElementById(iconId);

            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
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
                                <h1 class="mainTitle"><i class="fa fa-key"></i> Cambiar Contraseña</h1>
                            </div>
                            <ol class="breadcrumb">
                                <li><span>Usuario</span></li>
                                <li class="active"><span>Cambiar Contraseña</span></li>
                            </ol>
                        </div>
                    </section>

                    <div class="container-fluid container-fullw bg-white">
                        <div class="row">
                            <div class="col-md-8 col-md-offset-2">
                                <!-- Mensajes -->
                                <?php if ($success_message): ?>
                                    <div class="alert alert-success alert-dismissible fade show">
                                        <i class="fa fa-check-circle"></i> <?php echo $success_message; ?>
                                        <p class="mt-2 mb-0"><em>Redirigiendo al dashboard...</em></p>
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    </div>
                                <?php endif; ?>

                                <?php if ($error_message): ?>
                                    <div class="alert alert-danger alert-dismissible" style="display: block;">
                                        <i class="fa fa-exclamation-circle"></i> <strong>Error:</strong> <?php echo $error_message; ?>
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    </div>
                                <?php endif; ?>

                                <?php if ($warning_message): ?>
                                    <div class="alert alert-warning alert-dismissible fade show">
                                        <i class="fa fa-exclamation-triangle"></i> <?php echo $warning_message; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($info_message): ?>
                                    <div class="alert alert-info alert-dismissible fade show">
                                        <i class="fa fa-info-circle"></i> <?php echo $info_message; ?>
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    </div>
                                <?php endif; ?>

                                <!-- Requisitos de contraseña -->
                                <div class="password-requirements">
                                    <h5><i class="fa fa-shield"></i> Requisitos de Contraseña</h5>
                                    <ul>
                                        <?php foreach ($requirements as $req): ?>
                                            <li><?php echo htmlspecialchars($req); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <!-- Formulario -->
                                <div class="panel panel-white">
                                    <div class="panel-heading">
                                        <h5 class="panel-title">Cambiar Contraseña</h5>
                                    </div>
                                    <div class="panel-body">
                                        <form role="form" name="chngpwd" method="post" onSubmit="return validatePassword();">

                                            <!-- Contraseña Actual -->
                                            <div class="form-group">
                                                <label for="cpass">
                                                    <i class="fa fa-lock"></i> Contraseña Actual
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="password" id="cpass" name="cpass" class="form-control" placeholder="Ingrese su contraseña actual" required>
                                                    <span class="input-group-addon" style="cursor: pointer;" onclick="togglePasswordVisibility('cpass', 'icon-cpass')">
                                                        <i class="fa fa-eye" id="icon-cpass"></i>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Nueva Contraseña -->
                                            <div class="form-group">
                                                <label for="npass">
                                                    <i class="fa fa-key"></i> Nueva Contraseña
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="password" id="npass" name="npass" class="form-control" placeholder="Ingrese la nueva contraseña" onkeyup="checkPasswordStrength()" required>
                                                    <span class="input-group-addon" style="cursor: pointer;" onclick="togglePasswordVisibility('npass', 'icon-npass')">
                                                        <i class="fa fa-eye" id="icon-npass"></i>
                                                    </span>
                                                </div>

                                                <!-- Indicador de fortaleza -->
                                                <div class="password-strength">
                                                    <div class="password-strength-bar" id="strength-bar"></div>
                                                </div>
                                                <small id="strength-text"></small>

                                                <!-- Checks en vivo -->
                                                <div class="requirement-check">
                                                    <small id="check-length" class="invalid"><i class="fa fa-circle"></i> Mínimo 8 caracteres</small><br>
                                                    <small id="check-uppercase" class="invalid"><i class="fa fa-circle"></i> Una mayúscula</small><br>
                                                    <small id="check-lowercase" class="invalid"><i class="fa fa-circle"></i> Una minúscula</small><br>
                                                    <small id="check-number" class="invalid"><i class="fa fa-circle"></i> Un número</small><br>
                                                    <small id="check-special" class="invalid"><i class="fa fa-circle"></i> Un carácter especial</small>
                                                </div>
                                            </div>

                                            <!-- Confirmar Contraseña -->
                                            <div class="form-group">
                                                <label for="cfpass">
                                                    <i class="fa fa-check"></i> Confirmar Nueva Contraseña
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="password" id="cfpass" name="cfpass" class="form-control" placeholder="Confirme la nueva contraseña" required>
                                                    <span class="input-group-addon" style="cursor: pointer;" onclick="togglePasswordVisibility('cfpass', 'icon-cfpass')">
                                                        <i class="fa fa-eye" id="icon-cfpass"></i>
                                                    </span>
                                                </div>
                                            </div>

                                            <hr>

                                            <!-- Botones -->
                                            <div class="form-group">
                                                <button type="submit" name="submit" class="btn btn-primary btn-lg">
                                                    <i class="fa fa-save"></i> Cambiar Contraseña
                                                </button>
                                                <?php if (!$is_forced && !$is_expired): ?>
                                                    <a href="dashboard1.php" class="btn btn-default btn-lg">
                                                        <i class="fa fa-times"></i> Cancelar
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Información adicional -->
                                <div class="alert alert-info">
                                    <h5><i class="fa fa-info-circle"></i> Consejos de Seguridad</h5>
                                    <ul class="mb-0">
                                        <li>Use una contraseña única que no utilice en otros sitios</li>
                                        <li>Considere usar un gestor de contraseñas como LastPass o Bitwarden</li>
                                        <li>No comparta su contraseña con nadie</li>
                                        <li>Cambie su contraseña regularmente</li>
                                        <li>Su contraseña expirará cada 90 días</li>
                                    </ul>
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
    <script src="assets/js/main.js"></script>
    <script>
        jQuery(document).ready(function() {
            Main.init();
        });
    </script>
</body>
</html>
