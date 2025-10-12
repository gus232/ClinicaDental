<?php
/**
 * VERIFICAR ESTADO DE TODAS LAS VISTAS
 * Detecta cuáles están vacías, cuáles tienen contenido, errores, etc.
 */

echo "=== VERIFICACIÓN DE ESTADO DE VISTAS ===\n\n";

$views_to_check = [
    'dashboard1.php' => 'Dashboard Paciente',
    'doctor/dashboard.php' => 'Dashboard Doctor',
    'admin/dashboard.php' => 'Dashboard Admin',
    'book-appointment.php' => 'Agendar Cita',
    'appointment-history.php' => 'Historial Citas',
    'edit-profile.php' => 'Editar Perfil',
    'change-password.php' => 'Cambiar Contraseña',
];

foreach ($views_to_check as $file => $description) {
    $full_path = __DIR__ . '/' . $file;

    echo "📄 $description ($file)\n";

    if (!file_exists($full_path)) {
        echo "  ❌ NO EXISTE\n\n";
        continue;
    }

    $size = filesize($full_path);
    $content = file_get_contents($full_path);

    // Verificar tamaño
    if ($size == 0) {
        echo "  ❌ ARCHIVO VACÍO (0 bytes)\n\n";
        continue;
    }

    echo "  📊 Tamaño: " . round($size/1024, 2) . " KB\n";

    // Verificar si tiene HTML
    $has_html = (stripos($content, '<html') !== false || stripos($content, '<!DOCTYPE') !== false);
    echo "  🌐 HTML: " . ($has_html ? "✅ Sí" : "❌ No") . "\n";

    // Verificar si tiene PHP
    $has_php = (stripos($content, '<?php') !== false);
    echo "  🐘 PHP: " . ($has_php ? "✅ Sí" : "❌ No") . "\n";

    // Verificar si verifica sesión
    $has_session_check = (stripos($content, 'session_start') !== false || stripos($content, '$_SESSION') !== false);
    echo "  🔒 Verifica sesión: " . ($has_session_check ? "✅ Sí" : "⚠️ No") . "\n";

    // Verificar si está vacío o solo tiene includes
    $content_without_php = preg_replace('/<\?php.*?\?>/s', '', $content);
    $content_trimmed = trim(strip_tags($content_without_php));

    if (strlen($content_trimmed) < 50) {
        echo "  ⚠️  CONTENIDO MUY CORTO (probablemente vacío o solo includes)\n";
    }

    // Buscar includes
    preg_match_all('/include.*?[\'"]([^\'"]+)[\'"]/', $content, $includes);
    if (!empty($includes[1])) {
        echo "  📦 Includes detectados:\n";
        foreach ($includes[1] as $inc) {
            echo "     - $inc\n";
        }
    }

    // Verificar errores de sintaxis
    $check_result = shell_exec("php -l " . escapeshellarg($full_path) . " 2>&1");
    if (stripos($check_result, 'No syntax errors') !== false) {
        echo "  ✅ Sin errores de sintaxis\n";
    } else {
        echo "  ❌ ERRORES DE SINTAXIS:\n";
        echo "     " . trim($check_result) . "\n";
    }

    echo "\n";
}

// Verificar archivos de control de flujo
echo "=== ARCHIVOS DE CONTROL DE FLUJO ===\n\n";

$control_files = [
    'login.php' => 'Login Unificado',
    'logout.php' => 'Logout',
    'include/checklogin.php' => 'Verificar Login',
];

foreach ($control_files as $file => $description) {
    $full_path = __DIR__ . '/' . $file;

    echo "📄 $description ($file)\n";

    if (!file_exists($full_path)) {
        echo "  ❌ NO EXISTE\n\n";
        continue;
    }

    $content = file_get_contents($full_path);

    // Buscar redirecciones
    preg_match_all('/header\s*\(\s*[\'"]location:\s*([^\'"]+)[\'"]/i', $content, $redirects);
    if (!empty($redirects[1])) {
        echo "  🔀 Redirecciones encontradas:\n";
        foreach ($redirects[1] as $redirect) {
            echo "     → $redirect\n";
        }
    }

    echo "\n";
}

echo "✅ VERIFICACIÓN COMPLETADA\n";
?>
