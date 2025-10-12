<?php
session_start();
//error_reporting(0);
include("include/config.php");
// Código para actualizar la contraseña
if(isset($_POST['change']))
{
    $name=$_SESSION['name'];
    $email=$_SESSION['email'];
    $newpassword=md5($_POST['password']);
    $query=mysqli_query($con,"update users set password='$newpassword' where fullName='$name' and email='$email'");
    if ($query) {
        echo "<script>alert('Contraseña actualizada exitosamente.');</script>";
        echo "<script>window.location.href ='login.php'</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <title>Restablecer Contraseña</title>
    
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

    <script type="text/javascript">
        function validar()
        {
            if(document.passwordreset.password.value!= document.passwordreset.password_again.value)
            {
                alert("La contraseña y la confirmación de contraseña no coinciden.");
                document.passwordreset.password_again.focus();
                return false;
            }
            return true;
        }
    </script>
</head>
<body class="login">
<div class="row">
    <div class="main-login col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2 col-md-4 col-md-offset-4">
        <div class="logo margin-top-30">
            <a href="../index.html"><h2> HMS | Restablecer Contraseña del Paciente</h2></a>
        </div>

        <div class="box-login">
            <form class="form-login" name="passwordreset" method="post" onSubmit="return validar();">
                <fieldset>
                    <legend>
                        Restablecer Contraseña del Paciente
                    </legend>
                    <p>
                        Por favor, establece tu nueva contraseña.<br />
                        <span style="color:red;"><?php echo $_SESSION['errmsg']; ?><?php echo $_SESSION['errmsg']="";?></span>
                    </p>

                    <div class="form-group">
                        <span class="input-icon">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                            <i class="fa fa-lock"></i>
                        </span>
                    </div>

                    <div class="form-group">
                        <span class="input-icon">
                            <input type="password" class="form-control"  id="password_again" name="password_again" placeholder="Confirmar Contraseña" required>
                            <i class="fa fa-lock"></i>
                        </span>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary pull-right" name="change">
                            Cambiar <i class="fa fa-arrow-circle-right"></i>
                        </button>
                    </div>
                    <div class="new-account">
                        ¿Ya tienes una cuenta?
                        <a href="login.php">
                            Iniciar Sesión
                        </a>
                    </div>
                </fieldset>
            </form>

            <div class="copyright">
                &copy; <span class="current-year"></span><span class="text-bold text-uppercase"> HMS</span>. <span>Todos los derechos reservados</span>
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
</body>
<!-- end: BODY -->
</html>
