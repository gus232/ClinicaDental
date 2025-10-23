<?php
/**
 * ============================================================================
 * SCRIPT: Actualizar iconos a Font Awesome 4
 * ============================================================================
 * El proyecto usa Font Awesome 4.x, pero algunos iconos están definidos
 * con nombres de Font Awesome 5+
 * ============================================================================
 */

// Configuración
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'hms_v2';

// Conectar
$con = mysqli_connect($host, $user, $pass, $db);

if (!$con) {
    die("❌ Error de conexión: " . mysqli_connect_error());
}

echo "<h2>🔧 Actualizando iconos a Font Awesome 4</h2>";
echo "<hr>";

// Mapeo de iconos: FA5 → FA4
$icon_updates = [
    'patients' => 'fa-wheelchair',
    'appointments' => 'fa-calendar',
    'medical_records' => 'fa-file-text-o',
    'billing' => 'fa-usd',
    'reports' => 'fa-bar-chart',
    'security' => 'fa-shield'
];

echo "<h3>📝 Actualizaciones a realizar:</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse:collapse; margin-bottom:20px;'>";
echo "<tr><th>Categoría</th><th>Icono Anterior</th><th>Icono Nuevo (FA4)</th><th>Estado</th></tr>";

foreach ($icon_updates as $category => $new_icon) {
    // Obtener icono actual
    $query = "SELECT icon FROM permission_categories WHERE category_name = '$category'";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    $old_icon = $row['icon'] ?? 'N/A';
    
    // Actualizar
    $update_sql = "UPDATE permission_categories SET icon = '$new_icon' WHERE category_name = '$category'";
    
    if (mysqli_query($con, $update_sql)) {
        $status = "<span style='color:green'>✅ Actualizado</span>";
    } else {
        $status = "<span style='color:red'>❌ Error</span>";
    }
    
    echo "<tr>";
    echo "<td><strong>$category</strong></td>";
    echo "<td>$old_icon</td>";
    echo "<td><strong style='color:#4CAF50'>$new_icon</strong></td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";

// Mostrar resultado final
echo "<hr>";
echo "<h3>📋 Iconos actuales en la base de datos:</h3>";
$result = mysqli_query($con, "SELECT category_name, display_name, icon FROM permission_categories ORDER BY sort_order");

echo "<table border='1' cellpadding='10' style='border-collapse:collapse'>";
echo "<tr style='background:#4CAF50; color:white;'><th>Categoría</th><th>Nombre</th><th>Icono</th><th>Vista Previa</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['category_name']}</td>";
    echo "<td>{$row['display_name']}</td>";
    echo "<td><code>{$row['icon']}</code></td>";
    echo "<td style='text-align:center; font-size:24px;'><i class='fa {$row['icon']}'></i></td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h2 style='color:green'>✅ ICONOS ACTUALIZADOS CORRECTAMENTE</h2>";
echo "<p><strong>Siguiente paso:</strong> Actualiza <code>manage-roles.php</code> (F5) para ver los cambios</p>";

mysqli_close($con);
?>

<!-- Font Awesome 4 para ver los iconos en este reporte -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
