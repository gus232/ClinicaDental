<?php
/**
 * Script de migración mejorado
 */

require_once 'hms/include/config.php';

echo "<h2>Migración: Deshabilitar Categorías de Permisos</h2>";
echo "<hr>";

$errors = [];
$success = [];

try {
    // PASO 1: Agregar columna is_active a permission_categories
    echo "<h3>Paso 1: Agregar columna is_active a permission_categories...</h3>";
    $query = "ALTER TABLE permission_categories ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1";
    if (mysqli_query($con, $query)) {
        echo "✓ Columna agregada\n";
        $success[] = "Columna is_active en permission_categories";
    } else {
        echo "✗ Error: " . mysqli_error($con) . "\n";
        $errors[] = mysqli_error($con);
    }

    // PASO 2: Agregar columna is_active a permissions
    echo "<h3>Paso 2: Agregar columna is_active a permissions...</h3>";
    $query = "ALTER TABLE permissions ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1";
    if (mysqli_query($con, $query)) {
        echo "✓ Columna agregada\n";
        $success[] = "Columna is_active en permissions";
    } else {
        echo "✗ Error: " . mysqli_error($con) . "\n";
        $errors[] = mysqli_error($con);
    }

    // PASO 3: Deshabilitar categorías
    echo "<h3>Paso 3: Deshabilitando categorías no necesarias...</h3>";
    $query = "UPDATE permission_categories SET is_active = 0 WHERE category_name IN ('medical_records', 'billing', 'reports')";
    if (mysqli_query($con, $query)) {
        $affected = mysqli_affected_rows($con);
        echo "✓ $affected categorías deshabilitadas\n";
        $success[] = "$affected categorías deshabilitadas";
    } else {
        echo "✗ Error: " . mysqli_error($con) . "\n";
        $errors[] = mysqli_error($con);
    }

    // PASO 4: Deshabilitar permisos
    echo "<h3>Paso 4: Deshabilitando permisos correspondientes...</h3>";
    $query = "UPDATE permissions SET is_active = 0 WHERE module IN ('medical_records', 'billing', 'reports')";
    if (mysqli_query($con, $query)) {
        $affected = mysqli_affected_rows($con);
        echo "✓ $affected permisos deshabilitados\n";
        $success[] = "$affected permisos deshabilitados";
    } else {
        echo "✗ Error: " . mysqli_error($con) . "\n";
        $errors[] = mysqli_error($con);
    }

    // PASO 5: Revocar permisos
    echo "<h3>Paso 5: Revocando permisos de los roles...</h3>";
    $query = "DELETE FROM role_permissions WHERE permission_id IN (
                SELECT id FROM permissions WHERE module IN ('medical_records', 'billing', 'reports')
              )";
    if (mysqli_query($con, $query)) {
        $affected = mysqli_affected_rows($con);
        echo "✓ $affected asignaciones de permisos revocadas\n";
        $success[] = "$affected permisos revocados de los roles";
    } else {
        echo "✗ Error: " . mysqli_error($con) . "\n";
        $errors[] = mysqli_error($con);
    }

    echo "<hr>";
    echo "<h3>Verificación Final:</h3>";

    // Verificar categorías activas
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM permission_categories WHERE is_active = 1");
    $row = mysqli_fetch_assoc($result);
    echo "<p>✓ Categorías activas: <strong>" . $row['count'] . "</strong> (esperado: 6)</p>";

    // Verificar permisos activos
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM permissions WHERE is_active = 1");
    $row = mysqli_fetch_assoc($result);
    echo "<p>✓ Permisos activos: <strong>" . $row['count'] . "</strong> (esperado: ~39)</p>";

    // Verificar permisos revocados
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM role_permissions");
    $row = mysqli_fetch_assoc($result);
    echo "<p>✓ Total de asignaciones de permisos: <strong>" . $row['count'] . "</strong></p>";

    // Listar categorías deshabilitadas
    echo "<h3>Categorías deshabilitadas:</h3>";
    echo "<ul>";
    $result = mysqli_query($con, "SELECT display_name, category_name FROM permission_categories WHERE is_active = 0");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<li>" . htmlspecialchars($row['display_name']) . " (" . htmlspecialchars($row['category_name']) . ")</li>";
    }
    echo "</ul>";

    echo "<hr>";
    if (count($errors) === 0) {
        echo "<h3 style='color: green;'>✓ MIGRACIÓN COMPLETADA EXITOSAMENTE</h3>";
        echo "<p>Ahora puedes acceder a <a href='hms/admin/manage-roles.php'>manage-roles.php</a> para ver los cambios.</p>";
    } else {
        echo "<h3 style='color: orange;'>⚠ MIGRACIÓN COMPLETADA CON ADVERTENCIAS</h3>";
        echo "<p>Errores encontrados: " . count($errors) . "</p>";
    }

} catch (Exception $e) {
    echo "<h3 style='color: red;'>✗ Error crítico:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

mysqli_close($con);
?>
