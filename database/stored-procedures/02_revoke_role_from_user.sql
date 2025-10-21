-- ============================================================================
-- STORED PROCEDURE: revoke_role_from_user
-- ============================================================================
-- Revoca un rol de un usuario con auditoría

DROP PROCEDURE IF EXISTS revoke_role_from_user;

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
END;
