-- ============================================================================
-- INSTALADOR DE STORED PROCEDURES - FASE 3 (VERSIÓN CORREGIDA)
-- ============================================================================
-- Este archivo instala los 4 stored procedures nuevos de FASE 3
-- CORRECCIÓN: Eliminadas TODAS las referencias a contactno
-- Ejecutar en phpMyAdmin o MySQL Workbench
-- ============================================================================

USE hospital;

-- ============================================================================
-- SP 1: create_user_with_audit
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

    SELECT COUNT(*) INTO v_email_exists
    FROM users
    WHERE email = p_email;

    IF v_email_exists > 0 THEN
        SET p_new_user_id = -1;
        ROLLBACK;
    ELSE
        INSERT INTO users (
            full_name, address, city, gender, email, password,
            reg_date, updation_date, status
        ) VALUES (
            p_full_name, p_address, p_city, p_gender, p_email, p_password,
            NOW(), NOW(), 1
        );

        SET p_new_user_id = LAST_INSERT_ID();

        INSERT INTO user_change_history (
            user_id, changed_by, change_type, change_reason, ip_address, created_at
        ) VALUES (
            p_new_user_id, p_created_by, 'create', COALESCE(p_reason, 'Usuario creado'), p_ip_address, NOW()
        );

        COMMIT;
    END IF;
END$$

DELIMITER ;

SELECT 'SP create_user_with_audit instalado correctamente ✓' as Status;

-- ============================================================================
-- SP 2: update_user_with_history
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
    DECLARE v_old_status TINYINT;
    DECLARE v_email_exists INT DEFAULT 0;
    DECLARE v_changes_made INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_result = 0;
    END;

    START TRANSACTION;

    SELECT full_name, address, city, gender, email, status
    INTO v_old_full_name, v_old_address, v_old_city, v_old_gender, v_old_email, v_old_status
    FROM users WHERE id = p_user_id;

    IF p_email IS NOT NULL AND p_email != v_old_email THEN
        SELECT COUNT(*) INTO v_email_exists
        FROM users WHERE email = p_email AND id != p_user_id;

        IF v_email_exists > 0 THEN
            SET p_result = -1;
            ROLLBACK;
            LEAVE proc_label;
        END IF;
    END IF;

    proc_label: BEGIN
        IF p_full_name IS NOT NULL AND p_full_name != v_old_full_name THEN
            UPDATE users SET full_name = p_full_name, updation_date = NOW() WHERE id = p_user_id;
            INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
            VALUES (p_user_id, p_updated_by, 'update', 'full_name', v_old_full_name, p_full_name, p_reason, p_ip_address);
            SET v_changes_made = v_changes_made + 1;
        END IF;

        IF p_address IS NOT NULL AND p_address != v_old_address THEN
            UPDATE users SET address = p_address, updation_date = NOW() WHERE id = p_user_id;
            INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
            VALUES (p_user_id, p_updated_by, 'update', 'address', v_old_address, p_address, p_reason, p_ip_address);
            SET v_changes_made = v_changes_made + 1;
        END IF;

        IF p_city IS NOT NULL AND p_city != v_old_city THEN
            UPDATE users SET city = p_city, updation_date = NOW() WHERE id = p_user_id;
            INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
            VALUES (p_user_id, p_updated_by, 'update', 'city', v_old_city, p_city, p_reason, p_ip_address);
            SET v_changes_made = v_changes_made + 1;
        END IF;

        IF p_gender IS NOT NULL AND p_gender != v_old_gender THEN
            UPDATE users SET gender = p_gender, updation_date = NOW() WHERE id = p_user_id;
            INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
            VALUES (p_user_id, p_updated_by, 'update', 'gender', v_old_gender, p_gender, p_reason, p_ip_address);
            SET v_changes_made = v_changes_made + 1;
        END IF;

        IF p_email IS NOT NULL AND p_email != v_old_email THEN
            UPDATE users SET email = p_email, updation_date = NOW() WHERE id = p_user_id;
            INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
            VALUES (p_user_id, p_updated_by, 'update', 'email', v_old_email, p_email, p_reason, p_ip_address);
            SET v_changes_made = v_changes_made + 1;
        END IF;

        IF p_status IS NOT NULL AND p_status != v_old_status THEN
            UPDATE users SET status = p_status, updation_date = NOW() WHERE id = p_user_id;
            INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason, ip_address)
            VALUES (p_user_id, p_updated_by, 'status_change', 'status', v_old_status, p_status, p_reason, p_ip_address);
            SET v_changes_made = v_changes_made + 1;
        END IF;

        IF v_changes_made > 0 THEN
            COMMIT;
            SET p_result = 1;
        ELSE
            ROLLBACK;
            SET p_result = 2;
        END IF;
    END;
END$$

DELIMITER ;

SELECT 'SP update_user_with_history instalado correctamente ✓' as Status;

-- ============================================================================
-- SP 3: search_users
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

    IF p_search_term IS NOT NULL AND LENGTH(TRIM(p_search_term)) > 0 THEN
        SET v_search_pattern = CONCAT('%', p_search_term, '%');
    ELSE
        SET v_search_pattern = '%';
    END IF;

    IF p_limit IS NULL OR p_limit <= 0 THEN
        SET p_limit = 50;
    END IF;

    IF p_offset IS NULL OR p_offset < 0 THEN
        SET p_offset = 0;
    END IF;

    SELECT DISTINCT
        u.id,
        u.full_name,
        u.address,
        u.city,
        u.gender,
        u.email,
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
            OR u.city LIKE v_search_pattern
        )
        AND (p_status IS NULL OR u.status = p_status)
        AND (p_gender IS NULL OR u.gender = p_gender)
        AND (p_city IS NULL OR u.city LIKE CONCAT('%', p_city, '%'))
        AND (p_role_id IS NULL OR ur.role_id = p_role_id)
    GROUP BY u.id, u.full_name, u.address, u.city, u.gender, u.email, u.reg_date, u.updation_date, u.status
    ORDER BY u.full_name ASC
    LIMIT p_limit OFFSET p_offset;
END$$

DELIMITER ;

SELECT 'SP search_users instalado correctamente ✓' as Status;

-- ============================================================================
-- SP 4: get_user_statistics
-- ============================================================================

DROP PROCEDURE IF EXISTS get_user_statistics;

DELIMITER $$

CREATE PROCEDURE get_user_statistics()
BEGIN
    SELECT
        COUNT(*) as total_users,
        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as inactive_users,
        SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as male_users,
        SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female_users,
        SUM(CASE WHEN gender = 'Other' THEN 1 ELSE 0 END) as other_gender_users,
        SUM(CASE WHEN reg_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as users_last_7_days,
        SUM(CASE WHEN reg_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as users_last_30_days,
        (SELECT COUNT(*) FROM user_sessions WHERE is_active = 1 AND expires_at > NOW()) as active_sessions,
        (SELECT COUNT(*) FROM user_change_history WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as changes_last_24h,
        (SELECT COUNT(*) FROM user_change_history WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as changes_last_7_days
    FROM users;
END$$

DELIMITER ;

SELECT 'SP get_user_statistics instalado correctamente ✓' as Status;

-- ============================================================================
-- RESUMEN
-- ============================================================================

SELECT '===== INSTALACIÓN COMPLETADA =====' as '';
SELECT 'Total de Stored Procedures instalados: 4' as '';
SELECT '¡Todas las referencias a contactno eliminadas!' as '✓ FIXED';

SHOW PROCEDURE STATUS WHERE Db = 'hospital' AND Name IN (
    'create_user_with_audit',
    'update_user_with_history',
    'search_users',
    'get_user_statistics'
);
