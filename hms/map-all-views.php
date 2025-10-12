<?php
/**
 * MAPEAR TODAS LAS VISTAS DEL SISTEMA
 * Detecta archivos HTML/PHP y busca enlaces de login
 */

$base_dir = __DIR__;

echo "=== MAPA COMPLETO DE VISTAS DEL SISTEMA ===\n\n";

// 1. Buscar archivo HOME/INDEX
echo "📁 BUSCANDO PÁGINA HOME...\n";
$possible_home = ['index.html', 'index.php', 'home.html', 'home.php', '../index.html', '../index.php'];

$home_file = null;
foreach ($possible_home as $file) {
    if (file_exists($base_dir . '/' . $file)) {
        $home_file = $file;
        echo "  ✅ Encontrado: $file\n";
        break;
    }
}

if (!$home_file) {
    echo "  ⚠️  No se encontró página home en /hms/\n";
    echo "  Buscando en directorio padre...\n";

    if (file_exists($base_dir . '/../index.html')) {
        $home_file = '../index.html';
        echo "  ✅ Encontrado: ../index.html\n";
    } elseif (file_exists($base_dir . '/../index.php')) {
        $home_file = '../index.php';
        echo "  ✅ Encontrado: ../index.php\n";
    }
}

// 2. Analizar HOME y buscar enlaces de login
if ($home_file) {
    echo "\n📄 ANALIZANDO HOME: $home_file\n";
    $content = file_get_contents($base_dir . '/' . $home_file);

    // Buscar enlaces que contengan "login"
    preg_match_all('/<a[^>]*href=["\']([^"\']*login[^"\']*)["\']/i', $content, $matches);

    if (!empty($matches[1])) {
        echo "  Enlaces de login encontrados:\n";
        foreach ($matches[1] as $link) {
            echo "    - $link\n";
        }
    }

    // Buscar enlaces que contengan "appointment" o "cita"
    preg_match_all('/<a[^>]*href=["\']([^"\']*appointment[^"\']*)["\']/i', $content, $matches2);
    preg_match_all('/<a[^>]*href=["\']([^"\']*book[^"\']*)["\']/i', $content, $matches3);

    $appointment_links = array_merge($matches2[1] ?? [], $matches3[1] ?? []);

    if (!empty($appointment_links)) {
        echo "  Enlaces de citas encontrados:\n";
        foreach ($appointment_links as $link) {
            echo "    - $link\n";
        }
    }
}

// 3. Listar TODAS las páginas PHP en /hms
echo "\n📂 TODAS LAS VISTAS EN /hms/:\n";
$files = glob($base_dir . '/*.{php,html}', GLOB_BRACE);
foreach ($files as $file) {
    $filename = basename($file);
    $size = filesize($file);

    if ($size > 100) { // Solo archivos con contenido
        echo "  - $filename (" . round($size/1024, 1) . " KB)\n";

        // Si contiene "login" en el nombre
        if (stripos($filename, 'login') !== false) {
            echo "    ⚠️  ARCHIVO DE LOGIN\n";
        }
    }
}

// 4. Buscar TODOS los archivos que referencian login antiguo
echo "\n🔍 BUSCANDO REFERENCIAS A LOGIN ANTIGUO...\n";

$all_files = array_merge(
    glob($base_dir . '/*.php'),
    glob($base_dir . '/*.html'),
    glob($base_dir . '/../*.php'),
    glob($base_dir . '/../*.html')
);

$old_logins = ['user-login.php', 'admin/index.php', 'doctor/index.php'];
$references = [];

foreach ($all_files as $file) {
    if (filesize($file) < 10) continue; // Saltar vacíos

    $content = file_get_contents($file);

    foreach ($old_logins as $old_login) {
        if (stripos($content, $old_login) !== false) {
            $filename = str_replace($base_dir, '', $file);
            if (!isset($references[$filename])) {
                $references[$filename] = [];
            }
            $references[$filename][] = $old_login;
        }
    }
}

if (!empty($references)) {
    echo "  ⚠️  Archivos que referencian logins antiguos:\n";
    foreach ($references as $file => $logins) {
        echo "    📄 $file\n";
        foreach ($logins as $login) {
            echo "       → $login\n";
        }
    }
} else {
    echo "  ✅ No se encontraron referencias a logins antiguos\n";
}

// 5. Estructura de directorios
echo "\n📁 ESTRUCTURA DE DIRECTORIOS:\n";
echo "  /hospital/\n";
echo "    ├── index.html (HOME público)\n";
echo "    └── /hms/\n";
echo "        ├── login.php (NUEVO - unificado)\n";
echo "        ├── user-login.php (ANTIGUO - deprecar)\n";
echo "        ├── /admin/\n";
echo "        │   └── index.php (ANTIGUO - deprecar)\n";
echo "        └── /doctor/\n";
echo "            └── index.php (ANTIGUO - deprecar)\n";

echo "\n✅ MAPEO COMPLETADO\n";
?>
