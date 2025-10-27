-- ============================================================================
-- MIGRACIÓN: Deshabilitar categorías de permisos no necesarias
-- ============================================================================
-- Autor: Sistema de Seguridad HMS
-- Fecha: 2025-10-24
-- Descripción: Deshabilita 3 categorías (Registros Médicos, Facturación, Reportes)
--              y revoca automáticamente sus permisos de todos los roles
-- ============================================================================

-- 1. Agregar campo is_active a permission_categories (si no existe)
ALTER TABLE permission_categories
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1
COMMENT 'Estado activo (1=activo, 0=deshabilitado)';

-- 2. Agregar campo is_active a permissions (si no existe)
ALTER TABLE permissions
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1
COMMENT 'Estado activo (1=activo, 0=deshabilitado)';

-- 3. Deshabilitar las 3 categorías no necesarias
UPDATE permission_categories
SET is_active = 0
WHERE category_name IN ('medical_records', 'billing', 'reports');

-- 4. Deshabilitar todos los permisos de esas categorías (19 permisos)
UPDATE permissions
SET is_active = 0
WHERE module IN ('medical_records', 'billing', 'reports');

-- 5. REVOCAR automáticamente permisos deshabilitados de todos los roles
-- Este DELETE automáticamente revoca los permisos de todos los roles
DELETE FROM role_permissions
WHERE permission_id IN (
    SELECT id FROM permissions
    WHERE module IN ('medical_records', 'billing', 'reports')
);

-- 6. Registrar la acción en auditoría (si la tabla tiene campos suficientes)
-- Nota: Esto es informativo/logging
INSERT INTO audit_role_changes (role_id, action, performed_by)
SELECT id, 'categories_disabled_batch', 1
FROM roles
WHERE id NOT IN (
    SELECT DISTINCT performed_by FROM audit_role_changes
    WHERE action = 'categories_disabled_batch'
)
LIMIT 1;

-- ============================================================================
-- VERIFICACIÓN POST-MIGRACIÓN (ejecutar por separado para confirmar)
-- ============================================================================
-- Verificar categorías activas (debería devolver 6)
-- SELECT COUNT(*) as categorias_activas FROM permission_categories WHERE is_active = 1;

-- Verificar permisos activos (debería devolver 39 de 58)
-- SELECT COUNT(*) as permisos_activos FROM permissions WHERE is_active = 1;

-- Verificar permisos revocados (debería devolver 0)
-- SELECT COUNT(*) as permisos_revocados_asignados FROM role_permissions rp
-- INNER JOIN permissions p ON rp.permission_id = p.id
-- WHERE p.is_active = 0;

-- ============================================================================
-- REVERSIÓN (si es necesario, ejecutar estos comandos)
-- ============================================================================
-- UPDATE permission_categories SET is_active = 1
-- WHERE category_name IN ('medical_records', 'billing', 'reports');
-- UPDATE permissions SET is_active = 1
-- WHERE module IN ('medical_records', 'billing', 'reports');
