<?php
/**
 * HASHEAR CONTRASEÑAS DE ADMIN A BCRYPT
 * Ejecutar ANTES de migrar tabla admin
 */

include("include/config.php");

echo "=== MIGRACIÓN DE CONTRASEÑAS ADMIN A BCRYPT ===\n\n";

// Obtener todos los admin
$sql = "SELECT id, username, password FROM admin";
$result = mysqli_query($con, $sql);

$migrated = 0;
$already_hashed = 0;

while ($admin = mysqli_fetch_assoc($result)) {
    $id = $admin['id'];
    $username = $admin['username'];
    $password = $admin['password'];

    echo "Procesando admin ID: $id | Username: $username\n";

    // Verificar si ya está hasheada (bcrypt tiene 60 caracteres)
    if (strlen($password) === 60 && substr($password, 0, 4) === '$2y$') {
        echo "  ⏭️  Ya está hasheada con bcrypt\n";
        $already_hashed++;
    } else {
        echo "  🔓 Contraseña en texto plano: $password\n";

        // Hashear con bcrypt
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        // Actualizar en BD
        $update_sql = "UPDATE admin SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($con, $update_sql);
        mysqli_stmt_bind_param($stmt, "si", $hashed, $id);

        if (mysqli_stmt_execute($stmt)) {
            echo "  ✅ Migrada a bcrypt exitosamente\n";
            echo "  🔐 Nueva hash: " . substr($hashed, 0, 30) . "...\n";
            $migrated++;
        } else {
            echo "  ❌ Error al migrar: " . mysqli_error($con) . "\n";
        }

        mysqli_stmt_close($stmt);
    }
    echo "\n";
}

echo "==========================================\n";
echo "RESUMEN:\n";
echo "  - Contraseñas migradas: $migrated\n";
echo "  - Ya estaban hasheadas: $already_hashed\n";
echo "  - Total procesadas: " . ($migrated + $already_hashed) . "\n";
echo "\n✅ PROCESO COMPLETADO!\n\n";

// Verificar resultado
echo "Verificando resultado...\n";
$sql = "SELECT id, username, LEFT(password, 30) as pass_preview FROM admin";
$result = mysqli_query($con, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    echo "  ID: {$row['id']} | {$row['username']} | {$row['pass_preview']}...\n";
}

mysqli_close($con);
?>
