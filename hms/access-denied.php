<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - HMS</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .container {
            max-width: 600px;
            width: 90%;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            text-align: center;
        }

        .error-icon {
            font-size: 80px;
            color: #e74c3c;
            margin-bottom: 20px;
        }

        h1 {
            color: #e74c3c;
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .error-code {
            font-size: 18px;
            color: #95a5a6;
            margin-bottom: 30px;
            font-weight: 300;
        }

        .message {
            font-size: 16px;
            color: #555;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .details {
            background: #f8f9fa;
            border-left: 4px solid #e74c3c;
            padding: 15px 20px;
            text-align: left;
            margin-bottom: 30px;
            border-radius: 5px;
        }

        .details p {
            margin: 8px 0;
            font-size: 14px;
            color: #555;
        }

        .details strong {
            color: #333;
            font-weight: 600;
        }

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 13px;
            color: #999;
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 24px;
            }

            .error-icon {
                font-size: 60px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">
            🚫
        </div>

        <h1>Acceso Denegado</h1>
        <p class="error-code">Error 403 - Forbidden</p>

        <div class="message">
            <p>Lo sentimos, no tienes los permisos necesarios para acceder a este recurso.</p>
        </div>

        <?php
        // Mostrar detalles del permiso/rol requerido
        $permission = $_GET['permission'] ?? null;
        $role = $_GET['role'] ?? null;
        $roles = $_GET['roles'] ?? null;
        $reason = $_GET['reason'] ?? null;

        if ($permission || $role || $roles || $reason):
        ?>
        <div class="details">
            <?php if ($permission): ?>
                <p><strong>Permiso requerido:</strong> <?php echo htmlspecialchars($permission); ?></p>
                <p>Necesitas el permiso específico "<strong><?php echo htmlspecialchars($permission); ?></strong>" para realizar esta acción.</p>
            <?php endif; ?>

            <?php if ($role): ?>
                <p><strong>Rol requerido:</strong> <?php echo htmlspecialchars($role); ?></p>
                <p>Esta página requiere el rol de "<strong><?php echo htmlspecialchars($role); ?></strong>".</p>
            <?php endif; ?>

            <?php if ($roles): ?>
                <p><strong>Roles requeridos:</strong> <?php echo htmlspecialchars($roles); ?></p>
                <p>Necesitas uno de los siguientes roles: <strong><?php echo htmlspecialchars($roles); ?></strong>.</p>
            <?php endif; ?>

            <?php if ($reason === 'data_access'): ?>
                <p><strong>Razón:</strong> Solo puedes acceder a tus propios datos.</p>
            <?php endif; ?>

            <p style="margin-top: 15px; color: #7f8c8d; font-size: 13px;">
                Si crees que esto es un error, contacta con el administrador del sistema.
            </p>
        </div>
        <?php endif; ?>

        <div class="actions">
            <a href="javascript:history.back()" class="btn btn-secondary">
                ← Volver Atrás
            </a>
            <a href="dashboard.php" class="btn btn-primary">
                Ir al Dashboard
            </a>
        </div>

        <div class="footer">
            <p>Hospital Management System - SIS 321</p>
            <p>Si necesitas ayuda, contacta al administrador</p>
        </div>
    </div>
</body>
</html>
