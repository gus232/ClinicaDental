<?php
session_start();
include('include/config.php');
require_once('include/UserActivityLogger.php');

// Registrar logout usando la nueva clase
if (isset($_SESSION['id']) && $_SESSION['id']) {
    $logger = new UserActivityLogger($con);
    $logger->logLogout($_SESSION['id'], session_id(), 'manual');
}

$_SESSION['login']="";
session_unset();
//session_destroy();
$_SESSION['errmsg']="You have successfully logout";
?>
<script language="javascript">
document.location="../index.html";
</script>
