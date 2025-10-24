-- ============================================================================
-- INSTALACIÓN DE STORED PROCEDURES CRÍTICOS
-- ============================================================================
-- INSTRUCCIONES:
-- 1. Abre phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Selecciona la base de datos "hms_v2"
-- 3. Ve a la pestaña "SQL"
-- 4. Copia TODO este archivo y pégalo en el editor
-- 5. Presiona "Continuar" para ejecutar
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- STORED PROCEDURE: create_user_with_audit
-- ============================================================================

DROP PROCEDURE IF EXISTS create_user_with_audit;

DELIMITER $$

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
        ROLLBACK;
        SET p_new_user_id = 0;
    END;

    START TRANSACTION;

    -- Verificar que el email no exista
    SELECT COUNT(*) INTO v_email_exists
    FROM users
    WHERE email = p_email;

    IF v_email_exists > 0 THEN
        SET p_new_user_id = -1; -- Email duplicado
        ROLLBACK;
    ELSE
        -- Insertar el nuevo usuario
        INSERT INTO users (
            full_name, address, city, gender, email, password,
            contactno, reg_date, updation_date, status
        ) VALUES (
            p_full_name, p_address, p_city, p_gender, p_email, p_password,
            p_contactno, NOW(), NOW(), 1
        );

        SET p_new_user_id = LAST_INSERT_ID();

        -- Registrar en historial
        INSERT INTO user_change_history (
            user_id, changed_by, change_type, change_reason, ip_address, created_at
        ) VALUES (
            p_new_user_id, p_created_by, 'create',
            COALESCE(p_reason, 'Usuario creado'), p_ip_address, NOW()
        );

        COMMIT;
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- STORED PROCEDURE: update_user_with_history
-- ============================================================================

DROP PROCEDURE IF EXISTS update_user_with_history;

DELIMITER $$

CREATE PROCEDURE update_user_with_history(
    IN p_user_id INT,
    IN p_full_name VARCHAR(255),
    IN p_address VARCHAR(255),
    IN p_city VARCHAR(255),
    IN p_gender VARCHAR(10),
    IN p_email VARCHAR(255),
    IN p_contactno VARCHAR(20),
    IN p_status TINYINT,
    IN p_updated_by INT,
    IN p_ip_address VARCHAR(45),
    IN p_reason VARCHAR(255),
    OUT p_result INT
)
BEGIN
    DECLARE v_old_full_name VARCHAR(255);
    DECLARE v_old_address VARCHAR(255);
    DECLARE v_old_city VARCHAR(255);
    DECLARE v_old_gender VARCHAR(10);
    DECLARE v_old_email VARCHAR(255);
    DECLARE v_old_contactno VARCHAR(20);
    DECLARE v_old_status TINYINT;
    DECLARE v_email_exists INT DEFAULT 0;
    DECLARE v_changes_made INT DEFAULT 0;

    -- Handler para errores
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_result = 0;
    END;

    -- Iniciar transacción
    START TRANSACTION;

    -- Obtener valores actuales del usuario
    SELECT full_name, address, city, gender, email, contactno, status
    INTO v_old_full_name, v_old_address, v_old_city, v_old_gender, v_old_email, v_old_contactno, v_old_status
    FROM users
    WHERE id = p_user_id;

    -- Verificar si el nuevo email ya existe (si se está cambiando)
    IF p_email IS NOT NULL AND p_email != v_old_email THEN
        SELECT COUNT(*) INTO v_email_exists
        FROM users
        WHERE email = p_email AND id != p_user_id;

        IF v_email_exists > 0 THEN
            SET p_result = -1; -- Email duplicado
            ROLLBACK;
            LEAVE proc_label;
        END IF;
    END IF;

    proc_label: BEGIN
        -- Actualizar full_name si cambió
        IF p_full_name IS NOT NULL AND p_full_name != v_old_full_name THEN
            UPDATE users SET full_name = p_full_name, updation_date = NOW() WHERE id = p_user_id;
            INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
            VALUES (p_user_id, p_updated_by, 'update', 'full_name', v_old_full_name, p_full_name, p_reason, p_ip_address);
            SET v_changes_made = v_changes_made + 1;
        END IF;

        -- Actualizar address si cambió
        IF p_address IS NOT NULL AND p_address != v_old_address THEN
            UPDATE users SET address = p_address, updation_date = NOW() WHERE id = p_user_id;
            INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
            VALUES (p_user_id, p_updated_by, 'update', 'address', v_old_address, p_address, p_reason, p_ip_address);
            SET v_changes_made = v_changes_made + 1;
        END IF;

        -- Actualizar city si cambió
        IF p_city IS NOT NULL AND p_city != v_old_city THEN
            UPDATE users SET city = p_city, updation_date = NOW() WHERE id = p_user_id;
            INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
            VALUES (p_user_id, p_updated_by, 'update', 'city', v_old_city, p_city, p_reason, p_ip_address);
            SET v_changes_made = v_changes_made + 1;
        END IF;

        -- Actualizar gender si cambió
        IF p_gender IS NOT NULL AND p_gender != v_old_gender THEN
            UPDATE users SET gender = p_gender, updation_date = NOW() WHERE id = p_user_id;
            INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
            VALUES (p_user_id, p_updated_by, 'update', 'gender', v_old_gender, p_gender, p_reason, p_ip_address);
            SET v_changes_made = v_changes_made + 1;
        END IF;

        -- Actualizar email si cambió
        IF p_email IS NOT NULL AND p_email != v_old_email THEN
            UPDATE users SET email = p_email, updation_date = NOW() WHERE id = p_user_id;
            INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
            VALUES (p_user_id, p_updated_by, 'update', 'email', v_old_email, p_email, p_reason, p_ip_address);
            SET v_changes_made = v_changes_made + 1;
        END IF;

        -- Actualizar contactno si cambió
        IF p_contactno IS NOT NULL AND p_contactno != v_old_contactno THEN
            UPDATE users SET contactno = p_contactno, updation_date = NOW() WHERE id = p_user_id;
            INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
            VALUES (p_user_id, p_updated_by, 'update', 'contactno', v_old_contactno, p_contactno, p_reason, p_ip_address);
            SET v_changes_made = v_changes_made + 1;
        END IF;

        -- Actualizar status si cambió
        IF p_status IS NOT NULL AND p_status != v_old_status THEN
            UPDATE users SET status = p_status, updation_date = NOW() WHERE id = p_user_id;
            INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
            VALUES (p_user_id, p_updated_by, 'status_change', 'status', v_old_status, p_status, p_reason, p_ip_address);
            SET v_changes_made = v_changes_made + 1;
        END IF;

        -- Commit si hubo cambios
        IF v_changes_made > 0 THEN
            COMMIT;
            SET p_result = 1; -- Éxito
        ELSE
            ROLLBACK;
            SET p_result = 2; -- No hubo cambios
        END IF;
    END;
END$$

DELIMITER ;

-- ============================================================================
-- STORED PROCEDURE: search_users
-- ============================================================================

DROP PROCEDURE IF EXISTS search_users;

DELIMITER $$

CREATE PROCEDURE search_users(
    IN p_search_term VARCHAR(255),
    IN p_role_id INT,
    IN p_status TINYINT,
    IN p_gender VARCHAR(10),
    IN p_city VARCHAR(255),
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    DECLARE v_search_pattern VARCHAR(257);

    -- Preparar patrón de búsqueda
    IF p_search_term IS NOT NULL AND LENGTH(TRIM(p_search_term)) > 0 THEN
        SET v_search_pattern = CONCAT('%', p_search_term, '%');
    ELSE
        SET v_search_pattern = '%';
    END IF;

    -- Establecer límite por defecto
    IF p_limit IS NULL OR p_limit <= 0 THEN
        SET p_limit = 50;
    END IF;

    -- Establecer offset por defecto
    IF p_offset IS NULL OR p_offset < 0 THEN
        SET p_offset = 0;
    END IF;

    -- Búsqueda con todos los filtros
    SELECT DISTINCT
        u.id,
        u.full_name,
        u.address,
        u.city,
        u.gender,
        u.email,
        u.contactno,
        u.reg_date,
        u.updation_date,
        u.status,
        GROUP_CONCAT(DISTINCT r.display_name ORDER BY r.priority SEPARATOR ', ') as roles,
        GROUP_CONCAT(DISTINCT r.id ORDER BY r.priority) as role_ids,
        (SELECT COUNT(*) FROM user_change_history WHERE user_id = u.id) as total_changes,
        (SELECT COUNT(*) FROM user_sessions WHERE user_id = u.id AND is_active = 1 AND expires_at > NOW()) as active_sessions
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_active = 1
    LEFT JOIN roles r ON ur.role_id = r.id AND r.status = 'active'
    WHERE
        (
            u.full_name LIKE v_search_pattern
            OR u.email LIKE v_search_pattern
            OR u.contactno LIKE v_search_pattern
            OR u.city LIKE v_search_pattern
        )
        AND (p_status IS NULL OR u.status = p_status)
        AND (p_gender IS NULL OR u.gender = p_gender)
        AND (p_city IS NULL OR u.city LIKE CONCAT('%', p_city, '%'))
        AND (p_role_id IS NULL OR ur.role_id = p_role_id)
    GROUP BY u.id, u.full_name, u.address, u.city, u.gender, u.email, u.contactno, u.reg_date, u.updation_date, u.status
    ORDER BY u.full_name ASC
    LIMIT p_limit OFFSET p_offset;
END$$

DELIMITER ;

-- ============================================================================
-- VERIFICACIÓN
-- ============================================================================
SELECT 'Stored procedures instalados exitosamente' AS status;

-- Verificar que existen
SELECT 
    ROUTINE_NAME, 
    ROUTINE_TYPE,
    CREATED
FROM INFORMATION_SCHEMA.ROUTINES 
WHERE ROUTINE_SCHEMA = 'hms_v2' 
AND ROUTINE_NAME IN ('create_user_with_audit', 'update_user_with_history', 'search_users')
ORDER BY ROUTINE_NAME;
