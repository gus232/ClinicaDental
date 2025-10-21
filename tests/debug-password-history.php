<?php
/**
 * Debug del Historial de Contraseñas
 */
session_start();
include('../hms/include/config.php');
include('../hms/include/password-policy.php');

// Obtener usuario de prueba
$test_email = 'test@hospital.com';
$user_sql = "SELECT * FROM users WHERE email = ?";
$stmt = mysqli_prepare($con, $user_sql);
mysqli_stmt_bind_param($stmt, "s", $test_email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug - Historial de Contraseñas</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; font-size: 11px; }
        pre { background: #f0f0f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .test-box { background: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Debug del Historial de Contraseñas</h1>

    <div class="section">
        <h2>1. Usuario de Prueba: <?php echo $test_email; ?></h2>
        <?php if ($user): ?>
            <table>
                <tr><th>Campo</th><th>Valor</th></tr>
                <tr><td>ID</td><td><?php echo $user['id']; ?></td></tr>
                <tr><td>Email</td><td><?php echo $user['email']; ?></td></tr>
                <tr><td>Hash Actual</td><td><code><?php echo substr($user['password'], 0, 60); ?>...</code></td></tr>
                <tr><td>Tipo</td><td><?php echo $user['user_type']; ?></td></tr>
            </table>
        <?php else: ?>
            <p class="error">❌ Usuario no encontrado</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>2. Historial de Contraseñas en BD</h2>
        <?php
        if ($user) {
            $history_sql = "SELECT * FROM password_history WHERE user_id = ? ORDER BY changed_at DESC";
            $history_stmt = mysqli_prepare($con, $history_sql);
            mysqli_stmt_bind_param($history_stmt, "i", $user['id']);
            mysqli_stmt_execute($history_stmt);
            $history_result = mysqli_stmt_get_result($history_stmt);

            $history_count = mysqli_num_rows($history_result);

            echo "<p><strong>Total en historial:</strong> {$history_count} contraseña(s)</p>";

            if ($history_count == 0) {
                echo "<p class='error'>❌ PROBLEMA: No hay contraseñas en el historial</p>";
                echo "<p>Esto significa que cuando cambias contraseña, NO se está guardando la anterior.</p>";
            } else {
                echo "<table>";
                echo "<tr><th>#</th><th>Hash (primeros 60 chars)</th><th>Fecha de Cambio</th><th>Cambiado Por</th></tr>";

                $i = 1;
                while ($row = mysqli_fetch_assoc($history_result)) {
                    echo "<tr>";
                    echo "<td>{$i}</td>";
                    echo "<td><code>" . substr($row['password_hash'], 0, 60) . "...</code></td>";
                    echo "<td>{$row['changed_at']}</td>";
                    echo "<td>Usuario ID: {$row['changed_by']}</td>";
                    echo "</tr>";
                    $i++;
                }

                echo "</table>";
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>3. Prueba en Vivo: Verificar Contraseña contra Historial</h2>

        <div class="test-box">
            <h3>Probar si una contraseña está en el historial:</h3>
            <form method="post">
                <p>
                    <label>Contraseña a probar:</label><br>
                    <input type="text" name="test_password" placeholder="Ej: Test123@!" style="padding: 8px; width: 300px;">
                </p>
                <button type="submit" name="test" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    Probar Contraseña
                </button>
            </form>

            <?php
            if (isset($_POST['test']) && $user) {
                $test_password = $_POST['test_password'];

                echo "<hr>";
                echo "<h4>Resultados de la Prueba:</h4>";

                // Probar contra contraseña actual
                echo "<p><strong>1. Comparar con contraseña ACTUAL:</strong></p>";
                if (password_verify($test_password, $user['password'])) {
                    echo "<p class='ok'>✅ COINCIDE con la contraseña actual</p>";
                } else {
                    echo "<p>❌ NO coincide con la contraseña actual</p>";
                }

                // Probar contra historial
                echo "<p><strong>2. Comparar con HISTORIAL:</strong></p>";

                $history_check_sql = "SELECT password_hash, changed_at FROM password_history WHERE user_id = ? ORDER BY changed_at DESC";
                $history_check_stmt = mysqli_prepare($con, $history_check_sql);
                mysqli_stmt_bind_param($history_check_stmt, "i", $user['id']);
                mysqli_stmt_execute($history_check_stmt);
                $history_check_result = mysqli_stmt_get_result($history_check_stmt);

                $found_in_history = false;
                $position = 1;

                while ($hist_row = mysqli_fetch_assoc($history_check_result)) {
                    if (password_verify($test_password, $hist_row['password_hash'])) {
                        echo "<p class='ok'>✅ ENCONTRADA en posición {$position} del historial (fecha: {$hist_row['changed_at']})</p>";
                        $found_in_history = true;
                        break;
                    }
                    $position++;
                }

                if (!$found_in_history) {
                    echo "<p>❌ NO encontrada en el historial</p>";
                }

                // Usar la clase PasswordPolicy
                echo "<p><strong>3. Verificar con PasswordPolicy::checkPasswordHistory():</strong></p>";

                $policy = new PasswordPolicy($con);
                $check_result = $policy->checkPasswordHistory($user['id'], $test_password);

                echo "<pre>";
                print_r($check_result);
                echo "</pre>";

                if ($check_result['allowed']) {
                    echo "<p class='ok'>✅ La clase dice: PERMITIDO (no está en historial)</p>";
                } else {
                    echo "<p class='error'>❌ La clase dice: RECHAZADO (está en historial)</p>";
                    echo "<p>Mensaje: {$check_result['message']}</p>";
                }
            }
            ?>
        </div>
    </div>

    <div class="section">
        <h2>4. Verificar Código de change-password.php</h2>

        <p>El archivo change-password.php debería estar llamando a:</p>
        <pre>$policy->changePassword($user_id, $new_password, $user_id);</pre>

        <p>Esta función internamente llama a:</p>
        <pre>$policy->checkPasswordHistory($user_id, $new_password)</pre>

        <p>Y después guarda en historial con:</p>
        <pre>$policy->saveToHistory($user_id, $old_password_hash, $changed_by)</pre>

        <?php
        // Verificar si el archivo existe y tiene el código correcto
        $change_pass_file = '../hms/change-password.php';
        if (file_exists($change_pass_file)) {
            $content = file_get_contents($change_pass_file);

            $checks = [
                'include password-policy.php' => strpos($content, "include('include/password-policy.php')") !== false,
                'new PasswordPolicy' => strpos($content, 'new PasswordPolicy') !== false,
                'changePassword()' => strpos($content, 'changePassword(') !== false,
            ];

            echo "<table>";
            echo "<tr><th>Verificación</th><th>Estado</th></tr>";

            foreach ($checks as $check => $result) {
                $status = $result ? "<span class='ok'>✅ Encontrado</span>" : "<span class='error'>❌ NO encontrado</span>";
                echo "<tr><td>{$check}</td><td>{$status}</td></tr>";
            }

            echo "</table>";
        }
        ?>
    </div>

    <div class="section">
        <h2>5. Prueba Manual de Guardar en Historial</h2>

        <div class="test-box">
            <h3>Forzar guardado en historial (para pruebas):</h3>
            <form method="post">
                <p>Esto guardará la contraseña ACTUAL en el historial (sin cambiarla):</p>
                <button type="submit" name="force_save" style="padding: 10px 20px; background: #ffc107; color: black; border: none; border-radius: 5px; cursor: pointer;">
                    Guardar Contraseña Actual en Historial
                </button>
            </form>

            <?php
            if (isset($_POST['force_save']) && $user) {
                $policy = new PasswordPolicy($con);
                $policy->saveToHistory($user['id'], $user['password'], $user['id']);

                echo "<p class='ok' style='margin-top: 15px;'>✅ Contraseña guardada en historial</p>";
                echo "<p><a href='debug-password-history.php'>Recargar para ver cambios</a></p>";
            }
            ?>
        </div>
    </div>

    <div class="section">
        <h2>📝 Diagnóstico</h2>

        <?php
        $issues = [];

        // Verificar tabla existe
        $table_check = mysqli_query($con, "SHOW TABLES LIKE 'password_history'");
        if (mysqli_num_rows($table_check) == 0) {
            $issues[] = "❌ La tabla password_history NO existe";
        }

        // Verificar registros en historial
        if ($user && $history_count == 0) {
            $issues[] = "❌ No hay registros en password_history para este usuario";
        }

        // Verificar archivo incluye password-policy
        if (isset($checks) && !$checks['include password-policy.php']) {
            $issues[] = "❌ change-password.php no incluye password-policy.php";
        }

        if (empty($issues)) {
            echo "<p class='ok'>✅ No se detectaron problemas obvios</p>";
            echo "<p><strong>Posibles causas:</strong></p>";
            echo "<ul>";
            echo "<li>El código de saveToHistory() no se está ejecutando</li>";
            echo "<li>Hay un error silencioso en la función</li>";
            echo "<li>La transacción se está haciendo rollback</li>";
            echo "</ul>";
        } else {
            echo "<p class='error'><strong>Problemas Detectados:</strong></p>";
            echo "<ul>";
            foreach ($issues as $issue) {
                echo "<li>{$issue}</li>";
            }
            echo "</ul>";
        }
        ?>
    </div>

    <div class="section">
        <h2>🔧 Solución Sugerida</h2>

        <p>Si el historial no se está guardando, probablemente hay un error en la función changePassword().</p>

        <p><strong>Pasos para corregir:</strong></p>
        <ol>
            <li>Usa el botón "Forzar guardado" arriba para verificar que saveToHistory() funciona</li>
            <li>Si funciona, el problema está en changePassword()</li>
            <li>Revisa el archivo: <code>hms/include/password-policy.php</code> línea ~180</li>
            <li>Agrega logs para depurar</li>
        </ol>
    </div>

    <p><a href="../hms/change-password.php">← Ir a Cambiar Contraseña</a></p>
</body>
</html>
<?php mysqli_close($con); ?>
