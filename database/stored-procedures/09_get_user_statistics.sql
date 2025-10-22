-- ============================================================================
-- STORED PROCEDURE: get_user_statistics
-- ============================================================================
-- Descripción: Obtiene estadísticas generales del sistema de usuarios
-- Retorna: Conjunto con estadísticas completas
-- ============================================================================

DROP PROCEDURE IF EXISTS get_user_statistics;

CREATE PROCEDURE get_user_statistics()
BEGIN
    -- Estadísticas generales
    SELECT
        -- Totales de usuarios
        COUNT(*) as total_users,
        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as inactive_users,

        -- Por género
        SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as male_users,
        SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female_users,
        SUM(CASE WHEN gender = 'Other' THEN 1 ELSE 0 END) as other_gender_users,

        -- Usuarios recientes
        SUM(CASE WHEN reg_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as users_last_7_days,
        SUM(CASE WHEN reg_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as users_last_30_days,

        -- Sesiones
        (SELECT COUNT(*) FROM user_sessions WHERE is_active = 1 AND expires_at > NOW()) as active_sessions,

        -- Cambios recientes
        (SELECT COUNT(*) FROM user_change_history WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as changes_last_24h,
        (SELECT COUNT(*) FROM user_change_history WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as changes_last_7_days

    FROM users;
END;
