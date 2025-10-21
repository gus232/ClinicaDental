<?php
/**
 * Script de Verificación de Migración 002
 * Verifica que todas las tablas y campos se hayan creado correctamente
 */

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hms_v2";

$con = mysqli_connect($servername, $username, $password, $dbname);

if (!$con) {
    die("❌ Error de conexión: " . mysqli_connect_error());
}

echo "=================================================================\n";
echo "VERIFICACIÓN DE MIGRACIÓN 002 - SEGURIDAD DE CONTRASEÑAS\n";
echo "=================================================================\n\n";

$all_ok = true;

// ========================================================================
// 1. VERIFICAR CAMPOS NUEVOS EN TABLA USERS
// ========================================================================
echo "1. Verificando campos nuevos en tabla 'users'...\n";
echo "-----------------------------------------------------------------\n";

$required_fields = [
    'failed_login_attempts',
    'account_locked_until',
    'password_expires_at',
    'password_changed_at',
    'last_login_ip',
    'force_password_change'
];

$result = mysqli_query($con, "DESCRIBE users");
$existing_fields = [];

while ($row = mysqli_fetch_assoc($result)) {
    $existing_fields[] = $row['Field'];
}

foreach ($required_fields as $field) {
    if (in_array($field, $existing_fields)) {
        echo "   ✅ Campo '$field' encontrado\n";
    } else {
        echo "   ❌ Campo '$field' NO encontrado\n";
        $all_ok = false;
    }
}

echo "\n";

// ========================================================================
// 2. VERIFICAR TABLAS NUEVAS
// ========================================================================
echo "2. Verificando tablas nuevas...\n";
echo "-----------------------------------------------------------------\n";

$required_tables = [
    'password_history',
    'password_reset_tokens',
    'login_attempts',
    'password_policy_config'
];

$result = mysqli_query($con, "SHOW TABLES");
$existing_tables = [];

while ($row = mysqli_fetch_array($result)) {
    $existing_tables[] = $row[0];
}

foreach ($required_tables as $table) {
    if (in_array($table, $existing_tables)) {
        echo "   ✅ Tabla '$table' encontrada\n";

        // Contar registros
        $count_result = mysqli_query($con, "SELECT COUNT(*) as count FROM $table");
        $count_row = mysqli_fetch_assoc($count_result);
        echo "      → Registros: {$count_row['count']}\n";
    } else {
        echo "   ❌ Tabla '$table' NO encontrada\n";
        $all_ok = false;
    }
}

echo "\n";

// ========================================================================
// 3. VERIFICAR CONFIGURACIÓN DE POLÍTICAS
// ========================================================================
echo "3. Verificando configuración de políticas...\n";
echo "-----------------------------------------------------------------\n";

if (in_array('password_policy_config', $existing_tables)) {
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM password_policy_config");
    $row = mysqli_fetch_assoc($result);

    if ($row['count'] >= 13) {
        echo "   ✅ Configuración cargada: {$row['count']} políticas\n";

        // Mostrar algunas políticas
        $policies = mysqli_query($con, "SELECT setting_name, setting_value FROM password_policy_config LIMIT 5");
        while ($policy = mysqli_fetch_assoc($policies)) {
            echo "      → {$policy['setting_name']} = {$policy['setting_value']}\n";
        }
    } else {
        echo "   ⚠️ Solo {$row['count']} políticas encontradas (se esperaban 13)\n";
    }
} else {
    echo "   ❌ Tabla password_policy_config no encontrada\n";
    $all_ok = false;
}

echo "\n";

// ========================================================================
// 4. VERIFICAR VISTAS
// ========================================================================
echo "4. Verificando vistas creadas...\n";
echo "-----------------------------------------------------------------\n";

$required_views = [
    'users_password_expiring_soon',
    'locked_accounts'
];

$result = mysqli_query($con, "SHOW FULL TABLES WHERE TABLE_TYPE LIKE 'VIEW'");
$existing_views = [];

while ($row = mysqli_fetch_array($result)) {
    $existing_views[] = $row[0];
}

foreach ($required_views as $view) {
    if (in_array($view, $existing_views)) {
        echo "   ✅ Vista '$view' encontrada\n";
    } else {
        echo "   ❌ Vista '$view' NO encontrada\n";
        $all_ok = false;
    }
}

echo "\n";

// ========================================================================
// 5. VERIFICAR STORED PROCEDURES
// ========================================================================
echo "5. Verificando stored procedures...\n";
echo "-----------------------------------------------------------------\n";

$result = mysqli_query($con, "SHOW PROCEDURE STATUS WHERE Db = 'hms_v2'");
$procedures = [];

while ($row = mysqli_fetch_assoc($result)) {
    $procedures[] = $row['Name'];
}

if (in_array('cleanup_old_security_data', $procedures)) {
    echo "   ✅ Procedure 'cleanup_old_security_data' encontrado\n";
} else {
    echo "   ❌ Procedure 'cleanup_old_security_data' NO encontrado\n";
    $all_ok = false;
}

echo "\n";

// ========================================================================
// 6. VERIFICAR DATOS EXISTENTES
// ========================================================================
echo "6. Verificando datos de usuarios existentes...\n";
echo "-----------------------------------------------------------------\n";

$result = mysqli_query($con, "SELECT COUNT(*) as count FROM users");
$row = mysqli_fetch_assoc($result);
echo "   → Total de usuarios: {$row['count']}\n";

// Verificar que los campos se actualizaron
$result = mysqli_query($con, "
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN password_changed_at IS NOT NULL THEN 1 ELSE 0 END) as with_changed_at,
        SUM(CASE WHEN password_expires_at IS NOT NULL THEN 1 ELSE 0 END) as with_expires_at
    FROM users
");
$row = mysqli_fetch_assoc($result);

echo "   → Con password_changed_at: {$row['with_changed_at']}/{$row['total']}\n";
echo "   → Con password_expires_at: {$row['with_expires_at']}/{$row['total']}\n";

if ($row['with_changed_at'] == $row['total'] && $row['with_expires_at'] == $row['total']) {
    echo "   ✅ Todos los usuarios actualizados correctamente\n";
} else {
    echo "   ⚠️ Algunos usuarios no tienen las fechas actualizadas\n";
}

echo "\n";

// ========================================================================
// RESUMEN FINAL
// ========================================================================
echo "=================================================================\n";
if ($all_ok) {
    echo "✅ MIGRACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "=================================================================\n";
    echo "\nTodas las tablas, campos y configuraciones se crearon correctamente.\n";
    echo "El sistema está listo para implementar políticas de contraseñas.\n\n";
    echo "SIGUIENTE PASO:\n";
    echo "- Modificar login.php para bloqueo al 3er intento\n";
    echo "- Actualizar change-password.php con validaciones\n";
    echo "- Crear módulo de desbloqueo para admin\n\n";
} else {
    echo "⚠️ MIGRACIÓN INCOMPLETA\n";
    echo "=================================================================\n";
    echo "\nAlgunos elementos no se crearon correctamente.\n";
    echo "Revisa los errores marcados con ❌ arriba.\n\n";
    echo "SOLUCIÓN:\n";
    echo "1. Ejecuta el rollback: 002_password_security_rollback.sql\n";
    echo "2. Vuelve a ejecutar: 002_password_security.sql\n\n";
}

mysqli_close($con);
?>
