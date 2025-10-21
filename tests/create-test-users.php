<?php
/**
 * Script para Crear Usuarios de Prueba
 * Uso: http://localhost/hospital/tests/create-test-users.php
 */

// Incluir configuración de base de datos
include('../hms/include/config.php');

$messages = [];
$created_users = [];

// Verificar si se envió el formulario
if (isset($_POST['create_users'])) {
    $users_to_create = [
        [
            'email' => 'test@hospital.com',
            'password' => 'Test123@!',
            'user_type' => 'patient',
            'full_name' => 'Usuario Prueba',
            'address' => 'Calle Falsa 123',
            'city' => 'La Paz',
            'gender' => 'Male'
        ],
        [
            'email' => 'admin@hospital.com',
            'password' => 'Admin123@!',
            'user_type' => 'admin',
            'full_name' => 'Administrador Sistema',
            'address' => 'Oficina Central',
            'city' => 'La Paz',
            'gender' => 'Male'
        ],
        [
            'email' => 'doctor@hospital.com',
            'password' => 'Doctor123@!',
            'user_type' => 'doctor',
            'full_name' => 'Dr. Juan Pérez',
            'address' => 'Consultorio 1',
            'city' => 'La Paz',
            'gender' => 'Male'
        ]
    ];

    foreach ($users_to_create as $user_data) {
        // Verificar si el usuario ya existe
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = mysqli_prepare($con, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $user_data['email']);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $messages[] = [
                'type' => 'warning',
                'message' => "Usuario {$user_data['email']} ya existe, saltando..."
            ];
            continue;
        }

        // Hashear contraseña
        $password_hash = password_hash($user_data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        // Insertar usuario
        $insert_sql = "INSERT INTO users (
            email,
            password,
            user_type,
            full_name,
            status,
            created_at,
            updated_at,
            password_changed_at,
            password_expires_at,
            failed_login_attempts
        ) VALUES (?, ?, ?, ?, 'active', NOW(), NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 0)";

        $insert_stmt = mysqli_prepare($con, $insert_sql);
        mysqli_stmt_bind_param(
            $insert_stmt,
            "ssss",
            $user_data['email'],
            $password_hash,
            $user_data['user_type'],
            $user_data['full_name']
        );

        if (mysqli_stmt_execute($insert_stmt)) {
            $user_id = mysqli_insert_id($con);

            // Insertar datos específicos según tipo de usuario
            switch ($user_data['user_type']) {
                case 'patient':
                    $patient_sql = "INSERT INTO patients (user_id, address, city, gender)
                                    VALUES (?, ?, ?, ?)";
                    $patient_stmt = mysqli_prepare($con, $patient_sql);
                    mysqli_stmt_bind_param(
                        $patient_stmt,
                        "isss",
                        $user_id,
                        $user_data['address'],
                        $user_data['city'],
                        $user_data['gender']
                    );
                    mysqli_stmt_execute($patient_stmt);
                    mysqli_stmt_close($patient_stmt);
                    break;

                case 'doctor':
                    $doctor_sql = "INSERT INTO doctors (user_id, specilization, doctorName, address, contactno, docEmail, creationDate)
                                   VALUES (?, 'General', ?, ?, '77777777', ?, NOW())";
                    $doctor_stmt = mysqli_prepare($con, $doctor_sql);
                    mysqli_stmt_bind_param(
                        $doctor_stmt,
                        "isss",
                        $user_id,
                        $user_data['full_name'],
                        $user_data['address'],
                        $user_data['email']
                    );
                    mysqli_stmt_execute($doctor_stmt);
                    mysqli_stmt_close($doctor_stmt);
                    break;

                case 'admin':
                    $admin_sql = "INSERT INTO admins (user_id, username, permissions)
                                  VALUES (?, 'admin', NULL)";
                    $admin_stmt = mysqli_prepare($con, $admin_sql);
                    mysqli_stmt_bind_param($admin_stmt, "i", $user_id);
                    mysqli_stmt_execute($admin_stmt);
                    mysqli_stmt_close($admin_stmt);
                    break;
            }

            $messages[] = [
                'type' => 'success',
                'message' => "Usuario {$user_data['email']} creado exitosamente"
            ];

            $created_users[] = [
                'email' => $user_data['email'],
                'password' => $user_data['password'],
                'type' => $user_data['user_type']
            ];
        } else {
            $messages[] = [
                'type' => 'danger',
                'message' => "Error al crear {$user_data['email']}: " . mysqli_error($con)
            ];
        }

        mysqli_stmt_close($insert_stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuarios de Prueba</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { padding: 50px; background: #f5f5f5; }
        .container { max-width: 900px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .user-card { border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin-bottom: 15px; }
        .badge-patient { background-color: #007bff; }
        .badge-doctor { background-color: #28a745; }
        .badge-admin { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fa fa-users"></i> Crear Usuarios de Prueba</h2>
        <p class="text-muted">Script automatizado para crear usuarios de prueba con contraseñas seguras</p>
        <hr>

        <?php foreach ($messages as $msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?> alert-dismissible fade show">
                <?php echo $msg['message']; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endforeach; ?>

        <?php if (!empty($created_users)): ?>
            <div class="alert alert-success">
                <h5><i class="fa fa-check-circle"></i> Usuarios Creados Exitosamente</h5>
                <p>Usa estas credenciales para hacer login:</p>
                <?php foreach ($created_users as $user): ?>
                    <div class="user-card">
                        <span class="badge badge-<?php echo $user['type']; ?>"><?php echo strtoupper($user['type']); ?></span>
                        <p class="mb-1 mt-2">
                            <strong>Email:</strong> <code><?php echo $user['email']; ?></code><br>
                            <strong>Password:</strong> <code><?php echo $user['password']; ?></code>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Usuarios que se crearán:</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Email</th>
                            <th>Contraseña</th>
                            <th>Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-patient">PATIENT</span></td>
                            <td>test@hospital.com</td>
                            <td>Test123@!</td>
                            <td>Usuario Prueba</td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-admin">ADMIN</span></td>
                            <td>admin@hospital.com</td>
                            <td>Admin123@!</td>
                            <td>Administrador Sistema</td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-doctor">DOCTOR</span></td>
                            <td>doctor@hospital.com</td>
                            <td>Doctor123@!</td>
                            <td>Dr. Juan Pérez</td>
                        </tr>
                    </tbody>
                </table>

                <form method="post">
                    <button type="submit" name="create_users" class="btn btn-primary btn-lg btn-block">
                        <i class="fa fa-plus-circle"></i> Crear Usuarios de Prueba
                    </button>
                </form>

                <div class="alert alert-info mt-3 mb-0">
                    <strong>Nota:</strong> Todas las contraseñas cumplen con las políticas de seguridad:
                    <ul class="mb-0">
                        <li>Mínimo 8 caracteres</li>
                        <li>1 mayúscula, 1 minúscula, 1 número, 1 carácter especial</li>
                        <li>Expiran en 90 días</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="../hms/login.php" class="btn btn-success">
                <i class="fa fa-sign-in-alt"></i> Ir al Login
            </a>
            <a href="generate-hash.php" class="btn btn-secondary">
                <i class="fa fa-key"></i> Generar Más Hashes
            </a>
            <a href="../database/migrations/verify-migration.php" class="btn btn-info">
                <i class="fa fa-check"></i> Verificar Migración
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
