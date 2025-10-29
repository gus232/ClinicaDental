<?php
/**
 * ============================================================================
 * FUNCIONES DE VERIFICACIÓN DE ACCESO ADMINISTRATIVO
 * ============================================================================
 */

/**
 * Verificar si existe sesión activa
 */
function check_login()
{
	if(strlen($_SESSION['login'])==0)
	{
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra="./index.php";
		$_SESSION["login"]="";
		header("Location: http://$host$uri/$extra");
		exit();
	}
}

/**
 * Verificar si el usuario actual tiene un rol administrativo
 *
 * Roles administrativos permitidos:
 * - admin_tecnico (Administrador Técnico)
 * - admin_operativo (Administrador Operativo)
 * - oficial_seguridad_informacion (OSI)
 *
 * @return bool true si tiene rol admin, false si no
 */
function isAdminRole()
{
	// Verificar que exista sesión
	if (!isset($_SESSION['id'])) {
		return false;
	}

	// Cargar función hasRole si no está cargada
	if (!function_exists('hasRole')) {
		require_once(dirname(__FILE__) . '/../../include/rbac-functions.php');
	}

	// Verificar si tiene alguno de los 3 roles administrativos
	$admin_roles = ['admin_tecnico', 'admin_operativo', 'oficial_seguridad_informacion'];

	foreach ($admin_roles as $role) {
		if (hasRole($role)) {
			return true;
		}
	}

	return false;
}

/**
 * Verificar acceso administrativo (combina check_login + isAdminRole)
 * Usar esta función en páginas que requieren acceso administrativo
 */
function check_admin_access()
{
	// Primero verificar que exista sesión
	check_login();

	// Luego verificar que sea rol administrativo
	if (!isAdminRole()) {
		// Si no es admin, redirigir al dashboard apropiado según su rol
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

		// Redirigir a la raíz del proyecto
		header("Location: http://$host/hospital/hms/");
		exit();
	}
}
?>