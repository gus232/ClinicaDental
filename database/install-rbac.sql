-- ============================================================================
-- INSTALADOR COMPLETO: SISTEMA RBAC
-- ============================================================================
-- Descripción: Script que ejecuta todas las migraciones necesarias para RBAC
--
-- IMPORTANTE: Ejecutar este archivo en MySQL/phpMyAdmin
--
-- Orden de ejecución:
--   1. Sistema RBAC (tablas)
--   2. Logs de seguridad
--   3. Datos iniciales (roles y permisos)
--
-- Versión: 2.2.0
-- Fecha: 2025-10-20
-- Proyecto: SIS 321 - Seguridad de Sistemas
-- ============================================================================

USE hms_v2;

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- ============================================================================
-- PASO 1: VERIFICAR BASE DE DATOS
-- ============================================================================

SELECT '════════════════════════════════════════════════════════════' AS '';
SELECT '🚀 INSTALADOR DE SISTEMA RBAC' AS '';
SELECT '════════════════════════════════════════════════════════════' AS '';
SELECT '' AS '';
SELECT '📋 Verificando base de datos...' AS '';

SELECT CONCAT('✓ Base de datos: ', DATABASE()) AS status;
SELECT CONCAT('✓ Usuario: ', USER()) AS status;
SELECT CONCAT('✓ Versión MySQL: ', VERSION()) AS status;

SELECT '' AS '';
SELECT '════════════════════════════════════════════════════════════' AS '';
SELECT '📦 PASO 1/3: Creando tablas del sistema RBAC...' AS '';
SELECT '════════════════════════════════════════════════════════════' AS '';

-- Ejecutar migración 003_rbac_system.sql
SOURCE database/migrations/003_rbac_system.sql;

SELECT '' AS '';
SELECT '════════════════════════════════════════════════════════════' AS '';
SELECT '📦 PASO 2/3: Creando tabla de logs de seguridad...' AS '';
SELECT '════════════════════════════════════════════════════════════' AS '';

-- Ejecutar migración 004_security_logs.sql
SOURCE database/migrations/004_security_logs.sql;

SELECT '' AS '';
SELECT '════════════════════════════════════════════════════════════' AS '';
SELECT '📦 PASO 3/3: Poblando roles y permisos por defecto...' AS '';
SELECT '════════════════════════════════════════════════════════════' AS '';

-- Ejecutar seed 003_default_roles_permissions.sql
SOURCE database/seeds/003_default_roles_permissions.sql;

-- ============================================================================
-- VERIFICACIÓN FINAL
-- ============================================================================

SELECT '' AS '';
SELECT '════════════════════════════════════════════════════════════' AS '';
SELECT '✅ VERIFICACIÓN DE INSTALACIÓN' AS '';
SELECT '════════════════════════════════════════════════════════════' AS '';

-- Contar tablas creadas
SELECT '' AS '';
SELECT 'TABLAS CREADAS:' AS '';
SELECT COUNT(*) AS total_tablas FROM information_schema.tables
WHERE table_schema = 'hms_v2'
AND table_name IN (
    'roles',
    'permissions',
    'role_permissions',
    'user_roles',
    'permission_categories',
    'role_hierarchy',
    'audit_role_changes',
    'security_logs'
);

-- Mostrar roles
SELECT '' AS '';
SELECT 'ROLES CREADOS:' AS '';
SELECT id, role_name, display_name, priority, status FROM roles ORDER BY priority;

-- Mostrar conteo de permisos
SELECT '' AS '';
SELECT 'PERMISOS POR CATEGORÍA:' AS '';
SELECT
    pc.display_name AS categoria,
    COUNT(p.id) AS total_permisos
FROM permission_categories pc
LEFT JOIN permissions p ON pc.id = p.category_id
GROUP BY pc.id, pc.display_name
ORDER BY pc.sort_order;

-- Mostrar matriz de permisos por rol
SELECT '' AS '';
SELECT 'PERMISOS ASIGNADOS POR ROL:' AS '';
SELECT
    r.display_name AS Rol,
    COUNT(rp.permission_id) AS Total_Permisos
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.display_name
ORDER BY r.priority;

-- Mostrar vistas creadas
SELECT '' AS '';
SELECT 'VISTAS CREADAS:' AS '';
SELECT table_name AS vista
FROM information_schema.views
WHERE table_schema = 'hms_v2'
AND table_name IN (
    'user_effective_permissions',
    'user_roles_summary',
    'role_permission_matrix',
    'expiring_user_roles',
    'unauthorized_access_summary',
    'access_attempts_by_ip'
);

-- Mostrar stored procedures
SELECT '' AS '';
SELECT 'STORED PROCEDURES CREADOS:' AS '';
SELECT routine_name AS procedimiento
FROM information_schema.routines
WHERE routine_schema = 'hms_v2'
AND routine_type = 'PROCEDURE'
AND routine_name IN (
    'assign_role_to_user',
    'revoke_role_from_user',
    'user_has_permission',
    'get_user_permissions',
    'cleanup_old_security_data'
);

-- ============================================================================
-- RESUMEN FINAL
-- ============================================================================

SELECT '' AS '';
SELECT '════════════════════════════════════════════════════════════' AS '';
SELECT '✅ INSTALACIÓN COMPLETADA EXITOSAMENTE' AS '';
SELECT '════════════════════════════════════════════════════════════' AS '';
SELECT '' AS '';

SELECT '📊 RESUMEN DE INSTALACIÓN:' AS '';
SELECT CONCAT('  ✓ ', COUNT(*), ' tablas creadas') AS estado
FROM information_schema.tables
WHERE table_schema = 'hms_v2'
AND table_name IN ('roles', 'permissions', 'role_permissions', 'user_roles', 'permission_categories', 'role_hierarchy', 'audit_role_changes', 'security_logs');

SELECT CONCAT('  ✓ ', COUNT(*), ' roles del sistema') AS estado FROM roles;
SELECT CONCAT('  ✓ ', COUNT(*), ' permisos definidos') AS estado FROM permissions;
SELECT CONCAT('  ✓ ', COUNT(*), ' asignaciones de permisos') AS estado FROM role_permissions;
SELECT CONCAT('  ✓ ', COUNT(*), ' vistas creadas') AS estado
FROM information_schema.views
WHERE table_schema = 'hms_v2'
AND table_name LIKE '%permission%' OR table_name LIKE '%role%';

SELECT '' AS '';
SELECT '📚 PRÓXIMOS PASOS:' AS '';
SELECT '  1. Incluir RBAC en tus páginas PHP:' AS paso;
SELECT '     require_once("include/permission-check.php");' AS codigo;
SELECT '     requirePermission("view_patients");' AS codigo;
SELECT '' AS '';
SELECT '  2. Asignar roles a usuarios existentes:' AS paso;
SELECT '     INSERT INTO user_roles (user_id, role_id, assigned_by)' AS codigo;
SELECT '     VALUES (1, 1, 1); -- Asignar Super Admin al usuario 1' AS codigo;
SELECT '' AS '';
SELECT '  3. Ver documentación completa en:' AS paso;
SELECT '     docs/RBAC_USAGE_GUIDE.md' AS codigo;
SELECT '' AS '';

SELECT '════════════════════════════════════════════════════════════' AS '';
SELECT '🎉 ¡SISTEMA RBAC LISTO PARA USAR!' AS '';
SELECT '════════════════════════════════════════════════════════════' AS '';

-- Restaurar configuración
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
