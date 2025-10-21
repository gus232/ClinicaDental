<?php
/**
 * Debug del Sistema de Bloqueo
 */
include('../hms/include/config.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug - Sistema de Bloqueo</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Debug del Sistema de Bloqueo</h1>

    <div class="section">
        <h2>1. Configuración en password_policy_config</h2>
        <?php
        $config_sql = "SELECT * FROM password_policy_config WHERE setting_name IN ('max_failed_attempts', 'lockout_duration_minutes')";
        $config_result = mysqli_query($con, $config_sql);

        echo "<table>";
        echo "<tr><th>Setting Name</th><th>Value</th><th>Estado</th></tr>";

        $lockout_minutes = null;
        while ($row = mysqli_fetch_assoc($config_result)) {
            if ($row['setting_name'] == 'lockout_duration_minutes') {
                $lockout_minutes = $row['setting_value'];
                $status = ($row['setting_value'] == '30') ? "<span class='ok'>✅ Correcto</span>" : "<span class='error'>❌ Incorrecto (debería ser 30)</span>";
            } else {
                $status = ($row['setting_value'] == '3') ? "<span class='ok'>✅ Correcto</span>" : "<span class='error'>❌ Incorrecto (debería ser 3)</span>";
            }

            echo "<tr>";
            echo "<td><strong>{$row['setting_name']}</strong></td>";
            echo "<td><code>{$row['setting_value']}</code></td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
        }
        echo "</table>";
        ?>
    </div>

    <div class="section">
        <h2>2. Cuentas Bloqueadas Actuales</h2>
        <?php
        $locked_sql = "SELECT
            id,
            email,
            failed_login_attempts,
            account_locked_until,
            NOW() as current_time,
            TIMESTAMPDIFF(MINUTE, NOW(), account_locked_until) as minutes_remaining,
            TIMESTAMPDIFF(HOUR, NOW(), account_locked_until) as hours_remaining
            FROM users
            WHERE account_locked_until IS NOT NULL
            AND account_locked_until > NOW()";

        $locked_result = mysqli_query($con, $locked_sql);

        if (mysqli_num_rows($locked_result) == 0) {
            echo "<p class='ok'>✅ No hay cuentas bloqueadas actualmente</p>";
        } else {
            echo "<table>";
            echo "<tr><th>Email</th><th>Intentos</th><th>Bloqueado Hasta</th><th>Hora Actual</th><th>Minutos Restantes</th><th>Horas Restantes</th><th>Análisis</th></tr>";

            while ($row = mysqli_fetch_assoc($locked_result)) {
                echo "<tr>";
                echo "<td>{$row['email']}</td>";
                echo "<td>{$row['failed_login_attempts']}</td>";
                echo "<td><code>{$row['account_locked_until']}</code></td>";
                echo "<td><code>{$row['current_time']}</code></td>";
                echo "<td>{$row['minutes_remaining']} min</td>";
                echo "<td>{$row['hours_remaining']} hrs</td>";

                // Análisis
                if ($row['hours_remaining'] >= 6) {
                    echo "<td class='error'>❌ PROBLEMA: Bloqueado por {$row['hours_remaining']} horas</td>";
                } elseif ($row['minutes_remaining'] <= 30) {
                    echo "<td class='ok'>✅ Correcto: ~30 minutos</td>";
                } else {
                    echo "<td class='error'>❌ {$row['minutes_remaining']} minutos (debería ser ≤30)</td>";
                }

                echo "</tr>";
            }
            echo "</table>";
        }
        ?>
    </div>

    <div class="section">
        <h2>3. Simular Bloqueo</h2>
        <p>Vamos a simular qué pasaría si bloqueáramos una cuenta AHORA:</p>

        <?php
        // Obtener configuración
        $config_sql = "SELECT setting_value FROM password_policy_config WHERE setting_name = 'lockout_duration_minutes'";
        $config_result = mysqli_query($con, $config_sql);
        $config_row = mysqli_fetch_assoc($config_result);
        $duration = $config_row['setting_value'];

        // Calcular fecha de bloqueo (EXACTAMENTE como lo hace login.php)
        $lockout_until = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));
        $current_time = date('Y-m-d H:i:s');

        echo "<table>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>Duración configurada</td><td><code>{$duration}</code> minutos</td></tr>";
        echo "<tr><td>Hora actual</td><td><code>{$current_time}</code></td></tr>";
        echo "<tr><td>Bloqueado hasta</td><td><code>{$lockout_until}</code></td></tr>";

        // Calcular diferencia
        $diff_seconds = strtotime($lockout_until) - strtotime($current_time);
        $diff_minutes = round($diff_seconds / 60);
        $diff_hours = round($diff_seconds / 3600, 2);

        echo "<tr><td>Diferencia en minutos</td><td><code>{$diff_minutes}</code></td></tr>";
        echo "<tr><td>Diferencia en horas</td><td><code>{$diff_hours}</code></td></tr>";

        if ($diff_minutes <= 30 && $diff_minutes >= 29) {
            echo "<tr><td>Estado</td><td class='ok'>✅ CORRECTO: Bloqueo de 30 minutos</td></tr>";
        } else {
            echo "<tr><td>Estado</td><td class='error'>❌ ERROR: No son 30 minutos</td></tr>";
        }

        echo "</table>";
        ?>
    </div>

    <div class="section">
        <h2>4. Historial de login_attempts</h2>
        <?php
        $attempts_sql = "SELECT
            email,
            attempt_result,
            attempted_at,
            ip_address
            FROM login_attempts
            ORDER BY attempted_at DESC
            LIMIT 10";

        $attempts_result = mysqli_query($con, $attempts_sql);

        echo "<table>";
        echo "<tr><th>Email</th><th>Resultado</th><th>Fecha/Hora</th><th>IP</th></tr>";

        while ($row = mysqli_fetch_assoc($attempts_result)) {
            echo "<tr>";
            echo "<td>{$row['email']}</td>";
            echo "<td><code>{$row['attempt_result']}</code></td>";
            echo "<td>{$row['attempted_at']}</td>";
            echo "<td>{$row['ip_address']}</td>";
            echo "</tr>";
        }

        echo "</table>";
        ?>
    </div>

    <div class="section">
        <h2>5. Código PHP que genera el bloqueo (login.php)</h2>
        <p>Este es el código exacto que se ejecuta en login.php línea 275:</p>
        <pre style="background: #f0f0f0; padding: 15px; border-radius: 5px; overflow-x: auto;">
$lockout_minutes = <?php echo $duration; ?>; // De la BD
$lockout_until = date('Y-m-d H:i:s', strtotime("+{$lockout_minutes} minutes"));

// Resultado:
// "<?php echo $lockout_until; ?>"
        </pre>
    </div>

    <div class="section">
        <h2>🔧 Acciones de Corrección</h2>
        <form method="post">
            <h3>Opción 1: Corregir valor en BD</h3>
            <button type="submit" name="fix_config" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Actualizar lockout_duration_minutes a 30
            </button>

            <h3>Opción 2: Desbloquear todas las cuentas</h3>
            <button type="submit" name="unlock_all" style="padding: 10px 20px; background: #ffc107; color: black; border: none; border-radius: 5px; cursor: pointer;">
                Desbloquear Todas las Cuentas
            </button>
        </form>

        <?php
        if (isset($_POST['fix_config'])) {
            $update_sql = "UPDATE password_policy_config SET setting_value = '30' WHERE setting_name = 'lockout_duration_minutes'";
            if (mysqli_query($con, $update_sql)) {
                echo "<p class='ok' style='margin-top: 15px;'>✅ Configuración actualizada a 30 minutos</p>";
                echo "<p><a href='debug-lockout.php'>Recargar página para ver cambios</a></p>";
            }
        }

        if (isset($_POST['unlock_all'])) {
            $unlock_sql = "UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE account_locked_until IS NOT NULL";
            $result = mysqli_query($con, $unlock_sql);
            $affected = mysqli_affected_rows($con);
            echo "<p class='ok' style='margin-top: 15px;'>✅ {$affected} cuenta(s) desbloqueada(s)</p>";
            echo "<p><a href='debug-lockout.php'>Recargar página para ver cambios</a></p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>📝 Resumen del Diagnóstico</h2>
        <ul>
            <li><strong>Configuración en BD:</strong>
                <?php
                $check_sql = "SELECT setting_value FROM password_policy_config WHERE setting_name = 'lockout_duration_minutes'";
                $check_result = mysqli_query($con, $check_sql);
                $check_row = mysqli_fetch_assoc($check_result);
                $current_value = $check_row['setting_value'];

                if ($current_value == '30') {
                    echo "<span class='ok'>✅ Correcto ({$current_value} minutos)</span>";
                } else {
                    echo "<span class='error'>❌ Incorrecto ({$current_value} minutos, debería ser 30)</span>";
                }
                ?>
            </li>
            <li><strong>Cuentas bloqueadas:</strong>
                <?php
                $count_sql = "SELECT COUNT(*) as count FROM users WHERE account_locked_until > NOW()";
                $count_result = mysqli_query($con, $count_sql);
                $count_row = mysqli_fetch_assoc($count_result);
                echo $count_row['count'] > 0 ? "{$count_row['count']} cuenta(s)" : "<span class='ok'>Ninguna</span>";
                ?>
            </li>
        </ul>
    </div>

    <p><a href="../hms/admin/unlock-accounts.php">← Volver a Panel de Desbloqueo</a></p>
</body>
</html>
<?php mysqli_close($con); ?>
