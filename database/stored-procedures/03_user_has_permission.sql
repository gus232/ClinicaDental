-- ============================================================================
-- STORED PROCEDURE: user_has_permission
-- ============================================================================
-- Verifica si un usuario tiene un permiso específico

DROP PROCEDURE IF EXISTS user_has_permission;

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
END;
