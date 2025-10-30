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

// Limpiar variables adicionales
$_SESSION['login'] = "";
?>
<script language="javascript">
document.location="../../index.html";
</script>
