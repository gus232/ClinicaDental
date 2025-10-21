-- ============================================================================
-- STORED PROCEDURE: assign_role_to_user
-- ============================================================================
-- Asigna un rol a un usuario con auditoría

DROP PROCEDURE IF EXISTS assign_role_to_user;

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
END;
