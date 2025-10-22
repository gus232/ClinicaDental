<?php
/**
 * ============================================================================
 * CSRF PROTECTION
 * ============================================================================
 * Protección contra ataques Cross-Site Request Forgery
 *
 * Uso:
 * 1. En formularios: echo csrf_token_field();
 * 2. En AJAX: headers: { 'X-CSRF-Token': '<?php echo csrf_token(); ?>' }
 * 3. Validar: if (!csrf_validate()) { die('CSRF token inválido'); }
 *
 * @version 2.3.0
 * @package HMS
 * @subpackage Security
 * ============================================================================
 */

if (!function_exists('csrf_token')) {
    /**
     * Generar o obtener token CSRF de la sesión
     *
     * @return string Token CSRF
     */
    function csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_token_field')) {
    /**
     * Generar campo hidden HTML con token CSRF
     *
     * @return string HTML input field
     */
    function csrf_token_field() {
        $token = csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}

if (!function_exists('csrf_validate')) {
    /**
     * Validar token CSRF
     *
     * @param string $token Token a validar (opcional, se toma de POST/Header por defecto)
     * @return bool true si es válido, false si no
     */
    function csrf_validate($token = null) {
        // Si no se provee token, intentar obtenerlo de POST o Header
        if ($token === null) {
            if (isset($_POST['csrf_token'])) {
                $token = $_POST['csrf_token'];
            } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
            } else {
                return false;
            }
        }

        // Verificar que existe token en sesión
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        // Comparación segura contra timing attacks
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('csrf_require')) {
    /**
     * Requerir token CSRF válido o terminar ejecución
     *
     * @param string $error_message Mensaje de error personalizado
     * @return void
     */
    function csrf_require($error_message = 'Token CSRF inválido') {
        if (!csrf_validate()) {
            http_response_code(403);
            die(json_encode([
                'success' => false,
                'message' => $error_message,
                'error' => 'CSRF_VALIDATION_FAILED'
            ]));
        }
    }
}

if (!function_exists('csrf_regenerate')) {
    /**
     * Regenerar token CSRF
     * Útil después de operaciones sensibles
     *
     * @return string Nuevo token
     */
    function csrf_regenerate() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}

?>
