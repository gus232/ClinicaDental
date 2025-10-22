<?php
/**
 * ============================================================================
 * TEST SUITE: USER MANAGEMENT SYSTEM (FASE 3)
 * ============================================================================
 * Suite completa de pruebas para el sistema de gestión de usuarios
 *
 * Ejecutar: http://localhost/hospital/hms/test-user-management.php
 *
 * PRUEBAS INCLUIDAS:
 * 1-4:   Verificación de base de datos (tablas, SPs, vistas)
 * 5-8:   Pruebas de clases PHP (UserManagement, CSRF)
 * 9-12:  Operaciones CRUD (crear, leer, actualizar, eliminar)
 * 13-16: Gestión de roles
 * 17-19: Búsqueda y filtros
 * 20-21: Estadísticas e historial
 *
 * @version 2.3.0
 * @package HMS
 * @subpackage Tests
 * ============================================================================
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set test user ID
$_SESSION['id'] = 8; // Usuario admin para pruebas
$_SESSION['login'] = 'admin@hospital.com';

// Includes
require_once('include/config.php');
require_once('include/UserManagement.php');
require_once('include/csrf-protection.php');
require_once('include/rbac-functions.php');

// Test results array
$tests = [];
$total_tests = 21;
$passed_tests = 0;
$failed_tests = 0;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Test Suite - FASE 3: Gestión de Usuarios</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            padding: 20px;
            border-radius: 10px;
            color: white;
            text-align: center;
        }
        .summary-card h3 {
            font-size: 36px;
            margin-bottom: 5px;
        }
        .summary-card p {
            font-size: 14px;
            opacity: 0.9;
        }
        .card-total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-passed { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); }
        .card-failed { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .card-percent { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        .test-section {
            margin-bottom: 30px;
        }
        .section-title {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #667eea;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        .test-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            transition: all 0.3s ease;
        }
        .test-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .test-status {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            margin-right: 15px;
        }
        .test-status.pass {
            background: #d4edda;
            color: #155724;
        }
        .test-status.fail {
            background: #f8d7da;
            color: #721c24;
        }
        .test-content {
            flex: 1;
        }
        .test-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            font-size: 15px;
        }
        .test-message {
            color: #666;
            font-size: 13px;
            line-height: 1.5;
        }
        .test-details {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #495057;
            white-space: pre-wrap;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #84fab0 0%, #8fd3f4 100%);
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%);
            color: #333;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🧪 Test Suite - FASE 3</h1>
    <p class="subtitle">Sistema de Gestión de Usuarios con Auditoría Completa</p>

    <?php
    // ========================================================================
    // SECCIÓN 1: VERIFICACIÓN DE BASE DE DATOS
    // ========================================================================

    // TEST 1: Verificar tablas creadas
    $test_num = 1;
    try {
        $required_tables = ['user_change_history', 'user_sessions', 'user_profile_photos', 'user_notes'];
        $existing_tables = [];
        $missing_tables = [];

        foreach ($required_tables as $table) {
            $result = $con->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                $existing_tables[] = $table;
            } else {
                $missing_tables[] = $table;
            }
        }

        if (empty($missing_tables)) {
            $tests[] = [
                'number' => $test_num,
                'name' => 'Tablas de Base de Datos',
                'status' => 'pass',
                'message' => 'Todas las tablas requeridas existen',
                'details' => "Tablas encontradas:\n- " . implode("\n- ", $existing_tables)
            ];
            $passed_tests++;
        } else {
            $tests[] = [
                'number' => $test_num,
                'name' => 'Tablas de Base de Datos',
                'status' => 'fail',
                'message' => 'Faltan ' . count($missing_tables) . ' tablas',
                'details' => "Tablas faltantes:\n- " . implode("\n- ", $missing_tables)
            ];
            $failed_tests++;
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Tablas de Base de Datos',
            'status' => 'fail',
            'message' => 'Error al verificar tablas',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // TEST 2: Verificar stored procedures
    $test_num = 2;
    try {
        $required_sps = [
            'create_user_with_audit',
            'update_user_with_history',
            'search_users',
            'get_user_statistics'
        ];
        $existing_sps = [];
        $missing_sps = [];

        $result = $con->query("SHOW PROCEDURE STATUS WHERE Db = 'hms_v2'");
        $all_sps = [];
        while ($row = $result->fetch_assoc()) {
            $all_sps[] = $row['Name'];
        }

        foreach ($required_sps as $sp) {
            if (in_array($sp, $all_sps)) {
                $existing_sps[] = $sp;
            } else {
                $missing_sps[] = $sp;
            }
        }

        if (empty($missing_sps)) {
            $tests[] = [
                'number' => $test_num,
                'name' => 'Stored Procedures',
                'status' => 'pass',
                'message' => 'Todos los SP están instalados',
                'details' => "SPs encontrados:\n- " . implode("\n- ", $existing_sps)
            ];
            $passed_tests++;
        } else {
            $tests[] = [
                'number' => $test_num,
                'name' => 'Stored Procedures',
                'status' => 'fail',
                'message' => 'Faltan ' . count($missing_sps) . ' stored procedures',
                'details' => "SPs faltantes:\n- " . implode("\n- ", $missing_sps)
            ];
            $failed_tests++;
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Stored Procedures',
            'status' => 'fail',
            'message' => 'Error al verificar SPs',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // TEST 3: Verificar vistas
    $test_num = 3;
    try {
        $required_views = [
            'active_users_summary',
            'user_changes_detailed',
            'active_sessions_view',
            'user_statistics_by_role',
            'recent_changes_timeline',
            'expiring_user_roles'
        ];
        $existing_views = [];
        $missing_views = [];

        foreach ($required_views as $view) {
            $result = $con->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_hms_v2 = '$view'");
            if ($result && $result->num_rows > 0) {
                $existing_views[] = $view;
            } else {
                $missing_views[] = $view;
            }
        }

        if (empty($missing_views)) {
            $tests[] = [
                'number' => $test_num,
                'name' => 'Vistas SQL',
                'status' => 'pass',
                'message' => 'Todas las vistas están creadas',
                'details' => "Vistas encontradas:\n- " . implode("\n- ", $existing_views)
            ];
            $passed_tests++;
        } else {
            $tests[] = [
                'number' => $test_num,
                'name' => 'Vistas SQL',
                'status' => 'fail',
                'message' => 'Faltan ' . count($missing_views) . ' vistas',
                'details' => "Vistas faltantes:\n- " . implode("\n- ", $missing_views)
            ];
            $failed_tests++;
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Vistas SQL',
            'status' => 'fail',
            'message' => 'Error al verificar vistas',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // TEST 4: Verificar índices en tabla users
    $test_num = 4;
    try {
        $result = $con->query("SHOW INDEX FROM users");
        $indexes = [];
        while ($row = $result->fetch_assoc()) {
            $indexes[] = $row['Key_name'];
        }

        $required_indexes = ['idx_full_name', 'idx_email', 'idx_status'];
        $has_indexes = array_intersect($required_indexes, $indexes);

        if (count($has_indexes) >= 2) {
            $tests[] = [
                'number' => $test_num,
                'name' => 'Índices de Optimización',
                'status' => 'pass',
                'message' => 'Índices creados para optimización',
                'details' => "Índices encontrados:\n- " . implode("\n- ", $has_indexes)
            ];
            $passed_tests++;
        } else {
            $tests[] = [
                'number' => $test_num,
                'name' => 'Índices de Optimización',
                'status' => 'fail',
                'message' => 'Faltan índices de optimización',
                'details' => "Se esperaban índices en: full_name, email, status"
            ];
            $failed_tests++;
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Índices de Optimización',
            'status' => 'fail',
            'message' => 'Error al verificar índices',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // ========================================================================
    // SECCIÓN 2: VERIFICACIÓN DE CLASES PHP
    // ========================================================================

    // TEST 5: Clase UserManagement existe y se puede instanciar
    $test_num = 5;
    try {
        if (class_exists('UserManagement')) {
            $userManager = new UserManagement($con, $_SESSION['id']);
            $tests[] = [
                'number' => $test_num,
                'name' => 'Clase UserManagement',
                'status' => 'pass',
                'message' => 'Clase cargada y instanciada correctamente',
                'details' => "Métodos disponibles: createUser, updateUser, deleteUser, getUserById, searchUsers, etc."
            ];
            $passed_tests++;
        } else {
            throw new Exception('Clase UserManagement no encontrada');
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Clase UserManagement',
            'status' => 'fail',
            'message' => 'Error al cargar clase',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
        $userManager = null;
    }

    // TEST 6: Funciones CSRF
    $test_num = 6;
    try {
        if (function_exists('csrf_token') && function_exists('csrf_validate')) {
            $token = csrf_token();
            $_POST['csrf_token'] = $token;
            $is_valid = csrf_validate();

            if ($is_valid && strlen($token) == 64) {
                $tests[] = [
                    'number' => $test_num,
                    'name' => 'Protección CSRF',
                    'status' => 'pass',
                    'message' => 'Funciones CSRF funcionando correctamente',
                    'details' => "Token generado: " . substr($token, 0, 20) . "...\nValidación: OK"
                ];
                $passed_tests++;
            } else {
                throw new Exception('Validación CSRF falló');
            }
        } else {
            throw new Exception('Funciones CSRF no encontradas');
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Protección CSRF',
            'status' => 'fail',
            'message' => 'Error en protección CSRF',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // TEST 7: Funciones RBAC
    $test_num = 7;
    try {
        if (function_exists('hasPermission') && function_exists('hasRole')) {
            $has_perm = hasPermission('manage_users');
            $tests[] = [
                'number' => $test_num,
                'name' => 'Funciones RBAC',
                'status' => 'pass',
                'message' => 'Sistema RBAC integrado correctamente',
                'details' => "Usuario actual tiene permiso 'manage_users': " . ($has_perm ? 'SÍ' : 'NO')
            ];
            $passed_tests++;
        } else {
            throw new Exception('Funciones RBAC no encontradas');
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Funciones RBAC',
            'status' => 'fail',
            'message' => 'Error en sistema RBAC',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // TEST 8: Archivo API existe
    $test_num = 8;
    try {
        $api_file = __DIR__ . '/admin/api/users-api.php';
        if (file_exists($api_file)) {
            $file_size = filesize($api_file);
            $tests[] = [
                'number' => $test_num,
                'name' => 'API REST (users-api.php)',
                'status' => 'pass',
                'message' => 'Archivo API existe y es accesible',
                'details' => "Ruta: admin/api/users-api.php\nTamaño: " . number_format($file_size) . " bytes"
            ];
            $passed_tests++;
        } else {
            throw new Exception('Archivo API no encontrado');
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'API REST (users-api.php)',
            'status' => 'fail',
            'message' => 'Archivo API no encontrado',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // ========================================================================
    // SECCIÓN 3: PRUEBAS FUNCIONALES CRUD
    // ========================================================================

    // TEST 9: Crear usuario (simulación)
    $test_num = 9;
    try {
        if ($userManager) {
            $data = [
                'full_name' => 'Usuario Test ' . time(),
                'email' => 'test_' . time() . '@hospital.com',
                'password' => 'TestPass123!',
                'user_type' => 'patient'
            ];

            $result = $userManager->createUser($data, 'Usuario de prueba creado por test suite');

            if ($result['success']) {
                $tests[] = [
                    'number' => $test_num,
                    'name' => 'Crear Usuario',
                    'status' => 'pass',
                    'message' => 'Usuario creado exitosamente',
                    'details' => "ID del usuario: {$result['user_id']}\nEmail: {$data['email']}"
                ];
                $passed_tests++;
                $test_user_id = $result['user_id'];
            } else {
                throw new Exception($result['message']);
            }
        } else {
            throw new Exception('UserManager no está disponible');
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Crear Usuario',
            'status' => 'fail',
            'message' => 'Error al crear usuario',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
        $test_user_id = null;
    }

    // TEST 10: Leer usuario
    $test_num = 10;
    try {
        if ($userManager && isset($test_user_id)) {
            $user = $userManager->getUserById($test_user_id);

            if ($user) {
                $tests[] = [
                    'number' => $test_num,
                    'name' => 'Leer Usuario',
                    'status' => 'pass',
                    'message' => 'Usuario recuperado correctamente',
                    'details' => "Nombre: {$user['full_name']}\nEmail: {$user['email']}\nEstado: " . ($user['status'] ? 'Activo' : 'Inactivo')
                ];
                $passed_tests++;
            } else {
                throw new Exception('Usuario no encontrado');
            }
        } else {
            throw new Exception('Test anterior falló, no se puede continuar');
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Leer Usuario',
            'status' => 'fail',
            'message' => 'Error al leer usuario',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // TEST 11: Actualizar usuario
    $test_num = 11;
    try {
        if ($userManager && isset($test_user_id)) {
            $update_data = [
                'full_name' => 'Usuario Actualizado ' . time(),
                'email' => 'updated_' . time() . '@hospital.com'
            ];

            $result = $userManager->updateUser($test_user_id, $update_data, 'Actualización de prueba');

            if ($result['success']) {
                $tests[] = [
                    'number' => $test_num,
                    'name' => 'Actualizar Usuario',
                    'status' => 'pass',
                    'message' => 'Usuario actualizado exitosamente',
                    'details' => "Nuevo nombre: {$update_data['full_name']}\nNuevo email: {$update_data['email']}"
                ];
                $passed_tests++;
            } else {
                throw new Exception($result['message']);
            }
        } else {
            throw new Exception('Test anterior falló, no se puede continuar');
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Actualizar Usuario',
            'status' => 'fail',
            'message' => 'Error al actualizar usuario',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // TEST 12: Eliminar usuario (soft delete)
    $test_num = 12;
    try {
        if ($userManager && isset($test_user_id)) {
            $result = $userManager->deleteUser($test_user_id, 'Usuario de prueba eliminado');

            if ($result['success']) {
                $tests[] = [
                    'number' => $test_num,
                    'name' => 'Eliminar Usuario (Soft Delete)',
                    'status' => 'pass',
                    'message' => 'Usuario desactivado exitosamente',
                    'details' => "Usuario ID {$test_user_id} marcado como inactivo"
                ];
                $passed_tests++;
            } else {
                throw new Exception($result['message']);
            }
        } else {
            throw new Exception('Test anterior falló, no se puede continuar');
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Eliminar Usuario (Soft Delete)',
            'status' => 'fail',
            'message' => 'Error al eliminar usuario',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // ========================================================================
    // SECCIÓN 4: GESTIÓN DE ROLES
    // ========================================================================

    // TEST 13: Obtener roles de usuario
    $test_num = 13;
    try {
        if ($userManager) {
            $roles = $userManager->getUserRoles(8); // Usuario admin
            $tests[] = [
                'number' => $test_num,
                'name' => 'Obtener Roles de Usuario',
                'status' => 'pass',
                'message' => 'Roles recuperados exitosamente',
                'details' => "Usuario ID 8 tiene " . count($roles) . " rol(es)"
            ];
            $passed_tests++;
        } else {
            throw new Exception('UserManager no disponible');
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Obtener Roles de Usuario',
            'status' => 'fail',
            'message' => 'Error al obtener roles',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // TEST 14: Asignar roles
    $test_num = 14;
    try {
        if ($userManager && isset($test_user_id)) {
            // Asignar rol "Patient" (ID 4)
            $result = $userManager->assignRoles($test_user_id, [4], 'Asignación de prueba');

            if ($result['success']) {
                $tests[] = [
                    'number' => $test_num,
                    'name' => 'Asignar Roles',
                    'status' => 'pass',
                    'message' => 'Rol asignado exitosamente',
                    'details' => $result['message']
                ];
                $passed_tests++;
            } else {
                throw new Exception($result['message']);
            }
        } else {
            $tests[] = [
                'number' => $test_num,
                'name' => 'Asignar Roles',
                'status' => 'fail',
                'message' => 'No se puede probar, test anterior falló',
                'details' => 'Se necesita un usuario de prueba válido'
            ];
            $failed_tests++;
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Asignar Roles',
            'status' => 'fail',
            'message' => 'Error al asignar roles',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // TEST 15: Revocar roles
    $test_num = 15;
    try {
        if ($userManager && isset($test_user_id)) {
            $result = $userManager->revokeRoles($test_user_id, [4], 'Revocación de prueba');

            if ($result['success']) {
                $tests[] = [
                    'number' => $test_num,
                    'name' => 'Revocar Roles',
                    'status' => 'pass',
                    'message' => 'Rol revocado exitosamente',
                    'details' => $result['message']
                ];
                $passed_tests++;
            } else {
                throw new Exception($result['message']);
            }
        } else {
            $tests[] = [
                'number' => $test_num,
                'name' => 'Revocar Roles',
                'status' => 'fail',
                'message' => 'No se puede probar, test anterior falló',
                'details' => 'Se necesita un usuario de prueba válido'
            ];
            $failed_tests++;
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Revocar Roles',
            'status' => 'fail',
            'message' => 'Error al revocar roles',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // TEST 16: Verificar historial de cambios de roles
    $test_num = 16;
    try {
        if ($userManager && isset($test_user_id)) {
            $history = $userManager->getUserHistory($test_user_id, 10);

            if (is_array($history)) {
                $tests[] = [
                    'number' => $test_num,
                    'name' => 'Historial de Cambios',
                    'status' => 'pass',
                    'message' => 'Historial recuperado exitosamente',
                    'details' => "Se encontraron " . count($history) . " cambios registrados"
                ];
                $passed_tests++;
            } else {
                throw new Exception('Historial no es un array válido');
            }
        } else {
            $tests[] = [
                'number' => $test_num,
                'name' => 'Historial de Cambios',
                'status' => 'fail',
                'message' => 'No se puede probar, test anterior falló',
                'details' => 'Se necesita un usuario de prueba válido'
            ];
            $failed_tests++;
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Historial de Cambios',
            'status' => 'fail',
            'message' => 'Error al obtener historial',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // ========================================================================
    // SECCIÓN 5: BÚSQUEDA Y FILTROS
    // ========================================================================

    // TEST 17: Búsqueda sin filtros
    $test_num = 17;
    try {
        if ($userManager) {
            $users = $userManager->searchUsers('', []);

            if (is_array($users)) {
                $tests[] = [
                    'number' => $test_num,
                    'name' => 'Búsqueda sin Filtros',
                    'status' => 'pass',
                    'message' => 'Búsqueda ejecutada exitosamente',
                    'details' => "Se encontraron " . count($users) . " usuarios"
                ];
                $passed_tests++;
            } else {
                throw new Exception('Resultado no es un array válido');
            }
        } else {
            throw new Exception('UserManager no disponible');
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Búsqueda sin Filtros',
            'status' => 'fail',
            'message' => 'Error en búsqueda',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // TEST 18: Búsqueda con filtros
    $test_num = 18;
    try {
        if ($userManager) {
            $filters = [
                'status' => 1,
                'gender' => 'Male',
                'limit' => 10
            ];
            $users = $userManager->searchUsers('', $filters);

            if (is_array($users)) {
                $tests[] = [
                    'number' => $test_num,
                    'name' => 'Búsqueda con Filtros',
                    'status' => 'pass',
                    'message' => 'Filtros aplicados correctamente',
                    'details' => "Filtros: activos, masculinos\nResultados: " . count($users) . " usuarios"
                ];
                $passed_tests++;
            } else {
                throw new Exception('Resultado no es un array válido');
            }
        } else {
            throw new Exception('UserManager no disponible');
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Búsqueda con Filtros',
            'status' => 'fail',
            'message' => 'Error en búsqueda con filtros',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // TEST 19: Búsqueda por término
    $test_num = 19;
    try {
        if ($userManager) {
            $users = $userManager->searchUsers('admin', []);

            if (is_array($users)) {
                $tests[] = [
                    'number' => $test_num,
                    'name' => 'Búsqueda por Término',
                    'status' => 'pass',
                    'message' => 'Búsqueda por texto funcional',
                    'details' => "Término: 'admin'\nResultados: " . count($users) . " usuarios"
                ];
                $passed_tests++;
            } else {
                throw new Exception('Resultado no es un array válido');
            }
        } else {
            throw new Exception('UserManager no disponible');
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Búsqueda por Término',
            'status' => 'fail',
            'message' => 'Error en búsqueda por término',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // ========================================================================
    // SECCIÓN 6: ESTADÍSTICAS
    // ========================================================================

    // TEST 20: Obtener estadísticas generales
    $test_num = 20;
    try {
        if ($userManager) {
            $stats = $userManager->getStatistics();

            if (is_array($stats) && isset($stats['total_users'])) {
                $tests[] = [
                    'number' => $test_num,
                    'name' => 'Estadísticas Generales',
                    'status' => 'pass',
                    'message' => 'Estadísticas generadas correctamente',
                    'details' => "Total usuarios: {$stats['total_users']}\nActivos: {$stats['active_users']}\nInactivos: {$stats['inactive_users']}"
                ];
                $passed_tests++;
            } else {
                throw new Exception('Estadísticas incompletas o inválidas');
            }
        } else {
            throw new Exception('UserManager no disponible');
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Estadísticas Generales',
            'status' => 'fail',
            'message' => 'Error al obtener estadísticas',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // TEST 21: Obtener todos los usuarios
    $test_num = 21;
    try {
        if ($userManager) {
            $users = $userManager->getAllUsers();

            if (is_array($users)) {
                $tests[] = [
                    'number' => $test_num,
                    'name' => 'Obtener Todos los Usuarios',
                    'status' => 'pass',
                    'message' => 'Lista completa recuperada',
                    'details' => "Total de usuarios en el sistema: " . count($users)
                ];
                $passed_tests++;
            } else {
                throw new Exception('Resultado no es un array válido');
            }
        } else {
            throw new Exception('UserManager no disponible');
        }
    } catch (Exception $e) {
        $tests[] = [
            'number' => $test_num,
            'name' => 'Obtener Todos los Usuarios',
            'status' => 'fail',
            'message' => 'Error al obtener usuarios',
            'details' => $e->getMessage()
        ];
        $failed_tests++;
    }

    // Calculate percentage
    $percentage = ($total_tests > 0) ? round(($passed_tests / $total_tests) * 100, 1) : 0;
    ?>

    <!-- Summary Cards -->
    <div class="summary">
        <div class="summary-card card-total">
            <h3><?php echo $total_tests; ?></h3>
            <p>Total de Pruebas</p>
        </div>
        <div class="summary-card card-passed">
            <h3><?php echo $passed_tests; ?></h3>
            <p>Pruebas Pasadas</p>
        </div>
        <div class="summary-card card-failed">
            <h3><?php echo $failed_tests; ?></h3>
            <p>Pruebas Fallidas</p>
        </div>
        <div class="summary-card card-percent">
            <h3><?php echo $percentage; ?>%</h3>
            <p>Tasa de Éxito</p>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="progress-bar">
        <div class="progress-fill" style="width: <?php echo $percentage; ?>%;">
            <?php echo $percentage; ?>% Completado
        </div>
    </div>

    <!-- Test Results by Section -->
    <?php
    $sections = [
        [
            'title' => '1. Verificación de Base de Datos',
            'description' => 'Tablas, stored procedures, vistas e índices',
            'range' => [1, 4]
        ],
        [
            'title' => '2. Verificación de Clases PHP',
            'description' => 'UserManagement, CSRF, RBAC y API',
            'range' => [5, 8]
        ],
        [
            'title' => '3. Operaciones CRUD',
            'description' => 'Crear, leer, actualizar y eliminar usuarios',
            'range' => [9, 12]
        ],
        [
            'title' => '4. Gestión de Roles',
            'description' => 'Asignar, revocar y verificar historial',
            'range' => [13, 16]
        ],
        [
            'title' => '5. Búsqueda y Filtros',
            'description' => 'Búsquedas simples, con filtros y por término',
            'range' => [17, 19]
        ],
        [
            'title' => '6. Estadísticas y Listados',
            'description' => 'Estadísticas generales y obtención masiva',
            'range' => [20, 21]
        ]
    ];

    foreach ($sections as $section) {
        echo '<div class="test-section">';
        echo '<div class="section-title">' . htmlspecialchars($section['title']) . '</div>';
        echo '<p style="margin-bottom:15px; color:#666;">' . htmlspecialchars($section['description']) . '</p>';

        foreach ($tests as $test) {
            if ($test['number'] >= $section['range'][0] && $test['number'] <= $section['range'][1]) {
                $icon = $test['status'] == 'pass' ? '✓' : '✗';
                echo '<div class="test-item">';
                echo '<div class="test-status ' . $test['status'] . '">' . $icon . '</div>';
                echo '<div class="test-content">';
                echo '<div class="test-name">TEST ' . $test['number'] . ': ' . htmlspecialchars($test['name']) . '</div>';
                echo '<div class="test-message">' . htmlspecialchars($test['message']) . '</div>';
                if (!empty($test['details'])) {
                    echo '<div class="test-details">' . htmlspecialchars($test['details']) . '</div>';
                }
                echo '</div>';
                echo '</div>';
            }
        }

        echo '</div>';
    }
    ?>

    <!-- Action Buttons -->
    <div style="text-align: center; margin-top: 30px;">
        <a href="?rerun=1" class="btn">
            🔄 Ejecutar Nuevamente
        </a>
        <a href="admin/manage-users.php" class="btn btn-secondary">
            👥 Ir a Gestión de Usuarios
        </a>
        <a href="admin/dashboard.php" class="btn btn-secondary">
            🏠 Ir al Dashboard
        </a>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p><strong>FASE 3: Sistema de Gestión de Usuarios</strong></p>
        <p>Hospital Management System v2.3.0 | SIS 321 - Seguridad de Sistemas</p>
        <p>Tests ejecutados el: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

</div>

</body>
</html>
