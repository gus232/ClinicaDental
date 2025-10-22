-- ============================================================================
-- MIGRATION 005: USER MANAGEMENT ENHANCEMENTS (VERSIÓN CORREGIDA)
-- ============================================================================
-- Descripción: Sistema completo de gestión de usuarios con auditoría
-- Versión: 2.3.0 FIXED
-- Fecha: 2025-10-21
-- Proyecto: Hospital Management System - FASE 3
-- ============================================================================

-- Verificar que estamos en la base de datos correcta
USE hospital;

-- ============================================================================
-- 1. TABLA: user_change_history
-- ============================================================================
-- Almacena historial completo de cambios en usuarios
-- CORRECCIÓN: changed_by ahora es NULL (compatible con ON DELETE SET NULL)
-- ============================================================================

CREATE TABLE IF NOT EXISTS user_change_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'Usuario que fue modificado',
    changed_by INT NULL COMMENT 'Usuario que realizó el cambio (NULL si se elimina)',
    change_type ENUM('create', 'update', 'delete', 'status_change', 'role_change', 'password_change') NOT NULL,
    field_changed VARCHAR(50) NULL COMMENT 'Campo específico que cambió',
    old_value TEXT NULL COMMENT 'Valor anterior',
    new_value TEXT NULL COMMENT 'Valor nuevo',
    change_reason VARCHAR(255) NULL COMMENT 'Razón del cambio',
    ip_address VARCHAR(45) NULL COMMENT 'IP desde donde se hizo el cambio',
    user_agent TEXT NULL COMMENT 'Navegador/dispositivo usado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_changed_by (changed_by),
    INDEX idx_change_type (change_type),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial completo de cambios en usuarios';

-- ============================================================================
-- 2. TABLA: user_sessions
-- ============================================================================
-- Rastrea sesiones activas de usuarios
-- Permite ver usuarios conectados y gestionar sesiones
-- ============================================================================

CREATE TABLE IF NOT EXISTS user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(128) UNIQUE NOT NULL COMMENT 'ID de sesión de PHP',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL COMMENT 'Navegador/dispositivo',
    login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Momento del login',
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL COMMENT 'Cuándo expira la sesión',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=activa, 0=cerrada',
    logout_at TIMESTAMP NULL COMMENT 'Momento del logout',

    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at),
    INDEX idx_last_activity (last_activity),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Sesiones activas de usuarios';

-- ============================================================================
-- 3. TABLA: user_profile_photos
-- ============================================================================
-- Almacena fotos de perfil de usuarios
-- Permite múltiples versiones (thumbnail, medium, full)
-- ============================================================================

CREATE TABLE IF NOT EXISTS user_profile_photos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    photo_path VARCHAR(255) NOT NULL COMMENT 'Ruta al archivo original',
    thumbnail_path VARCHAR(255) NULL COMMENT 'Ruta al thumbnail (150x150)',
    file_size INT NULL COMMENT 'Tamaño en bytes',
    mime_type VARCHAR(50) NULL COMMENT 'image/jpeg, image/png, etc.',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Fotos de perfil de usuarios';

-- ============================================================================
-- 4. TABLA: user_notes
-- ============================================================================
-- Notas administrativas sobre usuarios
-- Solo visible para admins (no para el usuario mismo)
-- CORRECCIÓN: created_by ahora es NULL (compatible con ON DELETE SET NULL)
-- ============================================================================

CREATE TABLE IF NOT EXISTS user_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'Usuario sobre el que se escribe la nota',
    created_by INT NULL COMMENT 'Admin que creó la nota (NULL si se elimina)',
    note_text TEXT NOT NULL,
    note_type ENUM('general', 'warning', 'restriction', 'important') DEFAULT 'general',
    is_pinned TINYINT(1) DEFAULT 0 COMMENT 'Mostrar siempre arriba',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_created_by (created_by),
    INDEX idx_note_type (note_type),
    INDEX idx_is_pinned (is_pinned),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Notas administrativas sobre usuarios';

-- ============================================================================
-- 5. VISTAS: Para consultas optimizadas
-- ============================================================================

-- Vista: Resumen de usuarios activos
-- CORRECCIÓN: Eliminada referencia a u.contactno que no existe
DROP VIEW IF EXISTS active_users_summary;
CREATE VIEW active_users_summary AS
SELECT
    u.id,
    u.full_name,
    u.email,
    u.status,
    GROUP_CONCAT(DISTINCT r.display_name ORDER BY r.priority SEPARATOR ', ') as roles,
    COUNT(DISTINCT s.id) as active_sessions,
    MAX(s.last_activity) as last_seen,
    (SELECT COUNT(*) FROM user_change_history WHERE user_id = u.id) as total_changes
FROM users u
LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_active = 1
LEFT JOIN roles r ON ur.role_id = r.id AND r.status = 'active'
LEFT JOIN user_sessions s ON u.id = s.user_id AND s.is_active = 1 AND s.expires_at > NOW()
WHERE u.status = 1
GROUP BY u.id, u.full_name, u.email, u.status;

-- Vista: Historial de cambios con información detallada
DROP VIEW IF EXISTS user_changes_detailed;
CREATE VIEW user_changes_detailed AS
SELECT
    uch.id,
    uch.user_id,
    u1.full_name as user_name,
    u1.email as user_email,
    uch.changed_by,
    u2.full_name as changed_by_name,
    u2.email as changed_by_email,
    uch.change_type,
    uch.field_changed,
    uch.old_value,
    uch.new_value,
    uch.change_reason,
    uch.ip_address,
    uch.created_at
FROM user_change_history uch
INNER JOIN users u1 ON uch.user_id = u1.id
LEFT JOIN users u2 ON uch.changed_by = u2.id
ORDER BY uch.created_at DESC;

-- Vista: Sesiones activas con información de usuario
DROP VIEW IF EXISTS active_sessions_view;
CREATE VIEW active_sessions_view AS
SELECT
    s.id as session_id,
    s.user_id,
    u.full_name,
    u.email,
    GROUP_CONCAT(DISTINCT r.display_name ORDER BY r.priority SEPARATOR ', ') as roles,
    s.ip_address,
    s.login_at,
    s.last_activity,
    s.expires_at,
    TIMESTAMPDIFF(MINUTE, s.last_activity, NOW()) as minutes_idle
FROM user_sessions s
INNER JOIN users u ON s.user_id = u.id
LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_active = 1
LEFT JOIN roles r ON ur.role_id = r.id AND r.status = 'active'
WHERE s.is_active = 1 AND s.expires_at > NOW()
GROUP BY s.id, s.user_id, u.full_name, u.email, s.ip_address, s.login_at, s.last_activity, s.expires_at
ORDER BY s.last_activity DESC;

-- Vista: Estadísticas de usuarios por rol
DROP VIEW IF EXISTS user_statistics_by_role;
CREATE VIEW user_statistics_by_role AS
SELECT
    r.id as role_id,
    r.role_name,
    r.display_name,
    COUNT(DISTINCT ur.user_id) as total_users,
    COUNT(DISTINCT CASE WHEN u.status = 1 THEN ur.user_id END) as active_users,
    COUNT(DISTINCT CASE WHEN u.status = 0 THEN ur.user_id END) as inactive_users,
    COUNT(DISTINCT CASE WHEN ur.expires_at IS NOT NULL THEN ur.user_id END) as temporary_assignments
FROM roles r
LEFT JOIN user_roles ur ON r.id = ur.role_id AND ur.is_active = 1
LEFT JOIN users u ON ur.user_id = u.id
GROUP BY r.id, r.role_name, r.display_name
ORDER BY r.priority;

-- Vista: Timeline de cambios recientes (últimos 30 días)
DROP VIEW IF EXISTS recent_changes_timeline;
CREATE VIEW recent_changes_timeline AS
SELECT
    uch.id,
    uch.user_id,
    u1.full_name as user_name,
    uch.changed_by,
    u2.full_name as changed_by_name,
    uch.change_type,
    uch.field_changed,
    uch.change_reason,
    uch.created_at,
    DATE(uch.created_at) as change_date
FROM user_change_history uch
INNER JOIN users u1 ON uch.user_id = u1.id
LEFT JOIN users u2 ON uch.changed_by = u2.id
WHERE uch.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY uch.created_at DESC;

-- Vista: Usuarios con roles expirados o próximos a expirar
DROP VIEW IF EXISTS expiring_user_roles;
CREATE VIEW expiring_user_roles AS
SELECT
    ur.id,
    ur.user_id,
    u.full_name,
    u.email,
    ur.role_id,
    r.display_name as role_name,
    ur.assigned_at,
    ur.expires_at,
    DATEDIFF(ur.expires_at, NOW()) as days_until_expiration
FROM user_roles ur
INNER JOIN users u ON ur.user_id = u.id
INNER JOIN roles r ON ur.role_id = r.id
WHERE ur.is_active = 1
  AND ur.expires_at IS NOT NULL
  AND ur.expires_at <= DATE_ADD(NOW(), INTERVAL 30 DAY)
ORDER BY ur.expires_at ASC;

-- ============================================================================
-- 6. TRIGGERS: Automatización de auditoría
-- ============================================================================

-- Trigger: Limpiar sesiones al desactivar usuario
DROP TRIGGER IF EXISTS after_user_deactivation;
DELIMITER $$
CREATE TRIGGER after_user_deactivation
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.status = 0 AND OLD.status = 1 THEN
        -- Cerrar todas las sesiones activas del usuario
        UPDATE user_sessions
        SET is_active = 0, logout_at = NOW()
        WHERE user_id = NEW.id AND is_active = 1;

        -- Registrar el cambio de estado
        INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason)
        VALUES (NEW.id, @current_user_id, 'status_change', 'status', '1', '0', 'Usuario desactivado');
    END IF;
END$$
DELIMITER ;

-- Trigger: Registrar creación de usuario
DROP TRIGGER IF EXISTS after_user_creation;
DELIMITER $$
CREATE TRIGGER after_user_creation
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO user_change_history (user_id, changed_by, change_type, change_reason, ip_address)
    VALUES (NEW.id, COALESCE(@current_user_id, NEW.id), 'create', 'Usuario creado', @current_user_ip);
END$$
DELIMITER ;

-- Trigger: Limpiar sesiones expiradas automáticamente
DROP EVENT IF EXISTS cleanup_expired_sessions;
CREATE EVENT cleanup_expired_sessions
ON SCHEDULE EVERY 1 HOUR
DO
    UPDATE user_sessions
    SET is_active = 0
    WHERE is_active = 1
      AND expires_at < NOW()
      AND logout_at IS NULL;

-- ============================================================================
-- 7. ÍNDICES ADICIONALES EN TABLA users EXISTENTE
-- ============================================================================
-- Optimizar búsquedas frecuentes

-- Verificar y crear índices si no existen
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
               WHERE table_schema = DATABASE()
               AND table_name = 'users'
               AND index_name = 'idx_full_name');
SET @sqlstmt := IF(@exist > 0, 'SELECT ''Index idx_full_name already exists''',
                   'CREATE INDEX idx_full_name ON users(full_name)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
               WHERE table_schema = DATABASE()
               AND table_name = 'users'
               AND index_name = 'idx_email');
SET @sqlstmt := IF(@exist > 0, 'SELECT ''Index idx_email already exists''',
                   'CREATE INDEX idx_email ON users(email)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
               WHERE table_schema = DATABASE()
               AND table_name = 'users'
               AND index_name = 'idx_status');
SET @sqlstmt := IF(@exist > 0, 'SELECT ''Index idx_status already exists''',
                   'CREATE INDEX idx_status ON users(status)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- MIGRACIÓN COMPLETADA
-- ============================================================================

SELECT 'Migration 005: User Management Enhancements - COMPLETADA ✓' as Status;
SELECT 'Tablas creadas: user_change_history, user_sessions, user_profile_photos, user_notes' as Info;
SELECT 'Vistas creadas: 6 vistas para consultas optimizadas' as Info;
SELECT 'Triggers creados: 2 triggers para auditoría automática' as Info;
SELECT 'Event creado: cleanup_expired_sessions (cada 1 hora)' as Info;
SELECT '¡Todos los errores corregidos!' as '✓ FIXED';
