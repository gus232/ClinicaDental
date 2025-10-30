<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Iniciando script...<br>";

include('include/config.php');
echo "2. Config incluido...<br>";

include('include/checklogin.php');
echo "3. Checklogin incluido...<br>";

include('../include/rbac-functions.php');
echo "4. RBAC incluido...<br>";

check_login();
echo "5. Login verificado...<br>";

echo "6. Session ID: " . $_SESSION['id'] . "<br>";

$docid = $_SESSION['id'];
echo "7. Doctor ID: " . $docid . "<br>";

// Test de conexión
if (!$con) {
    die("ERROR: No hay conexión a la base de datos");
}
echo "8. Conexión a BD OK<br>";

// Test de consulta simple
$test_sql = mysqli_query($con, "SELECT COUNT(*) as total FROM users");
if ($test_sql) {
    $result = mysqli_fetch_assoc($test_sql);
    echo "9. Test usuarios: " . $result['total'] . " usuarios en total<br>";
} else {
    echo "9. ERROR en test usuarios: " . mysqli_error($con) . "<br>";
}

// Test de consulta appointment
$test_sql2 = mysqli_query($con, "SELECT COUNT(*) as total FROM appointment");
if ($test_sql2) {
    $result2 = mysqli_fetch_assoc($test_sql2);
    echo "10. Test appointments: " . $result2['total'] . " citas en total<br>";
} else {
    echo "10. ERROR en test appointments: " . mysqli_error($con) . "<br>";
}

// Test de consulta específica del doctor
$test_sql3 = mysqli_query($con, "SELECT COUNT(*) as total FROM appointment WHERE doctorId = '$docid'");
if ($test_sql3) {
    $result3 = mysqli_fetch_assoc($test_sql3);
    echo "11. Citas del doctor: " . $result3['total'] . "<br>";
} else {
    echo "11. ERROR en citas del doctor: " . mysqli_error($con) . "<br>";
}

echo "12. Script completado exitosamente!<br>";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Diagnóstico</title>
</head>
<body>
    <h1>Test completado - Si ves esto, el PHP funciona correctamente</h1>
    <p>Ahora podemos identificar exactamente dónde está el problema.</p>
</body>
</html>
