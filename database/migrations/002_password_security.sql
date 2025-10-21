-- ============================================================================
-- MIGRACIÓN 002: SEGURIDAD DE CONTRASEÑAS
-- ============================================================================
-- Descripción: Agrega campos y tablas necesarias para implementar:
--   - Bloqueo de cuenta al 3er intento fallido
--   - Expiración de contraseñas (90 días)
--   - Historial de contraseñas (últimas 5)
--   - Tokens de recuperación de contraseña
--
-- Versión: 2.1.0
-- Fecha: 2025-10-20
-- Proyecto: SIS 321 - Seguridad de Sistemas
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- 1. AGREGAR CAMPOS DE SEGURIDAD A TABLA USERS
-- ============================================================================

-- Campo: Contador de intentos fallidos de login
ALTER TABLE users
ADD COLUMN failed_login_attempts INT DEFAULT 0
COMMENT 'Contador de intentos fallidos consecutivos';

-- Campo: Fecha hasta cuando la cuenta está bloqueada
ALTER TABLE users
ADD COLUMN account_locked_until DATETIME NULL
COMMENT 'Fecha/hora hasta cuando la cuenta permanece bloqueada';

-- Campo: Fecha de expiración de contraseña (90 días desde creación/cambio)
ALTER TABLE users
ADD COLUMN password_expires_at DATETIME NULL
COMMENT 'Fecha de expiración de la contraseña (90 días)';

-- Campo: Fecha del último cambio de contraseña
ALTER TABLE users
ADD COLUMN password_changed_at DATETIME NULL
COMMENT 'Fecha del último cambio de contraseña';

-- Campo: IP del último login
ALTER TABLE users
ADD COLUMN last_login_ip VARCHAR(45) NULL
COMMENT 'Dirección IP del último inicio de sesión (IPv4 o IPv6)';

-- Campo: Indica si debe cambiar contraseña en próximo login
ALTER TABLE users
ADD COLUMN force_password_change TINYINT(1) DEFAULT 0
COMMENT '1 = Debe cambiar contraseña en próximo login, 0 = No';

-- ============================================================================
-- 2. ACTUALIZAR DATOS EXISTENTES
-- ============================================================================

-- Establecer password_changed_at como la fecha de creación para usuarios existentes
UPDATE users
SET password_changed_at = created_at
WHERE password_changed_at IS NULL;

-- Calcular password_expires_at (90 días desde password_changed_at)
UPDATE users
SET password_expires_at = DATE_ADD(password_changed_at, INTERVAL 90 DAY)
WHERE password_expires_at IS NULL;

-- ============================================================================
-- 3. TABLA: PASSWORD_HISTORY
-- ============================================================================
-- Propósito: Almacenar historial de contraseñas para evitar reutilización
-- Retiene: Últimas 5 contraseñas por usuario

CREATE TABLE IF NOT EXISTS password_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt de la contraseña anterior',
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha del cambio',
    changed_by INT NULL COMMENT 'ID del usuario que realizó el cambio (admin/self)',
    ip_address VARCHAR(45) NULL COMMENT 'IP desde donde se cambió',

    -- Índices para búsqueda rápida
    INDEX idx_user_id (user_id),
    INDEX idx_changed_at (changed_at),

    -- Relación con tabla users
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Historial de contraseñas para prevenir reutilización';

-- ============================================================================
-- 4. TABLA: PASSWORD_RESET_TOKENS
-- ============================================================================
-- Propósito: Tokens de un solo uso para recuperación de contraseña
-- Expiración: 30 minutos desde creación

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL COMMENT 'Token único generado (SHA256)',
    expires_at DATETIME NOT NULL COMMENT 'Fecha de expiración (30 minutos)',
    used TINYINT(1) DEFAULT 0 COMMENT '1 = Token ya usado, 0 = No usado',
    used_at DATETIME NULL COMMENT 'Fecha/hora en que se usó el token',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL COMMENT 'IP desde donde se solicitó el token',
    user_agent TEXT NULL COMMENT 'Navegador/dispositivo que solicitó el token',

    -- Índices
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_used (used),

    -- Relación con tabla users
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tokens de recuperación de contraseña';

-- ============================================================================
-- 5. TABLA: LOGIN_ATTEMPTS
-- ============================================================================
-- Propósito: Registrar todos los intentos de login (exitosos y fallidos)
-- Seguridad: Detectar ataques de fuerza bruta

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL COMMENT 'Email usado en el intento',
    user_id INT NULL COMMENT 'ID del usuario (NULL si no existe)',
    ip_address VARCHAR(45) NOT NULL COMMENT 'IP del intento',
    user_agent TEXT NULL COMMENT 'Navegador/dispositivo',
    attempt_result ENUM('success', 'failed_password', 'failed_user_not_found', 'account_locked', 'account_inactive') NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Índices para análisis de seguridad
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at),
    INDEX idx_result (attempt_result),

    -- Relación con tabla users (puede ser NULL si usuario no existe)
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registro de intentos de inicio de sesión';

-- ============================================================================
-- 6. TABLA: PASSWORD_POLICY_CONFIG
-- ============================================================================
-- Propósito: Configuración dinámica de políticas de contraseña
-- Permite al admin cambiar reglas sin modificar código

CREATE TABLE IF NOT EXISTS password_policy_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(50) UNIQUE NOT NULL COMMENT 'Nombre de la configuración',
    setting_value VARCHAR(255) NOT NULL COMMENT 'Valor de la configuración',
    description TEXT COMMENT 'Descripción de qué hace este setting',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL COMMENT 'Admin que modificó el setting',

    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Configuración de políticas de contraseña';

-- ============================================================================
-- 7. INSERTAR CONFIGURACIÓN POR DEFECTO
-- ============================================================================

INSERT INTO password_policy_config (setting_name, setting_value, description) VALUES
('min_length', '8', 'Longitud mínima de contraseña'),
('max_length', '64', 'Longitud máxima de contraseña'),
('require_uppercase', '1', 'Requiere al menos 1 mayúscula (1=sí, 0=no)'),
('require_lowercase', '1', 'Requiere al menos 1 minúscula (1=sí, 0=no)'),
('require_number', '1', 'Requiere al menos 1 número (1=sí, 0=no)'),
('require_special_char', '1', 'Requiere al menos 1 carácter especial (1=sí, 0=no)'),
('special_chars_allowed', '@#$%^&*()_+-=[]{}|;:,.<>?', 'Caracteres especiales permitidos'),
('password_expiry_days', '90', 'Días hasta que expire la contraseña'),
('password_history_count', '5', 'Número de contraseñas anteriores que no se pueden reutilizar'),
('max_failed_attempts', '3', 'Intentos fallidos antes de bloqueo'),
('lockout_duration_minutes', '30', 'Minutos que dura el bloqueo de cuenta'),
('reset_token_expiry_minutes', '30', 'Minutos de validez del token de recuperación'),
('min_password_age_hours', '1', 'Horas mínimas entre cambios de contraseña (prevenir spam)');

-- ============================================================================
-- 8. TRIGGER: LIMPIAR TOKENS EXPIRADOS AUTOMÁTICAMENTE
-- ============================================================================

DELIMITER $$

CREATE TRIGGER cleanup_expired_tokens_before_insert
BEFORE INSERT ON password_reset_tokens
FOR EACH ROW
BEGIN
    -- Marcar como usados los tokens expirados del mismo usuario
    UPDATE password_reset_tokens
    SET used = 1
    WHERE user_id = NEW.user_id
      AND expires_at < NOW()
      AND used = 0;
END$$

DELIMITER ;

-- ============================================================================
-- 9. STORED PROCEDURE: LIMPIEZA DE DATOS ANTIGUOS
-- ============================================================================
-- Ejecutar periódicamente para mantener BD limpia

DELIMITER $$

CREATE PROCEDURE cleanup_old_security_data()
BEGIN
    -- Eliminar intentos de login mayores a 90 días
    DELETE FROM login_attempts
    WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

    -- Eliminar tokens de más de 7 días
    DELETE FROM password_reset_tokens
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);

    -- Eliminar historial de contraseñas excedente (mantener solo últimas 5 por usuario)
    DELETE ph1 FROM password_history ph1
    WHERE ph1.id NOT IN (
        SELECT id FROM (
            SELECT id FROM password_history ph2
            WHERE ph2.user_id = ph1.user_id
            ORDER BY changed_at DESC
            LIMIT 5
        ) AS keep_records
    );

    SELECT 'Limpieza completada exitosamente' AS message;
END$$

DELIMITER ;

-- ============================================================================
-- 10. VISTA: USUARIOS CON CONTRASEÑAS PRÓXIMAS A EXPIRAR
-- ============================================================================

CREATE OR REPLACE VIEW users_password_expiring_soon AS
SELECT
    u.id,
    u.email,
    u.full_name,
    u.user_type,
    u.password_expires_at,
    DATEDIFF(u.password_expires_at, NOW()) AS days_until_expiry,
    u.password_changed_at AS last_password_change
FROM users u
WHERE u.status = 'active'
  AND u.password_expires_at IS NOT NULL
  AND u.password_expires_at > NOW()
  AND DATEDIFF(u.password_expires_at, NOW()) <= 7
ORDER BY days_until_expiry ASC;

-- ============================================================================
-- 11. VISTA: CUENTAS BLOQUEADAS
-- ============================================================================

CREATE OR REPLACE VIEW locked_accounts AS
SELECT
    u.id,
    u.email,
    u.full_name,
    u.user_type,
    u.failed_login_attempts,
    u.account_locked_until,
    u.last_login,
    u.last_login_ip,
    CASE
        WHEN u.account_locked_until > NOW() THEN 'LOCKED'
        ELSE 'UNLOCKED'
    END AS lock_status,
    TIMESTAMPDIFF(MINUTE, NOW(), u.account_locked_until) AS minutes_remaining
FROM users u
WHERE u.account_locked_until IS NOT NULL
  AND u.account_locked_until > NOW()
ORDER BY u.account_locked_until DESC;

-- ============================================================================
-- 12. ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================================================

ALTER TABLE users ADD INDEX idx_account_locked (account_locked_until);
ALTER TABLE users ADD INDEX idx_password_expires (password_expires_at);
ALTER TABLE users ADD INDEX idx_status_type (status, user_type);

-- ============================================================================
-- MIGRACIÓN COMPLETADA EXITOSAMENTE
-- ============================================================================

SELECT '✓ Migración 002_password_security.sql ejecutada exitosamente' AS status;
SELECT CONCAT('✓ Campos agregados a tabla users: 6 columnas nuevas') AS status;
SELECT CONCAT('✓ Tablas creadas: password_history, password_reset_tokens, login_attempts, password_policy_config') AS status;
SELECT CONCAT('✓ Políticas configuradas: 13 settings por defecto') AS status;
SELECT CONCAT('✓ Vistas creadas: users_password_expiring_soon, locked_accounts') AS status;
SELECT CONCAT('✓ Procedures creados: cleanup_old_security_data') AS status;
SELECT '✓ Sistema listo para implementar políticas de contraseñas' AS status;
