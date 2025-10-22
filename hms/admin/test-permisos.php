<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de Permisos</h1>";

// Test 1: Verificar includes
echo "<h2>1. Verificando includes...</h2>";
try {
    require_once('include/config.php');
    echo "✓ config.php cargado<br>";
} catch (Exception $e) {
    echo "✗ Error en config.php: " . $e->getMessage() . "<br>";
}

try {
    require_once('../include/permission-check.php');
    echo "✓ permission-check.php cargado<br>";
} catch (Exception $e) {
    echo "✗ Error en permission-check.php: " . $e->getMessage() . "<br>";
}

try {
    require_once('../include/rbac-functions.php');
    echo "✓ rbac-functions.php cargado<br>";
} catch (Exception $e) {
    echo "✗ Error en rbac-functions.php: " . $e->getMessage() . "<br>";
}

// Test 2: Verificar sesión
echo "<h2>2. Verificando sesión...</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . ($_SESSION['id'] ?? 'NO DEFINIDO') . "<br>";
echo "User Type: " . ($_SESSION['user_type'] ?? 'NO DEFINIDO') . "<br>";

// Test 3: Verificar conexión a BD
echo "<h2>3. Verificando conexión a BD...</h2>";
if (isset($con)) {
    echo "✓ Conexión establecida<br>";

    // Verificar tabla de permisos
    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM permissions");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "✓ Total permisos en BD: " . $row['total'] . "<br>";
    }
} else {
    echo "✗ No hay conexión a BD<br>";
}

// Test 4: Verificar funciones RBAC
echo "<h2>4. Verificando funciones RBAC...</h2>";
if (function_exists('hasPermission')) {
    echo "✓ Función hasPermission() existe<br>";

    // Probar permisos
    $permisos_a_probar = ['view_users', 'manage_roles', 'create_user', 'edit_user'];
    foreach ($permisos_a_probar as $permiso) {
        $tiene = hasPermission($permiso) ? '✓ SI' : '✗ NO';
        echo "$tiene - $permiso<br>";
    }
} else {
    echo "✗ Función hasPermission() NO existe<br>";
}

if (function_exists('requirePermission')) {
    echo "✓ Función requirePermission() existe<br>";
} else {
    echo "✗ Función requirePermission() NO existe<br>";
}

// Test 5: Verificar vista user_effective_permissions
echo "<h2>5. Verificando vista user_effective_permissions...</h2>";
if (isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
    $query = "SELECT permission_name FROM user_effective_permissions WHERE user_id = $user_id";
    $result = mysqli_query($con, $query);

    if ($result) {
        echo "✓ Vista accesible<br>";
        echo "<strong>Permisos del usuario actual:</strong><br>";
        $permisos = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $permisos[] = $row['permission_name'];
        }
        echo "<pre>" . print_r($permisos, true) . "</pre>";
    } else {
        echo "✗ Error al consultar vista: " . mysqli_error($con) . "<br>";
    }
} else {
    echo "✗ No hay sesión activa<br>";
}

echo "<hr>";
echo "<a href='dashboard.php'>Volver al Dashboard</a>";
?>
