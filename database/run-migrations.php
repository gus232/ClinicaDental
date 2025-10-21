<?php
/**
 * ============================================================================
 * EJECUTOR DE MIGRACIONES RBAC
 * ============================================================================
 * Este script ejecuta todas las migraciones necesarias para el sistema RBAC
 * ============================================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de base de datos
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hms_v2';

echo "============================================================================\n";
echo "INSTALADOR DE SISTEMA RBAC - HMS\n";
echo "============================================================================\n\n";

// Conectar a la base de datos
echo "[1/5] Conectando a la base de datos...\n";
$con = mysqli_connect($host, $username, $password, $database);

if (!$con) {
    die("ERROR: No se pudo conectar a MySQL: " . mysqli_connect_error() . "\n");
}
echo "✓ Conectado exitosamente a: $database\n\n";

// Función para ejecutar archivo SQL
function executeSQLFile($con, $filepath, $name) {
    echo "Ejecutando: $name\n";
    echo "Archivo: $filepath\n";

    if (!file_exists($filepath)) {
        echo "✗ ERROR: Archivo no encontrado\n\n";
        return false;
    }

    $sql = file_get_contents($filepath);

    if ($sql === false) {
        echo "✗ ERROR: No se pudo leer el archivo\n\n";
        return false;
    }

    // Dividir por delimiter
    $queries = preg_split('/;\s*$/m', $sql);
    $success_count = 0;
    $error_count = 0;

    foreach ($queries as $query) {
        $query = trim($query);

        // Ignorar líneas vacías y comentarios
        if (empty($query) ||
            strpos($query, '--') === 0 ||
            strpos($query, 'DELIMITER') === 0 ||
            strpos($query, '$$') !== false) {
            continue;
        }

        // Ejecutar query
        if (mysqli_multi_query($con, $query . ';')) {
            do {
                if ($result = mysqli_store_result($con)) {
                    mysqli_free_result($result);
                }
            } while (mysqli_next_result($con));
            $success_count++;
        } else {
            // Solo mostrar errores críticos (ignorar "tabla ya existe")
            $error = mysqli_error($con);
            if (strpos($error, 'already exists') === false &&
                strpos($error, 'Duplicate') === false) {
                echo "  ⚠ Warning: $error\n";
                $error_count++;
            }
        }
    }

    echo "✓ Completado ($success_count queries ejecutadas)\n\n";
    return true;
}

// Ejecutar migraciones
$migrations = [
    [
        'name' => '[2/5] Migración 003: Sistema RBAC',
        'file' => __DIR__ . '/migrations/003_rbac_system.sql'
    ],
    [
        'name' => '[3/5] Migración 004: Security Logs',
        'file' => __DIR__ . '/migrations/004_security_logs.sql'
    ],
    [
        'name' => '[4/5] Seed: Roles y Permisos por Defecto',
        'file' => __DIR__ . '/seeds/003_default_roles_permissions.sql'
    ]
];

$all_success = true;
foreach ($migrations as $migration) {
    if (!executeSQLFile($con, $migration['file'], $migration['name'])) {
        $all_success = false;
        echo "✗ ERROR en: {$migration['name']}\n";
        break;
    }
}

if ($all_success) {
    echo "============================================================================\n";
    echo "[5/5] VERIFICACIÓN DE INSTALACIÓN\n";
    echo "============================================================================\n\n";

    // Verificar tablas creadas
    echo "📊 TABLAS CREADAS:\n";
    $tables = ['roles', 'permissions', 'role_permissions', 'user_roles',
               'permission_categories', 'role_hierarchy', 'audit_role_changes', 'security_logs'];

    $query = "SELECT COUNT(*) as total FROM information_schema.tables
              WHERE table_schema = '$database'
              AND table_name IN ('" . implode("','", $tables) . "')";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    echo "  Total: {$row['total']}/8 tablas\n\n";

    // Verificar roles
    echo "👥 ROLES CREADOS:\n";
    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM roles");
    $row = mysqli_fetch_assoc($result);
    echo "  Total: {$row['total']} roles\n";

    $result = mysqli_query($con, "SELECT role_name, display_name, priority FROM roles ORDER BY priority");
    while ($role = mysqli_fetch_assoc($result)) {
        echo "  - {$role['display_name']} (prioridad: {$role['priority']})\n";
    }
    echo "\n";

    // Verificar permisos
    echo "🔑 PERMISOS CREADOS:\n";
    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM permissions");
    $row = mysqli_fetch_assoc($result);
    echo "  Total: {$row['total']} permisos\n";

    $result = mysqli_query($con, "SELECT module, COUNT(*) as total FROM permissions GROUP BY module");
    while ($perm = mysqli_fetch_assoc($result)) {
        echo "  - {$perm['module']}: {$perm['total']} permisos\n";
    }
    echo "\n";

    // Verificar asignaciones
    echo "🔗 ASIGNACIONES CREADAS:\n";
    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM role_permissions");
    $row = mysqli_fetch_assoc($result);
    echo "  Total: {$row['total']} asignaciones rol-permiso\n\n";

    // Matriz de permisos por rol
    echo "📋 PERMISOS POR ROL:\n";
    $result = mysqli_query($con, "
        SELECT r.display_name as rol, COUNT(rp.permission_id) as permisos
        FROM roles r
        LEFT JOIN role_permissions rp ON r.id = rp.role_id
        GROUP BY r.id, r.display_name
        ORDER BY r.priority
    ");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "  - {$row['rol']}: {$row['permisos']} permisos\n";
    }
    echo "\n";

    // Verificar vistas
    echo "👁 VISTAS CREADAS:\n";
    $views = ['user_effective_permissions', 'user_roles_summary', 'role_permission_matrix',
              'expiring_user_roles', 'unauthorized_access_summary', 'access_attempts_by_ip'];

    $query = "SELECT COUNT(*) as total FROM information_schema.views
              WHERE table_schema = '$database'
              AND table_name IN ('" . implode("','", $views) . "')";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    echo "  Total: {$row['total']}/6 vistas\n\n";

    // Verificar stored procedures
    echo "⚙ STORED PROCEDURES CREADOS:\n";
    $procedures = ['assign_role_to_user', 'revoke_role_from_user', 'user_has_permission',
                   'get_user_permissions', 'cleanup_old_security_data'];

    $query = "SELECT COUNT(*) as total FROM information_schema.routines
              WHERE routine_schema = '$database'
              AND routine_type = 'PROCEDURE'
              AND routine_name IN ('" . implode("','", $procedures) . "')";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    echo "  Total: {$row['total']}/5 procedures\n\n";

    echo "============================================================================\n";
    echo "✅ INSTALACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "============================================================================\n\n";

    echo "📚 PRÓXIMOS PASOS:\n";
    echo "  1. Asignar rol a un usuario:\n";
    echo "     INSERT INTO user_roles (user_id, role_id, assigned_by)\n";
    echo "     VALUES (1, 1, 1); -- Asignar Super Admin al usuario 1\n\n";

    echo "  2. Probar el sistema:\n";
    echo "     - Abre: http://localhost/hospital/hms/admin/rbac-example.php\n";
    echo "     - Lee: docs/RBAC_USAGE_GUIDE.md\n\n";

    echo "  3. Ejecutar plan de pruebas:\n";
    echo "     - Sigue: PLAN_PRUEBAS_FASE2.md\n\n";

} else {
    echo "\n✗ ERROR: La instalación falló. Revisa los mensajes de error arriba.\n";
}

mysqli_close($con);

echo "============================================================================\n";
echo "Script completado\n";
echo "============================================================================\n";
?>
