<?php
/**
 * Endpoint AJAX para extender sesión
 * Llamado cuando el usuario responde al modal de advertencia
 */

session_start();
header('Content-Type: application/json');

// Verificar que exista sesión activa
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No hay sesión activa'
    ]);
    exit();
}

// Cargar dependencias
require_once('include/config.php');
require_once('../include/SessionManager.php');

try {
    $sessionManager = new SessionManager($con);

    // Extender sesión
    $result = $sessionManager->extendSession();

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al extender sesión: ' . $e->getMessage()
    ]);
}
