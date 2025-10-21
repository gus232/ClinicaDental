<?php
/**
 * ============================================================================
 * POLÍTICAS DE SEGURIDAD DE CONTRASEÑAS
 * ============================================================================
 *
 * Descripción: Funciones para validar y gestionar políticas de contraseñas
 *
 * Características:
 * - Validación de complejidad (mayúsculas, minúsculas, números, especiales)
 * - Validación de longitud (min/max)
 * - Verificación de historial (últimas 5 contraseñas)
 * - Gestión de bloqueos de cuenta
 * - Expiración de contraseñas
 *
 * Proyecto: SIS 321 - Seguridad de Sistemas
 * Versión: 2.1.0
 * Fecha: 2025-10-20
 * ============================================================================
 */

// Incluir configuración de base de datos si no está incluida
if (!isset($con)) {
    require_once(dirname(__FILE__) . '/config.php');
}

/**
 * ============================================================================
 * CLASE: PasswordPolicy
 * ============================================================================
 */
class PasswordPolicy {

    private $con;
    private $config;

    /**
     * Constructor: Carga la configuración desde la base de datos
     */
    public function __construct($connection) {
        $this->con = $connection;
        $this->loadConfig();
    }

    /**
     * Cargar configuración de políticas desde la base de datos
     */
    private function loadConfig() {
        $this->config = [];

        $query = "SELECT setting_name, setting_value FROM password_policy_config";
        $result = mysqli_query($this->con, $query);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $this->config[$row['setting_name']] = $row['setting_value'];
            }
        }

        // Valores por defecto si la tabla no existe o está vacía
        if (empty($this->config)) {
            $this->config = [
                'min_length' => 8,
                'max_length' => 64,
                'require_uppercase' => 1,
                'require_lowercase' => 1,
                'require_number' => 1,
                'require_special_char' => 1,
                'special_chars_allowed' => '@#$%^&*()_+-=[]{}|;:,.<>?',
                'password_expiry_days' => 90,
                'password_history_count' => 5,
                'max_failed_attempts' => 3,
                'lockout_duration_minutes' => 30,
                'reset_token_expiry_minutes' => 30,
                'min_password_age_hours' => 1
            ];
        }
    }

    /**
     * ========================================================================
     * VALIDACIÓN DE CONTRASEÑA
     * ========================================================================
     * Valida que la contraseña cumpla con todas las políticas
     *
     * @param string $password - Contraseña a validar
     * @return array - ['valid' => bool, 'errors' => array, 'message' => string]
     */
    public function validatePassword($password) {
        $errors = [];

        // 1. Validar longitud mínima
        if (strlen($password) < $this->config['min_length']) {
            $errors[] = "La contraseña debe tener al menos {$this->config['min_length']} caracteres";
        }

        // 2. Validar longitud máxima
        if (strlen($password) > $this->config['max_length']) {
            $errors[] = "La contraseña no debe exceder {$this->config['max_length']} caracteres";
        }

        // 3. Validar mayúscula
        if ($this->config['require_uppercase'] == 1 && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "La contraseña debe contener al menos una letra mayúscula (A-Z)";
        }

        // 4. Validar minúscula
        if ($this->config['require_lowercase'] == 1 && !preg_match('/[a-z]/', $password)) {
            $errors[] = "La contraseña debe contener al menos una letra minúscula (a-z)";
        }

        // 5. Validar número
        if ($this->config['require_number'] == 1 && !preg_match('/[0-9]/', $password)) {
            $errors[] = "La contraseña debe contener al menos un número (0-9)";
        }

        // 6. Validar carácter especial
        if ($this->config['require_special_char'] == 1) {
            $special_chars = preg_quote($this->config['special_chars_allowed'], '/');
            if (!preg_match("/[$special_chars]/", $password)) {
                $errors[] = "La contraseña debe contener al menos un carácter especial ({$this->config['special_chars_allowed']})";
            }
        }

        // 7. Validar que no contenga espacios
        if (preg_match('/\s/', $password)) {
            $errors[] = "La contraseña no debe contener espacios en blanco";
        }

        // Resultado
        $valid = empty($errors);
        $message = $valid ? "Contraseña válida" : "La contraseña no cumple con las políticas de seguridad";

        return [
            'valid' => $valid,
            'errors' => $errors,
            'message' => $message
        ];
    }

    /**
     * ========================================================================
     * VERIFICAR HISTORIAL DE CONTRASEÑAS
     * ========================================================================
     * Verifica que la contraseña no haya sido usada recientemente
     *
     * @param int $user_id - ID del usuario
     * @param string $new_password - Nueva contraseña en texto plano
     * @return array - ['allowed' => bool, 'message' => string]
     */
    public function checkPasswordHistory($user_id, $new_password) {
        $history_count = (int)$this->config['password_history_count'];

        // Obtener últimas N contraseñas del historial
        $query = "SELECT password_hash FROM password_history
                  WHERE user_id = ?
                  ORDER BY changed_at DESC
                  LIMIT ?";

        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $history_count);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Verificar contra cada contraseña del historial
        while ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($new_password, $row['password_hash'])) {
                return [
                    'allowed' => false,
                    'message' => "Esta contraseña ya fue utilizada recientemente. Por favor, elija una contraseña diferente (no puede reutilizar las últimas {$history_count} contraseñas)"
                ];
            }
        }

        // También verificar contra la contraseña actual
        $query_current = "SELECT password FROM users WHERE id = ?";
        $stmt_current = mysqli_prepare($this->con, $query_current);
        mysqli_stmt_bind_param($stmt_current, "i", $user_id);
        mysqli_stmt_execute($stmt_current);
        $result_current = mysqli_stmt_get_result($stmt_current);

        if ($row_current = mysqli_fetch_assoc($result_current)) {
            if (password_verify($new_password, $row_current['password'])) {
                return [
                    'allowed' => false,
                    'message' => "La nueva contraseña no puede ser igual a la contraseña actual"
                ];
            }
        }

        return [
            'allowed' => true,
            'message' => "Contraseña no encontrada en historial"
        ];
    }

    /**
     * ========================================================================
     * GUARDAR EN HISTORIAL
     * ========================================================================
     * Guarda la contraseña anterior en el historial
     *
     * @param int $user_id - ID del usuario
     * @param string $old_password_hash - Hash de la contraseña anterior
     * @param int $changed_by - ID del usuario que hizo el cambio (opcional)
     */
    public function saveToHistory($user_id, $old_password_hash, $changed_by = null) {
        $ip_address = $this->getClientIP();

        if ($changed_by === null) {
            $changed_by = $user_id; // Cambio por el mismo usuario
        }

        $query = "INSERT INTO password_history (user_id, password_hash, changed_by, ip_address)
                  VALUES (?, ?, ?, ?)";

        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "isis", $user_id, $old_password_hash, $changed_by, $ip_address);
        mysqli_stmt_execute($stmt);

        // Limpiar historial antiguo (mantener solo últimas N)
        $this->cleanupOldHistory($user_id);
    }

    /**
     * Limpiar historial antiguo (mantener solo últimas N contraseñas)
     */
    private function cleanupOldHistory($user_id) {
        $history_count = (int)$this->config['password_history_count'];

        $query = "DELETE FROM password_history
                  WHERE user_id = ?
                  AND id NOT IN (
                      SELECT id FROM (
                          SELECT id FROM password_history
                          WHERE user_id = ?
                          ORDER BY changed_at DESC
                          LIMIT ?
                      ) AS keep_records
                  )";

        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "iii", $user_id, $user_id, $history_count);
        mysqli_stmt_execute($stmt);
    }

    /**
     * ========================================================================
     * CAMBIAR CONTRASEÑA
     * ========================================================================
     * Cambia la contraseña del usuario con todas las validaciones
     *
     * @param int $user_id - ID del usuario
     * @param string $new_password - Nueva contraseña en texto plano
     * @param int $changed_by - ID del usuario que hace el cambio
     * @return array - ['success' => bool, 'message' => string]
     */
    public function changePassword($user_id, $new_password, $changed_by = null) {
        // 1. Validar complejidad
        $validation = $this->validatePassword($new_password);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => implode('. ', $validation['errors'])
            ];
        }

        // 2. Verificar historial
        $history_check = $this->checkPasswordHistory($user_id, $new_password);
        if (!$history_check['allowed']) {
            return [
                'success' => false,
                'message' => $history_check['message']
            ];
        }

        // 3. Verificar edad mínima de contraseña (prevenir cambios frecuentes)
        $min_age_hours = (int)$this->config['min_password_age_hours'];
        $query_age = "SELECT password_changed_at FROM users WHERE id = ?";
        $stmt_age = mysqli_prepare($this->con, $query_age);
        mysqli_stmt_bind_param($stmt_age, "i", $user_id);
        mysqli_stmt_execute($stmt_age);
        $result_age = mysqli_stmt_get_result($stmt_age);

        if ($row_age = mysqli_fetch_assoc($result_age)) {
            if ($row_age['password_changed_at']) {
                $last_change = strtotime($row_age['password_changed_at']);
                $hours_since_change = (time() - $last_change) / 3600;

                if ($hours_since_change < $min_age_hours) {
                    $hours_remaining = ceil($min_age_hours - $hours_since_change);
                    return [
                        'success' => false,
                        'message' => "Debe esperar al menos {$min_age_hours} hora(s) entre cambios de contraseña. Tiempo restante: {$hours_remaining} hora(s)"
                    ];
                }
            }
        }

        // 4. Obtener contraseña actual para guardar en historial
        $query_current = "SELECT password FROM users WHERE id = ?";
        $stmt_current = mysqli_prepare($this->con, $query_current);
        mysqli_stmt_bind_param($stmt_current, "i", $user_id);
        mysqli_stmt_execute($stmt_current);
        $result_current = mysqli_stmt_get_result($stmt_current);
        $current_password_hash = null;

        if ($row_current = mysqli_fetch_assoc($result_current)) {
            $current_password_hash = $row_current['password'];
        }

        // 5. Generar nuevo hash
        $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);

        // 6. Calcular fecha de expiración
        $expiry_days = (int)$this->config['password_expiry_days'];

        // 7. Actualizar contraseña en tabla users
        $query_update = "UPDATE users SET
                         password = ?,
                         password_changed_at = NOW(),
                         password_expires_at = DATE_ADD(NOW(), INTERVAL ? DAY),
                         failed_login_attempts = 0,
                         account_locked_until = NULL,
                         force_password_change = 0
                         WHERE id = ?";

        $stmt_update = mysqli_prepare($this->con, $query_update);
        mysqli_stmt_bind_param($stmt_update, "sii", $new_password_hash, $expiry_days, $user_id);

        if (mysqli_stmt_execute($stmt_update)) {
            // 8. Guardar contraseña anterior en historial
            if ($current_password_hash) {
                $this->saveToHistory($user_id, $current_password_hash, $changed_by);
            }

            return [
                'success' => true,
                'message' => "Contraseña cambiada exitosamente. La contraseña expirará en {$expiry_days} días"
            ];
        } else {
            return [
                'success' => false,
                'message' => "Error al cambiar la contraseña. Inténtelo nuevamente"
            ];
        }
    }

    /**
     * ========================================================================
     * VERIFICAR SI CONTRASEÑA ESTÁ EXPIRADA
     * ========================================================================
     */
    public function isPasswordExpired($user_id) {
        $query = "SELECT password_expires_at FROM users WHERE id = ?";
        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if ($row['password_expires_at']) {
                return strtotime($row['password_expires_at']) < time();
            }
        }

        return false;
    }

    /**
     * ========================================================================
     * DÍAS HASTA EXPIRACIÓN
     * ========================================================================
     */
    public function daysUntilExpiry($user_id) {
        $query = "SELECT DATEDIFF(password_expires_at, NOW()) as days_left FROM users WHERE id = ?";
        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            return (int)$row['days_left'];
        }

        return null;
    }

    /**
     * ========================================================================
     * OBTENER IP DEL CLIENTE
     * ========================================================================
     */
    private function getClientIP() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Validar IP
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return '0.0.0.0';
    }

    /**
     * ========================================================================
     * OBTENER REQUISITOS DE CONTRASEÑA (Para mostrar en UI)
     * ========================================================================
     */
    public function getPasswordRequirements() {
        $requirements = [];

        $requirements[] = "Longitud entre {$this->config['min_length']} y {$this->config['max_length']} caracteres";

        if ($this->config['require_uppercase'] == 1) {
            $requirements[] = "Al menos una letra mayúscula (A-Z)";
        }

        if ($this->config['require_lowercase'] == 1) {
            $requirements[] = "Al menos una letra minúscula (a-z)";
        }

        if ($this->config['require_number'] == 1) {
            $requirements[] = "Al menos un número (0-9)";
        }

        if ($this->config['require_special_char'] == 1) {
            $requirements[] = "Al menos un carácter especial ({$this->config['special_chars_allowed']})";
        }

        $requirements[] = "No puede contener espacios en blanco";
        $requirements[] = "No puede reutilizar las últimas {$this->config['password_history_count']} contraseñas";

        return $requirements;
    }

    /**
     * ========================================================================
     * OBTENER CONFIGURACIÓN
     * ========================================================================
     */
    public function getConfig($setting_name = null) {
        if ($setting_name) {
            return isset($this->config[$setting_name]) ? $this->config[$setting_name] : null;
        }
        return $this->config;
    }
}

/**
 * ============================================================================
 * FUNCIONES HELPER (Para usar sin instanciar la clase)
 * ============================================================================
 */

/**
 * Validar contraseña simple (sin historial)
 */
function validate_password_simple($password, $connection = null) {
    global $con;
    $db = $connection ?? $con;

    $policy = new PasswordPolicy($db);
    return $policy->validatePassword($password);
}

/**
 * Obtener requisitos para mostrar en formularios
 */
function get_password_requirements($connection = null) {
    global $con;
    $db = $connection ?? $con;

    $policy = new PasswordPolicy($db);
    return $policy->getPasswordRequirements();
}

/**
 * Generar contraseña segura aleatoria
 */
function generate_secure_password($length = 12) {
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special = '@#$%^&*()_+-=';

    $password = '';

    // Asegurar al menos uno de cada tipo
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];

    // Completar el resto
    $all_chars = $uppercase . $lowercase . $numbers . $special;
    for ($i = 4; $i < $length; $i++) {
        $password .= $all_chars[random_int(0, strlen($all_chars) - 1)];
    }

    // Mezclar caracteres
    return str_shuffle($password);
}

?>
