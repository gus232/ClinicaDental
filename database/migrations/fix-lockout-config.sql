-- Corregir configuración de tiempo de bloqueo
USE hms_v2;

-- Actualizar a 30 minutos
UPDATE password_policy_config
SET setting_value = '30'
WHERE setting_name = 'lockout_duration_minutes';

-- Desbloquear todas las cuentas actualmente bloqueadas (opcional)
UPDATE users
SET failed_login_attempts = 0,
    account_locked_until = NULL
WHERE account_locked_until IS NOT NULL;

-- Verificar
SELECT * FROM password_policy_config
WHERE setting_name = 'lockout_duration_minutes';

SELECT '✓ Configuración actualizada a 30 minutos' AS status;
