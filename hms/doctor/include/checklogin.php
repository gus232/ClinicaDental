<?php
/**
 * Verificar login y timeout de sesión para DOCTORES
 */
function check_login()
{
	// Verificar si el usuario está logueado usando el sistema moderno de sesiones
	if(!isset($_SESSION['id']) || empty($_SESSION['id'])) {
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra="index.php";
		header("Location: http://$host$uri/$extra");
		exit();
	}

	// Verificar que el usuario sea de tipo 'doctor'
	if(isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'doctor') {
		// Si no es doctor, redirigir al login
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra="index.php";
		header("Location: http://$host$uri/$extra");
		exit();
	}

	// Verificar timeout de sesión
	global $con;
	if (isset($con)) {
		require_once(dirname(__FILE__) . '/../../include/SessionManager.php');

		$sessionManager = new SessionManager($con);
		$result = $sessionManager->checkSessionTimeout();

		if ($result['expired']) {
			// Sesión expirada
			$reason = $result['reason'];
			$sessionManager->destroySession($reason);

			// Redirigir a login con mensaje de timeout
			$host = $_SERVER['HTTP_HOST'];
			$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
			$extra = "index.php?timeout=1&reason=" . $reason;
			header("Location: http://$host$uri/$extra");
			exit();
		}

		// Sesión válida, actualizar última actividad
		$sessionManager->updateLastActivity();
	}
}
?>