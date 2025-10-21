<?php
/**
 * Generador de Hashes Bcrypt para Contraseñas
 * Uso: http://localhost/hospital/tests/generate-hash.php
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Hash Bcrypt</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { padding: 50px; background: #f5f5f5; }
        .container { max-width: 800px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .hash-output { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; word-break: break-all; }
        .predefined-passwords { margin-top: 30px; }
        .predefined-passwords table { font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fa fa-lock"></i> Generador de Hash Bcrypt</h2>
        <p class="text-muted">Genera hashes seguros para contraseñas de prueba</p>
        <hr>

        <?php
        if (isset($_POST['generate'])) {
            $password = $_POST['password'];
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            echo "<div class='alert alert-success'>";
            echo "<h5>Hash Generado:</h5>";
            echo "<div class='hash-output'>{$hash}</div>";
            echo "<p class='mt-3 mb-0'><strong>Contraseña:</strong> {$password}</p>";
            echo "</div>";
        }
        ?>

        <form method="post">
            <div class="form-group">
                <label>Ingrese la contraseña:</label>
                <input type="text" name="password" class="form-control" placeholder="Ej: Test123@!" required>
                <small class="form-text text-muted">
                    Debe cumplir: min 8 chars, 1 mayúscula, 1 minúscula, 1 número, 1 especial
                </small>
            </div>
            <button type="submit" name="generate" class="btn btn-primary">
                Generar Hash
            </button>
        </form>

        <div class="predefined-passwords">
            <h5>Hashes Pre-generados para Pruebas:</h5>
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Contraseña</th>
                        <th>Hash Bcrypt (cost=12)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $predefined = [
                        'Test123@!' => password_hash('Test123@!', PASSWORD_BCRYPT, ['cost' => 12]),
                        'Admin123@!' => password_hash('Admin123@!', PASSWORD_BCRYPT, ['cost' => 12]),
                        'Doctor123@!' => password_hash('Doctor123@!', PASSWORD_BCRYPT, ['cost' => 12]),
                        'NewPass123@!' => password_hash('NewPass123@!', PASSWORD_BCRYPT, ['cost' => 12]),
                    ];

                    foreach ($predefined as $pass => $hash) {
                        echo "<tr>";
                        echo "<td><strong>{$pass}</strong></td>";
                        echo "<td><code style='font-size: 10px;'>{$hash}</code></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-info mt-4">
            <h6>Cómo usar:</h6>
            <ol class="mb-0">
                <li>Copia el hash generado</li>
                <li>Ve a phpMyAdmin → Tabla users</li>
                <li>Inserta un nuevo registro con el hash en el campo 'password'</li>
                <li>O usa el script <code>create-test-users.php</code></li>
            </ol>
        </div>
    </div>
</body>
</html>
