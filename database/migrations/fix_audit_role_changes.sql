-- ============================================================================
-- FIX: Permitir NULL en user_id de audit_role_changes
-- ============================================================================
-- Propósito: Cuando se actualizan permisos del ROL (no de un usuario específico)
--            necesitamos poder insertar NULL en user_id
-- Fecha: 2025-10-22
-- ============================================================================

USE hms_v2;

-- Eliminar la restricción de foreign key actual
ALTER TABLE audit_role_changes 
DROP FOREIGN KEY audit_role_changes_ibfk_1;

-- Modificar la columna para permitir NULL
ALTER TABLE audit_role_changes 
MODIFY COLUMN user_id INT NULL COMMENT 'Usuario afectado (NULL = cambio en el rol)';

-- Recrear la foreign key permitiendo NULL
ALTER TABLE audit_role_changes 
ADD CONSTRAINT audit_role_changes_ibfk_1 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Mensaje de confirmación
SELECT 'Tabla audit_role_changes modificada exitosamente. user_id ahora permite NULL.' AS status;
