<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Iniciando script...<br>";

try {
    require_once('include/config.php');
    echo "2. Config incluido...<br>";
} catch (Exception $e) {
    die("ERROR en config: " . $e->getMessage());
}

try {
    require_once('include/checklogin.php');
    echo "3. Checklogin incluido...<br>";
} catch (Exception $e) {
    die("ERROR en checklogin: " . $e->getMessage());
}

try {
    require_once('../include/permission-check.php');
    echo "4. Permission-check incluido...<br>";
} catch (Exception $e) {
    die("ERROR en permission-check: " . $e->getMessage());
}

try {
    require_once('../include/password-policy.php');
    echo "5. Password-policy incluido...<br>";
} catch (Exception $e) {
    die("ERROR en password-policy: " . $e->getMessage());
}

try {
    check_login();
    echo "6. Login verificado...<br>";
} catch (Exception $e) {
    die("ERROR en check_login: " . $e->getMessage());
}

echo "7. Session user_type: " . ($_SESSION['user_type'] ?? 'NO DEFINIDO') . "<br>";

// Test de conexión
if (!$con) {
    die("ERROR: No hay conexión a la base de datos");
}
echo "8. Conexión a BD OK<br>";

// Test de función hasPermission
if (function_exists('hasPermission')) {
    echo "9. Función hasPermission existe<br>";
    try {
        $has_manage_password = hasPermission('manage_password_policies');
        $has_manage_roles = hasPermission('manage_roles');
        echo "10. Permiso manage_password_policies: " . ($has_manage_password ? 'SÍ' : 'NO') . "<br>";
        echo "11. Permiso manage_roles: " . ($has_manage_roles ? 'SÍ' : 'NO') . "<br>";
    } catch (Exception $e) {
        echo "10. ERROR verificando permisos: " . $e->getMessage() . "<br>";
    }
} else {
    echo "9. Función hasPermission NO existe<br>";
}

// Test de clase PasswordPolicy
try {
    $passwordPolicy = new PasswordPolicy($con);
    echo "12. Clase PasswordPolicy creada exitosamente<br>";
} catch (Exception $e) {
    echo "12. ERROR creando PasswordPolicy: " . $e->getMessage() . "<br>";
}

echo "13. Script completado exitosamente!<br>";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Diagnóstico - Password Policies</title>
</head>
<body>
    <h1>Test completado - Si ves esto, el PHP funciona correctamente</h1>
    <p>Ahora podemos identificar exactamente dónde está el problema.</p>
</body>
</html>
