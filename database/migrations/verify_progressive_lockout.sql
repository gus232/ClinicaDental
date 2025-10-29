-- Script de verificación del sistema de bloqueo progresivo
-- Fecha: 2025-10-28

-- Verificar que las columnas existen en users
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'hms_v2' 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME IN ('lockout_count', 'last_lockout_date');

-- Verificar configuraciones en password_policy_config
SELECT * FROM password_policy_config 
WHERE setting_name LIKE '%lockout%' OR setting_name = 'progressive_lockout_enabled'
ORDER BY setting_name;

-- Ver estado actual de usuarios (para testing)
SELECT 
    id, 
    email, 
    status, 
    failed_login_attempts, 
    lockout_count, 
    account_locked_until, 
    last_lockout_date 
FROM users 
LIMIT 5;
