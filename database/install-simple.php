<?php
/**
 * ============================================================================
 * INSTALADOR SIMPLIFICADO - SISTEMA RBAC
 * ============================================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hms_v2';

echo "<pre>";
echo "============================================================================\n";
echo "INSTALADOR DE SISTEMA RBAC - HMS\n";
echo "============================================================================\n\n";

$con = mysqli_connect($host, $username, $password, $database);

if (!$con) {
    die("ERROR: " . mysqli_connect_error() . "\n");
}

echo "✓ Conectado a: $database\n\n";

// Función mejorada para ejecutar SQL
function executeSQL($con, $filepath, $name) {
    echo "[$name]\n";

    if (!file_exists($filepath)) {
        echo "✗ Archivo no encontrado: $filepath\n\n";
        return false;
    }

    $sql = file_get_contents($filepath);

    // Remover comentarios
    $sql = preg_replace('/--.*$/m', '', $sql);

    // Dividir por delimiter primero
    $parts = preg_split('/DELIMITER\s+\$\$/i', $sql);

    $success = 0;
    $errors = 0;

    foreach ($parts as $part) {
        // Restaurar delimiter normal
        $part = str_replace('$$', ';', $part);
        $part = preg_replace('/DELIMITER\s+;/i', '', $part);

        // Dividir por punto y coma
        $statements = array_filter(array_map('trim', explode(';', $part)));

        foreach ($statements as $statement) {
            if (empty($statement)) continue;

            // Ejecutar
            if (mysqli_query($con, $statement)) {
                $success++;
            } else {
                $error = mysqli_error($con);
                // Ignorar errores comunes
                if (strpos($error, 'already exists') === false &&
                    strpos($error, 'Duplicate') === false &&
                    !empty($error)) {
                    echo "  ⚠ $error\n";
                    $errors++;
                }
            }
        }
    }

    if ($errors > 0) {
        echo "✓ Completado con $errors advertencias\n\n";
    } else {
        echo "✓ Completado exitosamente ($success statements)\n\n";
    }

    return true;
}

// Ejecutar migraciones
$steps = [
    ['name' => '1/3', 'file' => __DIR__ . '/migrations/003_rbac_system.sql'],
    ['name' => '2/3', 'file' => __DIR__ . '/migrations/004_security_logs.sql'],
    ['name' => '3/3', 'file' => __DIR__ . '/seeds/003_default_roles_permissions.sql'],
];

foreach ($steps as $step) {
    executeSQL($con, $step['file'], $step['name']);
}

// Verificación
echo "============================================================================\n";
echo "VERIFICACIÓN\n";
echo "============================================================================\n\n";

$checks = [
    ['name' => 'Tablas', 'query' => "SELECT COUNT(*) as n FROM information_schema.tables WHERE table_schema='$database' AND table_name IN ('roles','permissions','role_permissions','user_roles','permission_categories','role_hierarchy','audit_role_changes','security_logs')"],
    ['name' => 'Roles', 'query' => "SELECT COUNT(*) as n FROM roles"],
    ['name' => 'Permisos', 'query' => "SELECT COUNT(*) as n FROM permissions"],
    ['name' => 'Asignaciones', 'query' => "SELECT COUNT(*) as n FROM role_permissions"],
    ['name' => 'Vistas', 'query' => "SELECT COUNT(*) as n FROM information_schema.views WHERE table_schema='$database' AND table_name LIKE '%permission%'"],
];

foreach ($checks as $check) {
    $result = mysqli_query($con, $check['query']);
    $row = mysqli_fetch_assoc($result);
    echo "✓ {$check['name']}: {$row['n']}\n";
}

echo "\n";
echo "============================================================================\n";
echo "✅ INSTALACIÓN COMPLETADA\n";
echo "============================================================================\n\n";

echo "Ver detalles completos:\n";
$result = mysqli_query($con, "SELECT role_name, display_name FROM roles ORDER BY priority");
echo "\nROLES CREADOS:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "  • {$row['display_name']} ({$row['role_name']})\n";
}

echo "\n</pre>";
mysqli_close($con);
?>
