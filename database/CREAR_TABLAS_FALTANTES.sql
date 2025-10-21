-- ============================================================================
-- CREAR TABLAS FALTANTES
-- ============================================================================
-- Este script crea las tablas que faltan para que el sistema funcione
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- TABLA: LOGIN_ATTEMPTS (si no existe)
-- ============================================================================

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL COMMENT 'Email usado en el intento',
    user_id INT NULL COMMENT 'ID del usuario (NULL si no existe)',
    ip_address VARCHAR(45) NOT NULL COMMENT 'IP del intento',
    user_agent TEXT NULL COMMENT 'Navegador/dispositivo',
    attempt_result ENUM('success', 'failed_password', 'failed_user_not_found', 'account_locked', 'account_inactive') NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at),
    INDEX idx_result (attempt_result),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registro de intentos de inicio de sesión';

-- ============================================================================
-- VERIFICAR TABLAS
-- ============================================================================

SELECT 'Verificando tablas creadas...' AS status;

SELECT
    CASE
        WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'hms_v2' AND table_name = 'login_attempts')
        THEN '✓ login_attempts existe'
        ELSE '✗ login_attempts NO existe'
    END AS tabla_login_attempts;

SELECT
    CASE
        WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'hms_v2' AND table_name = 'audit_role_changes')
        THEN '✓ audit_role_changes existe'
        ELSE '✗ audit_role_changes NO existe'
    END AS tabla_audit;

SELECT '✓ Tablas verificadas correctamente' AS status;
