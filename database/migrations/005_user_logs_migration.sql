-- ============================================================================
-- MIGRACIÓN 005: TABLA USER_LOGS (MODERNA)
-- ============================================================================
-- Descripción: Migrar de userlog antigua a user_logs moderna
--              Mejora tracking de sesiones con información de dispositivos
--
-- Versión: 2.3.0
-- Fecha: 2025-10-30
-- Proyecto: Hospital Management System - Security Enhancement
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- PASO 1: CREAR NUEVA TABLA user_logs
-- ============================================================================

CREATE TABLE IF NOT EXISTS user_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'ID del usuario (users.id)',
    login_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha/hora de inicio de sesión',
    logout_time TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha/hora de cierre de sesión',
    session_duration INT NULL COMMENT 'Duración de la sesión en segundos',
    ip_address VARCHAR(45) NOT NULL COMMENT 'Dirección IP (soporta IPv4 e IPv6)',
    user_agent TEXT NULL COMMENT 'User agent completo del navegador',
    device_type ENUM('desktop','mobile','tablet','other') DEFAULT 'other' COMMENT 'Tipo de dispositivo detectado',
    browser VARCHAR(50) NULL COMMENT 'Navegador detectado (Chrome, Firefox, etc)',
    os VARCHAR(50) NULL COMMENT 'Sistema operativo detectado',
    session_id VARCHAR(128) NULL COMMENT 'ID de sesión PHP',
    logout_reason ENUM('manual','timeout','forced','error') NULL COMMENT 'Razón del cierre de sesión',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1 = sesión activa, 0 = sesión cerrada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Índices para optimizar consultas
    INDEX idx_user_id (user_id),
    INDEX idx_login_time (login_time),
    INDEX idx_logout_time (logout_time),
    INDEX idx_is_active (is_active),
    INDEX idx_ip_address (ip_address),
    INDEX idx_session_id (session_id),
    INDEX idx_device_type (device_type),
    INDEX idx_created_at (created_at),

    -- Índice compuesto para queries comunes
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_user_login (user_id, login_time),

    -- Foreign Key
    CONSTRAINT fk_user_logs_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de sesiones de usuarios con información detallada de dispositivos';

-- ============================================================================
-- PASO 2: MIGRAR DATOS DE userlog A user_logs
-- ============================================================================

-- Insertar datos convertidos de la tabla antigua
INSERT INTO user_logs (
    id,
    user_id,
    login_time,
    logout_time,
    session_duration,
    ip_address,
    is_active
)
SELECT
    ul.id,
    ul.uid as user_id,
    ul.loginTime as login_time,
    -- Convertir logout VARCHAR a TIMESTAMP (puede ser NULL o formato fecha)
    CASE
        WHEN ul.logout IS NULL OR ul.logout = '' THEN NULL
        WHEN ul.logout REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}' THEN
            STR_TO_DATE(ul.logout, '%Y-%m-%d %H:%i:%s')
        ELSE NULL
    END as logout_time,
    -- Calcular duración si hay logout
    CASE
        WHEN ul.logout IS NOT NULL AND ul.logout != '' AND ul.logout REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}' THEN
            TIMESTAMPDIFF(SECOND,
                ul.loginTime,
                STR_TO_DATE(ul.logout, '%Y-%m-%d %H:%i:%s')
            )
        ELSE NULL
    END as session_duration,
    -- Convertir IP binaria a formato legible
    CASE
        WHEN ul.userip IS NOT NULL THEN INET6_NTOA(ul.userip)
        ELSE '0.0.0.0'
    END as ip_address,
    -- Determinar si está activa
    CASE
        WHEN ul.logout IS NULL OR ul.logout = '' THEN 1
        ELSE 0
    END as is_active
FROM userlog ul
WHERE ul.uid IS NOT NULL  -- Solo migrar registros con usuario válido
ORDER BY ul.id;

-- ============================================================================
-- PASO 3: VERIFICAR MIGRACIÓN
-- ============================================================================

-- Contar registros migrados
SELECT
    'Migración completada' as status,
    (SELECT COUNT(*) FROM userlog WHERE uid IS NOT NULL) as registros_origen,
    (SELECT COUNT(*) FROM user_logs) as registros_migrados,
    CASE
        WHEN (SELECT COUNT(*) FROM userlog WHERE uid IS NOT NULL) = (SELECT COUNT(*) FROM user_logs)
        THEN 'EXITOSO ✓'
        ELSE 'ADVERTENCIA: Revisar diferencias'
    END as resultado;

-- ============================================================================
-- PASO 4: RENOMBRAR TABLA ANTIGUA (BACKUP)
-- ============================================================================

-- Renombrar tabla antigua para mantenerla como backup
-- NOTA: Descomentar después de verificar que todo funciona correctamente
-- ALTER TABLE userlog RENAME TO userlog_deprecated;

-- ============================================================================
-- PASO 5: CREAR VISTAS ÚTILES
-- ============================================================================

-- Vista: Sesiones activas
CREATE OR REPLACE VIEW active_sessions AS
SELECT
    ul.id,
    ul.user_id,
    u.full_name,
    u.email,
    u.user_type,
    ul.login_time,
    ul.ip_address,
    ul.device_type,
    ul.browser,
    ul.os,
    TIMESTAMPDIFF(MINUTE, ul.login_time, NOW()) as minutes_active
FROM user_logs ul
INNER JOIN users u ON ul.user_id = u.id
WHERE ul.is_active = 1
ORDER BY ul.login_time DESC;

-- Vista: Resumen de sesiones por usuario
CREATE OR REPLACE VIEW user_session_summary AS
SELECT
    u.id as user_id,
    u.full_name,
    u.email,
    u.user_type,
    COUNT(*) as total_sessions,
    SUM(CASE WHEN ul.is_active = 1 THEN 1 ELSE 0 END) as active_sessions,
    MAX(ul.login_time) as last_login,
    AVG(ul.session_duration) as avg_session_duration_seconds,
    SUM(ul.session_duration) as total_time_logged_seconds
FROM users u
LEFT JOIN user_logs ul ON u.id = ul.user_id
GROUP BY u.id, u.full_name, u.email, u.user_type
ORDER BY total_sessions DESC;

-- Vista: Sesiones por dispositivo
CREATE OR REPLACE VIEW sessions_by_device AS
SELECT
    device_type,
    COUNT(*) as total_sessions,
    COUNT(DISTINCT user_id) as unique_users,
    AVG(session_duration) as avg_duration_seconds
FROM user_logs
WHERE device_type IS NOT NULL
GROUP BY device_type
ORDER BY total_sessions DESC;

-- Vista: Sesiones por navegador
CREATE OR REPLACE VIEW sessions_by_browser AS
SELECT
    browser,
    COUNT(*) as total_sessions,
    COUNT(DISTINCT user_id) as unique_users,
    AVG(session_duration) as avg_duration_seconds
FROM user_logs
WHERE browser IS NOT NULL
GROUP BY browser
ORDER BY total_sessions DESC;

-- ============================================================================
-- PASO 6: PROCEDIMIENTO PARA LIMPIAR SESIONES INACTIVAS
-- ============================================================================

DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS cleanup_inactive_sessions(
    IN timeout_minutes INT
)
BEGIN
    -- Marcar sesiones inactivas como cerradas por timeout
    UPDATE user_logs
    SET
        is_active = 0,
        logout_time = NOW(),
        logout_reason = 'timeout',
        session_duration = TIMESTAMPDIFF(SECOND, login_time, NOW())
    WHERE
        is_active = 1
        AND TIMESTAMPDIFF(MINUTE, login_time, NOW()) > timeout_minutes
        AND logout_time IS NULL;

    -- Retornar cantidad de sesiones cerradas
    SELECT ROW_COUNT() as sessions_closed;
END$$

DELIMITER ;

-- ============================================================================
-- PASO 7: EVENTO AUTOMÁTICO PARA LIMPIAR SESIONES (OPCIONAL)
-- ============================================================================

-- Habilitar event scheduler si no está habilitado
SET GLOBAL event_scheduler = ON;

-- Crear evento que limpia sesiones cada hora
-- NOTA: Descomentar si deseas limpieza automática
/*
CREATE EVENT IF NOT EXISTS cleanup_inactive_sessions_event
ON SCHEDULE EVERY 1 HOUR
DO
    CALL cleanup_inactive_sessions(30); -- Cerrar sesiones inactivas > 30 minutos
*/

-- ============================================================================
-- RESUMEN DE MIGRACIÓN
-- ============================================================================

SELECT '✓ Migración 005_user_logs_migration.sql ejecutada exitosamente' AS status;
SELECT '✓ Tabla user_logs creada con estructura moderna' AS info;
SELECT '✓ Datos migrados desde userlog' AS info;
SELECT '✓ Vistas de análisis creadas' AS info;
SELECT '✓ Procedimiento de limpieza creado' AS info;
SELECT '⚠ IMPORTANTE: Actualizar código PHP para usar user_logs' AS warning;
SELECT '⚠ RECORDATORIO: Renombrar userlog → userlog_deprecated después de verificar' AS reminder;

-- ============================================================================
-- NOTAS IMPORTANTES
-- ============================================================================
/*
1. La tabla userlog se mantiene intacta hasta verificar que todo funciona
2. Después de verificar, ejecutar: ALTER TABLE userlog RENAME TO userlog_deprecated;
3. Los campos nuevos (user_agent, device_type, browser, os) estarán NULL en registros migrados
4. Futuras sesiones tendrán toda la información completa
5. Las vistas creadas facilitan análisis y reportes
6. El procedimiento cleanup_inactive_sessions debe ejecutarse periódicamente
*/
