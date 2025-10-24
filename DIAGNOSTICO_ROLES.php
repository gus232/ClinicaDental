<?php
/**
 * DIAGNÓSTICO DEL SISTEMA DE ROLES
 * Verifica si todo está configurado correctamente
 */

require_once 'hms/include/config.php';

echo "<h1>🔍 DIAGNÓSTICO DEL SISTEMA DE ROLES</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h2 { color: #00a8b3; border-bottom: 2px solid #00a8b3; padding-bottom: 5px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #00a8b3; color: white; }
    .query { background: #f4f4f4; padding: 10px; margin: 10px 0; border-left: 4px solid #00a8b3; font-family: monospace; }
</style>";

// ==============================================================
// 1. VERIFICAR STORED PROCEDURES
// ==============================================================
echo "<h2>1. ✅ Verificar Stored Procedures</h2>";

$sps_required = ['create_user_with_audit', 'update_user_with_history', 'assign_role_to_user', 'revoke_role_from_user'];
$sps_found = [];

$result = $con->query("SHOW PROCEDURE STATUS WHERE Db = 'hms_v2'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sps_found[] = $row['Name'];
    }
}

echo "<table>";
echo "<tr><th>Stored Procedure</th><th>Estado</th></tr>";
foreach ($sps_required as $sp) {
    $exists = in_array($sp, $sps_found);
    $status = $exists ? "<span class='success'>✓ EXISTE</span>" : "<span class='error'>✗ NO EXISTE</span>";
    echo "<tr><td>$sp</td><td>$status</td></tr>";
}
echo "</table>";

// ==============================================================
// 2. VERIFICAR TABLA ROLES
// ==============================================================
echo "<h2>2. 📋 Verificar Tabla ROLES</h2>";

$result = $con->query("SELECT COUNT(*) as total FROM roles WHERE status = 'active'");
$count = $result->fetch_assoc()['total'] ?? 0;

if ($count > 0) {
    echo "<p class='success'>✓ Hay $count roles activos en el sistema</p>";
    
    $result = $con->query("SELECT * FROM roles WHERE status = 'active' ORDER BY priority ASC");
    echo "<table>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Nombre Display</th><th>Prioridad</th><th>Sistema</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $is_system = $row['is_system_role'] ? 'Sí' : 'No';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['role_name']}</td>";
        echo "<td>{$row['display_name']}</td>";
        echo "<td>{$row['priority']}</td>";
        echo "<td>$is_system</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>✗ NO HAY ROLES EN EL SISTEMA</p>";
    echo "<p class='warning'>⚠ Necesitas ejecutar el script de inicialización de roles</p>";
}

// ==============================================================
// 3. VERIFICAR TABLA USER_ROLES
// ==============================================================
echo "<h2>3. 🔗 Verificar Tabla USER_ROLES</h2>";

// Estructura de la tabla
$result = $con->query("DESCRIBE user_roles");
echo "<p><strong>Estructura de la tabla:</strong></p>";
echo "<table>";
echo "<tr><th>Columna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// Asignaciones actuales
$result = $con->query("SELECT COUNT(*) as total FROM user_roles WHERE is_active = 1");
$count = $result->fetch_assoc()['total'] ?? 0;

echo "<p><strong>Asignaciones de roles actuales:</strong> $count</p>";

if ($count > 0) {
    $result = $con->query("
        SELECT ur.id, u.full_name, u.email, r.display_name as role, 
               ur.assigned_at, ur.is_active
        FROM user_roles ur
        JOIN users u ON ur.user_id = u.id
        JOIN roles r ON ur.role_id = r.id
        ORDER BY ur.assigned_at DESC
        LIMIT 20
    ");
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Usuario</th><th>Email</th><th>Rol</th><th>Asignado</th><th>Activo</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $active = $row['is_active'] ? '✓' : '✗';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['full_name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['role']}</td>";
        echo "<td>{$row['assigned_at']}</td>";
        echo "<td>$active</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠ No hay asignaciones de roles en el sistema</p>";
}

// ==============================================================
// 4. PROBAR STORED PROCEDURE assign_role_to_user
// ==============================================================
echo "<h2>4. 🧪 Probar Stored Procedure assign_role_to_user</h2>";

// Obtener un usuario de prueba
$test_user = $con->query("SELECT id, full_name, email FROM users LIMIT 1")->fetch_assoc();
// Obtener un rol de prueba
$test_role = $con->query("SELECT id, display_name FROM roles WHERE status = 'active' LIMIT 1")->fetch_assoc();

if ($test_user && $test_role) {
    echo "<p>👤 <strong>Usuario de prueba:</strong> {$test_user['full_name']} (ID: {$test_user['id']})</p>";
    echo "<p>🏷️ <strong>Rol de prueba:</strong> {$test_role['display_name']} (ID: {$test_role['id']})</p>";
    
    // Verificar si ya tiene este rol
    $stmt = $con->prepare("SELECT id FROM user_roles WHERE user_id = ? AND role_id = ? AND is_active = 1");
    $stmt->bind_param("ii", $test_user['id'], $test_role['id']);
    $stmt->execute();
    $already_has = $stmt->get_result()->fetch_assoc();
    
    if ($already_has) {
        echo "<p class='success'>✓ El usuario ya tiene este rol asignado</p>";
    } else {
        echo "<p class='warning'>⚠ El usuario NO tiene este rol. Intentando asignar...</p>";
        
        try {
            $stmt = $con->prepare("CALL assign_role_to_user(?, ?, ?, NULL)");
            $admin_id = 1; // Usuario admin por defecto
            $stmt->bind_param("iii", $test_user['id'], $test_role['id'], $admin_id);
            
            if ($stmt->execute()) {
                echo "<p class='success'>✓ SP ejecutado exitosamente</p>";
                
                // Verificar si se insertó
                $stmt2 = $con->prepare("SELECT id FROM user_roles WHERE user_id = ? AND role_id = ? AND is_active = 1");
                $stmt2->bind_param("ii", $test_user['id'], $test_role['id']);
                $stmt2->execute();
                $inserted = $stmt2->get_result()->fetch_assoc();
                
                if ($inserted) {
                    echo "<p class='success'>✓✓ ROL ASIGNADO CORRECTAMENTE - ID: {$inserted['id']}</p>";
                } else {
                    echo "<p class='error'>✗ SP ejecutó pero NO se insertó el registro en user_roles</p>";
                }
            } else {
                echo "<p class='error'>✗ Error al ejecutar SP: " . $stmt->error . "</p>";
            }
            
            // Limpiar resultados
            while ($con->more_results()) {
                $con->next_result();
                if ($res = $con->store_result()) {
                    $res->free();
                }
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ Excepción: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p class='error'>✗ No se encontraron usuarios o roles para probar</p>";
}

// ==============================================================
// 5. VERIFICAR ERRORES SQL
// ==============================================================
echo "<h2>5. ⚠️ Errores SQL Recientes</h2>";

$result = $con->query("SHOW WARNINGS");
if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Nivel</th><th>Código</th><th>Mensaje</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Level']}</td>";
        echo "<td>{$row['Code']}</td>";
        echo "<td>{$row['Message']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='success'>✓ No hay advertencias SQL</p>";
}

// ==============================================================
// 6. RECOMENDACIONES
// ==============================================================
echo "<h2>6. 💡 Recomendaciones</h2>";

if ($count == 0 && isset($test_role)) {
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;'>";
    echo "<h3>📝 Script para crear roles por defecto:</h3>";
    echo "<div class='query'>";
    echo "INSERT INTO roles (role_name, display_name, description, is_system_role, priority, status) VALUES<br>";
    echo "('super_admin', 'Super Administrador', 'Control total del sistema', 1, 1, 'active'),<br>";
    echo "('admin', 'Administrador', 'Gestión general del sistema', 1, 10, 'active'),<br>";
    echo "('doctor', 'Doctor', 'Gestión de pacientes y citas', 1, 20, 'active'),<br>";
    echo "('nurse', 'Enfermera', 'Asistencia médica', 1, 30, 'active'),<br>";
    echo "('receptionist', 'Recepcionista', 'Gestión de citas', 1, 40, 'active'),<br>";
    echo "('patient', 'Paciente', 'Consulta de información personal', 1, 50, 'active');<br>";
    echo "</div>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>Fecha del diagnóstico:</strong> " . date('Y-m-d H:i:s') . "</p>";

$con->close();
?>
