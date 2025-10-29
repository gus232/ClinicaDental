<?php
/**
 * API Endpoint: Generar Email Corporativo
 *
 * Genera un email corporativo basado en nombre y apellido del usuario
 * según la configuración del sistema.
 *
 * Método: POST
 * Parámetros:
 *   - firstname: Nombre del usuario (requerido)
 *   - lastname: Apellido del usuario (requerido)
 *   - exclude_user_id: ID de usuario a excluir en verificación (opcional)
 *
 * Respuesta JSON:
 *   - success: boolean
 *   - email: string (si success=true)
 *   - error: string (si success=false)
 */

session_start();
header('Content-Type: application/json');

// Incluir archivos necesarios
include('../include/config.php');
include('../include/checklogin.php');
include('../../include/rbac-functions.php');

// Verificar que la sesión esté activa
if (!isset($_SESSION['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No autenticado'
    ]);
    exit();
}

// Verificar permisos (solo usuarios que pueden crear usuarios)
if (!hasPermission('create_user') && !hasPermission('manage_users')) {
    echo json_encode([
        'success' => false,
        'error' => 'Sin permisos para generar emails'
    ]);
    exit();
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
    exit();
}

// Obtener y validar parámetros
$firstname = trim($_POST['firstname'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$exclude_user_id = isset($_POST['exclude_user_id']) ? intval($_POST['exclude_user_id']) : null;

// Validar que los campos no estén vacíos
if (empty($firstname) || empty($lastname)) {
    echo json_encode([
        'success' => false,
        'error' => 'El nombre y apellido son requeridos'
    ]);
    exit();
}

// Validar que los nombres solo contengan letras y espacios
if (!preg_match("/^[a-záéíóúñü\s]+$/i", $firstname) || !preg_match("/^[a-záéíóúñü\s]+$/i", $lastname)) {
    echo json_encode([
        'success' => false,
        'error' => 'El nombre y apellido solo pueden contener letras'
    ]);
    exit();
}

try {
    // Generar el email corporativo
    $email = generateCorporateEmail($firstname, $lastname);

    if (!$email) {
        throw new Exception('No se pudo generar el email');
    }

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'email' => $email,
        'message' => 'Email generado exitosamente'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al generar email: ' . $e->getMessage()
    ]);
}
?>
