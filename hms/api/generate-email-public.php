<?php
/**
 * API Endpoint Público: Generar Email Corporativo para Registro
 *
 * Genera un email corporativo basado en nombre y apellido del usuario
 * según la configuración del sistema.
 * Esta versión es pública y no requiere autenticación.
 *
 * Método: POST
 * Parámetros:
 *   - firstname: Nombre del usuario (requerido)
 *   - lastname: Apellido del usuario (requerido)
 *
 * Respuesta JSON:
 *   - success: boolean
 *   - email: string (si success=true)
 *   - error: string (si success=false)
 */

header('Content-Type: application/json');

// Incluir archivos necesarios
include('../include/config.php');
include('../include/rbac-functions.php');

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
