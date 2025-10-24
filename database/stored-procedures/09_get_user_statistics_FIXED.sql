-- ============================================================================
-- STORED PROCEDURE: get_user_statistics (CORREGIDO)
-- ============================================================================
-- Descripción: Obtiene estadísticas generales del sistema de usuarios
-- Retorna: Conjunto con estadísticas completas
-- CORRIGE: status como VARCHAR ('active', 'inactive', 'blocked')
-- ============================================================================

DROP PROCEDURE IF EXISTS get_user_statistics;

DELIMITER $$

CREATE PROCEDURE get_user_statistics()
BEGIN
    -- Estadísticas generales
    SELECT
        -- Totales de usuarios
        COUNT(*) as total_users,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
        SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_users,

        -- Usuarios recientes
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as users_last_7_days,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as users_last_30_days,

        -- Cambios recientes (si existe la tabla)
        (SELECT COUNT(*) 
         FROM information_schema.tables 
         WHERE table_schema = DATABASE() 
         AND table_name = 'user_change_history') as has_audit_table,
        
        -- Sesiones activas (si existe la tabla)
        (SELECT COUNT(*) 
         FROM information_schema.tables 
         WHERE table_schema = DATABASE() 
         AND table_name = 'user_sessions') as has_sessions_table

    FROM users;
END$$

DELIMITER ;

SELECT 'SP get_user_statistics instalado correctamente ✓' as Status;
