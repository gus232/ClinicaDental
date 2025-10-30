-- ============================================================================
-- MIGRACIÓN 006: CONFIGURACIONES DE SESIONES Y ACCESO
-- ============================================================================
-- Descripción: Inserta configuraciones para gestión de sesiones automáticas
--              con timeouts, advertencias y función "Recordarme"
--
-- Versión: 2.4.0
-- Fecha: 2025-10-30
-- Proyecto: Hospital Management System - Session Management
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- PASO 1: INSERTAR CONFIGURACIONES DE SESIÓN
-- ============================================================================

-- Verificar que la tabla system_settings existe
-- Si no existe, crearla primero
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `setting_key` VARCHAR(100) UNIQUE NOT NULL,
    `setting_value` TEXT,
    `setting_category` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255),
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11),
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuraciones de sesión
-- Categoría: security (Seguridad y sesiones)

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_category`, `description`)
VALUES
    ('session_timeout_minutes', '30', 'security', 'Tiempo de inactividad en minutos antes de cerrar sesión automáticamente'),
    ('session_max_duration_hours', '8', 'security', 'Duración máxima de una sesión en horas (independiente de actividad)'),
    ('session_warning_minutes', '2', 'security', 'Minutos antes del timeout para mostrar advertencia al usuario'),
    ('remember_me_enabled', '1', 'security', 'Habilitar función "Recordarme" en login (1=sí, 0=no)'),
    ('remember_me_duration_days', '30', 'security', 'Duración de la cookie "Recordarme" en días')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    description = VALUES(description);

-- ============================================================================
-- PASO 2: CREAR TABLA PARA TOKENS "RECORDARME" (OPCIONAL)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `remember_me_tokens` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `token` VARCHAR(64) UNIQUE NOT NULL COMMENT 'Token único (hash SHA256)',
    `selector` VARCHAR(32) NOT NULL COMMENT 'Selector público del token',
    `expires_at` DATETIME NOT NULL COMMENT 'Fecha de expiración',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_used` TIMESTAMP NULL DEFAULT NULL,
    `user_agent` TEXT NULL COMMENT 'User agent del dispositivo',
    `ip_address` VARCHAR(45) NULL COMMENT 'IP desde donde se creó',

    -- Índices
    INDEX idx_user_id (user_id),
    INDEX idx_selector (selector),
    INDEX idx_expires_at (expires_at),

    -- Foreign Key
    CONSTRAINT fk_remember_tokens_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tokens para función "Recordarme" en login';

-- ============================================================================
-- PASO 3: PROCEDIMIENTO PARA LIMPIAR TOKENS EXPIRADOS
-- ============================================================================

DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS cleanup_expired_remember_tokens()
BEGIN
    -- Eliminar tokens expirados
    DELETE FROM remember_me_tokens
    WHERE expires_at < NOW();

    -- Retornar cantidad de tokens eliminados
    SELECT ROW_COUNT() as tokens_deleted;
END$$

DELIMITER ;

-- ============================================================================
-- PASO 4: EVENTO AUTOMÁTICO PARA LIMPIAR TOKENS (OPCIONAL)
-- ============================================================================

-- Habilitar event scheduler si no está habilitado
SET GLOBAL event_scheduler = ON;

-- Crear evento que limpia tokens expirados cada día
-- NOTA: Descomentar si deseas limpieza automática
/*
CREATE EVENT IF NOT EXISTS cleanup_remember_tokens_event
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
    CALL cleanup_expired_remember_tokens();
*/

-- ============================================================================
-- PASO 5: VERIFICAR INSTALACIÓN
-- ============================================================================

-- Mostrar configuraciones insertadas
SELECT
    setting_key,
    setting_value,
    setting_category,
    description
FROM system_settings
WHERE setting_category = 'security'
    AND setting_key LIKE 'session_%' OR setting_key LIKE 'remember_%'
ORDER BY setting_key;

-- ============================================================================
-- RESUMEN DE MIGRACIÓN
-- ============================================================================

SELECT '✓ Migración 006_session_settings.sql ejecutada exitosamente' AS status;
SELECT '✓ Configuraciones de sesión insertadas en system_settings' AS info;
SELECT '✓ Tabla remember_me_tokens creada' AS info;
SELECT '✓ Procedimiento de limpieza creado' AS info;
SELECT '⚠ IMPORTANTE: Implementar SessionManager.php para usar estas configuraciones' AS warning;

-- ============================================================================
-- NOTAS DE IMPLEMENTACIÓN
-- ============================================================================
/*
CONFIGURACIONES DISPONIBLES:

1. session_timeout_minutes (30)
   - Tiempo de inactividad antes de cerrar sesión
   - Rango recomendado: 5-120 minutos
   - Se resetea con cada acción del usuario

2. session_max_duration_hours (8)
   - Duración máxima total de sesión
   - Rango recomendado: 1-24 horas
   - NO se resetea con actividad

3. session_warning_minutes (2)
   - Tiempo de advertencia antes de timeout
   - Rango recomendado: 1-5 minutos
   - Muestra modal al usuario

4. remember_me_enabled (1)
   - Habilitar checkbox "Recordarme" en login
   - 1 = habilitado, 0 = deshabilitado

5. remember_me_duration_days (30)
   - Duración de cookie persistente
   - Rango recomendado: 7-90 días

USO EN PHP:

// Obtener configuración
$timeout_query = "SELECT setting_value FROM system_settings
                  WHERE setting_key = 'session_timeout_minutes'";
$result = mysqli_query($con, $timeout_query);
$row = mysqli_fetch_assoc($result);
$timeout_minutes = (int)$row['setting_value'];

// Calcular segundos
$timeout_seconds = $timeout_minutes * 60;

// Verificar timeout
if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    if ($inactive_time > $timeout_seconds) {
        // Sesión expirada
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
}

// Actualizar última actividad
$_SESSION['last_activity'] = time();

SEGURIDAD:

✅ Regenerar session_id después de login
✅ Usar HttpOnly en cookies
✅ Usar Secure flag en producción (HTTPS)
✅ SameSite=Strict para prevenir CSRF
✅ Tokens "Recordarme" únicos y hasheados
✅ Limpiar tokens expirados regularmente
✅ Registrar en user_logs con logout_reason

*/
