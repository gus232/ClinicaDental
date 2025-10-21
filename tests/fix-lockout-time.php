<?php
/**
 * Script para Corregir Tiempo de Bloqueo
 * Problema: El bloqueo dura 6 horas en vez de 30 minutos
 */

include('../hms/include/config.php');

echo "<h2>🔧 Corrigiendo Tiempo de Bloqueo</h2>";
echo "<hr>";

// 1. Verificar valor actual
echo "<h3>1. Verificando configuración actual...</h3>";
$check_sql = "SELECT setting_name, setting_value FROM password_policy_config
              WHERE setting_name = 'lockout_duration_minutes'";
$result = mysqli_query($con, $check_sql);

if ($row = mysqli_fetch_assoc($result)) {
    echo "<p>✅ Configuración encontrada:</p>";
    echo "<ul>";
    echo "<li><strong>setting_name:</strong> {$row['setting_name']}</li>";
    echo "<li><strong>setting_value:</strong> <span style='color: red; font-size: 20px;'>{$row['setting_value']}</span></li>";
    echo "</ul>";

    if ($row['setting_value'] != '30') {
        echo "<p style='color: red;'>❌ <strong>PROBLEMA DETECTADO:</strong> El valor debería ser 30, no {$row['setting_value']}</p>";
    } else {
        echo "<p style='color: green;'>✅ El valor es correcto (30 minutos)</p>";
    }
} else {
    echo "<p style='color: red;'>❌ No se encontró la configuración!</p>";
}

echo "<hr>";

// 2. Corregir el valor
echo "<h3>2. Corrigiendo a 30 minutos...</h3>";
$update_sql = "UPDATE password_policy_config
               SET setting_value = '30'
               WHERE setting_name = 'lockout_duration_minutes'";

if (mysqli_query($con, $update_sql)) {
    echo "<p style='color: green;'>✅ <strong>Valor actualizado exitosamente!</strong></p>";
} else {
    echo "<p style='color: red;'>❌ Error: " . mysqli_error($con) . "</p>";
}

echo "<hr>";

// 3. Verificar de nuevo
echo "<h3>3. Verificando corrección...</h3>";
$verify_sql = "SELECT setting_value FROM password_policy_config
               WHERE setting_name = 'lockout_duration_minutes'";
$verify_result = mysqli_query($con, $verify_sql);
$verify_row = mysqli_fetch_assoc($verify_result);

echo "<p><strong>Nuevo valor:</strong> <span style='color: green; font-size: 20px;'>{$verify_row['setting_value']} minutos</span></p>";

echo "<hr>";

// 4. Limpiar cuentas bloqueadas actuales (opcional)
echo "<h3>4. Desbloquear cuentas actualmente bloqueadas (opcional)</h3>";
echo "<form method='post'>";
echo "<p>¿Quieres desbloquear todas las cuentas bloqueadas ahora?</p>";
echo "<button type='submit' name='unlock_all' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;'>Desbloquear Todas las Cuentas</button>";
echo "</form>";

if (isset($_POST['unlock_all'])) {
    $unlock_sql = "UPDATE users SET
                   failed_login_attempts = 0,
                   account_locked_until = NULL
                   WHERE account_locked_until IS NOT NULL";

    if (mysqli_query($con, $unlock_sql)) {
        $affected = mysqli_affected_rows($con);
        echo "<p style='color: green; margin-top: 20px;'>✅ <strong>{$affected} cuenta(s) desbloqueada(s)</strong></p>";
    }
}

echo "<hr>";

// 5. Mostrar todas las configuraciones de seguridad
echo "<h3>5. Todas las Configuraciones de Seguridad</h3>";
$all_config_sql = "SELECT * FROM password_policy_config ORDER BY id";
$all_result = mysqli_query($con, $all_config_sql);

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Setting Name</th>";
echo "<th>Value</th>";
echo "<th>Description</th>";
echo "</tr>";

while ($config = mysqli_fetch_assoc($all_result)) {
    echo "<tr>";
    echo "<td><strong>{$config['setting_name']}</strong></td>";

    // Resaltar si es lockout_duration
    if ($config['setting_name'] == 'lockout_duration_minutes') {
        $color = $config['setting_value'] == '30' ? 'green' : 'red';
        echo "<td style='color: {$color}; font-weight: bold;'>{$config['setting_value']}</td>";
    } else {
        echo "<td>{$config['setting_value']}</td>";
    }

    echo "<td>{$config['description']}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>✅ Corrección Completada</h3>";
echo "<p><a href='../hms/login.php' style='color: #007bff; text-decoration: none;'>← Volver al Login</a></p>";
echo "<p><a href='../hms/admin/unlock-accounts.php' style='color: #007bff; text-decoration: none;'>Ver Panel de Desbloqueo →</a></p>";

mysqli_close($con);
?>
