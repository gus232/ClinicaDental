-- Script de testing para sistema de bloqueo progresivo
-- Fecha: 2025-10-28
-- IMPORTANTE: Usar en ambiente de prueba

-- ========================================
-- PREPARACIÓN: Crear usuario de prueba
-- ========================================
-- Descomentar si necesitas crear usuario de prueba
/*
INSERT INTO users (email, password, full_name, user_type, status) 
VALUES ('test.lockout@clinica.muelitas.com', 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
        'Usuario Test Bloqueo', 
        'patient', 
        'active');
*/

-- ========================================
-- CONSULTAS DE VERIFICACIÓN
-- ========================================

-- Ver estado actual del usuario de prueba
SELECT 
    id, 
    email, 
    status, 
    failed_login_attempts, 
    lockout_count, 
    account_locked_until, 
    last_lockout_date,
    CASE 
        WHEN account_locked_until IS NOT NULL AND account_locked_until > NOW() 
        THEN CONCAT('Bloqueado hasta: ', account_locked_until)
        ELSE 'No bloqueado'
    END as estado_bloqueo
FROM users 
WHERE email LIKE '%test%' OR email = 'juan.t@clinica.muelitas.com';

-- Ver últimos intentos de login
SELECT 
    id,
    email,
    user_id,
    attempt_result,
    ip_address,
    attempt_time
FROM login_attempts 
WHERE email LIKE '%test%' OR email = 'juan.t@clinica.muelitas.com'
ORDER BY attempt_time DESC 
LIMIT 10;

-- Ver configuración actual
SELECT * FROM password_policy_config 
WHERE setting_name IN ('progressive_lockout_enabled', 'lockout_1st_minutes', 
                       'lockout_2nd_minutes', 'lockout_3rd_minutes', 
                       'lockout_permanent_after', 'lockout_reset_days');

-- ========================================
-- SIMULAR BLOQUEOS (MANUAL)
-- ========================================

-- Simular primer bloqueo (30 minutos)
/*
UPDATE users SET 
    failed_login_attempts = 3,
    lockout_count = 1,
    account_locked_until = DATE_ADD(NOW(), INTERVAL 30 MINUTE),
    last_lockout_date = NOW()
WHERE email = 'test.lockout@clinica.muelitas.com';
*/

-- Simular segundo bloqueo (2 horas)
/*
UPDATE users SET 
    failed_login_attempts = 3,
    lockout_count = 2,
    account_locked_until = DATE_ADD(NOW(), INTERVAL 2 HOUR),
    last_lockout_date = NOW()
WHERE email = 'test.lockout@clinica.muelitas.com';
*/

-- Simular tercer bloqueo (24 horas)
/*
UPDATE users SET 
    failed_login_attempts = 3,
    lockout_count = 3,
    account_locked_until = DATE_ADD(NOW(), INTERVAL 24 HOUR),
    last_lockout_date = NOW()
WHERE email = 'test.lockout@clinica.muelitas.com';
*/

-- Simular bloqueo permanente
/*
UPDATE users SET 
    failed_login_attempts = 3,
    lockout_count = 4,
    status = 'blocked',
    last_lockout_date = NOW()
WHERE email = 'test.lockout@clinica.muelitas.com';
*/

-- ========================================
-- DESBLOQUEAR USUARIO (MANUAL)
-- ========================================

-- Desbloquear completamente y resetear contadores
/*
UPDATE users SET 
    status = 'active',
    failed_login_attempts = 0,
    lockout_count = 0,
    account_locked_until = NULL,
    last_lockout_date = NULL
WHERE email = 'test.lockout@clinica.muelitas.com';
*/

-- ========================================
-- LIMPIAR DATOS DE PRUEBA
-- ========================================

-- Eliminar usuario de prueba
/*
DELETE FROM users WHERE email = 'test.lockout@clinica.muelitas.com';
DELETE FROM login_attempts WHERE email = 'test.lockout@clinica.muelitas.com';
*/
