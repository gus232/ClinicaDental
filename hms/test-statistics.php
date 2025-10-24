<?php
/**
 * Script de prueba para verificar estadísticas de usuarios
 */

require_once('admin/include/config.php');

echo "<h1>Test de Estadísticas de Usuarios</h1>";
echo "<hr>";

// Probar el stored procedure directamente
echo "<h2>1. Prueba del Stored Procedure</h2>";
$result = mysqli_query($con, "CALL get_user_statistics()");

if ($result) {
    $stats = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    
    // Limpiar resultados adicionales
    while (mysqli_more_results($con)) {
        mysqli_next_result($con);
        if ($res = mysqli_store_result($con)) {
            mysqli_free_result($res);
        }
    }
    
    echo "<pre>";
    print_r($stats);
    echo "</pre>";
    
    echo "<h3>Resultados:</h3>";
    echo "<ul>";
    echo "<li><strong>Total Usuarios:</strong> " . ($stats['total_users'] ?? 0) . "</li>";
    echo "<li><strong>Usuarios Activos:</strong> " . ($stats['active_users'] ?? 0) . "</li>";
    echo "<li><strong>Usuarios Inactivos:</strong> " . ($stats['inactive_users'] ?? 0) . "</li>";
    echo "<li><strong>Usuarios Bloqueados:</strong> " . ($stats['blocked_users'] ?? 0) . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>Error: " . mysqli_error($con) . "</p>";
}

// Probar la clase UserManagement
echo "<hr>";
echo "<h2>2. Prueba de UserManagement::getStatistics()</h2>";

require_once('include/UserManagement.php');

$userManager = new UserManagement($con, 1);
$stats2 = $userManager->getStatistics();

echo "<pre>";
print_r($stats2);
echo "</pre>";

echo "<h3>Resultados:</h3>";
echo "<ul>";
echo "<li><strong>Total Usuarios:</strong> " . ($stats2['total_users'] ?? 0) . "</li>";
echo "<li><strong>Usuarios Activos:</strong> " . ($stats2['active_users'] ?? 0) . "</li>";
echo "<li><strong>Usuarios Inactivos:</strong> " . ($stats2['inactive_users'] ?? 0) . "</li>";
echo "<li><strong>Usuarios Bloqueados:</strong> " . ($stats2['blocked_users'] ?? 0) . "</li>";
echo "</ul>";

// Verificar usuarios en la base de datos
echo "<hr>";
echo "<h2>3. Verificación directa en la tabla users</h2>";

$query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactivos,
    SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as bloqueados
FROM users";

$result = mysqli_query($con, $query);
$verification = mysqli_fetch_assoc($result);

echo "<pre>";
print_r($verification);
echo "</pre>";

echo "<h3>Resultados:</h3>";
echo "<ul>";
echo "<li><strong>Total:</strong> " . $verification['total'] . "</li>";
echo "<li><strong>Activos:</strong> " . $verification['activos'] . "</li>";
echo "<li><strong>Inactivos:</strong> " . $verification['inactivos'] . "</li>";
echo "<li><strong>Bloqueados:</strong> " . $verification['bloqueados'] . "</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>✅ Prueba completada</h2>";
echo "<p><a href='admin/manage-users.php'>Ir a Gestión de Usuarios</a></p>";
?>
