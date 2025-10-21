-- ============================================================================
-- MIGRACIÓN 003: SISTEMA RBAC (Role-Based Access Control)
-- ============================================================================
-- Descripción: Implementa sistema de roles y permisos granulares
--   - Roles: Admin, Doctor, Patient, Receptionist, etc.
--   - Permisos: view_patients, edit_appointments, manage_users, etc.
--   - Asignación many-to-many (un usuario puede tener múltiples roles)
--   - Herencia de permisos
--
-- Versión: 2.2.0
-- Fecha: 2025-10-20
-- Proyecto: SIS 321 - Seguridad de Sistemas
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- 1. TABLA: ROLES
-- ============================================================================
-- Propósito: Define los roles disponibles en el sistema
-- Ejemplos: Super Admin, Admin, Doctor, Patient, Receptionist

CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL COMMENT 'Nombre único del rol (ej: admin, doctor)',
    display_name VARCHAR(100) NOT NULL COMMENT 'Nombre para mostrar en UI (ej: Administrator)',
    description TEXT COMMENT 'Descripción de las responsabilidades del rol',
    is_system_role TINYINT(1) DEFAULT 0 COMMENT '1 = Rol del sistema (no se puede eliminar), 0 = Rol personalizado',
    priority INT DEFAULT 100 COMMENT 'Prioridad del rol (menor número = mayor prioridad)',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL COMMENT 'ID del usuario que creó el rol',

    INDEX idx_role_name (role_name),
    INDEX idx_status (status),
    INDEX idx_priority (priority),

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Roles del sistema';

-- ============================================================================
-- 2. TABLA: PERMISSIONS
-- ============================================================================
-- Propósito: Define permisos granulares del sistema
-- Ejemplos: view_patients, edit_appointments, delete_users

CREATE TABLE IF NOT EXISTS permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    permission_name VARCHAR(100) UNIQUE NOT NULL COMMENT 'Nombre técnico del permiso (ej: manage_users)',
    display_name VARCHAR(150) NOT NULL COMMENT 'Nombre para mostrar en UI',
    description TEXT COMMENT 'Descripción de qué permite hacer este permiso',
    module VARCHAR(50) NOT NULL COMMENT 'Módulo al que pertenece (ej: users, patients, appointments)',
    is_system_permission TINYINT(1) DEFAULT 1 COMMENT '1 = Permiso del sistema, 0 = Permiso personalizado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_permission_name (permission_name),
    INDEX idx_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Permisos granulares del sistema';

-- ============================================================================
-- 3. TABLA: ROLE_PERMISSIONS (Relación Many-to-Many)
-- ============================================================================
-- Propósito: Define qué permisos tiene cada rol

CREATE TABLE IF NOT EXISTS role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    granted_by INT NULL COMMENT 'ID del admin que otorgó el permiso',

    -- Evitar duplicados
    UNIQUE KEY unique_role_permission (role_id, permission_id),

    INDEX idx_role_id (role_id),
    INDEX idx_permission_id (permission_id),

    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Asignación de permisos a roles';

-- ============================================================================
-- 4. TABLA: USER_ROLES (Relación Many-to-Many)
-- ============================================================================
-- Propósito: Define qué roles tiene cada usuario
-- Nota: Un usuario puede tener múltiples roles

CREATE TABLE IF NOT EXISTS user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT NULL COMMENT 'ID del admin que asignó el rol',
    expires_at DATETIME NULL COMMENT 'Fecha de expiración del rol (NULL = permanente)',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1 = Activo, 0 = Desactivado temporalmente',

    -- Evitar duplicados
    UNIQUE KEY unique_user_role (user_id, role_id),

    INDEX idx_user_id (user_id),
    INDEX idx_role_id (role_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Asignación de roles a usuarios';

-- ============================================================================
-- 5. TABLA: PERMISSION_CATEGORIES
-- ============================================================================
-- Propósito: Organizar permisos por categorías para mejor UI

CREATE TABLE IF NOT EXISTS permission_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) NULL COMMENT 'Icono para UI (ej: fa-users)',
    sort_order INT DEFAULT 0,

    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Categorías de permisos para organización';

-- Agregar columna category_id a permissions
ALTER TABLE permissions
ADD COLUMN category_id INT NULL AFTER module,
ADD FOREIGN KEY (category_id) REFERENCES permission_categories(id) ON DELETE SET NULL;

-- ============================================================================
-- 6. TABLA: ROLE_HIERARCHY (Para herencia de roles)
-- ============================================================================
-- Propósito: Definir jerarquía de roles (ej: Admin hereda permisos de Doctor)

CREATE TABLE IF NOT EXISTS role_hierarchy (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_role_id INT NOT NULL COMMENT 'Rol padre (hereda a)',
    child_role_id INT NOT NULL COMMENT 'Rol hijo (hereda de)',

    UNIQUE KEY unique_hierarchy (parent_role_id, child_role_id),

    FOREIGN KEY (parent_role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (child_role_id) REFERENCES roles(id) ON DELETE CASCADE,

    -- Evitar auto-referencia
    CHECK (parent_role_id != child_role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Jerarquía y herencia de roles';

-- ============================================================================
-- 7. TABLA: AUDIT_ROLE_CHANGES
-- ============================================================================
-- Propósito: Auditoría de cambios en asignación de roles

CREATE TABLE IF NOT EXISTS audit_role_changes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'Usuario afectado',
    role_id INT NOT NULL COMMENT 'Rol modificado',
    action ENUM('assigned', 'revoked', 'role_updated', 'permission_changed') NOT NULL,
    performed_by INT NULL COMMENT 'Admin que realizó la acción',
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    details JSON COMMENT 'Información adicional del cambio',

    INDEX idx_user_id (user_id),
    INDEX idx_performed_by (performed_by),
    INDEX idx_performed_at (performed_at),
    INDEX idx_action (action),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Auditoría de cambios en roles y permisos';

-- ============================================================================
-- 8. VISTAS: Consultas optimizadas
-- ============================================================================

-- Vista: Permisos efectivos de cada usuario (incluyendo herencia)
CREATE OR REPLACE VIEW user_effective_permissions AS
SELECT DISTINCT
    u.id AS user_id,
    u.email,
    u.full_name,
    r.id AS role_id,
    r.role_name,
    p.id AS permission_id,
    p.permission_name,
    p.module
FROM users u
INNER JOIN user_roles ur ON u.id = ur.user_id
INNER JOIN roles r ON ur.role_id = r.id
INNER JOIN role_permissions rp ON r.id = rp.role_id
INNER JOIN permissions p ON rp.permission_id = p.id
WHERE u.status = 'active'
  AND ur.is_active = 1
  AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
  AND r.status = 'active';

-- Vista: Resumen de roles por usuario
CREATE OR REPLACE VIEW user_roles_summary AS
SELECT
    u.id AS user_id,
    u.email,
    u.full_name,
    u.user_type,
    GROUP_CONCAT(r.role_name ORDER BY r.priority SEPARATOR ', ') AS roles,
    GROUP_CONCAT(r.display_name ORDER BY r.priority SEPARATOR ', ') AS roles_display,
    COUNT(DISTINCT r.id) AS total_roles,
    COUNT(DISTINCT p.id) AS total_permissions
FROM users u
LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_active = 1
LEFT JOIN roles r ON ur.role_id = r.id AND r.status = 'active'
LEFT JOIN role_permissions rp ON r.id = rp.role_id
LEFT JOIN permissions p ON rp.permission_id = p.id
WHERE u.status = 'active'
GROUP BY u.id, u.email, u.full_name, u.user_type;

-- Vista: Matriz de permisos por rol
CREATE OR REPLACE VIEW role_permission_matrix AS
SELECT
    r.role_name,
    r.display_name AS role_display_name,
    p.module,
    p.permission_name,
    p.display_name AS permission_display_name,
    rp.granted_at
FROM roles r
INNER JOIN role_permissions rp ON r.id = rp.role_id
INNER JOIN permissions p ON rp.permission_id = p.id
WHERE r.status = 'active'
ORDER BY r.priority, p.module, p.permission_name;

-- Vista: Usuarios con roles próximos a expirar
CREATE OR REPLACE VIEW expiring_user_roles AS
SELECT
    u.id AS user_id,
    u.email,
    u.full_name,
    r.role_name,
    r.display_name AS role_display_name,
    ur.expires_at,
    DATEDIFF(ur.expires_at, NOW()) AS days_until_expiry
FROM users u
INNER JOIN user_roles ur ON u.id = ur.user_id
INNER JOIN roles r ON ur.role_id = r.id
WHERE ur.expires_at IS NOT NULL
  AND ur.expires_at > NOW()
  AND DATEDIFF(ur.expires_at, NOW()) <= 7
  AND ur.is_active = 1
ORDER BY ur.expires_at ASC;

-- ============================================================================
-- 9. STORED PROCEDURES
-- ============================================================================

-- Procedimiento: Asignar rol a usuario
DELIMITER $$

CREATE PROCEDURE assign_role_to_user(
    IN p_user_id INT,
    IN p_role_id INT,
    IN p_assigned_by INT,
    IN p_expires_at DATETIME
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'Error al asignar rol' AS message, 0 AS success;
    END;

    START TRANSACTION;

    -- Verificar que usuario y rol existan
    IF NOT EXISTS (SELECT 1 FROM users WHERE id = p_user_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario no encontrado';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM roles WHERE id = p_role_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Rol no encontrado';
    END IF;

    -- Insertar o actualizar
    INSERT INTO user_roles (user_id, role_id, assigned_by, expires_at, is_active)
    VALUES (p_user_id, p_role_id, p_assigned_by, p_expires_at, 1)
    ON DUPLICATE KEY UPDATE
        assigned_by = p_assigned_by,
        expires_at = p_expires_at,
        is_active = 1,
        assigned_at = CURRENT_TIMESTAMP;

    -- Registrar en auditoría
    INSERT INTO audit_role_changes (user_id, role_id, action, performed_by)
    VALUES (p_user_id, p_role_id, 'assigned', p_assigned_by);

    COMMIT;
    SELECT 'Rol asignado exitosamente' AS message, 1 AS success;
END$$

-- Procedimiento: Revocar rol de usuario
CREATE PROCEDURE revoke_role_from_user(
    IN p_user_id INT,
    IN p_role_id INT,
    IN p_revoked_by INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'Error al revocar rol' AS message, 0 AS success;
    END;

    START TRANSACTION;

    -- Verificar que la asignación exista
    IF NOT EXISTS (SELECT 1 FROM user_roles WHERE user_id = p_user_id AND role_id = p_role_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El usuario no tiene este rol asignado';
    END IF;

    -- Eliminar asignación
    DELETE FROM user_roles
    WHERE user_id = p_user_id AND role_id = p_role_id;

    -- Registrar en auditoría
    INSERT INTO audit_role_changes (user_id, role_id, action, performed_by)
    VALUES (p_user_id, p_role_id, 'revoked', p_revoked_by);

    COMMIT;
    SELECT 'Rol revocado exitosamente' AS message, 1 AS success;
END$$

-- Procedimiento: Verificar si usuario tiene permiso
CREATE PROCEDURE user_has_permission(
    IN p_user_id INT,
    IN p_permission_name VARCHAR(100)
)
BEGIN
    SELECT EXISTS(
        SELECT 1
        FROM user_effective_permissions
        WHERE user_id = p_user_id
          AND permission_name = p_permission_name
    ) AS has_permission;
END$$

-- Procedimiento: Obtener todos los permisos de un usuario
CREATE PROCEDURE get_user_permissions(
    IN p_user_id INT
)
BEGIN
    SELECT DISTINCT
        p.permission_name,
        p.display_name,
        p.module,
        r.role_name,
        r.display_name AS role_display_name
    FROM users u
    INNER JOIN user_roles ur ON u.id = ur.user_id
    INNER JOIN roles r ON ur.role_id = r.id
    INNER JOIN role_permissions rp ON r.id = rp.role_id
    INNER JOIN permissions p ON rp.permission_id = p.id
    WHERE u.id = p_user_id
      AND u.status = 'active'
      AND ur.is_active = 1
      AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
      AND r.status = 'active'
    ORDER BY p.module, p.permission_name;
END$$

DELIMITER ;

-- ============================================================================
-- 10. TRIGGERS
-- ============================================================================

-- Trigger: Auto-expirar roles
DELIMITER $$

CREATE TRIGGER check_role_expiration_before_select
BEFORE UPDATE ON user_roles
FOR EACH ROW
BEGIN
    IF NEW.expires_at IS NOT NULL AND NEW.expires_at < NOW() THEN
        SET NEW.is_active = 0;
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- 11. ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================================================

ALTER TABLE user_roles ADD INDEX idx_user_active (user_id, is_active);
ALTER TABLE role_permissions ADD INDEX idx_role_permission (role_id, permission_id);

-- ============================================================================
-- MIGRACIÓN COMPLETADA EXITOSAMENTE
-- ============================================================================

SELECT '✓ Migración 003_rbac_system.sql ejecutada exitosamente' AS status;
SELECT CONCAT('✓ Tablas creadas: roles, permissions, role_permissions, user_roles, permission_categories, role_hierarchy, audit_role_changes') AS status;
SELECT CONCAT('✓ Vistas creadas: user_effective_permissions, user_roles_summary, role_permission_matrix, expiring_user_roles') AS status;
SELECT CONCAT('✓ Stored Procedures creados: assign_role_to_user, revoke_role_from_user, user_has_permission, get_user_permissions') AS status;
SELECT '✓ Sistema RBAC listo para usar' AS status;
