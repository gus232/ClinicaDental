<?php
session_start();
require_once('include/config.php');
require_once('../include/SessionManager.php');

// Determinar razón del logout
$reason = isset($_GET['reason']) ? $_GET['reason'] : 'manual';

// Opción C: Destruir sesión completa + cookie + user_logs
if (isset($_SESSION['id']) && $_SESSION['id']) {
    $sessionManager = new SessionManager($con);
    $sessionManager->destroySession($reason);
}

// Mensaje de logout
session_start();
$_SESSION['errmsg'] = "Has cerrado sesión exitosamente";
?>
<script language="javascript">
document.location="../index.php";
</script>
