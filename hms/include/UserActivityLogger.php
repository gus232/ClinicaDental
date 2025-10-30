<?php
/**
 * UserActivityLogger - Clase para registrar actividad de usuarios
 *
 * Maneja el registro de sesiones de usuarios incluyendo:
 * - Login/Logout
 * - Detección de dispositivo y navegador
 * - Tracking de sesiones activas
 * - Limpieza de sesiones inactivas
 *
 * @package Hospital Management System
 * @version 2.3.0
 * @since 2025-10-30
 */

class UserActivityLogger
{
    private $db;

    /**
     * Constructor
     * @param mysqli $db_connection Conexión a la base de datos
     */
    public function __construct($db_connection)
    {
        $this->db = $db_connection;
    }

    /**
     * Detectar tipo de dispositivo basado en user agent
     *
     * @param string $user_agent User agent del navegador
     * @return string Tipo de dispositivo: desktop, mobile, tablet, other
     */
    public static function detectDeviceType($user_agent)
    {
        if (empty($user_agent)) {
            return 'other';
        }

        $user_agent = strtolower($user_agent);

        // Detectar tablets primero (son más específicos)
        if (preg_match('/(ipad|tablet|playbook|silk|kindle|android(?!.*mobile))/i', $user_agent)) {
            return 'tablet';
        }

        // Detectar móviles
        if (preg_match('/(android|webos|iphone|ipod|blackberry|iemobile|opera mini)/i', $user_agent)) {
            return 'mobile';
        }

        // Si no es móvil ni tablet, es desktop
        if (preg_match('/(windows|macintosh|linux|x11)/i', $user_agent)) {
            return 'desktop';
        }

        return 'other';
    }

    /**
     * Detectar navegador basado en user agent
     *
     * @param string $user_agent User agent del navegador
     * @return string|null Nombre del navegador
     */
    public static function detectBrowser($user_agent)
    {
        if (empty($user_agent)) {
            return null;
        }

        // Edge (debe ir antes que Chrome)
        if (preg_match('/edg(?:e|ios|a)/i', $user_agent)) {
            return 'Edge';
        }

        // Chrome (debe ir antes que Safari)
        if (preg_match('/chrome|chromium|crios/i', $user_agent)) {
            return 'Chrome';
        }

        // Safari
        if (preg_match('/safari/i', $user_agent)) {
            return 'Safari';
        }

        // Firefox
        if (preg_match('/firefox|fxios/i', $user_agent)) {
            return 'Firefox';
        }

        // Opera
        if (preg_match('/opera|opr\//i', $user_agent)) {
            return 'Opera';
        }

        // Internet Explorer
        if (preg_match('/msie|trident/i', $user_agent)) {
            return 'Internet Explorer';
        }

        // Brave
        if (preg_match('/brave/i', $user_agent)) {
            return 'Brave';
        }

        return 'Other';
    }

    /**
     * Detectar sistema operativo basado en user agent
     *
     * @param string $user_agent User agent del navegador
     * @return string|null Sistema operativo detectado
     */
    public static function detectOS($user_agent)
    {
        if (empty($user_agent)) {
            return null;
        }

        // Windows
        if (preg_match('/windows nt 10/i', $user_agent)) {
            return 'Windows 10/11';
        }
        if (preg_match('/windows nt 6\.3/i', $user_agent)) {
            return 'Windows 8.1';
        }
        if (preg_match('/windows nt 6\.2/i', $user_agent)) {
            return 'Windows 8';
        }
        if (preg_match('/windows nt 6\.1/i', $user_agent)) {
            return 'Windows 7';
        }
        if (preg_match('/windows/i', $user_agent)) {
            return 'Windows';
        }

        // macOS
        if (preg_match('/mac os x (\d+[._]\d+)/i', $user_agent, $matches)) {
            $version = str_replace('_', '.', $matches[1]);
            return 'macOS ' . $version;
        }
        if (preg_match('/macintosh|mac os x/i', $user_agent)) {
            return 'macOS';
        }

        // iOS
        if (preg_match('/iphone/i', $user_agent)) {
            return 'iOS (iPhone)';
        }
        if (preg_match('/ipad/i', $user_agent)) {
            return 'iOS (iPad)';
        }
        if (preg_match('/ipod/i', $user_agent)) {
            return 'iOS (iPod)';
        }

        // Android
        if (preg_match('/android (\d+\.?\d*)/i', $user_agent, $matches)) {
            return 'Android ' . $matches[1];
        }
        if (preg_match('/android/i', $user_agent)) {
            return 'Android';
        }

        // Linux
        if (preg_match('/linux/i', $user_agent)) {
            return 'Linux';
        }

        // ChromeOS
        if (preg_match('/cros/i', $user_agent)) {
            return 'ChromeOS';
        }

        return 'Other';
    }

    /**
     * Registrar inicio de sesión de un usuario
     *
     * @param int $user_id ID del usuario
     * @param string $session_id ID de sesión PHP
     * @param string|null $ip_address IP del usuario (si no se proporciona, usa REMOTE_ADDR)
     * @param string|null $user_agent User agent (si no se proporciona, usa HTTP_USER_AGENT)
     * @return array Resultado de la operación ['success' => bool, 'log_id' => int, 'message' => string]
     */
    public function logLogin($user_id, $session_id, $ip_address = null, $user_agent = null)
    {
        // Obtener IP si no se proporcionó
        if ($ip_address === null) {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        // Obtener user agent si no se proporcionó
        if ($user_agent === null) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        }

        // Detectar información del dispositivo
        $device_type = self::detectDeviceType($user_agent);
        $browser = self::detectBrowser($user_agent);
        $os = self::detectOS($user_agent);

        // Preparar query
        $sql = "INSERT INTO user_logs (
                    user_id,
                    login_time,
                    ip_address,
                    user_agent,
                    device_type,
                    browser,
                    os,
                    session_id,
                    is_active
                ) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, 1)";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Error al preparar query: ' . $this->db->error
            ];
        }

        $stmt->bind_param(
            'issssss',
            $user_id,
            $ip_address,
            $user_agent,
            $device_type,
            $browser,
            $os,
            $session_id
        );

        if ($stmt->execute()) {
            $log_id = $stmt->insert_id;
            $stmt->close();

            return [
                'success' => true,
                'log_id' => $log_id,
                'message' => 'Login registrado exitosamente'
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();

            return [
                'success' => false,
                'message' => 'Error al registrar login: ' . $error
            ];
        }
    }

    /**
     * Registrar cierre de sesión de un usuario
     *
     * @param int $user_id ID del usuario
     * @param string $session_id ID de sesión PHP
     * @param string $logout_reason Razón del logout: manual, timeout, forced, error
     * @return array Resultado de la operación
     */
    public function logLogout($user_id, $session_id, $logout_reason = 'manual')
    {
        // Validar logout_reason
        $valid_reasons = ['manual', 'timeout', 'forced', 'error'];
        if (!in_array($logout_reason, $valid_reasons)) {
            $logout_reason = 'manual';
        }

        // Actualizar la sesión activa más reciente del usuario
        $sql = "UPDATE user_logs SET
                    logout_time = NOW(),
                    session_duration = TIMESTAMPDIFF(SECOND, login_time, NOW()),
                    logout_reason = ?,
                    is_active = 0
                WHERE user_id = ?
                  AND session_id = ?
                  AND is_active = 1
                ORDER BY login_time DESC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Error al preparar query: ' . $this->db->error
            ];
        }

        $stmt->bind_param('sis', $logout_reason, $user_id, $session_id);

        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            $stmt->close();

            return [
                'success' => true,
                'affected_rows' => $affected_rows,
                'message' => 'Logout registrado exitosamente'
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();

            return [
                'success' => false,
                'message' => 'Error al registrar logout: ' . $error
            ];
        }
    }

    /**
     * Marcar sesiones inactivas como cerradas por timeout
     *
     * @param int $timeout_minutes Minutos de inactividad para considerar timeout
     * @return array Resultado con cantidad de sesiones cerradas
     */
    public function cleanupInactiveSessions($timeout_minutes = 30)
    {
        $sql = "UPDATE user_logs SET
                    logout_time = NOW(),
                    session_duration = TIMESTAMPDIFF(SECOND, login_time, NOW()),
                    logout_reason = 'timeout',
                    is_active = 0
                WHERE is_active = 1
                  AND TIMESTAMPDIFF(MINUTE, login_time, NOW()) > ?
                  AND logout_time IS NULL";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Error al preparar query: ' . $this->db->error
            ];
        }

        $stmt->bind_param('i', $timeout_minutes);

        if ($stmt->execute()) {
            $sessions_closed = $stmt->affected_rows;
            $stmt->close();

            return [
                'success' => true,
                'sessions_closed' => $sessions_closed,
                'message' => "$sessions_closed sesiones cerradas por timeout"
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();

            return [
                'success' => false,
                'message' => 'Error al limpiar sesiones: ' . $error
            ];
        }
    }

    /**
     * Obtener sesiones activas de un usuario
     *
     * @param int $user_id ID del usuario
     * @return array Lista de sesiones activas
     */
    public function getActiveSessions($user_id)
    {
        $sql = "SELECT
                    id,
                    login_time,
                    ip_address,
                    device_type,
                    browser,
                    os,
                    session_id,
                    TIMESTAMPDIFF(MINUTE, login_time, NOW()) as minutes_active
                FROM user_logs
                WHERE user_id = ?
                  AND is_active = 1
                ORDER BY login_time DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $sessions = [];
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }

        $stmt->close();
        return $sessions;
    }

    /**
     * Forzar cierre de una sesión específica
     *
     * @param int $log_id ID del registro en user_logs
     * @param int $admin_user_id ID del admin que fuerza el cierre (opcional)
     * @return array Resultado de la operación
     */
    public function forceLogout($log_id, $admin_user_id = null)
    {
        $sql = "UPDATE user_logs SET
                    logout_time = NOW(),
                    session_duration = TIMESTAMPDIFF(SECOND, login_time, NOW()),
                    logout_reason = 'forced',
                    is_active = 0
                WHERE id = ?
                  AND is_active = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $log_id);

        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            $stmt->close();

            // Opcional: Registrar en security_logs que un admin forzó el logout
            if ($admin_user_id && $affected_rows > 0) {
                $this->logForcedLogoutToSecurity($log_id, $admin_user_id);
            }

            return [
                'success' => true,
                'affected_rows' => $affected_rows,
                'message' => 'Sesión cerrada forzadamente'
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();

            return [
                'success' => false,
                'message' => 'Error al forzar logout: ' . $error
            ];
        }
    }

    /**
     * Registrar en security_logs que un admin forzó el cierre de sesión
     *
     * @param int $log_id ID del log de sesión
     * @param int $admin_user_id ID del admin
     */
    private function logForcedLogoutToSecurity($log_id, $admin_user_id)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $desc = 'Admin forzó cierre de sesión';
        $data = json_encode([
            'action' => 'forced_logout',
            'by' => $admin_user_id,
            'session_log_id' => $log_id
        ]);

        $sql = "INSERT INTO security_logs
                (user_id, event_type, event_description, ip_address, user_agent, additional_data)
                VALUES (?, 'forced_logout', ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('issss', $admin_user_id, $desc, $ip, $ua, $data);
            $stmt->execute();
            $stmt->close();
        }
    }
}
