<?php
/**
 * SessionManager - Gestión Completa de Sesiones con Timeouts
 *
 * Maneja el ciclo de vida de sesiones incluyendo:
 * - Control de timeout por inactividad
 * - Control de duración máxima de sesión
 * - Advertencias antes de expiración
 * - Integración con user_logs
 * - Seguridad contra session hijacking
 *
 * @package Hospital Management System
 * @version 2.4.0
 * @since 2025-10-30
 */

class SessionManager
{
    private $db;
    private $settings;

    /**
     * Constructor
     * @param mysqli $db_connection Conexión a la base de datos
     */
    public function __construct($db_connection)
    {
        $this->db = $db_connection;
        $this->settings = $this->loadSettings();
    }

    /**
     * Cargar configuraciones desde system_settings
     * @return array Configuraciones de sesión
     */
    private function loadSettings()
    {
        $settings = [
            'timeout_minutes' => 30,          // Por defecto
            'max_duration_hours' => 8,
            'warning_minutes' => 2,
            'remember_me_enabled' => 1,
            'remember_me_days' => 30
        ];

        $sql = "SELECT setting_key, setting_value
                FROM system_settings
                WHERE setting_category = 'security'
                  AND (setting_key LIKE 'session_%' OR setting_key LIKE 'remember_%')";

        $result = $this->db->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $key = str_replace(['session_', 'remember_me_'], ['', ''], $row['setting_key']);
                $key = str_replace('_', '_', $key);

                // Mapear nombres
                $mapping = [
                    'timeout_minutes' => 'timeout_minutes',
                    'max_duration_hours' => 'max_duration_hours',
                    'warning_minutes' => 'warning_minutes',
                    'enabled' => 'remember_me_enabled',
                    'duration_days' => 'remember_me_days'
                ];

                if (isset($mapping[$key])) {
                    $settings[$mapping[$key]] = (int)$row['setting_value'];
                } elseif (strpos($row['setting_key'], 'session_') === 0) {
                    $simple_key = str_replace('session_', '', $row['setting_key']);
                    $settings[$simple_key] = (int)$row['setting_value'];
                }
            }
        }

        // Calcular valores en segundos para uso interno
        $settings['timeout_seconds'] = $settings['timeout_minutes'] * 60;
        $settings['max_duration_seconds'] = $settings['max_duration_hours'] * 3600;
        $settings['warning_seconds'] = $settings['warning_minutes'] * 60;

        return $settings;
    }

    /**
     * Obtener configuraciones (método estático para uso en templates)
     * @param mysqli $con Conexión a BD
     * @return array Configuraciones
     */
    public static function getSettings($con)
    {
        $instance = new self($con);
        return $instance->settings;
    }

    /**
     * Inicializar sesión después de login exitoso
     * @param int $user_id ID del usuario
     * @param int $session_log_id ID del registro en user_logs
     */
    public function initializeSession($user_id, $session_log_id)
    {
        // Regenerar session ID para prevenir session fixation
        session_regenerate_id(true);

        // Guardar información de sesión
        $_SESSION['session_started_at'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['session_log_id'] = $session_log_id;
        $_SESSION['login_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['session_fingerprint'] = $this->generateFingerprint();
    }

    /**
     * Generar huella digital de la sesión para prevenir hijacking
     * @return string Hash de la huella digital
     */
    private function generateFingerprint()
    {
        $data = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
            // No incluir datos que cambien frecuentemente
        ];

        return hash('sha256', implode('|', $data));
    }

    /**
     * Verificar huella digital de la sesión
     * @return bool True si coincide, false si no
     */
    private function verifyFingerprint()
    {
        if (!isset($_SESSION['session_fingerprint'])) {
            return true; // Sesión antigua, permitir por compatibilidad
        }

        $current = $this->generateFingerprint();
        return $current === $_SESSION['session_fingerprint'];
    }

    /**
     * Verificar timeouts de sesión
     * @return array ['expired' => bool, 'reason' => string, 'remaining' => int]
     */
    public function checkSessionTimeout()
    {
        // Verificar que existan campos de sesión
        if (!isset($_SESSION['last_activity']) || !isset($_SESSION['session_started_at'])) {
            // Sesión antigua sin tracking, inicializar
            $_SESSION['session_started_at'] = time();
            $_SESSION['last_activity'] = time();
            return ['expired' => false, 'reason' => '', 'remaining' => $this->settings['timeout_seconds']];
        }

        $current_time = time();
        $inactive_time = $current_time - $_SESSION['last_activity'];
        $total_session_time = $current_time - $_SESSION['session_started_at'];

        // Verificar huella digital (prevenir session hijacking)
        if (!$this->verifyFingerprint()) {
            return [
                'expired' => true,
                'reason' => 'security',
                'message' => 'Sesión invalidada por razones de seguridad',
                'remaining' => 0
            ];
        }

        // 1. Verificar timeout por inactividad
        if ($inactive_time > $this->settings['timeout_seconds']) {
            return [
                'expired' => true,
                'reason' => 'timeout',
                'message' => 'Sesión expirada por inactividad',
                'remaining' => 0
            ];
        }

        // 2. Verificar duración máxima de sesión
        if ($total_session_time > $this->settings['max_duration_seconds']) {
            return [
                'expired' => true,
                'reason' => 'max_duration',
                'message' => 'Sesión expirada por exceder tiempo máximo',
                'remaining' => 0
            ];
        }

        // Sesión válida
        $remaining_timeout = $this->settings['timeout_seconds'] - $inactive_time;
        $remaining_max = $this->settings['max_duration_seconds'] - $total_session_time;
        $remaining = min($remaining_timeout, $remaining_max);

        return [
            'expired' => false,
            'reason' => '',
            'remaining' => $remaining
        ];
    }

    /**
     * Actualizar última actividad del usuario
     */
    public function updateLastActivity()
    {
        $_SESSION['last_activity'] = time();
    }

    /**
     * Obtener tiempo restante de sesión (para JavaScript)
     * @return array ['timeout' => seconds, 'max_duration' => seconds, 'minimum' => seconds]
     */
    public function getRemainingTime()
    {
        if (!isset($_SESSION['last_activity']) || !isset($_SESSION['session_started_at'])) {
            return [
                'timeout' => $this->settings['timeout_seconds'],
                'max_duration' => $this->settings['max_duration_seconds'],
                'minimum' => $this->settings['timeout_seconds']
            ];
        }

        $current_time = time();
        $inactive_time = $current_time - $_SESSION['last_activity'];
        $total_session_time = $current_time - $_SESSION['session_started_at'];

        $remaining_timeout = $this->settings['timeout_seconds'] - $inactive_time;
        $remaining_max = $this->settings['max_duration_seconds'] - $total_session_time;

        return [
            'timeout' => max(0, $remaining_timeout),
            'max_duration' => max(0, $remaining_max),
            'minimum' => max(0, min($remaining_timeout, $remaining_max)),
            'warning_seconds' => $this->settings['warning_seconds']
        ];
    }

    /**
     * Extender sesión (cuando usuario responde al modal)
     * @return array Resultado de la operación
     */
    public function extendSession()
    {
        // Verificar que no haya excedido duración máxima
        if (isset($_SESSION['session_started_at'])) {
            $total_time = time() - $_SESSION['session_started_at'];
            if ($total_time > $this->settings['max_duration_seconds']) {
                return [
                    'success' => false,
                    'message' => 'No se puede extender: sesión excedió duración máxima'
                ];
            }
        }

        // Actualizar última actividad
        $this->updateLastActivity();

        return [
            'success' => true,
            'message' => 'Sesión extendida exitosamente',
            'remaining' => $this->getRemainingTime()
        ];
    }

    /**
     * Destruir sesión completamente (Opción C)
     * - Destruye sesión PHP
     * - Elimina cookies
     * - Marca sesión como cerrada en user_logs
     *
     * @param string $reason Razón del cierre: manual, timeout, forced, max_duration, security
     */
    public function destroySession($reason = 'manual')
    {
        $user_id = $_SESSION['id'] ?? null;
        $session_log_id = $_SESSION['session_log_id'] ?? null;

        // 1. Marcar sesión como cerrada en user_logs
        if ($user_id && $session_log_id) {
            require_once(__DIR__ . '/UserActivityLogger.php');
            $logger = new UserActivityLogger($this->db);
            $logger->logLogout($user_id, session_id(), $reason);
        }

        // 2. Destruir sesión PHP
        $_SESSION = array();

        // 3. Eliminar cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // 4. Eliminar cookie "Recordarme" si existe
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);

            // Eliminar token de la BD
            if ($user_id) {
                $sql = "DELETE FROM remember_me_tokens WHERE user_id = ?";
                $stmt = $this->db->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('i', $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 5. Destruir sesión
        session_destroy();
    }

    /**
     * Verificar si función "Recordarme" está habilitada
     * @return bool
     */
    public function isRememberMeEnabled()
    {
        return $this->settings['remember_me_enabled'] == 1;
    }

    /**
     * Crear token "Recordarme"
     * @param int $user_id ID del usuario
     * @return array|false Array con selector y token, o false si falla
     */
    public function createRememberMeToken($user_id)
    {
        if (!$this->isRememberMeEnabled()) {
            return false;
        }

        // Generar selector y token únicos
        $selector = bin2hex(random_bytes(16));
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);

        // Calcular expiración
        $expires_at = date('Y-m-d H:i:s', time() + ($this->settings['remember_me_days'] * 24 * 60 * 60));

        // Guardar en BD
        $sql = "INSERT INTO remember_me_tokens (user_id, token, selector, expires_at, user_agent, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        $stmt->bind_param('isssss', $user_id, $token_hash, $selector, $expires_at, $user_agent, $ip);

        if ($stmt->execute()) {
            $stmt->close();

            // Crear cookie segura
            $cookie_value = $selector . ':' . $token;
            $cookie_duration = $this->settings['remember_me_days'] * 24 * 60 * 60;

            setcookie(
                'remember_token',
                $cookie_value,
                time() + $cookie_duration,
                '/',
                '',
                isset($_SERVER['HTTPS']), // Secure solo en HTTPS
                true // HttpOnly
            );

            return [
                'selector' => $selector,
                'token' => $token
            ];
        }

        $stmt->close();
        return false;
    }

    /**
     * Validar token "Recordarme"
     * @return int|false User ID si es válido, false si no
     */
    public function validateRememberMeToken()
    {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }

        $parts = explode(':', $_COOKIE['remember_token']);
        if (count($parts) !== 2) {
            return false;
        }

        list($selector, $token) = $parts;
        $token_hash = hash('sha256', $token);

        // Buscar en BD
        $sql = "SELECT user_id, token, expires_at
                FROM remember_me_tokens
                WHERE selector = ?
                  AND expires_at > NOW()
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $selector);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            return false;
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        // Verificar token
        if (hash_equals($row['token'], $token_hash)) {
            // Actualizar last_used
            $update_sql = "UPDATE remember_me_tokens SET last_used = NOW() WHERE selector = ?";
            $update_stmt = $this->db->prepare($update_sql);
            if ($update_stmt) {
                $update_stmt->bind_param('s', $selector);
                $update_stmt->execute();
                $update_stmt->close();
            }

            return (int)$row['user_id'];
        }

        return false;
    }
}
