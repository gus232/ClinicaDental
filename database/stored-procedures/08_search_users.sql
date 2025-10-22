-- ============================================================================
-- STORED PROCEDURE: search_users
-- ============================================================================
-- Descripción: Búsqueda avanzada de usuarios con filtros múltiples
-- Parámetros:
--   - p_search_term: Término de búsqueda (nombre, email, teléfono)
--   - p_role_id: Filtrar por rol (NULL = todos)
--   - p_status: Filtrar por estado (NULL = todos, 1 = activos, 0 = inactivos)
--   - p_gender: Filtrar por género (NULL = todos)
--   - p_city: Filtrar por ciudad (NULL = todos)
--   - p_limit: Límite de resultados (default 50)
--   - p_offset: Offset para paginación (default 0)
-- Retorna: Conjunto de resultados con usuarios que coinciden
-- ============================================================================

DROP PROCEDURE IF EXISTS search_users;

CREATE PROCEDURE search_users(
    IN p_search_term VARCHAR(255),
    IN p_role_id INT,
    IN p_status TINYINT,
    IN p_gender VARCHAR(10),
    IN p_city VARCHAR(255),
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    DECLARE v_search_pattern VARCHAR(257);

    -- Preparar patrón de búsqueda
    IF p_search_term IS NOT NULL AND LENGTH(TRIM(p_search_term)) > 0 THEN
        SET v_search_pattern = CONCAT('%', p_search_term, '%');
    ELSE
        SET v_search_pattern = '%';
    END IF;

    -- Establecer límite por defecto
    IF p_limit IS NULL OR p_limit <= 0 THEN
        SET p_limit = 50;
    END IF;

    -- Establecer offset por defecto
    IF p_offset IS NULL OR p_offset < 0 THEN
        SET p_offset = 0;
    END IF;

    -- Búsqueda con todos los filtros
    SELECT DISTINCT
        u.id,
        u.full_name,
        u.address,
        u.city,
        u.gender,
        u.email,
        u.contactno,
        u.reg_date,
        u.updation_date,
        u.status,
        GROUP_CONCAT(DISTINCT r.display_name ORDER BY r.priority SEPARATOR ', ') as roles,
        GROUP_CONCAT(DISTINCT r.id ORDER BY r.priority) as role_ids,
        (SELECT COUNT(*) FROM user_change_history WHERE user_id = u.id) as total_changes,
        (SELECT COUNT(*) FROM user_sessions WHERE user_id = u.id AND is_active = 1 AND expires_at > NOW()) as active_sessions
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_active = 1
    LEFT JOIN roles r ON ur.role_id = r.id AND r.status = 'active'
    WHERE
        (
            u.full_name LIKE v_search_pattern
            OR u.email LIKE v_search_pattern
            OR u.contactno LIKE v_search_pattern
            OR u.city LIKE v_search_pattern
        )
        AND (p_status IS NULL OR u.status = p_status)
        AND (p_gender IS NULL OR u.gender = p_gender)
        AND (p_city IS NULL OR u.city LIKE CONCAT('%', p_city, '%'))
        AND (p_role_id IS NULL OR ur.role_id = p_role_id)
    GROUP BY u.id, u.full_name, u.address, u.city, u.gender, u.email, u.contactno, u.reg_date, u.updation_date, u.status
    ORDER BY u.full_name ASC
    LIMIT p_limit OFFSET p_offset;
END;
