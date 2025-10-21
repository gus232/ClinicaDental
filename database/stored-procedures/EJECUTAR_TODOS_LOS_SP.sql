-- ============================================================================
-- INSTALADOR DE STORED PROCEDURES - VERSIÓN COMPATIBLE
-- ============================================================================
-- Ejecuta este archivo COMPLETO en phpMyAdmin (pestaña SQL)
-- No ejecutes línea por línea, copia TODO el archivo
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- SP 1: assign_role_to_user
-- ============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS assign_role_to_user//

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
END//

-- ============================================================================
-- SP 2: revoke_role_from_user
-- ============================================================================

DROP PROCEDURE IF EXISTS revoke_role_from_user//

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
END//

-- ============================================================================
-- SP 3: user_has_permission
-- ============================================================================

DROP PROCEDURE IF EXISTS user_has_permission//

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
END//

-- ============================================================================
-- SP 4: get_user_permissions
-- ============================================================================

DROP PROCEDURE IF EXISTS get_user_permissions//

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
END//

-- ============================================================================
-- SP 5: cleanup_old_security_data
-- ============================================================================

DROP PROCEDURE IF EXISTS cleanup_old_security_data//

CREATE PROCEDURE cleanup_old_security_data()
BEGIN
    -- Eliminar intentos de login mayores a 90 días
    DELETE FROM login_attempts
    WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

    -- Eliminar tokens de más de 7 días
    DELETE FROM password_reset_tokens
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);

    SELECT 'Limpieza completada exitosamente' AS message;
END//

DELIMITER ;

-- ============================================================================
-- VERIFICACIÓN
-- ============================================================================

SELECT '✓ Stored Procedures creados exitosamente' AS status;

SELECT routine_name AS 'Procedures Instalados'
FROM information_schema.routines
WHERE routine_schema = 'hms_v2'
AND routine_type = 'PROCEDURE'
ORDER BY routine_name;
