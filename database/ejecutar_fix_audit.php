<?php
/**
 * ============================================================================
 * SCRIPT DE CORRECCIÓN: audit_role_changes permite NULL en user_id
 * ============================================================================
 * Ejecutar este archivo UNA VEZ para corregir la tabla
 * URL: http://localhost/hospital/database/ejecutar_fix_audit.php
 * ============================================================================
 */

// Configuración de base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'hms_v2';

// Conectar
$con = mysqli_connect($host, $user, $pass, $db);

if (!$con) {
    die("❌ Error de conexión: " . mysqli_connect_error());
}

echo "<h2>🔧 Corrigiendo estructura de tabla audit_role_changes</h2>";
echo "<hr>";

// Paso 1: Eliminar foreign key
echo "<p><strong>Paso 1:</strong> Eliminando foreign key...</p>";
$sql1 = "ALTER TABLE audit_role_changes DROP FOREIGN KEY audit_role_changes_ibfk_1";
if (mysqli_query($con, $sql1)) {
    echo "<p style='color:green'>✅ Foreign key eliminada</p>";
} else {
    echo "<p style='color:orange'>⚠️ " . mysqli_error($con) . " (puede ser que ya esté eliminada)</p>";
}

// Paso 2: Modificar columna para permitir NULL
echo "<p><strong>Paso 2:</strong> Modificando columna user_id para permitir NULL...</p>";
$sql2 = "ALTER TABLE audit_role_changes MODIFY COLUMN user_id INT NULL COMMENT 'Usuario afectado (NULL = cambio en el rol)'";
if (mysqli_query($con, $sql2)) {
    echo "<p style='color:green'>✅ Columna user_id modificada (ahora permite NULL)</p>";
} else {
    echo "<p style='color:red'>❌ Error: " . mysqli_error($con) . "</p>";
    die();
}

// Paso 3: Recrear foreign key
echo "<p><strong>Paso 3:</strong> Recreando foreign key...</p>";
$sql3 = "ALTER TABLE audit_role_changes ADD CONSTRAINT audit_role_changes_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE";
if (mysqli_query($con, $sql3)) {
    echo "<p style='color:green'>✅ Foreign key recreada</p>";
} else {
    echo "<p style='color:orange'>⚠️ " . mysqli_error($con) . "</p>";
}

// Verificar estructura
echo "<hr>";
echo "<h3>📋 Estructura actual de la tabla:</h3>";
$result = mysqli_query($con, "DESCRIBE audit_role_changes");
echo "<table border='1' cellpadding='10' style='border-collapse:collapse'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    $nullColor = $row['Field'] == 'user_id' && $row['Null'] == 'YES' ? 'background:lightgreen' : '';
    echo "<tr style='$nullColor'>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td><strong>{$row['Null']}</strong></td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2 style='color:green'>✅ CORRECCIÓN COMPLETADA</h2>";
echo "<p>La tabla <code>audit_role_changes</code> ahora permite NULL en la columna <code>user_id</code></p>";
echo "<p><strong>Siguiente paso:</strong> Vuelve a <code>manage-roles.php</code> y prueba asignar permisos a un rol</p>";

mysqli_close($con);
?>
