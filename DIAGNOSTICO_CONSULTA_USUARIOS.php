<?php
/**
 * DIAGNÓSTICO DE CONSULTA DE USUARIOS CON ROLES
 * Verifica si la consulta SQL devuelve correctamente los roles
 */

require_once 'hms/include/config.php';

echo "<h1>🔍 DIAGNÓSTICO DE CONSULTA DE USUARIOS</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h2 { color: #00a8b3; border-bottom: 2px solid #00a8b3; padding-bottom: 5px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #00a8b3; color: white; }
    .query { background: #f4f4f4; padding: 10px; margin: 10px 0; border-left: 4px solid #00a8b3; font-family: monospace; font-size: 12px; }
    pre { background: #f9f9f9; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
</style>";

// ==============================================================
// 1. CONSULTA DIRECTA - LO QUE DEBERÍA FUNCIONAR
// ==============================================================
echo "<h2>1. 📊 Consulta Directa (como debería funcionar)</h2>";

$sql = "SELECT u.id, u.full_name, u.email, u.user_type, u.status, u.created_at, u.last_login,
        GROUP_CONCAT(DISTINCT r.display_name ORDER BY r.priority SEPARATOR ', ') as roles
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_active = 1
        LEFT JOIN roles r ON ur.role_id = r.id AND r.status = 'active'
        GROUP BY u.id
        ORDER BY u.full_name ASC
        LIMIT 10";

echo "<p><strong>Query SQL:</strong></p>";
echo "<div class='query'>" . nl2br(htmlspecialchars($sql)) . "</div>";

$result = $con->query($sql);

if ($result) {
    echo "<p class='success'>✓ Consulta ejecutada exitosamente - Resultados: " . $result->num_rows . "</p>";
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Tipo</th><th>Status</th><th>Roles</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $roles_display = !empty($row['roles']) ? $row['roles'] : '<span class="error">Sin roles</span>';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['full_name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['user_type']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>$roles_display</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>✗ Error en consulta: " . $con->error . "</p>";
}

// ==============================================================
// 2. VERIFICAR CADA JOIN POR SEPARADO
// ==============================================================
echo "<h2>2. 🔗 Verificar JOINs Paso a Paso</h2>";

// Usuarios
$users_count = $con->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
echo "<p>📊 <strong>Total de usuarios:</strong> $users_count</p>";

// User_roles activos
$ur_count = $con->query("SELECT COUNT(*) as total FROM user_roles WHERE is_active = 1")->fetch_assoc()['total'];
echo "<p>🔗 <strong>Asignaciones activas en user_roles:</strong> $ur_count</p>";

// Roles activos
$roles_count = $con->query("SELECT COUNT(*) as total FROM roles WHERE status = 'active'")->fetch_assoc()['total'];
echo "<p>🏷️ <strong>Roles activos:</strong> $roles_count</p>";

// Usuarios CON roles
$users_with_roles = $con->query("
    SELECT COUNT(DISTINCT ur.user_id) as total 
    FROM user_roles ur 
    WHERE ur.is_active = 1
")->fetch_assoc()['total'];
echo "<p>👥 <strong>Usuarios con al menos 1 rol:</strong> $users_with_roles</p>";

// ==============================================================
// 3. DETALLE DE ASIGNACIONES
// ==============================================================
echo "<h2>3. 📋 Detalle de Todas las Asignaciones</h2>";

$sql = "SELECT u.id, u.full_name, u.email, r.display_name as role_name, ur.is_active
        FROM user_roles ur
        JOIN users u ON ur.user_id = u.id
        JOIN roles r ON ur.role_id = r.id
        ORDER BY u.full_name, r.priority";

$result = $con->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>User ID</th><th>Usuario</th><th>Email</th><th>Rol</th><th>Activo</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $active_badge = $row['is_active'] ? '<span class="success">✓ Sí</span>' : '<span class="error">✗ No</span>';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['full_name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['role_name']}</td>";
        echo "<td>$active_badge</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠ No hay asignaciones de roles registradas</p>";
}

// ==============================================================
// 4. PROBAR MÉTODO DE UserManagement
// ==============================================================
echo "<h2>4. 🧪 Probar Método UserManagement::getAllUsers()</h2>";

require_once 'hms/include/UserManagement.php';

try {
    $userManager = new UserManagement($con);
    $users = $userManager->getAllUsers(100, 'full_name', 'ASC');
    
    echo "<p class='success'>✓ Método getAllUsers() ejecutado - Resultados: " . count($users) . "</p>";
    
    if (!empty($users)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Tipo</th><th>Status</th><th>Roles (del método)</th></tr>";
        
        foreach ($users as $user) {
            $roles_display = !empty($user['roles']) ? $user['roles'] : '<span class="error">NULL o vacío</span>';
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['user_type']}</td>";
            echo "<td>{$user['status']}</td>";
            echo "<td>$roles_display</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Debug del primer usuario
        echo "<h3>🔍 Debug del primer usuario (print_r):</h3>";
        echo "<pre>";
        print_r($users[0]);
        echo "</pre>";
    } else {
        echo "<p class='error'>✗ El método no devolvió usuarios</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

// ==============================================================
// 5. PROBAR ASIGNACIÓN MANUAL
// ==============================================================
echo "<h2>5. 🔧 Probar Asignación Manual de Rol</h2>";

// Obtener primer usuario sin roles
$test_user = $con->query("
    SELECT u.id, u.full_name, u.email 
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_active = 1
    WHERE ur.id IS NULL
    LIMIT 1
")->fetch_assoc();

// Obtener primer rol activo
$test_role = $con->query("SELECT id, display_name FROM roles WHERE status = 'active' LIMIT 1")->fetch_assoc();

if ($test_user && $test_role) {
    echo "<p>👤 <strong>Usuario de prueba:</strong> {$test_user['full_name']} (ID: {$test_user['id']}) - Sin roles actualmente</p>";
    echo "<p>🏷️ <strong>Rol a asignar:</strong> {$test_role['display_name']} (ID: {$test_role['id']})</p>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;'>";
    echo "<h3>💡 Para asignar este rol manualmente, ejecuta:</h3>";
    echo "<div class='query'>";
    echo "CALL assign_role_to_user({$test_user['id']}, {$test_role['id']}, 1, NULL);";
    echo "</div>";
    echo "<p>Luego recarga esta página para ver si aparece en la tabla.</p>";
    echo "</div>";
} else {
    echo "<p class='warning'>⚠ No se encontró usuario sin roles para probar</p>";
}

echo "<hr>";
echo "<p><strong>Fecha del diagnóstico:</strong> " . date('Y-m-d H:i:s') . "</p>";

$con->close();
?>
