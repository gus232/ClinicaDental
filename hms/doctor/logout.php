<?php
session_start();
include('include/config.php');

// Registrar logout en la tabla de logs si existe
date_default_timezone_set('Asia/Kolkata');
$ldate=date( 'd-m-Y h:i:s A', time () );
if(isset($_SESSION['id'])) {
	mysqli_query($con,"UPDATE doctorslog SET logout = '$ldate' WHERE uid = '".$_SESSION['id']."' ORDER BY id DESC LIMIT 1");
}

// Destruir todas las variables de sesión
session_unset();
session_destroy();

// Mensaje de logout
session_start();
$_SESSION['errmsg']="Has cerrado sesión exitosamente";
?>
<script language="javascript">
document.location="../index.php";
</script>
