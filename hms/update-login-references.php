<?php
/**
 * ACTUALIZAR TODAS LAS REFERENCIAS DE LOGIN ANTIGUO AL NUEVO
 */

echo "=== ACTUALIZANDO REFERENCIAS DE LOGIN ===\n\n";

$base_dir = dirname(__DIR__);
$updated_files = [];
$errors = [];

// Archivos a actualizar
$files_to_update = [
    $base_dir . '/index.html',
    $base_dir . '/hms/registration.php',
    $base_dir . '/hms/forgot-password.php',
    $base_dir . '/hms/reset-password.php',
    $base_dir . '/hms/dashboard1.php'
];

// Reemplazos a realizar
$replacements = [
    'hms/user-login.php' => 'hms/login.php',
    'user-login.php' => 'login.php',
    '"user-login.php"' => '"login.php"',
    "'user-login.php'" => "'login.php'",
];

foreach ($files_to_update as $file) {
    if (!file_exists($file)) {
        echo "⏭️  Saltando: " . basename($file) . " (no existe)\n";
        continue;
    }

    echo "📄 Procesando: " . basename($file) . "\n";

    // Leer contenido
    $content = file_get_contents($file);
    $original_content = $content;
    $changes_made = 0;

    // Aplicar reemplazos
    foreach ($replacements as $old => $new) {
        $count = 0;
        $content = str_replace($old, $new, $content, $count);
        if ($count > 0) {
            echo "  ✅ Reemplazado '$old' → '$new' ($count veces)\n";
            $changes_made += $count;
        }
    }

    // Guardar si hubo cambios
    if ($content !== $original_content) {
        // Crear backup
        $backup_file = $file . '.backup.' . date('YmdHis');
        if (copy($file, $backup_file)) {
            echo "  💾 Backup creado: " . basename($backup_file) . "\n";
        }

        // Guardar archivo actualizado
        if (file_put_contents($file, $content)) {
            echo "  ✅ Archivo actualizado ($changes_made cambios)\n";
            $updated_files[] = basename($file);
        } else {
            echo "  ❌ Error al guardar\n";
            $errors[] = basename($file);
        }
    } else {
        echo "  ⏭️  Sin cambios necesarios\n";
    }

    echo "\n";
}

// Resumen
echo "==========================================\n";
echo "RESUMEN:\n";
echo "  - Archivos actualizados: " . count($updated_files) . "\n";

if (!empty($updated_files)) {
    foreach ($updated_files as $file) {
        echo "    ✅ $file\n";
    }
}

if (!empty($errors)) {
    echo "  - Errores: " . count($errors) . "\n";
    foreach ($errors as $file) {
        echo "    ❌ $file\n";
    }
}

echo "\n✅ ACTUALIZACIÓN COMPLETADA!\n\n";

echo "📋 PRÓXIMOS PASOS:\n";
echo "  1. Verificar que el home ahora apunte a login.php\n";
echo "  2. Probar el flujo completo desde index.html\n";
echo "  3. Deprecar archivos antiguos (renombrar a .old)\n";
?>
