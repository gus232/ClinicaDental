-- ============================================================================
-- STORED PROCEDURE: get_user_permissions
-- ============================================================================
-- Obtiene todos los permisos de un usuario

DROP PROCEDURE IF EXISTS get_user_permissions;

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
END;
