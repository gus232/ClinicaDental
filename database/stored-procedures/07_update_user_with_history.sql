-- ============================================================================
-- STORED PROCEDURE: update_user_with_history
-- ============================================================================
-- Descripción: Actualiza usuario y registra cambios en historial
-- Parámetros:
--   - p_user_id: ID del usuario a actualizar
--   - p_full_name: Nuevo nombre (NULL = no cambiar)
--   - p_address: Nueva dirección (NULL = no cambiar)
--   - p_city: Nueva ciudad (NULL = no cambiar)
--   - p_gender: Nuevo género (NULL = no cambiar)
--   - p_email: Nuevo email (NULL = no cambiar)
--   - p_contactno: Nuevo teléfono (NULL = no cambiar)
--   - p_status: Nuevo estado (NULL = no cambiar)
--   - p_updated_by: ID del usuario que hace el cambio
--   - p_ip_address: IP desde donde se actualiza
--   - p_reason: Razón del cambio
-- Retorna: 1 = éxito, 0 = error, -1 = email duplicado
-- ============================================================================

DROP PROCEDURE IF EXISTS update_user_with_history;

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
END;
