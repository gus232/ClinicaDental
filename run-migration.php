<?php
/**
 * Script de migración para deshabilitar categorías de permisos
 * Ejecutar desde: http://localhost/hospital/run-migration.php
 */

require_once 'hms/include/config.php';

echo "<h2>Ejecutando Migración: Deshabilitar Categorías de Permisos</h2>";
echo "<hr>";

try {
    // Leer el archivo SQL
    $sql_file = 'database/migration_disable_categories.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("Archivo SQL no encontrado: $sql_file");
    }

    $sql_content = file_get_contents($sql_file);

    // Separar las consultas (ignorar comentarios)
    $queries = array_filter(array_map('trim', preg_split('/;/', $sql_content)));

    $executed = 0;
    $errors = [];

    echo "<h3>Ejecutando querys...</h3>";
    echo "<pre>";

    foreach ($queries as $query) {
        // Ignorar líneas vacías y comentarios
        if (empty($query) || strpos(trim($query), '--') === 0) {
            continue;
        }

        echo "Ejecutando: " . substr($query, 0, 80) . "...\n";

        if (mysqli_query($con, $query)) {
            echo "✓ OK\n";
            $executed++;
        } else {
            $error = mysqli_error($con);
            echo "✗ ERROR: $error\n";
            $errors[] = [
                'query' => substr($query, 0, 100),
                'error' => $error
            ];
        }
    }

    echo "</pre>";

    // Mostrar resultados
    echo "<h3>Resultados:</h3>";
    echo "<ul>";
    echo "<li><strong>Querys ejecutadas:</strong> $executed</li>";
    echo "<li><strong>Errores:</strong> " . count($errors) . "</li>";
    echo "</ul>";

    if (count($errors) > 0) {
        echo "<h3>Errores encontrados:</h3>";
        echo "<ul>";
        foreach ($errors as $err) {
            echo "<li><strong>" . htmlspecialchars($err['query']) . "</strong><br>";
            echo "Error: " . htmlspecialchars($err['error']) . "</li>";
        }
        echo "</ul>";
    }

    // Verificar cambios
    echo "<h3>Verificación de cambios:</h3>";
    echo "<pre>";

    // 1. Categorías activas
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM permission_categories WHERE is_active = 1");
    $row = mysqli_fetch_assoc($result);
    echo "Categorías activas: " . $row['count'] . " (esperado: 6)\n";

    // 2. Permisos activos
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM permissions WHERE is_active = 1");
    $row = mysqli_fetch_assoc($result);
    echo "Permisos activos: " . $row['count'] . " (esperado: 39 aprox)\n";

    // 3. Total de permisos
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM permissions");
    $row = mysqli_fetch_assoc($result);
    echo "Total de permisos: " . $row['count'] . " (incluye deshabilitados)\n";

    // 4. Permisos revocados
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM role_permissions rp
                                  INNER JOIN permissions p ON rp.permission_id = p.id
                                  WHERE p.is_active = 0");
    $row = mysqli_fetch_assoc($result);
    echo "Permisos deshabilitados asignados a roles: " . $row['count'] . " (esperado: 0)\n";

    echo "</pre>";

    // Mostrar categorías deshabilitadas
    echo "<h3>Categorías deshabilitadas:</h3>";
    echo "<pre>";
    $result = mysqli_query($con, "SELECT category_name, display_name FROM permission_categories WHERE is_active = 0");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- " . $row['display_name'] . " (" . $row['category_name'] . ")\n";
    }
    echo "</pre>";

    echo "<h3 style='color: green;'>✓ Migración completada exitosamente</h3>";
    echo "<p><a href='hms/admin/manage-roles.php'>Ver matriz de roles actualizada →</a></p>";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>✗ Error en migración:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

mysqli_close($con);
?>
