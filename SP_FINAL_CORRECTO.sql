-- ============================================================================
-- STORED PROCEDURES CORRECTOS PARA ESTRUCTURA REAL DE users
-- ============================================================================
-- Ejecuta TODO este archivo en phpMyAdmin
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- SP 1: create_user_with_audit
-- ============================================================================
DROP PROCEDURE IF EXISTS create_user_with_audit;

DELIMITER $$

CREATE PROCEDURE create_user_with_audit(
    IN p_full_name VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_user_type VARCHAR(20),
    IN p_created_by INT,
    IN p_ip_address VARCHAR(45),
    IN p_reason VARCHAR(255),
    OUT p_new_user_id INT
)
BEGIN
    DECLARE v_email_exists INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_new_user_id = 0;
    END;

    START TRANSACTION;

    -- Verificar email duplicado
    SELECT COUNT(*) INTO v_email_exists
    FROM users
    WHERE email = p_email;

    IF v_email_exists > 0 THEN
        SET p_new_user_id = -1;
        ROLLBACK;
    ELSE
        -- Insertar nuevo usuario
        INSERT INTO users (
            full_name, email, password, user_type, status, created_at, updated_at
        ) VALUES (
            p_full_name, p_email, p_password, p_user_type, 'active', NOW(), NOW()
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
-- SP 2: update_user_with_history
-- ============================================================================
DROP PROCEDURE IF EXISTS update_user_with_history;

DELIMITER $$

CREATE PROCEDURE update_user_with_history(
    IN p_user_id INT,
    IN p_full_name VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_status VARCHAR(20),
    IN p_updated_by INT,
    IN p_ip_address VARCHAR(45),
    IN p_reason VARCHAR(255),
    OUT p_result INT
)
proc_label: BEGIN
    DECLARE v_old_full_name VARCHAR(255);
    DECLARE v_old_email VARCHAR(255);
    DECLARE v_old_status VARCHAR(20);
    DECLARE v_email_exists INT DEFAULT 0;
    DECLARE v_changes_made INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_result = 0;
    END;

    START TRANSACTION;

    -- Obtener valores actuales
    SELECT full_name, email, status
    INTO v_old_full_name, v_old_email, v_old_status
    FROM users
    WHERE id = p_user_id;

    -- Verificar email duplicado
    IF p_email IS NOT NULL AND p_email != v_old_email THEN
        SELECT COUNT(*) INTO v_email_exists
        FROM users
        WHERE email = p_email AND id != p_user_id;

        IF v_email_exists > 0 THEN
            SET p_result = -1;
            ROLLBACK;
            LEAVE proc_label;
        END IF;
    END IF;

    -- Actualizar full_name
    IF p_full_name IS NOT NULL AND p_full_name != v_old_full_name THEN
        UPDATE users SET full_name = p_full_name, updated_at = NOW() WHERE id = p_user_id;
        INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
        VALUES (p_user_id, p_updated_by, 'update', 'full_name', v_old_full_name, p_full_name, p_reason, p_ip_address);
        SET v_changes_made = v_changes_made + 1;
    END IF;

    -- Actualizar email
    IF p_email IS NOT NULL AND p_email != v_old_email THEN
        UPDATE users SET email = p_email, updated_at = NOW() WHERE id = p_user_id;
        INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
        VALUES (p_user_id, p_updated_by, 'update', 'email', v_old_email, p_email, p_reason, p_ip_address);
        SET v_changes_made = v_changes_made + 1;
    END IF;

    -- Actualizar status
    IF p_status IS NOT NULL AND p_status != v_old_status THEN
        UPDATE users SET status = p_status, updated_at = NOW() WHERE id = p_user_id;
        INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
        VALUES (p_user_id, p_updated_by, 'status_change', 'status', v_old_status, p_status, p_reason, p_ip_address);
        SET v_changes_made = v_changes_made + 1;
    END IF;

    -- Resultado
    IF v_changes_made > 0 THEN
        COMMIT;
        SET p_result = 1;
    ELSE
        ROLLBACK;
        SET p_result = 2;
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- SP 3: assign_role_to_user
-- ============================================================================
DROP PROCEDURE IF EXISTS assign_role_to_user;

DELIMITER $$

CREATE PROCEDURE assign_role_to_user(
    IN p_user_id INT,
    IN p_role_id INT,
    IN p_assigned_by INT,
    IN p_expires_at DATETIME
)
BEGIN
    DECLARE v_user_exists INT DEFAULT 0;
    DECLARE v_role_exists INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;

    -- Verificar que usuario exista
    SELECT COUNT(*) INTO v_user_exists FROM users WHERE id = p_user_id;
    
    IF v_user_exists = 0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario no encontrado';
    END IF;

    -- Verificar que rol exista
    SELECT COUNT(*) INTO v_role_exists FROM roles WHERE id = p_role_id;
    
    IF v_role_exists = 0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Rol no encontrado';
    END IF;

    -- Insertar o actualizar en user_roles
    INSERT INTO user_roles (user_id, role_id, assigned_by, assigned_at, expires_at, is_active)
    VALUES (p_user_id, p_role_id, p_assigned_by, NOW(), p_expires_at, 1)
    ON DUPLICATE KEY UPDATE
        assigned_by = p_assigned_by,
        assigned_at = NOW(),
        expires_at = p_expires_at,
        is_active = 1;

    -- Registrar en auditoría (si la tabla existe)
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'hms_v2' AND table_name = 'audit_role_changes') THEN
        INSERT INTO audit_role_changes (user_id, role_id, action, performed_by, created_at)
        VALUES (p_user_id, p_role_id, 'assigned', p_assigned_by, NOW());
    END IF;

    COMMIT;
END$$

DELIMITER ;

-- ============================================================================
-- SP 4: revoke_role_from_user
-- ============================================================================
DROP PROCEDURE IF EXISTS revoke_role_from_user;

DELIMITER $$

CREATE PROCEDURE revoke_role_from_user(
    IN p_user_id INT,
    IN p_role_id INT,
    IN p_revoked_by INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;

    -- Desactivar rol
    UPDATE user_roles
    SET is_active = 0, revoked_at = NOW(), revoked_by = p_revoked_by
    WHERE user_id = p_user_id AND role_id = p_role_id;

    -- Registrar en auditoría (si la tabla existe)
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'hms_v2' AND table_name = 'audit_role_changes') THEN
        INSERT INTO audit_role_changes (user_id, role_id, action, performed_by, created_at)
        VALUES (p_user_id, p_role_id, 'revoked', p_revoked_by, NOW());
    END IF;

    COMMIT;
END$$

DELIMITER ;

-- ============================================================================
-- VERIFICACIÓN
-- ============================================================================
SELECT 'Stored procedures instalados correctamente!' AS Estado;

SHOW PROCEDURE STATUS WHERE Db = 'hms_v2' AND Name IN ('create_user_with_audit', 'update_user_with_history', 'assign_role_to_user', 'revoke_role_from_user');
