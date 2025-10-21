-- ============================================================================
-- STORED PROCEDURE: cleanup_old_security_data
-- ============================================================================
-- Limpia datos de seguridad antiguos (ejecutar periódicamente)

DROP PROCEDURE IF EXISTS cleanup_old_security_data;

CREATE PROCEDURE cleanup_old_security_data()
BEGIN
    -- Eliminar intentos de login mayores a 90 días
    DELETE FROM login_attempts
    WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

    -- Eliminar tokens de más de 7 días
    DELETE FROM password_reset_tokens
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);

    -- Eliminar historial de contraseñas excedente (mantener solo últimas 5 por usuario)
    DELETE ph1 FROM password_history ph1
    WHERE ph1.id NOT IN (
        SELECT id FROM (
            SELECT id FROM password_history ph2
            WHERE ph2.user_id = ph1.user_id
            ORDER BY changed_at DESC
            LIMIT 5
        ) AS keep_records
    );

    SELECT 'Limpieza completada exitosamente' AS message;
END;
