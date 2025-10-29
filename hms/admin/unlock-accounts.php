<?php
session_start();
error_reporting(1);
include('include/config.php');
include('include/checklogin.php');
check_login();

// Verificar que sea administrador
if ($_SESSION['user_type'] !== 'admin') {
    header('location: ../logout.php');
    exit();
}

$success_message = '';
$error_message = '';

// Procesar desbloqueo de cuenta
if (isset($_POST['unlock'])) {
    $user_id = intval($_POST['user_id']);

    $unlock_sql = "UPDATE users SET
                   failed_login_attempts = 0,
                   account_locked_until = NULL
                   WHERE id = ?";

    $stmt = mysqli_prepare($con, $unlock_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);

    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Cuenta desbloqueada exitosamente";

        // Registrar acción en log (opcional - crear tabla de auditoría más tarde)
        // Log: Admin X desbloqueó cuenta de usuario Y
    } else {
        $error_message = "Error al desbloquear la cuenta";
    }

    mysqli_stmt_close($stmt);
}

// Procesar reseteo de contador sin desbloqueo completo
if (isset($_POST['reset_counter'])) {
    $user_id = intval($_POST['user_id']);

    $reset_sql = "UPDATE users SET failed_login_attempts = 0 WHERE id = ?";
    $stmt = mysqli_prepare($con, $reset_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);

    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Contador de intentos reiniciado exitosamente";
    } else {
        $error_message = "Error al reiniciar contador";
    }

    mysqli_stmt_close($stmt);
}

// Obtener cuentas bloqueadas
$locked_accounts_sql = "SELECT
                        u.id,
                        u.email,
                        u.full_name,
                        u.user_type,
                        u.failed_login_attempts,
                        u.account_locked_until,
                        u.last_login,
                        u.last_login_ip,
                        CASE
                            WHEN u.account_locked_until > NOW() THEN 'BLOQUEADA'
                            ELSE 'DESBLOQUEADA'
                        END AS lock_status,
                        TIMESTAMPDIFF(MINUTE, NOW(), u.account_locked_until) AS minutes_remaining
                        FROM users u
                        WHERE u.account_locked_until IS NOT NULL
                        ORDER BY u.account_locked_until DESC";

$locked_result = mysqli_query($con, $locked_accounts_sql);

// Obtener cuentas con intentos fallidos (pero no bloqueadas)
$attempts_sql = "SELECT
                 u.id,
                 u.email,
                 u.full_name,
                 u.user_type,
                 u.failed_login_attempts,
                 u.last_login,
                 u.last_login_ip
                 FROM users u
                 WHERE u.failed_login_attempts > 0
                 AND (u.account_locked_until IS NULL OR u.account_locked_until < NOW())
                 ORDER BY u.failed_login_attempts DESC";

$attempts_result = mysqli_query($con, $attempts_sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Desbloqueo de Cuentas - Admin Panel</title>
    <link href="http://fonts.googleapis.com/css?family=Lato:300,400,400italic,600,700|Raleway:300,400,500,600,700|Crete+Round:400italic" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/plugins.css">
    <link rel="stylesheet" href="assets/css/themes/theme-1.css" id="skin_color" />
</head>
<body>
    <div id="app">
        <?php include('include/sidebar.php'); ?>
        <div class="app-content">
            <?php include('include/header.php'); ?>
            <div class="main-content">
                <div class="wrap-content container" id="container">
                    <!-- Breadcrumb -->
                    <section id="page-title">
                        <div class="row">
                            <div class="col-sm-8">
                                <h1 class="mainTitle"><i class="fa fa-unlock"></i> Gestión de Bloqueos de Cuenta</h1>
                            </div>
                            <ol class="breadcrumb">
                                <li><span>Admin</span></li>
                                <li><span>Seguridad</span></li>
                                <li class="active"><span>Desbloqueo de Cuentas</span></li>
                            </ol>
                        </div>
                    </section>

                    <!-- Mensajes -->
                    <div class="container-fluid container-fullw bg-white">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fa fa-check-circle"></i> <?php echo $success_message; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fa fa-exclamation-circle"></i> <?php echo $error_message; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <!-- SECCIÓN 1: CUENTAS BLOQUEADAS -->
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="over-title margin-bottom-15">
                                    <i class="fa fa-ban text-danger"></i> Cuentas Bloqueadas
                                    <span class="badge badge-danger"><?php echo mysqli_num_rows($locked_result); ?></span>
                                </h4>

                                <?php if (mysqli_num_rows($locked_result) == 0): ?>
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> No hay cuentas bloqueadas actualmente.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped" id="locked-table">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Usuario</th>
                                                    <th>Email</th>
                                                    <th>Tipo</th>
                                                    <th>Intentos</th>
                                                    <th>Estado</th>
                                                    <th>Bloqueado Hasta</th>
                                                    <th>Tiempo Restante</th>
                                                    <th>Última IP</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = mysqli_fetch_assoc($locked_result)): ?>
                                                    <tr>
                                                        <td><?php echo $row['id']; ?></td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                        <td>
                                                            <?php
                                                            $badge_class = [
                                                                'patient' => 'info',
                                                                'doctor' => 'primary',
                                                                'admin' => 'danger'
                                                            ];
                                                            $badge = $badge_class[$row['user_type']] ?? 'secondary';
                                                            ?>
                                                            <span class="badge badge-<?php echo $badge; ?>">
                                                                <?php echo ucfirst($row['user_type']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-warning">
                                                                <?php echo $row['failed_login_attempts']; ?> intentos
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($row['lock_status'] == 'BLOQUEADA'): ?>
                                                                <span class="badge badge-danger">
                                                                    <i class="fa fa-lock"></i> BLOQUEADA
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge badge-success">
                                                                    <i class="fa fa-unlock"></i> DESBLOQUEADA
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo date('d/m/Y H:i', strtotime($row['account_locked_until'])); ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            if ($row['minutes_remaining'] > 0) {
                                                                echo "<strong class='text-danger'>{$row['minutes_remaining']} min</strong>";
                                                            } else {
                                                                echo "<em class='text-muted'>Expirado</em>";
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <code><?php echo htmlspecialchars($row['last_login_ip'] ?? 'N/A'); ?></code>
                                                        </td>
                                                        <td>
                                                            <?php if ($row['lock_status'] == 'BLOQUEADA'): ?>
                                                                <form method="post" style="display: inline;">
                                                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                                    <button type="submit" name="unlock" class="btn btn-success btn-sm" onclick="return confirm('¿Está seguro de desbloquear esta cuenta?')">
                                                                        <i class="fa fa-unlock"></i> Desbloquear
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <span class="text-muted"><em>Ya desbloqueada</em></span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr class="my-5">

                        <!-- SECCIÓN 2: CUENTAS CON INTENTOS FALLIDOS -->
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="over-title margin-bottom-15">
                                    <i class="fa fa-exclamation-triangle text-warning"></i> Cuentas con Intentos Fallidos
                                    <span class="badge badge-warning"><?php echo mysqli_num_rows($attempts_result); ?></span>
                                </h4>

                                <?php if (mysqli_num_rows($attempts_result) == 0): ?>
                                    <div class="alert alert-success">
                                        <i class="fa fa-check-circle"></i> No hay cuentas con intentos fallidos.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped" id="attempts-table">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Usuario</th>
                                                    <th>Email</th>
                                                    <th>Tipo</th>
                                                    <th>Intentos Fallidos</th>
                                                    <th>Último Login</th>
                                                    <th>Última IP</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = mysqli_fetch_assoc($attempts_result)): ?>
                                                    <tr>
                                                        <td><?php echo $row['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                        <td>
                                                            <?php
                                                            $badge_class = [
                                                                'patient' => 'info',
                                                                'doctor' => 'primary',
                                                                'admin' => 'danger'
                                                            ];
                                                            $badge = $badge_class[$row['user_type']] ?? 'secondary';
                                                            ?>
                                                            <span class="badge badge-<?php echo $badge; ?>">
                                                                <?php echo ucfirst($row['user_type']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $attempts_class = $row['failed_login_attempts'] >= 2 ? 'danger' : 'warning';
                                                            ?>
                                                            <span class="badge badge-<?php echo $attempts_class; ?>">
                                                                <?php echo $row['failed_login_attempts']; ?> intentos
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            if ($row['last_login']) {
                                                                echo date('d/m/Y H:i', strtotime($row['last_login']));
                                                            } else {
                                                                echo '<em class="text-muted">Nunca</em>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <code><?php echo htmlspecialchars($row['last_login_ip'] ?? 'N/A'); ?></code>
                                                        </td>
                                                        <td>
                                                            <form method="post" style="display: inline;">
                                                                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                                <button type="submit" name="reset_counter" class="btn btn-warning btn-sm" onclick="return confirm('¿Reiniciar contador de intentos?')">
                                                                    <i class="fa fa-refresh"></i> Reiniciar
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Estadísticas -->
                        <div class="row mt-5">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h5><i class="fa fa-info-circle"></i> Información</h5>
                                    <ul class="mb-0">
                                        <li>Las cuentas se bloquean automáticamente después de 3 intentos fallidos de login</li>
                                        <li>El bloqueo dura 30 minutos por defecto (configurable)</li>
                                        <li>Puedes desbloquear cuentas manualmente desde esta página</li>
                                        <li>Los contadores se reinician automáticamente al hacer login exitoso</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('include/footer.php'); ?>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
