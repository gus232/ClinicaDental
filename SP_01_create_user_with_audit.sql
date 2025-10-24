-- Ejecuta este archivo PRIMERO en phpMyAdmin
USE hms_v2;

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
            contactno, reg_date, updation_date, status
        ) VALUES (
            p_full_name, p_address, p_city, p_gender, p_email, p_password,
            p_contactno, NOW(), NOW(), 1
        );

        SET p_new_user_id = LAST_INSERT_ID();

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

SELECT 'Procedure create_user_with_audit instalado correctamente' AS Resultado;
