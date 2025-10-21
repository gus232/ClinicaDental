-- ============================================================================
-- SCRIPT DE REPARACIÓN: Instalación RBAC
-- ============================================================================
-- Ejecuta este archivo si tuviste problemas con la instalación inicial
-- Especialmente si faltan los stored procedures
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- VERIFICACIÓN 1: Contar elementos instalados
-- ============================================================================

SELECT '============================================' AS '';
SELECT 'VERIFICACIÓN DE INSTALACIÓN RBAC' AS '';
SELECT '============================================' AS '';
SELECT '' AS '';

-- Tablas
SELECT 'TABLAS:' AS '';
SELECT COUNT(*) as total_tablas,
       'Esperado: 8' as esperado
FROM information_schema.tables
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

SELECT '' AS '';

-- Roles
SELECT 'ROLES:' AS '';
SELECT COUNT(*) as total_roles,
       'Esperado: 7' as esperado
FROM roles;

SELECT '' AS '';

-- Permisos
SELECT 'PERMISOS:' AS '';
SELECT COUNT(*) as total_permisos,
       'Esperado: 58-60' as esperado
FROM permissions;

SELECT '' AS '';

-- Vistas
SELECT 'VISTAS:' AS '';
SELECT COUNT(*) as total_vistas,
       'Esperado: 6' as esperado
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

SELECT '' AS '';

-- Stored Procedures
SELECT 'STORED PROCEDURES:' AS '';
SELECT COUNT(*) as total_procedures,
       'Esperado: 5' as esperado
FROM information_schema.routines
WHERE routine_schema = 'hms_v2'
AND routine_type = 'PROCEDURE';

SELECT '' AS '';

-- Listar procedures existentes
SELECT 'Procedures encontrados:' AS '';
SELECT routine_name
FROM information_schema.routines
WHERE routine_schema = 'hms_v2'
AND routine_type = 'PROCEDURE';

SELECT '' AS '';
SELECT '============================================' AS '';
SELECT 'DIAGNÓSTICO COMPLETADO' AS '';
SELECT '============================================' AS '';
SELECT '' AS '';
SELECT 'Si falta algún stored procedure, ejecuta los archivos en:' AS '';
SELECT 'database/stored-procedures/*.sql (uno por uno en phpMyAdmin)' AS '';
SELECT '' AS '';
