<?php
/**
 * ============================================================================
 * INSTALADOR DE STORED PROCEDURES VIA PHP
 * ============================================================================
 * Este script instala los 5 stored procedures necesarios
 * Abre en navegador: http://localhost/hospital/database/instalar-sp.php
 * ============================================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hms_v2';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Instalador de Stored Procedures</title>
    <style>
        body { font-family: monospace; background: #1a1a2e; color: #0f3; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: #16213e; padding: 30px; border-radius: 10px; }
        h1 { color: #0f3; text-align: center; }
        .success { color: #0f3; }
        .error { color: #f33; }
        .info { color: #ff0; }
        .step { background: #0f1419; padding: 15px; margin: 10px 0; border-left: 4px solid #0f3; border-radius: 5px; }
        pre { background: #000; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔧 Instalador de Stored Procedures</h1>";

// Conectar
echo "<div class='step'>";
echo "<strong>PASO 1:</strong> Conectando a MySQL...<br>";
$con = mysqli_connect($host, $username, $password, $database);

if (!$con) {
    echo "<span class='error'>✗ ERROR: " . mysqli_connect_error() . "</span>";
    die("</div></div></body></html>");
}
echo "<span class='success'>✓ Conectado a: $database</span>";
echo "</div>";

// Array de stored procedures
$procedures = [
    'assign_role_to_user' => "
        CREATE PROCEDURE assign_role_to_user(
            IN p_user_id INT,
            IN p_role_id INT,
            IN p_assigned_by INT,
            IN p_expires_at DATETIME
        )
        BEGIN
            DECLARE EXIT HANDLER FOR SQLEXCEPTION
            BEGIN
                ROLLBACK;
                SELECT 'Error al asignar rol' AS message, 0 AS success;
            END;

            START TRANSACTION;

            IF NOT EXISTS (SELECT 1 FROM users WHERE id = p_user_id) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario no encontrado';
            END IF;

            IF NOT EXISTS (SELECT 1 FROM roles WHERE id = p_role_id) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Rol no encontrado';
            END IF;

            INSERT INTO user_roles (user_id, role_id, assigned_by, expires_at, is_active)
            VALUES (p_user_id, p_role_id, p_assigned_by, p_expires_at, 1)
            ON DUPLICATE KEY UPDATE
                assigned_by = p_assigned_by,
                expires_at = p_expires_at,
                is_active = 1,
                assigned_at = CURRENT_TIMESTAMP;

            INSERT INTO audit_role_changes (user_id, role_id, action, performed_by)
            VALUES (p_user_id, p_role_id, 'assigned', p_assigned_by);

            COMMIT;
            SELECT 'Rol asignado exitosamente' AS message, 1 AS success;
        END
    ",

    'revoke_role_from_user' => "
        CREATE PROCEDURE revoke_role_from_user(
            IN p_user_id INT,
            IN p_role_id INT,
            IN p_revoked_by INT
        )
        BEGIN
            DECLARE EXIT HANDLER FOR SQLEXCEPTION
            BEGIN
                ROLLBACK;
                SELECT 'Error al revocar rol' AS message, 0 AS success;
            END;

            START TRANSACTION;

            IF NOT EXISTS (SELECT 1 FROM user_roles WHERE user_id = p_user_id AND role_id = p_role_id) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El usuario no tiene este rol asignado';
            END IF;

            DELETE FROM user_roles WHERE user_id = p_user_id AND role_id = p_role_id;

            INSERT INTO audit_role_changes (user_id, role_id, action, performed_by)
            VALUES (p_user_id, p_role_id, 'revoked', p_revoked_by);

            COMMIT;
            SELECT 'Rol revocado exitosamente' AS message, 1 AS success;
        END
    ",

    'user_has_permission' => "
        CREATE PROCEDURE user_has_permission(
            IN p_user_id INT,
            IN p_permission_name VARCHAR(100)
        )
        BEGIN
            SELECT EXISTS(
                SELECT 1 FROM user_effective_permissions
                WHERE user_id = p_user_id AND permission_name = p_permission_name
            ) AS has_permission;
        END
    ",

    'get_user_permissions' => "
        CREATE PROCEDURE get_user_permissions(IN p_user_id INT)
        BEGIN
            SELECT DISTINCT
                p.permission_name,
                p.display_name,
                p.module,
                r.role_name,
                r.display_name AS role_display_name
            FROM users u
            INNER JOIN user_roles ur ON u.id = ur.user_id
            INNER JOIN roles r ON ur.role_id = r.id
            INNER JOIN role_permissions rp ON r.id = rp.role_id
            INNER JOIN permissions p ON rp.permission_id = p.id
            WHERE u.id = p_user_id
              AND u.status = 'active'
              AND ur.is_active = 1
              AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
              AND r.status = 'active'
            ORDER BY p.module, p.permission_name;
        END
    ",

    'cleanup_old_security_data' => "
        CREATE PROCEDURE cleanup_old_security_data()
        BEGIN
            DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
            DELETE FROM password_reset_tokens WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
            SELECT 'Limpieza completada exitosamente' AS message;
        END
    "
];

// Instalar cada procedure
echo "<div class='step'>";
echo "<strong>PASO 2:</strong> Instalando Stored Procedures...<br><br>";

$installed = 0;
$errors = 0;

foreach ($procedures as $name => $sql) {
    echo "<div style='margin: 10px 0;'>";
    echo "→ Instalando: <strong>$name</strong>...<br>";

    // Primero intentar eliminar si existe
    $drop_sql = "DROP PROCEDURE IF EXISTS $name";
    mysqli_query($con, $drop_sql);

    // Crear el procedure
    if (mysqli_query($con, $sql)) {
        echo "<span class='success'>✓ Creado exitosamente</span>";
        $installed++;
    } else {
        echo "<span class='error'>✗ Error: " . mysqli_error($con) . "</span>";
        $errors++;
    }
    echo "</div>";
}

echo "</div>";

// Verificar instalación
echo "<div class='step'>";
echo "<strong>PASO 3:</strong> Verificando instalación...<br><br>";

$query = "SELECT routine_name FROM information_schema.routines
          WHERE routine_schema = '$database' AND routine_type = 'PROCEDURE'
          ORDER BY routine_name";
$result = mysqli_query($con, $query);

$count = mysqli_num_rows($result);

echo "Total de procedures instalados: <strong class='success'>$count / 5</strong><br><br>";

if ($count > 0) {
    echo "Procedures encontrados:<br>";
    echo "<ul>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<li class='success'>{$row['routine_name']}</li>";
    }
    echo "</ul>";
}

echo "</div>";

// Resumen
echo "<div class='step' style='text-align: center; font-size: 18px;'>";
if ($count == 5) {
    echo "<span class='success'>";
    echo "✅ ¡INSTALACIÓN EXITOSA!<br><br>";
    echo "Los 5 stored procedures están instalados correctamente.<br>";
    echo "Ahora puedes asignar roles a usuarios.";
    echo "</span>";
} else {
    echo "<span class='error'>";
    echo "⚠️ INSTALACIÓN INCOMPLETA<br><br>";
    echo "Se esperaban 5 procedures pero solo hay $count.<br>";
    echo "Revisa los errores arriba.";
    echo "</span>";
}
echo "</div>";

// Próximos pasos
echo "<div class='step'>";
echo "<strong>📚 PRÓXIMOS PASOS:</strong><br><br>";
echo "1. <strong>Asignar rol a un usuario:</strong><br>";
echo "<pre>CALL assign_role_to_user(1, 1, 1, NULL);</pre><br>";
echo "2. <strong>Verificar permisos:</strong><br>";
echo "<pre>CALL user_has_permission(1, 'view_patients');</pre><br>";
echo "3. <strong>Ver permisos del usuario:</strong><br>";
echo "<pre>CALL get_user_permissions(1);</pre><br>";
echo "4. <strong>Ejecutar pruebas completas:</strong><br>";
echo "→ <a href='../hms/test-rbac-sistema.php' style='color:#0f3;'>test-rbac-sistema.php</a>";
echo "</div>";

mysqli_close($con);

echo "</div></body></html>";
?>
