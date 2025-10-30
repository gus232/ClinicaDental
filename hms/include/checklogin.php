<?php
/**
 * Verificar login y timeout de sesión para PACIENTES
 */
function check_login()
{
	// Verificar que exista sesión
	if(strlen($_SESSION['login'])==0) {
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra="./user-login.php";
		header("Location: http://$host$uri/$extra");
		exit();
	}

	// Verificar timeout de sesión
	global $con;
	if (isset($con)) {
		require_once(dirname(__FILE__) . '/SessionManager.php');

		$sessionManager = new SessionManager($con);
		$result = $sessionManager->checkSessionTimeout();

		if ($result['expired']) {
			// Sesión expirada
			$reason = $result['reason'];
			$sessionManager->destroySession($reason);

			// Redirigir a login con mensaje de timeout
			$host = $_SERVER['HTTP_HOST'];
			$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
			$extra = "./user-login.php?timeout=1&reason=" . $reason;
			header("Location: http://$host$uri/$extra");
			exit();
		}

		// Sesión válida, actualizar última actividad
		$sessionManager->updateLastActivity();
	}
}
?>