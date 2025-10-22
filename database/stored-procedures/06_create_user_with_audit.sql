-- ============================================================================
-- STORED PROCEDURE: create_user_with_audit
-- ============================================================================
-- Descripción: Crea un nuevo usuario con auditoría completa
-- Parámetros:
--   - p_full_name: Nombre completo del usuario
--   - p_address: Dirección
--   - p_city: Ciudad
--   - p_gender: Género (Male/Female/Other)
--   - p_email: Email (único)
--   - p_password: Password (ya hasheado con bcrypt)
--   - p_contactno: Número de contacto
--   - p_created_by: ID del usuario que crea este usuario
--   - p_ip_address: IP desde donde se crea
--   - p_reason: Razón de creación (opcional)
-- Retorna: ID del nuevo usuario o 0 si falla
-- ============================================================================

DROP PROCEDURE IF EXISTS create_user_with_audit;

CREATE PROCEDURE create_user_with_audit(
    IN p_full_name VARCHAR(255),
    IN p_address VARCHAR(255),
    IN p_city VARCHAR(255),
    IN p_gender VARCHAR(10),
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_contactno VARCHAR(20),
    IN p_created_by INT,
    IN p_ip_address VARCHAR(45),
    IN p_reason VARCHAR(255),
    OUT p_new_user_id INT
)
BEGIN
    DECLARE v_email_exists INT DEFAULT 0;
    DECLARE v_error_msg VARCHAR(255);

    -- Handler para errores
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        -- Rollback en caso de error
        ROLLBACK;
        SET p_new_user_id = 0;
    END;

    -- Iniciar transacción
    START TRANSACTION;

    -- Verificar que el email no exista
    SELECT COUNT(*) INTO v_email_exists
    FROM users
    WHERE email = p_email;

    IF v_email_exists > 0 THEN
        SET p_new_user_id = -1; -- Código de error: email duplicado
        ROLLBACK;
    ELSE
        -- Insertar el nuevo usuario
        INSERT INTO users (
            full_name,
            address,
            city,
            gender,
            email,
            password,
            contactno,
            reg_date,
            updation_date,
            status
        ) VALUES (
            p_full_name,
            p_address,
            p_city,
            p_gender,
            p_email,
            p_password,
            p_contactno,
            NOW(),
            NOW(),
            1 -- Activo por defecto
        );

        -- Obtener el ID del usuario recién creado
        SET p_new_user_id = LAST_INSERT_ID();

        -- Registrar en historial de cambios
        INSERT INTO user_change_history (
            user_id,
            changed_by,
            change_type,
            change_reason,
            ip_address,
            created_at
        ) VALUES (
            p_new_user_id,
            p_created_by,
            'create',
            COALESCE(p_reason, 'Usuario creado'),
            p_ip_address,
            NOW()
        );

        -- Commit de la transacción
        COMMIT;
    END IF;
END;
