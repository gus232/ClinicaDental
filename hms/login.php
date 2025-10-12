<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hospital Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        .user-type-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 5px;
        }
        .badge-patient { background-color: #e3f2fd; color: #1976d2; }
        .badge-doctor { background-color: #f3e5f5; color: #7b1fa2; }
        .badge-admin { background-color: #fce4ec; color: #c2185b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <i class="fas fa-hospital fa-3x mb-3"></i>
                    <h2>Hospital Management System</h2>
                    <p>Ingrese sus credenciales para continuar</p>
                </div>
                <div class="login-body">
                    <?php
                    session_start();
                    error_reporting(0);
                    include("include/config.php");

                    $error_message = '';

                    if (isset($_POST['submit'])) {
                        $email = mysqli_real_escape_string($con, trim($_POST['email']));
                        $password = $_POST['password'];

                        if (empty($email) || empty($password)) {
                            $error_message = "Por favor ingrese email y contraseña";
                        } else {
                            // Buscar usuario en la tabla unificada
                            $sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
                            $stmt = mysqli_prepare($con, $sql);
                            mysqli_stmt_bind_param($stmt, "s", $email);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $user = mysqli_fetch_assoc($result);

                            if ($user && password_verify($password, $user['password'])) {
                                // Login exitoso
                                $_SESSION['login'] = $user['email'];
                                $_SESSION['id'] = $user['id'];
                                $_SESSION['user_type'] = $user['user_type'];
                                $_SESSION['full_name'] = $user['full_name'];

                                // Actualizar �ltimo login
                                $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                                $update_stmt = mysqli_prepare($con, $update_sql);
                                mysqli_stmt_bind_param($update_stmt, "i", $user['id']);
                                mysqli_stmt_execute($update_stmt);

                                // Redirigir seg�n tipo de usuario
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
                                $error_message = "Email o contraseña incorrectos";
                            }
                            mysqli_stmt_close($stmt);
                        }
                    }
                    ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" class="form-control" name="email" placeholder="Ingrese su email" required autofocus>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Contraseña</label>
                            <input type="password" class="form-control" name="password" placeholder="Ingrese su contraseña" required>
                        </div>

                        <button type="submit" name="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </button>
                    </form>

                    <div class="login-footer">
                        <p class="text-muted mb-2">
                            <small>El sistema detectá automáticamente su tipo de usuario</small>
                        </p>
                        <div>
                            <span class="user-type-badge badge-patient"><i class="fas fa-user"></i> Paciente</span>
                            <span class="user-type-badge badge-doctor"><i class="fas fa-user-md"></i> Doctor</span>
                            <span class="user-type-badge badge-admin"><i class="fas fa-user-shield"></i> Admin</span>
                        </div>
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

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
