<?php
/**
 * EJECUTAR MIGRACIÓN SQL
 * Lee y ejecuta el archivo SQL paso a paso
 */

include("include/config.php");

echo "=== EJECUTANDO MIGRACIÓN DE BASE DE DATOS ===\n\n";

$sql_file = 'migrate-normalize-database.sql';

if (!file_exists($sql_file)) {
    die("❌ Error: Archivo $sql_file no encontrado\n");
}

$sql_content = file_get_contents($sql_file);

// Dividir por punto y coma (cada statement)
$statements = explode(';', $sql_content);

$executed = 0;
$failed = 0;
$skipped = 0;

foreach ($statements as $statement) {
    $statement = trim($statement);

    // Saltar comentarios y líneas vacías
    if (empty($statement) || substr($statement, 0, 2) === '--' || substr($statement, 0, 2) === '/*') {
        continue;
    }

    // Saltar solo comentarios de una línea
    if (preg_match('/^--/', $statement)) {
        continue;
    }

    // Saltar SELECT para verificación (solo mostrar)
    if (stripos($statement, 'SELECT') === 0) {
        echo "\n📊 VERIFICACIÓN:\n";
        $result = mysqli_query($con, $statement);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                foreach ($row as $key => $value) {
                    echo "  $key: $value\n";
                }
            }
        }
        $skipped++;
        continue;
    }

    // Mostrar qué se está ejecutando
    $preview = substr($statement, 0, 80);
    echo "Ejecutando: $preview...\n";

    // Ejecutar statement
    if (mysqli_query($con, $statement)) {
        echo "  ✅ OK\n";
        $executed++;
    } else {
        $error = mysqli_error($con);
        // Algunos errores son esperados (ej: tabla ya existe)
        if (strpos($error, 'already exists') !== false ||
            strpos($error, 'Duplicate column name') !== false) {
            echo "  ⏭️  Ya existe (saltando)\n";
            $skipped++;
        } else {
            echo "  ❌ Error: $error\n";
            $failed++;
        }
    }
}

echo "\n==========================================\n";
echo "RESUMEN DE MIGRACIÓN:\n";
echo "  - Statements ejecutados: $executed\n";
echo "  - Errores: $failed\n";
echo "  - Saltados/Verificación: $skipped\n";
echo "  - Total procesados: " . ($executed + $failed + $skipped) . "\n";

if ($failed > 0) {
    echo "\n⚠️  Hubo $failed errores. Revisa los mensajes arriba.\n";
} else {
    echo "\n✅ MIGRACIÓN COMPLETADA EXITOSAMENTE!\n";
}

mysqli_close($con);
?>
