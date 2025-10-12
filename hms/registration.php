<?php
include_once('include/config.php');
if(isset($_POST['submit']))
{
	// Sanitizar y obtener datos del formulario
	$fname = mysqli_real_escape_string($con, trim($_POST['full_name']));
	$address = mysqli_real_escape_string($con, trim($_POST['address']));
	$city = mysqli_real_escape_string($con, trim($_POST['city']));
	$gender = mysqli_real_escape_string($con, trim($_POST['gender']));
	$email = mysqli_real_escape_string($con, trim($_POST['email']));
	$password = $_POST['password'];

	// Hash de la contraseña con bcrypt (seguro)
	$hashed_password = password_hash($password, PASSWORD_BCRYPT);

	// Verificar si el email ya existe
	$check_sql = "SELECT id FROM users WHERE email = ?";
	$check_stmt = mysqli_prepare($con, $check_sql);
	mysqli_stmt_bind_param($check_stmt, "s", $email);
	mysqli_stmt_execute($check_stmt);
	mysqli_stmt_store_result($check_stmt);

	if(mysqli_stmt_num_rows($check_stmt) > 0) {
		echo "<script>alert('Email already exists. Please use a different email.');</script>";
		mysqli_stmt_close($check_stmt);
	} else {
		mysqli_stmt_close($check_stmt);

		// Iniciar transacción para garantizar integridad
		mysqli_begin_transaction($con);

		try {
			// 1. Insertar en tabla users (información de autenticación)
			$user_sql = "INSERT INTO users (email, password, user_type, full_name, status, created_at) VALUES (?, ?, 'patient', ?, 'active', NOW())";
			$user_stmt = mysqli_prepare($con, $user_sql);
			mysqli_stmt_bind_param($user_stmt, "sss", $email, $hashed_password, $fname);

			if(!mysqli_stmt_execute($user_stmt)) {
				throw new Exception("Error al insertar usuario: " . mysqli_stmt_error($user_stmt));
			}

			// Obtener el ID del usuario recién creado
			$user_id = mysqli_insert_id($con);
			mysqli_stmt_close($user_stmt);

			// 2. Insertar en tabla patients (información específica del paciente)
			$patient_sql = "INSERT INTO patients (user_id, address, city, gender) VALUES (?, ?, ?, ?)";
			$patient_stmt = mysqli_prepare($con, $patient_sql);
			mysqli_stmt_bind_param($patient_stmt, "isss", $user_id, $address, $city, $gender);

			if(!mysqli_stmt_execute($patient_stmt)) {
				throw new Exception("Error al insertar paciente: " . mysqli_stmt_error($patient_stmt));
			}

			mysqli_stmt_close($patient_stmt);

			// Commit de la transacción
			mysqli_commit($con);

			echo "<script>alert('Successfully Registered. You can login now'); window.location.href='login.php';</script>";

		} catch (Exception $e) {
			// Rollback en caso de error
			mysqli_rollback($con);
			echo "<script>alert('Registration failed: " . $e->getMessage() . "');</script>";
		}
	}
}
?>


<!DOCTYPE html>
<html lang="en">

	<head>
		<title>User Registration</title>
		
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
			<div class="main-login col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2 col-md-4 col-md-offset-4">
				<div class="logo margin-top-30">
				<a href="../index.html"><h2>HMS | Patient Registration</h2></a>
				</div>
				<!-- start: REGISTER BOX -->
				<div class="box-register">
					<form name="registration" id="registration"  method="post" onSubmit="return valid();">
						<fieldset>
							<legend>
								Sign Up
							</legend>
							<p>
								Enter your personal details below:
							</p>
							<div class="form-group">
								<input type="text" class="form-control" name="full_name" placeholder="Full Name" required>
							</div>
							<div class="form-group">
								<input type="text" class="form-control" name="address" placeholder="Address" required>
							</div>
							<div class="form-group">
								<input type="text" class="form-control" name="city" placeholder="City" required>
							</div>
							<div class="form-group">
								<label class="block">
									Gender
								</label>
								<div class="clip-radio radio-primary">
									<input type="radio" id="rg-female" name="gender" value="female" >
									<label for="rg-female">
										Female
									</label>
									<input type="radio" id="rg-male" name="gender" value="male">
									<label for="rg-male">
										Male
									</label>
								</div>
							</div>
							<p>
								Enter your account details below:
							</p>
							<div class="form-group">
								<span class="input-icon">
									<input type="email" class="form-control" name="email" id="email" onBlur="userAvailability()"  placeholder="Email" required>
									<i class="fa fa-envelope"></i> </span>
									 <span id="user-availability-status1" style="font-size:12px;"></span>
							</div>
							<div class="form-group">
								<span class="input-icon">
									<input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
									<i class="fa fa-lock"></i> </span>
							</div>
							<div class="form-group">
								<span class="input-icon">
									<input type="password" class="form-control"  id="password_again" name="password_again" placeholder="Password Again" required>
									<i class="fa fa-lock"></i> </span>
							</div>
							<div class="form-group">
								<div class="checkbox clip-check check-primary">
									<input type="checkbox" id="agree" value="agree" checked="true" readonly=" true">
									<label for="agree">
										I agree
									</label>
								</div>
							</div>
							<div class="form-actions">
								<p>
									Already have an account?
									<a href="login.php">
										Log-in
									</a>
								</p>
								<button type="submit" class="btn btn-primary pull-right" id="submit" name="submit">
									Submit <i class="fa fa-arrow-circle-right"></i>
								</button>
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
</script>	
		
	</body>
	<!-- end: BODY -->
</html>