<?php
/**
 * ============================================================================
 * SCRIPT: ASIGNAR ROLES FALTANTES A USUARIOS EXISTENTES
 * ============================================================================
 * 
 * Este script asigna automáticamente el rol correspondiente a usuarios
 * que fueron registrados ANTES de implementar la asignación automática de roles.
 * 
 * IMPORTANTE: Solo debe ejecutarse UNA VEZ para corregir usuarios antiguos.
 * ============================================================================
 */

require_once 'hms/include/config.php';
require_once 'hms/include/rbac-functions.php';

echo "<h1>🔧 Asignar Roles Faltantes a Usuarios Existentes</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    h1 { color: #00a8b3; }
    h2 { color: #333; border-bottom: 2px solid #00a8b3; padding-bottom: 10px; margin-top: 30px; }
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    .warning { color: #ff9800; font-weight: bold; }
    .info { color: #2196F3; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; background: white; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #00a8b3; color: white; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .btn { padding: 12px 24px; background: #00a8b3; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
    .btn:hover { background: #007a82; }
    .summary { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
</style>";

// Inicializar RBAC
$rbac = new RBAC($con);

// ============================================================================
// PASO 1: VERIFICAR SITUACIÓN ACTUAL
// ============================================================================
echo "<h2>📊 Paso 1: Análisis de la Situación Actual</h2>";

// Contar usuarios sin roles
$query_no_roles = "
    SELECT u.id, u.full_name, u.email, u.user_type, u.created_at
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_active = 1
    WHERE ur.id IS NULL
    ORDER BY u.user_type, u.created_at DESC
";

$result_no_roles = mysqli_query($con, $query_no_roles);
$users_without_roles = [];

if ($result_no_roles) {
    while ($row = mysqli_fetch_assoc($result_no_roles)) {
        $users_without_roles[] = $row;
    }
}

$total_without_roles = count($users_without_roles);

// Obtener roles disponibles
$roles_query = "SELECT id, role_name, display_name FROM roles WHERE status = 'active' ORDER BY role_name";
$roles_result = mysqli_query($con, $roles_query);
$available_roles = [];
while ($role = mysqli_fetch_assoc($roles_result)) {
    $available_roles[$role['role_name']] = $role;
}

echo "<div class='summary'>";
echo "<h3>Resumen:</h3>";
echo "<p><strong>Total de usuarios sin roles:</strong> <span class='warning'>$total_without_roles</span></p>";
echo "<p><strong>Roles disponibles en el sistema:</strong> " . count($available_roles) . "</p>";
echo "</div>";

if ($total_without_roles > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Tipo Usuario</th><th>Registrado</th><th>Rol Sugerido</th></tr>";
    
    foreach ($users_without_roles as $user) {
        // Determinar qué rol debería tener según su user_type
        $suggested_role = 'Sin rol sugerido';
        $role_class = 'error';
        
        if (isset($available_roles[$user['user_type']])) {
            $suggested_role = $available_roles[$user['user_type']]['display_name'];
            $role_class = 'success';
        }
        
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td><span class='info'>{$user['user_type']}</span></td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($user['created_at'])) . "</td>";
        echo "<td><span class='$role_class'>$suggested_role</span></td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// ============================================================================
// PASO 2: ASIGNAR ROLES (Solo si se envió el formulario)
// ============================================================================
if (isset($_POST['assign_roles'])) {
    echo "<h2>🚀 Paso 2: Asignando Roles...</h2>";
    
    $assigned_count = 0;
    $failed_count = 0;
    $skipped_count = 0;
    
    echo "<table>";
    echo "<tr><th>Usuario</th><th>Tipo</th><th>Rol Asignado</th><th>Estado</th></tr>";
    
    foreach ($users_without_roles as $user) {
        $user_type = $user['user_type'];
        
        // Verificar si existe el rol para este tipo de usuario
        if (!isset($available_roles[$user_type])) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>{$user_type}</td>";
            echo "<td>-</td>";
            echo "<td><span class='warning'>⚠ No hay rol '$user_type' disponible</span></td>";
            echo "</tr>";
            $skipped_count++;
            continue;
        }
        
        $role_id = $available_roles[$user_type]['id'];
        $role_name = $available_roles[$user_type]['display_name'];
        
        // Asignar el rol usando RBAC
        $result = $rbac->assignRoleToUser($user['id'], $role_id, 1); // assigned_by = 1 (admin)
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
        echo "<td>{$user_type}</td>";
        echo "<td>{$role_name}</td>";
        
        if ($result['success']) {
            echo "<td><span class='success'>✓ Asignado correctamente</span></td>";
            $assigned_count++;
        } else {
            echo "<td><span class='error'>✗ Error: {$result['message']}</span></td>";
            $failed_count++;
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Resumen final
    echo "<div class='summary'>";
    echo "<h3>📊 Resultado Final:</h3>";
    echo "<p><span class='success'>✓ Roles asignados exitosamente: $assigned_count</span></p>";
    
    if ($failed_count > 0) {
        echo "<p><span class='error'>✗ Asignaciones fallidas: $failed_count</span></p>";
    }
    
    if ($skipped_count > 0) {
        echo "<p><span class='warning'>⚠ Usuarios omitidos (sin rol disponible): $skipped_count</span></p>";
    }
    
    echo "<p><strong>Total procesado:</strong> " . ($assigned_count + $failed_count + $skipped_count) . "</p>";
    echo "</div>";
    
    echo "<br><a href='ASIGNAR_ROLES_FALTANTES.php' class='btn'>↻ Recargar para verificar</a>";
    
} else {
    // Mostrar botón para ejecutar la asignación
    if ($total_without_roles > 0) {
        echo "<h2>⚡ Paso 2: Ejecutar Asignación</h2>";
        echo "<div class='summary'>";
        echo "<p>¿Deseas asignar automáticamente los roles correspondientes a estos $total_without_roles usuarios?</p>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='assign_roles' class='btn'>✅ SÍ, ASIGNAR ROLES AHORA</button>";
        echo "</form>";
        echo "</div>";
    } else {
        echo "<div class='summary'>";
        echo "<p class='success'>✅ ¡Perfecto! Todos los usuarios tienen roles asignados.</p>";
        echo "<p>No hay nada que hacer. El sistema está correctamente configurado.</p>";
        echo "</div>";
    }
}

// ============================================================================
// VERIFICACIÓN POST-ASIGNACIÓN
// ============================================================================
if (isset($_POST['assign_roles'])) {
    echo "<h2>🔍 Verificación Final</h2>";
    
    // Volver a contar usuarios sin roles
    $verify_query = "
        SELECT COUNT(*) as total
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_active = 1
        WHERE ur.id IS NULL
    ";
    
    $verify_result = mysqli_query($con, $verify_query);
    $verify_row = mysqli_fetch_assoc($verify_result);
    $remaining_without_roles = $verify_row['total'];
    
    if ($remaining_without_roles == 0) {
        echo "<div class='summary'>";
        echo "<p class='success' style='font-size: 20px;'>🎉 ¡EXCELENTE! Todos los usuarios ahora tienen roles asignados.</p>";
        echo "</div>";
    } else {
        echo "<div class='summary'>";
        echo "<p class='warning'>⚠ Aún quedan $remaining_without_roles usuarios sin roles.</p>";
        echo "<p>Es posible que no exista un rol correspondiente a su user_type.</p>";
        echo "</div>";
    }
}

echo "<hr>";
echo "<p><small>Script ejecutado: " . date('Y-m-d H:i:s') . "</small></p>";

mysqli_close($con);
?>
