-- ============================================================================
-- MIGRACIÓN 004: TABLA DE LOGS DE SEGURIDAD
-- ============================================================================
-- Descripción: Tabla para registrar eventos de seguridad y accesos no autorizados
--
-- Versión: 2.2.1
-- Fecha: 2025-10-20
-- Proyecto: SIS 321 - Seguridad de Sistemas
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- TABLA: SECURITY_LOGS
-- ============================================================================

CREATE TABLE IF NOT EXISTS security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL COMMENT 'ID del usuario (NULL si no está autenticado)',
    event_type VARCHAR(50) NOT NULL COMMENT 'Tipo de evento (unauthorized_access, permission_denied, etc.)',
    event_description TEXT COMMENT 'Descripción detallada del evento',
    ip_address VARCHAR(45) NOT NULL COMMENT 'IP desde donde se originó el evento',
    user_agent TEXT NULL COMMENT 'User agent del navegador',
    additional_data JSON NULL COMMENT 'Datos adicionales en formato JSON',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_event_type (event_type),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Logs de eventos de seguridad';

-- ============================================================================
-- VISTA: Resumen de intentos de acceso no autorizado por usuario
-- ============================================================================

CREATE OR REPLACE VIEW unauthorized_access_summary AS
SELECT
    u.id AS user_id,
    u.email,
    u.full_name,
    COUNT(*) AS total_attempts,
    MAX(sl.created_at) AS last_attempt,
    GROUP_CONCAT(DISTINCT sl.event_description SEPARATOR '; ') AS attempted_actions
FROM security_logs sl
INNER JOIN users u ON sl.user_id = u.id
WHERE sl.event_type = 'unauthorized_access'
GROUP BY u.id, u.email, u.full_name
ORDER BY total_attempts DESC;

-- ============================================================================
-- VISTA: Intentos de acceso por IP
-- ============================================================================

CREATE OR REPLACE VIEW access_attempts_by_ip AS
SELECT
    sl.ip_address,
    COUNT(*) AS total_attempts,
    COUNT(DISTINCT sl.user_id) AS unique_users,
    MAX(sl.created_at) AS last_attempt,
    SUM(CASE WHEN sl.event_type = 'unauthorized_access' THEN 1 ELSE 0 END) AS unauthorized_attempts
FROM security_logs sl
GROUP BY sl.ip_address
ORDER BY total_attempts DESC;

SELECT '✓ Migración 004_security_logs.sql ejecutada exitosamente' AS status;
