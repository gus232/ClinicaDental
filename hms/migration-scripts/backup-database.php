<?php
/**
 * BACKUP COMPLETO DE BASE DE DATOS HMS
 * Ejecutar ANTES de cualquier migración
 */

include("include/config.php");

$backup_file = 'backup_hms_' . date('Y-m-d_H-i-s') . '.sql';
$backup_path = __DIR__ . '/backups/' . $backup_file;

// Crear carpeta backups si no existe
if (!file_exists(__DIR__ . '/backups')) {
    mkdir(__DIR__ . '/backups', 0777, true);
}

echo "=== BACKUP DE BASE DE DATOS HMS ===\n\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "Archivo: $backup_file\n\n";

// Obtener todas las tablas
$tables = array();
$result = mysqli_query($con, "SHOW TABLES");
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

echo "Tablas a respaldar: " . implode(', ', $tables) . "\n\n";

$sql_dump = "-- HMS Database Backup\n";
$sql_dump .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
$sql_dump .= "-- Base de datos: hms\n\n";
$sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

// Por cada tabla
foreach ($tables as $table) {
    echo "Respaldando tabla: $table...\n";

    // DROP TABLE
    $sql_dump .= "-- \n";
    $sql_dump .= "-- Tabla: $table\n";
    $sql_dump .= "-- \n";
    $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n\n";

    // CREATE TABLE
    $result = mysqli_query($con, "SHOW CREATE TABLE `$table`");
    $row = mysqli_fetch_row($result);
    $sql_dump .= $row[1] . ";\n\n";

    // INSERT DATA
    $result = mysqli_query($con, "SELECT * FROM `$table`");
    $num_rows = mysqli_num_rows($result);

    if ($num_rows > 0) {
        $sql_dump .= "-- Datos de $table ($num_rows registros)\n";

        while ($row = mysqli_fetch_assoc($result)) {
            $sql_dump .= "INSERT INTO `$table` (";
            $columns = array_keys($row);
            $sql_dump .= "`" . implode("`, `", $columns) . "`";
            $sql_dump .= ") VALUES (";

            $values = array();
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = "NULL";
                } else {
                    $values[] = "'" . mysqli_real_escape_string($con, $value) . "'";
                }
            }
            $sql_dump .= implode(", ", $values);
            $sql_dump .= ");\n";
        }
        $sql_dump .= "\n";
    }
}

$sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

// Guardar archivo
file_put_contents($backup_path, $sql_dump);

echo "\n✅ BACKUP COMPLETADO!\n";
echo "Archivo guardado en: $backup_path\n";
echo "Tamaño: " . round(filesize($backup_path) / 1024, 2) . " KB\n\n";
echo "⚠️  IMPORTANTE: Guarda este archivo en un lugar seguro!\n";

mysqli_close($con);
?>
