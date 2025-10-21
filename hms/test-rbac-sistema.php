<?php
/**
 * ============================================================================
 * ARCHIVO DE PRUEBAS: Sistema RBAC Completo
 * ============================================================================
 * Este archivo ejecuta 8 pruebas del sistema RBAC
 * ============================================================================
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simular sesión del usuario 1 (Super Admin)
// CAMBIA ESTE NÚMERO si tu usuario tiene otro ID
$_SESSION['id'] = 8;

require_once('include/config.php');
require_once('include/rbac-functions.php');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pruebas RBAC - HMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Consolas', 'Monaco', monospace;
            background: #1a1a2e;
            color: #eee;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #16213e;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        h1 {
            color: #0f3;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
            text-shadow: 0 0 10px rgba(0, 255, 51, 0.5);
        }

        .subtitle {
            text-align: center;
            color: #888;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .test {
            background: #0f1419;
            border-left: 4px solid #0f3;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .test-title {
            color: #0f3;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .result {
            margin: 5px 0;
            padding: 8px;
            background: #1a1a2e;
            border-radius: 3px;
        }

        .pass {
            color: #0f3;
            font-weight: bold;
        }

        .fail {
            color: #f33;
            font-weight: bold;
        }

        .expected {
            color: #888;
            font-size: 13px;
        }

        .divider {
            border-top: 2px solid #0f3;
            margin: 30px 0;
            opacity: 0.3;
        }

        .summary {
            background: #0f3;
            color: #000;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-top: 30px;
            font-size: 18px;
            font-weight: bold;
        }

        .summary.fail-summary {
            background: #f33;
            color: #fff;
        }

        .detail {
            color: #aaa;
            font-size: 13px;
            margin: 5px 0;
        }

        .list {
            margin: 10px 0;
            padding-left: 20px;
        }

        .list-item {
            color: #ccc;
            margin: 5px 0;
        }

        pre {
            background: #000;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            color: #0f3;
            font-size: 12px;
        }

        .header-info {
            background: #0f1419;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #ff0;
        }

        .warning {
            color: #ff0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 PRUEBAS DEL SISTEMA RBAC</h1>
        <p class="subtitle">Sistema de Roles y Permisos - Hospital Management System</p>

        <div class="header-info">
            <div class="warning">⚠️ CONFIGURACIÓN</div>
            <div class="detail">Usuario de prueba: ID = <?php echo $_SESSION['id']; ?></div>
            <div class="detail">Si tu usuario tiene otro ID, edita la línea 11 de este archivo</div>
        </div>

        <?php
        $tests_passed = 0;
        $total_tests = 8;

        // Test 1: hasPermission()
        echo '<div class="test">';
        echo '<div class="test-title">TEST 1: hasPermission("view_patients")</div>';
        $result1 = hasPermission('view_patients');
        echo '<div class="result">Resultado: <span class="' . ($result1 ? 'pass' : 'fail') . '">' .
             ($result1 ? '✅ PASS (TRUE)' : '❌ FAIL (FALSE)') . '</span></div>';
        echo '<div class="expected">Esperado: ✅ TRUE (el usuario debe tener este permiso)</div>';
        if ($result1) $tests_passed++;
        echo '</div>';

        // Test 2: hasRole()
        echo '<div class="test">';
        echo '<div class="test-title">TEST 2: hasRole("super_admin")</div>';
        $result2 = hasRole('super_admin');
        echo '<div class="result">Resultado: <span class="' . ($result2 ? 'pass' : 'fail') . '">' .
             ($result2 ? '✅ PASS (TRUE)' : '❌ FAIL (FALSE)') . '</span></div>';
        echo '<div class="expected">Esperado: ✅ TRUE (el usuario debe tener rol super_admin)</div>';
        if ($result2) $tests_passed++;
        echo '</div>';

        // Test 3: isSuperAdmin()
        echo '<div class="test">';
        echo '<div class="test-title">TEST 3: isSuperAdmin()</div>';
        $result3 = isSuperAdmin();
        echo '<div class="result">Resultado: <span class="' . ($result3 ? 'pass' : 'fail') . '">' .
             ($result3 ? '✅ PASS (TRUE)' : '❌ FAIL (FALSE)') . '</span></div>';
        echo '<div class="expected">Esperado: ✅ TRUE</div>';
        if ($result3) $tests_passed++;
        echo '</div>';

        // Test 4: isAdmin()
        echo '<div class="test">';
        echo '<div class="test-title">TEST 4: isAdmin()</div>';
        $result4 = isAdmin();
        echo '<div class="result">Resultado: <span class="' . ($result4 ? 'pass' : 'fail') . '">' .
             ($result4 ? '✅ PASS (TRUE)' : '❌ FAIL (FALSE)') . '</span></div>';
        echo '<div class="expected">Esperado: ✅ TRUE (super_admin es también admin)</div>';
        if ($result4) $tests_passed++;
        echo '</div>';

        // Test 5: getUserPermissions()
        echo '<div class="test">';
        echo '<div class="test-title">TEST 5: getUserPermissions()</div>';
        $perms = getUserPermissions();
        $total_perms = count($perms);
        $result5 = $total_perms >= 58;
        echo '<div class="result">Total de permisos: <span class="' . ($result5 ? 'pass' : 'fail') . '">' .
             $total_perms . '</span></div>';
        echo '<div class="expected">Esperado: >= 58 permisos</div>';
        echo '<div class="result">Resultado: <span class="' . ($result5 ? 'pass' : 'fail') . '">' .
             ($result5 ? '✅ PASS' : '❌ FAIL') . '</span></div>';
        if ($result5) $tests_passed++;
        if ($total_perms > 0) {
            echo '<div class="detail">Primeros 5 permisos:</div>';
            echo '<div class="list">';
            foreach (array_slice($perms, 0, 5) as $perm) {
                echo '<div class="list-item">• ' . htmlspecialchars($perm) . '</div>';
            }
            echo '</div>';
        }
        echo '</div>';

        // Test 6: getUserRoles()
        echo '<div class="test">';
        echo '<div class="test-title">TEST 6: getUserRoles()</div>';
        $roles = getUserRoles();
        $total_roles = count($roles);
        $result6 = $total_roles >= 1;
        echo '<div class="result">Total de roles: <span class="' . ($result6 ? 'pass' : 'fail') . '">' .
             $total_roles . '</span></div>';
        echo '<div class="expected">Esperado: >= 1 rol</div>';
        echo '<div class="result">Resultado: <span class="' . ($result6 ? 'pass' : 'fail') . '">' .
             ($result6 ? '✅ PASS' : '❌ FAIL') . '</span></div>';
        if ($total_roles > 0) {
            echo '<div class="detail">Roles asignados:</div>';
            echo '<div class="list">';
            foreach ($roles as $role) {
                echo '<div class="list-item">• ' . htmlspecialchars($role['display_name']) .
                     ' (prioridad: ' . $role['priority'] . ')</div>';
            }
            echo '</div>';
        }
        if ($result6) $tests_passed++;
        echo '</div>';

        // Test 7: Clase RBAC - getRoleInfo()
        echo '<div class="test">';
        echo '<div class="test-title">TEST 7: Clase RBAC - getRoleInfo(1)</div>';
        $rbac = new RBAC($con);
        $role_info = $rbac->getRoleInfo(1); // Super Admin
        $result7 = ($role_info && $role_info['role_name'] === 'super_admin');
        if ($role_info) {
            echo '<div class="result">Rol ID 1: <span class="pass">' .
                 htmlspecialchars($role_info['display_name']) . '</span></div>';
        }
        echo '<div class="expected">Esperado: Super Administrador</div>';
        echo '<div class="result">Resultado: <span class="' . ($result7 ? 'pass' : 'fail') . '">' .
             ($result7 ? '✅ PASS' : '❌ FAIL') . '</span></div>';
        if ($result7) $tests_passed++;
        echo '</div>';

        // Test 8: getRolePermissions()
        echo '<div class="test">';
        echo '<div class="test-title">TEST 8: getRolePermissions(1) - Permisos de Super Admin</div>';
        $role_perms = $rbac->getRolePermissions(1);
        $total_role_perms = count($role_perms);
        $result8 = $total_role_perms >= 58;
        echo '<div class="result">Total de permisos del rol: <span class="' . ($result8 ? 'pass' : 'fail') . '">' .
             $total_role_perms . '</span></div>';
        echo '<div class="expected">Esperado: >= 58 permisos</div>';
        echo '<div class="result">Resultado: <span class="' . ($result8 ? 'pass' : 'fail') . '">' .
             ($result8 ? '✅ PASS' : '❌ FAIL') . '</span></div>';
        if ($total_role_perms > 0) {
            echo '<div class="detail">Primeros 5 permisos del rol:</div>';
            echo '<div class="list">';
            foreach (array_slice($role_perms, 0, 5) as $perm) {
                echo '<div class="list-item">• ' . htmlspecialchars($perm['display_name']) .
                     ' (' . htmlspecialchars($perm['permission_name']) . ')</div>';
            }
            echo '</div>';
        }
        if ($result8) $tests_passed++;
        echo '</div>';

        // Resumen
        $percentage = round(($tests_passed / $total_tests) * 100);
        $summary_class = ($tests_passed === $total_tests) ? 'summary' : 'summary fail-summary';

        echo '<div class="divider"></div>';
        echo '<div class="' . $summary_class . '">';
        echo '📊 RESUMEN DE PRUEBAS<br>';
        echo 'Pruebas pasadas: ' . $tests_passed . ' / ' . $total_tests . ' (' . $percentage . '%)<br><br>';

        if ($tests_passed === $total_tests) {
            echo '✅ ¡TODAS LAS PRUEBAS PASARON!<br>';
            echo 'El sistema RBAC está funcionando correctamente.';
        } else {
            echo '⚠️ Algunas pruebas fallaron (' . ($total_tests - $tests_passed) . ')<br>';
            echo 'Revisa los resultados arriba y verifica:<br>';
            echo '1. Que ejecutaste los 5 stored procedures<br>';
            echo '2. Que asignaste el rol Super Admin al usuario ' . $_SESSION['id'];
        }
        echo '</div>';
        ?>

        <div class="divider"></div>

        <div class="test">
            <div class="test-title">📚 Próximos Pasos</div>
            <div class="list">
                <div class="list-item">• Ver demo interactiva: <a href="admin/rbac-example.php" style="color: #0f3;">rbac-example.php</a></div>
                <div class="list-item">• Leer documentación: docs/RBAC_USAGE_GUIDE.md</div>
                <div class="list-item">• Probar middleware: <a href="test-protected.php" style="color: #0f3;">test-protected.php</a></div>
                <div class="list-item">• Ver plan completo: PASOS_COMPLETOS_INSTALACION_Y_PRUEBAS.md</div>
            </div>
        </div>
    </div>
</body>
</html>
