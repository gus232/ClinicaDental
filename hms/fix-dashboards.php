<?php
/**
 * CORREGIR DASHBOARDS Y URLS HARDCODEADAS
 */

echo "=== CORRECCIÓN DE DASHBOARDS Y URLs ===\n\n";

$base_dir = __DIR__;
$fixed_files = [];
$errors = [];

// CORRECCIÓN 1: doctor/dashboard.php
echo "📄 1. Corrigiendo doctor/dashboard.php...\n";
$file = $base_dir . '/doctor/dashboard.php';

if (file_exists($file)) {
    $content = file_get_contents($file);
    $backup = $file . '.backup.' . date('YmdHis');
    copy($file, $backup);

    // Buscar la URL hardcodeada
    $pattern = '/window\.location\.href\s*=\s*[\'"]http:\/\/[^\'"]+user-login\.php[\'"]/';
    $replacement = "window.location.href = '../login.php'";

    $new_content = preg_replace($pattern, $replacement, $content, -1, $count);

    if ($count > 0) {
        file_put_contents($file, $new_content);
        echo "  ✅ Corregido ($count URLs hardcodeadas)\n";
        echo "  💾 Backup: " . basename($backup) . "\n";
        $fixed_files[] = 'doctor/dashboard.php';
    } else {
        echo "  ⏭️  No se encontraron URLs hardcodeadas\n";
    }
} else {
    echo "  ❌ Archivo no existe\n";
}

echo "\n";

// CORRECCIÓN 2: admin/dashboard.php
echo "📄 2. Corrigiendo admin/dashboard.php...\n";
$file = $base_dir . '/admin/dashboard.php';

if (file_exists($file)) {
    $content = file_get_contents($file);

    // Verificar si tiene URLs hardcodeadas
    if (preg_match('/http:\/\/localhost:[0-9]+/', $content)) {
        $backup = $file . '.backup.' . date('YmdHis');
        copy($file, $backup);

        $pattern = '/window\.location\.href\s*=\s*[\'"]http:\/\/[^\'"]+user-login\.php[\'"]/';
        $replacement = "window.location.href = '../login.php'";

        $new_content = preg_replace($pattern, $replacement, $content, -1, $count);

        if ($count > 0) {
            file_put_contents($file, $new_content);
            echo "  ✅ Corregido ($count URLs hardcodeadas)\n";
            echo "  💾 Backup: " . basename($backup) . "\n";
            $fixed_files[] = 'admin/dashboard.php';
        }
    } else {
        echo "  ✅ Sin URLs hardcodeadas\n";
    }
} else {
    echo "  ❌ Archivo no existe\n";
}

echo "\n";

// CORRECCIÓN 3: include/checklogin.php
echo "📄 3. Corrigiendo include/checklogin.php...\n";
$file = $base_dir . '/include/checklogin.php';

if (file_exists($file)) {
    $content = file_get_contents($file);
    $backup = $file . '.backup.' . date('YmdHis');
    copy($file, $backup);

    // Cambiar la redirección
    $content = str_replace('$extra="../admin.php";', '$extra="../login.php";', $content, $count1);
    $content = str_replace('$extra="user-login.php";', '$extra="login.php";', $content, $count2);

    $total_changes = $count1 + $count2;

    if ($total_changes > 0) {
        file_put_contents($file, $content);
        echo "  ✅ Corregido ($total_changes redirecciones)\n";
        echo "  💾 Backup: " . basename($backup) . "\n";
        $fixed_files[] = 'include/checklogin.php';
    } else {
        echo "  ⏭️  Sin cambios necesarios\n";
    }
} else {
    echo "  ❌ Archivo no existe\n";
}

echo "\n";

// CORRECCIÓN 4: doctor/include/checklogin.php
echo "📄 4. Corrigiendo doctor/include/checklogin.php...\n";
$file = $base_dir . '/doctor/include/checklogin.php';

if (file_exists($file)) {
    $content = file_get_contents($file);
    $backup = $file . '.backup.' . date('YmdHis');
    copy($file, $backup);

    $content = str_replace('$extra="index.php";', '$extra="../login.php";', $content, $count);

    if ($count > 0) {
        file_put_contents($file, $content);
        echo "  ✅ Corregido ($count redirecciones)\n";
        echo "  💾 Backup: " . basename($backup) . "\n";
        $fixed_files[] = 'doctor/include/checklogin.php';
    } else {
        echo "  ⏭️  Sin cambios necesarios\n";
    }
} else {
    echo "  ❌ Archivo no existe\n";
}

echo "\n";

// CORRECCIÓN 5: admin/include/checklogin.php
echo "📄 5. Corrigiendo admin/include/checklogin.php...\n";
$file = $base_dir . '/admin/include/checklogin.php';

if (file_exists($file)) {
    $content = file_get_contents($file);
    $backup = $file . '.backup.' . date('YmdHis');
    copy($file, $backup);

    $content = str_replace('$extra="index.php";', '$extra="../login.php";', $content, $count);

    if ($count > 0) {
        file_put_contents($file, $content);
        echo "  ✅ Corregido ($count redirecciones)\n";
        echo "  💾 Backup: " . basename($backup) . "\n";
        $fixed_files[] = 'admin/include/checklogin.php';
    } else {
        echo "  ⏭️  Sin cambios necesarios\n";
    }
} else {
    echo "  ❌ Archivo no existe\n";
}

echo "\n";

// Resumen
echo "==========================================\n";
echo "RESUMEN:\n";
echo "  - Archivos corregidos: " . count($fixed_files) . "\n";

if (!empty($fixed_files)) {
    foreach ($fixed_files as $file) {
        echo "    ✅ $file\n";
    }
}

if (!empty($errors)) {
    echo "  - Errores: " . count($errors) . "\n";
    foreach ($errors as $file) {
        echo "    ❌ $file\n";
    }
}

echo "\n✅ CORRECCIÓN COMPLETADA!\n\n";

echo "📋 PRÓXIMOS PASOS:\n";
echo "  1. Probar login como doctor\n";
echo "  2. Probar login como admin\n";
echo "  3. Verificar que los dashboards cargan correctamente\n";
?>
