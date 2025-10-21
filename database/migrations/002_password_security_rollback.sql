-- ============================================================================
-- ROLLBACK 002: SEGURIDAD DE CONTRASEÑAS
-- ============================================================================
-- Descripción: Revierte todos los cambios de la migración 002
-- Uso: Solo ejecutar si necesitas deshacer la migración
-- ADVERTENCIA: Esto eliminará TODOS los datos de las tablas de seguridad
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- 1. ELIMINAR VISTAS
-- ============================================================================

DROP VIEW IF EXISTS users_password_expiring_soon;
DROP VIEW IF EXISTS locked_accounts;

-- ============================================================================
-- 2. ELIMINAR STORED PROCEDURE
-- ============================================================================

DROP PROCEDURE IF EXISTS cleanup_old_security_data;

-- ============================================================================
-- 3. ELIMINAR TRIGGERS
-- ============================================================================

DROP TRIGGER IF EXISTS cleanup_expired_tokens_before_insert;

-- ============================================================================
-- 4. ELIMINAR TABLAS (en orden inverso por dependencias)
-- ============================================================================

DROP TABLE IF EXISTS password_policy_config;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS password_history;

-- ============================================================================
-- 5. ELIMINAR ÍNDICES DE TABLA USERS
-- ============================================================================

ALTER TABLE users DROP INDEX IF EXISTS idx_account_locked;
ALTER TABLE users DROP INDEX IF EXISTS idx_password_expires;
ALTER TABLE users DROP INDEX IF EXISTS idx_status_type;

-- ============================================================================
-- 6. ELIMINAR COLUMNAS DE TABLA USERS
-- ============================================================================

ALTER TABLE users DROP COLUMN IF EXISTS force_password_change;
ALTER TABLE users DROP COLUMN IF EXISTS last_login_ip;
ALTER TABLE users DROP COLUMN IF EXISTS password_changed_at;
ALTER TABLE users DROP COLUMN IF EXISTS password_expires_at;
ALTER TABLE users DROP COLUMN IF EXISTS account_locked_until;
ALTER TABLE users DROP COLUMN IF EXISTS failed_login_attempts;

-- ============================================================================
-- ROLLBACK COMPLETADO
-- ============================================================================

SELECT '✓ Rollback 002_password_security completado' AS status;
SELECT '⚠ Todas las tablas y campos de seguridad han sido eliminados' AS warning;
