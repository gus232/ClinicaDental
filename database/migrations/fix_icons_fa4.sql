-- ============================================================================
-- FIX: Actualizar iconos a Font Awesome 4
-- ============================================================================
-- El proyecto usa Font Awesome 4.x, algunos iconos están en FA 5+
-- ============================================================================

USE hms_v2;

-- Actualizar iconos a versiones compatibles con Font Awesome 4
UPDATE permission_categories SET icon = 'fa-wheelchair' WHERE category_name = 'patients';
UPDATE permission_categories SET icon = 'fa-calendar' WHERE category_name = 'appointments';
UPDATE permission_categories SET icon = 'fa-file-text-o' WHERE category_name = 'medical_records';
UPDATE permission_categories SET icon = 'fa-usd' WHERE category_name = 'billing';
UPDATE permission_categories SET icon = 'fa-bar-chart' WHERE category_name = 'reports';
UPDATE permission_categories SET icon = 'fa-shield' WHERE category_name = 'security';

SELECT 'Iconos actualizados a Font Awesome 4' AS status;

-- Verificar cambios
SELECT category_name, display_name, icon FROM permission_categories ORDER BY sort_order;
