<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test reCAPTCHA v2</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .recaptcha-container {
            display: flex;
            justify-content: center;
            margin: 30px 0;
        }
        button {
            width: 100%;
            padding: 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #5568d3;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test de reCAPTCHA v2</h1>

        <div class="info">
            <p><strong>Site Key:</strong> 6LdwHvorAAAAAORkpk1do93ydCb34HEGuYREyD73</p>
            <p><strong>Dominio Actual:</strong> <?php echo $_SERVER['HTTP_HOST']; ?></p>
            <p><strong>Instrucciones:</strong> Si el checkbox de reCAPTCHA aparece abajo, tus claves están correctas.</p>
        </div>

        <form method="post" id="testForm">
            <div class="recaptcha-container">
                <div class="g-recaptcha" data-sitekey="6LdwHvorAAAAAORkpk1do93ydCb34HEGuYREyD73"></div>
            </div>

            <button type="submit">Probar reCAPTCHA</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

            if (empty($recaptcha_response)) {
                echo '<div class="result error" style="display:block;">❌ <strong>Error:</strong> No completaste el reCAPTCHA</div>';
            } else {
                // Verificar con Google
                $secret_key = '6LdwHvorAAAAAL7F2YKFT3KDyUTaJhjxeu-yhRHS';
                $verify_url = 'https://www.google.com/recaptcha/api/siteverify';

                $data = [
                    'secret' => $secret_key,
                    'response' => $recaptcha_response,
                    'remoteip' => $_SERVER['REMOTE_ADDR']
                ];

                $options = [
                    'http' => [
                        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method' => 'POST',
                        'content' => http_build_query($data)
                    ]
                ];

                $context = stream_context_create($options);
                $result = file_get_contents($verify_url, false, $context);
                $response = json_decode($result, true);

                if ($response['success']) {
                    echo '<div class="result success" style="display:block;">✅ <strong>¡Éxito!</strong> reCAPTCHA verificado correctamente</div>';
                    echo '<div class="info"><pre>' . print_r($response, true) . '</pre></div>';
                } else {
                    echo '<div class="result error" style="display:block;">❌ <strong>Error de Verificación:</strong><br>';
                    echo 'Códigos de error: ' . implode(', ', $response['error-codes'] ?? []) . '</div>';
                    echo '<div class="info"><pre>' . print_r($response, true) . '</pre></div>';
                }
            }
        }
        ?>

        <div class="info" style="margin-top: 30px;">
            <h3>📋 Checklist de Diagnóstico:</h3>
            <p>✓ Si NO ves el checkbox arriba:</p>
            <ul>
                <li>Las claves no son correctas, O</li>
                <li>El dominio "<?php echo $_SERVER['HTTP_HOST']; ?>" no está autorizado en Google reCAPTCHA</li>
            </ul>
            <p>✓ Si SÍ ves el checkbox:</p>
            <ul>
                <li>Marca el checkbox y haz clic en "Probar reCAPTCHA"</li>
                <li>Si dice "¡Éxito!", todo está funcionando correctamente</li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="login.php" style="color: #667eea;">← Volver al Login</a>
        </div>
    </div>

    <script>
        console.log('✅ Script de reCAPTCHA cargado');
        console.log('Tipo de grecaptcha:', typeof grecaptcha);

        // Verificar si se cargó correctamente
        setTimeout(function() {
            if (typeof grecaptcha !== 'undefined') {
                console.log('✅ grecaptcha está disponible');
            } else {
                console.error('❌ grecaptcha NO está disponible - posible problema de red o dominio bloqueado');
            }
        }, 2000);
    </script>
</body>
</html>
