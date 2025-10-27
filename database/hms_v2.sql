-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 27, 2025 at 02:56 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hms_v2`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `assign_role_to_user` (IN `p_user_id` INT, IN `p_role_id` INT, IN `p_assigned_by` INT, IN `p_expires_at` DATETIME)   BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `create_user_with_audit` (IN `p_full_name` VARCHAR(255), IN `p_email` VARCHAR(255), IN `p_password` VARCHAR(255), IN `p_user_type` VARCHAR(20), IN `p_created_by` INT, IN `p_ip_address` VARCHAR(45), IN `p_reason` VARCHAR(255), OUT `p_new_user_id` INT)   BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_user_statistics` ()   BEGIN
    
    SELECT
        
        COUNT(*) as total_users,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
        SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_users,

        
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as users_last_7_days,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as users_last_30_days,

        
        (SELECT COUNT(*) 
         FROM information_schema.tables 
         WHERE table_schema = DATABASE() 
         AND table_name = 'user_change_history') as has_audit_table,
        
        
        (SELECT COUNT(*) 
         FROM information_schema.tables 
         WHERE table_schema = DATABASE() 
         AND table_name = 'user_sessions') as has_sessions_table

    FROM users;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `revoke_role_from_user` (IN `p_user_id` INT, IN `p_role_id` INT, IN `p_revoked_by` INT)   BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `search_users` (IN `p_search_term` VARCHAR(255), IN `p_role_id` INT, IN `p_status` TINYINT, IN `p_gender` VARCHAR(10), IN `p_city` VARCHAR(255), IN `p_limit` INT, IN `p_offset` INT)   BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `update_user_with_history` (IN `p_user_id` INT, IN `p_full_name` VARCHAR(255), IN `p_email` VARCHAR(255), IN `p_status` VARCHAR(20), IN `p_updated_by` INT, IN `p_ip_address` VARCHAR(45), IN `p_reason` VARCHAR(255), OUT `p_result` INT)   proc_label: BEGIN
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

-- --------------------------------------------------------

--
-- Stand-in structure for view `access_attempts_by_ip`
-- (See below for the actual view)
--
CREATE TABLE `access_attempts_by_ip` (
`ip_address` varchar(45)
,`total_attempts` bigint(21)
,`unique_users` bigint(21)
,`last_attempt` timestamp
,`unauthorized_attempts` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `active_sessions_view`
-- (See below for the actual view)
--
CREATE TABLE `active_sessions_view` (
`session_id` int(11)
,`user_id` int(11)
,`full_name` varchar(255)
,`email` varchar(255)
,`roles` mediumtext
,`ip_address` varchar(45)
,`login_at` timestamp
,`last_activity` timestamp
,`expires_at` timestamp
,`minutes_idle` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `active_users_summary`
-- (See below for the actual view)
--
CREATE TABLE `active_users_summary` (
`id` int(11)
,`full_name` varchar(255)
,`email` varchar(255)
,`status` enum('active','inactive','blocked')
,`roles` mediumtext
,`active_sessions` bigint(21)
,`last_seen` timestamp
,`total_changes` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `permissions` longtext DEFAULT NULL CHECK (json_valid(`permissions`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `user_id`, `username`, `permissions`) VALUES
(2, 8, 'admin', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `id` int(11) NOT NULL,
  `doctorSpecialization` varchar(255) DEFAULT NULL,
  `doctorId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `consultancyFees` int(11) DEFAULT NULL,
  `appointmentDate` varchar(255) DEFAULT NULL,
  `appointmentTime` varchar(255) DEFAULT NULL,
  `postingDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `userStatus` int(11) DEFAULT NULL,
  `doctorStatus` int(11) DEFAULT NULL,
  `updationDate` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_role_changes`
--

CREATE TABLE `audit_role_changes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Usuario afectado (NULL = cambio en el rol)',
  `role_id` int(11) NOT NULL COMMENT 'Rol modificado',
  `action` enum('assigned','revoked','role_updated','permission_changed') NOT NULL,
  `performed_by` int(11) DEFAULT NULL COMMENT 'Admin que realizó la acción',
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `details` longtext DEFAULT NULL COMMENT 'Información adicional del cambio' CHECK (json_valid(`details`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auditoría de cambios en roles y permisos';

--
-- Dumping data for table `audit_role_changes`
--

INSERT INTO `audit_role_changes` (`id`, `user_id`, `role_id`, `action`, `performed_by`, `performed_at`, `ip_address`, `user_agent`, `details`) VALUES
(23, NULL, 3, '', NULL, '2025-10-23 02:06:17', NULL, NULL, NULL),
(24, NULL, 3, '', NULL, '2025-10-23 02:14:15', NULL, NULL, NULL),
(25, NULL, 3, '', NULL, '2025-10-23 02:14:18', NULL, NULL, NULL),
(26, NULL, 2, '', NULL, '2025-10-23 02:16:03', NULL, NULL, NULL),
(27, NULL, 2, '', NULL, '2025-10-23 02:22:16', NULL, NULL, NULL),
(28, NULL, 2, '', NULL, '2025-10-23 02:27:31', NULL, NULL, NULL),
(29, NULL, 2, '', NULL, '2025-10-23 02:32:59', NULL, NULL, NULL),
(31, NULL, 1, '', 8, '2025-10-24 00:49:32', NULL, NULL, NULL),
(32, NULL, 2, '', 8, '2025-10-24 00:56:32', NULL, NULL, NULL),
(33, NULL, 1, '', 8, '2025-10-24 00:57:07', NULL, NULL, NULL),
(34, 25, 4, 'assigned', 8, '2025-10-24 01:00:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(35, 26, 3, 'assigned', 8, '2025-10-24 01:15:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(36, 24, 4, 'assigned', 8, '2025-10-24 01:15:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(37, 28, 4, 'assigned', 28, '2025-10-24 01:47:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(38, 29, 3, 'assigned', 8, '2025-10-24 01:52:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(39, 30, 4, 'assigned', 8, '2025-10-24 02:36:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(40, 24, 4, 'revoked', 8, '2025-10-24 02:45:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(41, 24, 1, 'assigned', 8, '2025-10-24 02:45:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(42, 24, 1, 'revoked', 8, '2025-10-24 03:29:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(43, 24, 4, 'assigned', 8, '2025-10-24 03:29:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(44, 30, 4, 'revoked', 8, '2025-10-24 03:31:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(45, 30, 1, 'assigned', 8, '2025-10-24 03:31:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(46, NULL, 1, '', 8, '2025-10-24 03:35:56', NULL, NULL, NULL),
(47, NULL, 1, '', 8, '2025-10-24 03:37:10', NULL, NULL, NULL),
(48, 31, 10, 'assigned', 8, '2025-10-26 23:30:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(49, 30, 1, 'revoked', 8, '2025-10-26 23:30:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(50, 30, 4, 'assigned', 8, '2025-10-26 23:30:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL),
(51, NULL, 1, '', 8, '2025-10-26 23:34:40', NULL, NULL, NULL),
(52, NULL, 10, '', 8, '2025-10-26 23:35:40', NULL, NULL, NULL),
(53, NULL, 2, '', 8, '2025-10-26 23:36:48', NULL, NULL, NULL),
(54, NULL, 3, '', 8, '2025-10-26 23:38:13', NULL, NULL, NULL),
(55, NULL, 6, '', 8, '2025-10-26 23:40:03', NULL, NULL, NULL),
(56, NULL, 5, '', 8, '2025-10-26 23:42:12', NULL, NULL, NULL),
(57, NULL, 7, '', 8, '2025-10-26 23:43:30', NULL, NULL, NULL),
(58, NULL, 4, '', 8, '2025-10-26 23:44:55', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `specilization` varchar(255) DEFAULT NULL,
  `doctorName` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `docFees` varchar(255) DEFAULT NULL,
  `contactno` bigint(11) DEFAULT NULL,
  `docEmail` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `creationDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `updationDate` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctorslog`
--

CREATE TABLE `doctorslog` (
  `id` int(11) NOT NULL,
  `uid` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `userip` binary(16) DEFAULT NULL,
  `loginTime` timestamp NULL DEFAULT current_timestamp(),
  `logout` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctorspecilization`
--

CREATE TABLE `doctorspecilization` (
  `id` int(11) NOT NULL,
  `specilization` varchar(255) DEFAULT NULL,
  `creationDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `updationDate` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctorspecilization`
--

INSERT INTO `doctorspecilization` (`id`, `specilization`, `creationDate`, `updationDate`) VALUES
(1, 'Dentist', '2025-10-15 02:38:35', NULL),
(2, 'Orthodontist', '2025-10-15 02:38:35', NULL),
(3, 'Endodontist', '2025-10-15 02:38:35', NULL),
(4, 'Periodontist', '2025-10-15 02:38:35', NULL),
(5, 'Oral Surgeon', '2025-10-15 02:38:35', NULL),
(6, 'Pediatric Dentist', '2025-10-15 02:38:35', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `expiring_user_roles`
-- (See below for the actual view)
--
CREATE TABLE `expiring_user_roles` (
`id` int(11)
,`user_id` int(11)
,`full_name` varchar(255)
,`email` varchar(255)
,`role_id` int(11)
,`role_name` varchar(100)
,`assigned_at` timestamp
,`expires_at` datetime
,`days_until_expiration` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `locked_accounts`
-- (See below for the actual view)
--
CREATE TABLE `locked_accounts` (
`id` int(11)
,`email` varchar(255)
,`full_name` varchar(255)
,`user_type` enum('patient','doctor','admin')
,`failed_login_attempts` int(11)
,`account_locked_until` datetime
,`last_login` timestamp
,`last_login_ip` varchar(45)
,`lock_status` varchar(8)
,`minutes_remaining` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL COMMENT 'Email usado en el intento',
  `user_id` int(11) DEFAULT NULL COMMENT 'ID del usuario (NULL si no existe)',
  `ip_address` varchar(45) NOT NULL COMMENT 'IP del intento',
  `user_agent` text DEFAULT NULL COMMENT 'Navegador/dispositivo',
  `attempt_result` enum('success','failed_password','failed_user_not_found','account_locked','account_inactive') NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de intentos de inicio de sesión';

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `user_id`, `ip_address`, `user_agent`, `attempt_result`, `attempted_at`) VALUES
(2, 'gm@gmail.com', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'failed_user_not_found', '2025-10-21 15:27:04'),
(4, 'admin@hospital.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'failed_password', '2025-10-21 15:34:39'),
(5, 'admin@hospital.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-21 15:35:02'),
(6, 'admin@hospital.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'failed_password', '2025-10-21 19:13:30'),
(7, 'admin@hospital.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-21 19:13:58'),
(9, 'admin@hospital.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'failed_password', '2025-10-22 13:25:54'),
(10, 'admin@hospital.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-22 13:26:22'),
(13, 'admin@hospital.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-22 23:25:22'),
(15, 'admin@hospital.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-22 23:48:52'),
(19, 'admin@hospital.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'failed_password', '2025-10-23 01:35:26'),
(20, 'admin@hospital.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-23 01:35:57'),
(22, 'ketanA@hospital.com', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'failed_user_not_found', '2025-10-23 03:11:44'),
(25, 'admin@hospital.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-23 03:22:31'),
(26, 'admin@hospital.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-23 14:18:24'),
(28, 'admin@hospital.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-23 21:31:54'),
(29, 'pablo.c@clinica.muelitas.com', 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-24 00:44:09'),
(30, 'willy.z@clinica.muelitas.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-24 00:46:38'),
(31, 'quenta.f@clinica.muelitas.com', 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-24 01:22:32'),
(32, 'willy.z@clinica.muelitas.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-24 01:25:07'),
(33, 'willy.z@clinica.muelitas.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-24 01:28:30'),
(34, 'paul.g@clinica.muelitas.com', 28, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-24 01:47:46'),
(35, 'willy.z@clinica.muelitas.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-24 01:49:03'),
(36, 'carlos.m@clinica.muelitas.com', 29, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'failed_password', '2025-10-25 01:31:35'),
(37, 'carlos.m@clinica.muelitas.com', 29, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'failed_password', '2025-10-25 01:31:50'),
(38, 'carlos.m@clinica.muelitas.com', 29, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'account_locked', '2025-10-25 01:32:34'),
(39, 'carlos.m@clinica.muelitas.com', 29, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'account_locked', '2025-10-25 01:32:49'),
(40, 'juan.t@clinica.muelitas.com', 25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-25 01:41:01'),
(41, 'willy.z@clinica.muelitas.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'failed_password', '2025-10-25 01:58:57'),
(42, 'willy.z@clinica.muelitas.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-25 01:59:28'),
(43, 'willy.z@clinica.muelitas.com', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-26 23:23:29'),
(44, 'fredy.y@clinica.muelitas.com', 31, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', '2025-10-27 01:54:05');

-- --------------------------------------------------------

--
-- Table structure for table `password_history`
--

CREATE TABLE `password_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Hash bcrypt de la contraseña anterior',
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha del cambio',
  `changed_by` int(11) DEFAULT NULL COMMENT 'ID del usuario que realizó el cambio (admin/self)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP desde donde se cambió'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de contraseñas para prevenir reutilización';

-- --------------------------------------------------------

--
-- Table structure for table `password_policy_config`
--

CREATE TABLE `password_policy_config` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(50) NOT NULL COMMENT 'Nombre de la configuración',
  `setting_value` varchar(255) NOT NULL COMMENT 'Valor de la configuración',
  `description` text DEFAULT NULL COMMENT 'Descripción de qué hace este setting',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL COMMENT 'Admin que modificó el setting'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuración de políticas de contraseña';

--
-- Dumping data for table `password_policy_config`
--

INSERT INTO `password_policy_config` (`id`, `setting_name`, `setting_value`, `description`, `updated_at`, `updated_by`) VALUES
(1, 'min_length', '12', 'Longitud mínima de contraseña', '2025-10-25 01:25:34', 8),
(2, 'max_length', '64', 'Longitud máxima de contraseña', '2025-10-21 00:20:33', NULL),
(3, 'require_uppercase', '1', 'Requiere al menos 1 mayúscula (1=sí, 0=no)', '2025-10-21 00:20:33', NULL),
(4, 'require_lowercase', '1', 'Requiere al menos 1 minúscula (1=sí, 0=no)', '2025-10-21 00:20:33', NULL),
(5, 'require_number', '1', 'Requiere al menos 1 número (1=sí, 0=no)', '2025-10-21 00:20:33', NULL),
(6, 'require_special_char', '1', 'Requiere al menos 1 carácter especial (1=sí, 0=no)', '2025-10-21 00:20:33', NULL),
(7, 'special_chars_allowed', '@#$%^&*()_+-=[]{}|;:,.<>?', 'Caracteres especiales permitidos', '2025-10-21 00:20:33', NULL),
(8, 'password_expiry_days', '90', 'Días hasta que expire la contraseña', '2025-10-21 00:20:33', NULL),
(9, 'password_history_count', '5', 'Número de contraseñas anteriores que no se pueden reutilizar', '2025-10-21 00:20:33', NULL),
(10, 'max_failed_attempts', '3', 'Intentos fallidos antes de bloqueo', '2025-10-21 00:20:33', NULL),
(11, 'lockout_duration_minutes', '30', 'Minutos que dura el bloqueo de cuenta', '2025-10-21 00:20:33', NULL),
(12, 'reset_token_expiry_minutes', '30', 'Minutos de validez del token de recuperación', '2025-10-21 00:20:33', NULL),
(13, 'min_password_age_hours', '0', 'Horas mínimas entre cambios de contraseña (prevenir spam)', '2025-10-21 02:38:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL COMMENT 'Token único generado (SHA256)',
  `expires_at` datetime NOT NULL COMMENT 'Fecha de expiración (30 minutos)',
  `used` tinyint(1) DEFAULT 0 COMMENT '1 = Token ya usado, 0 = No usado',
  `used_at` datetime DEFAULT NULL COMMENT 'Fecha/hora en que se usó el token',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP desde donde se solicitó el token',
  `user_agent` text DEFAULT NULL COMMENT 'Navegador/dispositivo que solicitó el token'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tokens de recuperación de contraseña';

--
-- Triggers `password_reset_tokens`
--
DELIMITER $$
CREATE TRIGGER `cleanup_expired_tokens_before_insert` BEFORE INSERT ON `password_reset_tokens` FOR EACH ROW BEGIN
    -- Marcar como usados los tokens expirados del mismo usuario
    UPDATE password_reset_tokens
    SET used = 1
    WHERE user_id = NEW.user_id
      AND expires_at < NOW()
      AND used = 0;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `user_id`, `address`, `city`, `gender`) VALUES
(9, 27, 'La Paz - Bolivia', 'CIUDAD LA PAZ', 'male'),
(10, 28, 'La Paz - Bolivia', 'CIUDAD LA PAZ', '');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL COMMENT 'Nombre técnico del permiso (ej: manage_users)',
  `display_name` varchar(150) NOT NULL COMMENT 'Nombre para mostrar en UI',
  `description` text DEFAULT NULL COMMENT 'Descripción de qué permite hacer este permiso',
  `module` varchar(50) NOT NULL COMMENT 'Módulo al que pertenece (ej: users, patients, appointments)',
  `category_id` int(11) DEFAULT NULL,
  `is_system_permission` tinyint(1) DEFAULT 1 COMMENT '1 = Permiso del sistema, 0 = Permiso personalizado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permisos granulares del sistema';

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `permission_name`, `display_name`, `description`, `module`, `category_id`, `is_system_permission`, `created_at`, `is_active`) VALUES
(1, 'view_users', 'Ver Usuarios', 'Permite ver la lista de usuarios del sistema', 'users', 1, 1, '2025-10-21 11:40:15', 1),
(2, 'create_user', 'Crear Usuario', 'Permite registrar nuevos usuarios', 'users', 1, 1, '2025-10-21 11:40:15', 1),
(3, 'edit_user', 'Editar Usuario', 'Permite modificar información de usuarios', 'users', 1, 1, '2025-10-21 11:40:15', 1),
(4, 'delete_user', 'Eliminar Usuario', 'Permite dar de baja usuarios', 'users', 1, 1, '2025-10-21 11:40:15', 1),
(5, 'manage_user_roles', 'Gestionar Roles de Usuario', 'Permite asignar/revocar roles a usuarios', 'users', 1, 1, '2025-10-21 11:40:15', 1),
(6, 'unlock_accounts', 'Desbloquear Cuentas', 'Permite desbloquear cuentas bloqueadas', 'users', 1, 1, '2025-10-21 11:40:15', 1),
(7, 'reset_passwords', 'Resetear Contraseñas', 'Permite resetear contraseñas de otros usuarios', 'users', 1, 1, '2025-10-21 11:40:15', 1),
(8, 'view_user_activity', 'Ver Actividad de Usuarios', 'Permite ver logs de actividad de usuarios', 'users', 1, 1, '2025-10-21 11:40:15', 1),
(9, 'view_patients', 'Ver Pacientes', 'Permite ver la lista de pacientes', 'patients', 2, 1, '2025-10-21 11:40:15', 1),
(10, 'view_patient_details', 'Ver Detalles de Paciente', 'Permite ver información detallada de pacientes', 'patients', 2, 1, '2025-10-21 11:40:15', 1),
(11, 'create_patient', 'Registrar Paciente', 'Permite registrar nuevos pacientes', 'patients', 2, 1, '2025-10-21 11:40:15', 1),
(12, 'edit_patient', 'Editar Paciente', 'Permite modificar información de pacientes', 'patients', 2, 1, '2025-10-21 11:40:15', 1),
(13, 'delete_patient', 'Eliminar Paciente', 'Permite dar de baja pacientes', 'patients', 2, 1, '2025-10-21 11:40:15', 1),
(14, 'view_own_patient_data', 'Ver Mis Datos', 'Permite al paciente ver su propia información', 'patients', 2, 1, '2025-10-21 11:40:15', 1),
(15, 'export_patient_data', 'Exportar Datos de Pacientes', 'Permite exportar información de pacientes', 'patients', 2, 1, '2025-10-21 11:40:15', 1),
(16, 'view_doctors', 'Ver Doctores', 'Permite ver la lista de doctores', 'doctors', 3, 1, '2025-10-21 11:40:15', 1),
(17, 'create_doctor', 'Registrar Doctor', 'Permite registrar nuevos doctores', 'doctors', 3, 1, '2025-10-21 11:40:15', 1),
(18, 'edit_doctor', 'Editar Doctor', 'Permite modificar información de doctores', 'doctors', 3, 1, '2025-10-21 11:40:15', 1),
(19, 'delete_doctor', 'Eliminar Doctor', 'Permite dar de baja doctores', 'doctors', 3, 1, '2025-10-21 11:40:15', 1),
(20, 'manage_doctor_schedule', 'Gestionar Horarios de Doctor', 'Permite configurar horarios de doctores', 'doctors', 3, 1, '2025-10-21 11:40:15', 1),
(21, 'view_doctor_performance', 'Ver Rendimiento de Doctores', 'Permite ver estadísticas de doctores', 'doctors', 3, 1, '2025-10-21 11:40:15', 1),
(22, 'view_appointments', 'Ver Citas', 'Permite ver todas las citas', 'appointments', 4, 1, '2025-10-21 11:40:15', 1),
(23, 'view_own_appointments', 'Ver Mis Citas', 'Permite ver solo sus propias citas', 'appointments', 4, 1, '2025-10-21 11:40:15', 1),
(24, 'create_appointment', 'Crear Cita', 'Permite agendar nuevas citas', 'appointments', 4, 1, '2025-10-21 11:40:15', 1),
(25, 'edit_appointment', 'Editar Cita', 'Permite modificar citas existentes', 'appointments', 4, 1, '2025-10-21 11:40:15', 1),
(26, 'cancel_appointment', 'Cancelar Cita', 'Permite cancelar citas', 'appointments', 4, 1, '2025-10-21 11:40:15', 1),
(27, 'approve_appointment', 'Aprobar Cita', 'Permite aprobar/rechazar citas', 'appointments', 4, 1, '2025-10-21 11:40:15', 1),
(28, 'reschedule_appointment', 'Reprogramar Cita', 'Permite cambiar fecha/hora de citas', 'appointments', 4, 1, '2025-10-21 11:40:15', 1),
(29, 'view_medical_records', 'Ver Registros Médicos', 'Permite ver historiales médicos', 'medical_records', 5, 1, '2025-10-21 11:40:15', 0),
(30, 'view_own_medical_records', 'Ver Mi Historial Médico', 'Permite ver solo su propio historial', 'medical_records', 5, 1, '2025-10-21 11:40:15', 0),
(31, 'create_medical_record', 'Crear Registro Médico', 'Permite crear nuevas entradas médicas', 'medical_records', 5, 1, '2025-10-21 11:40:15', 0),
(32, 'edit_medical_record', 'Editar Registro Médico', 'Permite modificar registros médicos', 'medical_records', 5, 1, '2025-10-21 11:40:15', 0),
(33, 'delete_medical_record', 'Eliminar Registro Médico', 'Permite eliminar registros médicos', 'medical_records', 5, 1, '2025-10-21 11:40:15', 0),
(34, 'view_prescriptions', 'Ver Recetas', 'Permite ver recetas médicas', 'medical_records', 5, 1, '2025-10-21 11:40:15', 0),
(35, 'create_prescription', 'Crear Receta', 'Permite generar recetas médicas', 'medical_records', 5, 1, '2025-10-21 11:40:15', 0),
(36, 'view_invoices', 'Ver Facturas', 'Permite ver facturas', 'billing', 6, 1, '2025-10-21 11:40:15', 0),
(37, 'view_own_invoices', 'Ver Mis Facturas', 'Permite ver solo sus propias facturas', 'billing', 6, 1, '2025-10-21 11:40:15', 0),
(38, 'create_invoice', 'Crear Factura', 'Permite generar nuevas facturas', 'billing', 6, 1, '2025-10-21 11:40:15', 0),
(39, 'edit_invoice', 'Editar Factura', 'Permite modificar facturas', 'billing', 6, 1, '2025-10-21 11:40:15', 0),
(40, 'delete_invoice', 'Eliminar Factura', 'Permite eliminar facturas', 'billing', 6, 1, '2025-10-21 11:40:15', 0),
(41, 'process_payment', 'Procesar Pagos', 'Permite registrar pagos', 'billing', 6, 1, '2025-10-21 11:40:15', 0),
(42, 'view_payment_reports', 'Ver Reportes de Pagos', 'Permite ver reportes financieros', 'billing', 6, 1, '2025-10-21 11:40:15', 0),
(43, 'view_reports', 'Ver Reportes', 'Permite ver reportes generales', 'reports', 7, 1, '2025-10-21 11:40:15', 0),
(44, 'create_report', 'Crear Reporte', 'Permite generar nuevos reportes', 'reports', 7, 1, '2025-10-21 11:40:15', 0),
(45, 'export_reports', 'Exportar Reportes', 'Permite exportar reportes a PDF/Excel', 'reports', 7, 1, '2025-10-21 11:40:15', 0),
(46, 'view_analytics', 'Ver Analíticas', 'Permite ver dashboards analíticos', 'reports', 7, 1, '2025-10-21 11:40:15', 0),
(47, 'view_audit_logs', 'Ver Logs de Auditoría', 'Permite ver registros de auditoría', 'reports', 7, 1, '2025-10-21 11:40:15', 0),
(48, 'manage_roles', 'Gestionar Roles', 'Permite crear/editar/eliminar roles', 'system', 8, 1, '2025-10-21 11:40:15', 1),
(49, 'manage_permissions', 'Gestionar Permisos', 'Permite asignar permisos a roles', 'system', 8, 1, '2025-10-21 11:40:15', 1),
(50, 'manage_system_settings', 'Gestionar Configuración', 'Permite modificar configuración del sistema', 'system', 8, 1, '2025-10-21 11:40:15', 1),
(51, 'manage_password_policies', 'Gestionar Políticas de Contraseña', 'Permite configurar políticas de seguridad', 'system', 8, 1, '2025-10-21 11:40:15', 1),
(52, 'view_system_logs', 'Ver Logs del Sistema', 'Permite ver logs técnicos', 'system', 8, 1, '2025-10-21 11:40:15', 1),
(53, 'backup_database', 'Respaldar Base de Datos', 'Permite crear backups', 'system', 8, 1, '2025-10-21 11:40:15', 1),
(54, 'restore_database', 'Restaurar Base de Datos', 'Permite restaurar desde backups', 'system', 8, 1, '2025-10-21 11:40:15', 1),
(55, 'view_security_logs', 'Ver Logs de Seguridad', 'Permite ver intentos de login y eventos de seguridad', 'security', 9, 1, '2025-10-21 11:40:15', 1),
(56, 'manage_security_settings', 'Gestionar Seguridad', 'Permite configurar opciones de seguridad', 'security', 9, 1, '2025-10-21 11:40:15', 1),
(57, 'view_failed_logins', 'Ver Intentos Fallidos', 'Permite ver intentos de login fallidos', 'security', 9, 1, '2025-10-21 11:40:15', 1),
(58, 'manage_session_timeout', 'Gestionar Timeouts', 'Permite configurar tiempos de sesión', 'security', 9, 1, '2025-10-21 11:40:15', 1);

-- --------------------------------------------------------

--
-- Table structure for table `permission_categories`
--

CREATE TABLE `permission_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL COMMENT 'Icono para UI (ej: fa-users)',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categorías de permisos para organización';

--
-- Dumping data for table `permission_categories`
--

INSERT INTO `permission_categories` (`id`, `category_name`, `display_name`, `description`, `icon`, `sort_order`, `is_active`) VALUES
(1, 'users', 'Gestión de Usuarios', 'Permisos relacionados con la administración de usuarios', 'fa-users', 1, 1),
(2, 'patients', 'Gestión de Pacientes', 'Permisos para manejo de pacientes', 'fa-wheelchair', 2, 1),
(3, 'doctors', 'Gestión de Doctores', 'Permisos para manejo de doctores', 'fa-user-md', 3, 1),
(4, 'appointments', 'Gestión de Citas', 'Permisos para manejo de citas médicas', 'fa-calendar', 4, 1),
(5, 'medical_records', 'Registros Médicos', 'Permisos para historiales médicos', 'fa-file-text-o', 5, 0),
(6, 'billing', 'Facturación', 'Permisos para manejo de facturación', 'fa-usd', 6, 0),
(7, 'reports', 'Reportes', 'Permisos para generación de reportes', 'fa-bar-chart', 7, 0),
(8, 'system', 'Configuración del Sistema', 'Permisos de administración del sistema', 'fa-cogs', 8, 1),
(9, 'security', 'Seguridad', 'Permisos de auditoría y seguridad', 'fa-shield', 9, 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `recent_changes_timeline`
-- (See below for the actual view)
--
CREATE TABLE `recent_changes_timeline` (
`id` int(11)
,`user_id` int(11)
,`user_name` varchar(255)
,`changed_by` int(11)
,`changed_by_name` varchar(255)
,`change_type` enum('create','update','delete','status_change','role_change','password_change')
,`field_changed` varchar(50)
,`change_reason` varchar(255)
,`created_at` timestamp
,`change_date` date
);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL COMMENT 'Nombre único del rol (ej: admin, doctor)',
  `display_name` varchar(100) NOT NULL COMMENT 'Nombre para mostrar en UI (ej: Administrator)',
  `description` text DEFAULT NULL COMMENT 'Descripción de las responsabilidades del rol',
  `is_system_role` tinyint(1) DEFAULT 0 COMMENT '1 = Rol del sistema (no se puede eliminar), 0 = Rol personalizado',
  `priority` int(11) DEFAULT 100 COMMENT 'Prioridad del rol (menor número = mayor prioridad)',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'ID del usuario que creó el rol'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Roles del sistema';

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `display_name`, `description`, `is_system_role`, `priority`, `status`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 'admin_tecnico', 'Administrador Técnico', 'Supervisa configuración y seguridad del sistema', 1, 1, 'active', '2025-10-21 11:40:15', '2025-10-24 23:57:53', NULL),
(2, 'oficial_seguridad_informacion', 'OSI', 'Protección de los activos de información de la organización', 1, 10, 'active', '2025-10-21 11:40:15', '2025-10-23 22:22:17', NULL),
(3, 'doctor', 'Doctor', 'Gestión de pacientes, citas y registros médicos', 1, 20, 'active', '2025-10-21 11:40:15', '2025-10-21 11:40:15', NULL),
(4, 'patient', 'Paciente', 'Acceso limitado a sus propios datos y citas', 1, 40, 'active', '2025-10-21 11:40:15', '2025-10-21 11:40:15', NULL),
(5, 'receptionist', 'Recepcionista', 'Gestión de citas y registro de pacientes', 1, 30, 'active', '2025-10-21 11:40:15', '2025-10-21 11:40:15', NULL),
(6, 'nurse', 'Enfermera', 'Asistencia en registros médicos y gestión de pacientes', 1, 25, 'active', '2025-10-21 11:40:15', '2025-10-21 11:40:15', NULL),
(7, 'lab_technician', 'Técnico de Laboratorio', 'Gestión de resultados de laboratorio', 1, 35, 'active', '2025-10-21 11:40:15', '2025-10-21 11:40:15', NULL),
(10, 'admin_operativo', 'Administrador Operativo', 'Gestiona usuarios, pacientes y doctores', 0, 5, 'active', '2025-10-23 00:59:21', '2025-10-25 00:00:32', NULL),
(11, 'auditor_uno', 'Auditor 1', 'Supervisa y verifica las acciones del sistema para garantizar el cumplimiento y la seguridad', 0, 45, 'inactive', '2025-10-25 00:10:23', '2025-10-25 00:28:05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `role_hierarchy`
--

CREATE TABLE `role_hierarchy` (
  `id` int(11) NOT NULL,
  `parent_role_id` int(11) NOT NULL COMMENT 'Rol padre (hereda a)',
  `child_role_id` int(11) NOT NULL COMMENT 'Rol hijo (hereda de)'
) ;

--
-- Dumping data for table `role_hierarchy`
--

INSERT INTO `role_hierarchy` (`id`, `parent_role_id`, `child_role_id`) VALUES
(1, 2, 3),
(2, 2, 5),
(3, 3, 6);

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `granted_by` int(11) DEFAULT NULL COMMENT 'ID del admin que otorgó el permiso'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Asignación de permisos a roles';

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `granted_at`, `granted_by`) VALUES
(492, 1, 56, '2025-10-26 23:34:39', 8),
(493, 1, 53, '2025-10-26 23:34:39', 8),
(494, 1, 49, '2025-10-26 23:34:39', 8),
(495, 1, 48, '2025-10-26 23:34:39', 8),
(496, 1, 50, '2025-10-26 23:34:39', 8),
(497, 1, 54, '2025-10-26 23:34:39', 8),
(498, 1, 52, '2025-10-26 23:34:40', 8),
(499, 10, 17, '2025-10-26 23:35:40', 8),
(500, 10, 19, '2025-10-26 23:35:40', 8),
(501, 10, 18, '2025-10-26 23:35:40', 8),
(502, 10, 20, '2025-10-26 23:35:40', 8),
(503, 10, 21, '2025-10-26 23:35:40', 8),
(504, 10, 16, '2025-10-26 23:35:40', 8),
(505, 10, 11, '2025-10-26 23:35:40', 8),
(506, 10, 13, '2025-10-26 23:35:40', 8),
(507, 10, 12, '2025-10-26 23:35:40', 8),
(508, 10, 15, '2025-10-26 23:35:40', 8),
(509, 10, 14, '2025-10-26 23:35:40', 8),
(510, 10, 10, '2025-10-26 23:35:40', 8),
(511, 10, 9, '2025-10-26 23:35:40', 8),
(512, 10, 2, '2025-10-26 23:35:40', 8),
(513, 10, 4, '2025-10-26 23:35:40', 8),
(514, 10, 3, '2025-10-26 23:35:40', 8),
(515, 10, 5, '2025-10-26 23:35:40', 8),
(516, 10, 7, '2025-10-26 23:35:40', 8),
(517, 10, 6, '2025-10-26 23:35:40', 8),
(518, 10, 8, '2025-10-26 23:35:40', 8),
(519, 10, 1, '2025-10-26 23:35:40', 8),
(520, 2, 56, '2025-10-26 23:36:48', 8),
(521, 2, 58, '2025-10-26 23:36:48', 8),
(522, 2, 57, '2025-10-26 23:36:48', 8),
(523, 2, 55, '2025-10-26 23:36:48', 8),
(524, 2, 53, '2025-10-26 23:36:48', 8),
(525, 2, 51, '2025-10-26 23:36:48', 8),
(526, 2, 49, '2025-10-26 23:36:48', 8),
(527, 2, 48, '2025-10-26 23:36:48', 8),
(528, 3, 27, '2025-10-26 23:38:13', 8),
(529, 3, 26, '2025-10-26 23:38:13', 8),
(530, 3, 24, '2025-10-26 23:38:13', 8),
(531, 3, 25, '2025-10-26 23:38:13', 8),
(532, 3, 28, '2025-10-26 23:38:13', 8),
(533, 3, 22, '2025-10-26 23:38:13', 8),
(534, 3, 23, '2025-10-26 23:38:13', 8),
(535, 3, 20, '2025-10-26 23:38:13', 8),
(536, 3, 16, '2025-10-26 23:38:13', 8),
(537, 3, 11, '2025-10-26 23:38:13', 8),
(538, 3, 13, '2025-10-26 23:38:13', 8),
(539, 3, 12, '2025-10-26 23:38:13', 8),
(540, 3, 15, '2025-10-26 23:38:13', 8),
(541, 3, 14, '2025-10-26 23:38:13', 8),
(542, 3, 10, '2025-10-26 23:38:13', 8),
(543, 3, 9, '2025-10-26 23:38:13', 8),
(544, 6, 25, '2025-10-26 23:40:03', 8),
(545, 6, 22, '2025-10-26 23:40:03', 8),
(546, 6, 23, '2025-10-26 23:40:03', 8),
(547, 6, 16, '2025-10-26 23:40:03', 8),
(548, 6, 12, '2025-10-26 23:40:03', 8),
(549, 6, 14, '2025-10-26 23:40:03', 8),
(550, 6, 10, '2025-10-26 23:40:03', 8),
(551, 6, 9, '2025-10-26 23:40:03', 8),
(552, 5, 26, '2025-10-26 23:42:12', 8),
(553, 5, 24, '2025-10-26 23:42:12', 8),
(554, 5, 25, '2025-10-26 23:42:12', 8),
(555, 5, 28, '2025-10-26 23:42:12', 8),
(556, 5, 22, '2025-10-26 23:42:12', 8),
(557, 5, 23, '2025-10-26 23:42:12', 8),
(558, 5, 20, '2025-10-26 23:42:12', 8),
(559, 5, 16, '2025-10-26 23:42:12', 8),
(560, 5, 9, '2025-10-26 23:42:12', 8),
(561, 7, 22, '2025-10-26 23:43:30', 8),
(562, 7, 16, '2025-10-26 23:43:30', 8),
(563, 7, 14, '2025-10-26 23:43:30', 8),
(564, 7, 10, '2025-10-26 23:43:30', 8),
(565, 7, 9, '2025-10-26 23:43:30', 8),
(566, 4, 26, '2025-10-26 23:44:55', 8),
(567, 4, 24, '2025-10-26 23:44:55', 8),
(568, 4, 25, '2025-10-26 23:44:55', 8),
(569, 4, 28, '2025-10-26 23:44:55', 8),
(570, 4, 23, '2025-10-26 23:44:55', 8),
(571, 4, 16, '2025-10-26 23:44:55', 8),
(572, 4, 14, '2025-10-26 23:44:55', 8);

-- --------------------------------------------------------

--
-- Stand-in structure for view `role_permission_matrix`
-- (See below for the actual view)
--
CREATE TABLE `role_permission_matrix` (
`role_name` varchar(50)
,`role_display_name` varchar(100)
,`module` varchar(50)
,`permission_name` varchar(100)
,`permission_display_name` varchar(150)
,`granted_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'ID del usuario (NULL si no está autenticado)',
  `event_type` varchar(50) NOT NULL COMMENT 'Tipo de evento (unauthorized_access, permission_denied, etc.)',
  `event_description` text DEFAULT NULL COMMENT 'Descripción detallada del evento',
  `ip_address` varchar(45) NOT NULL COMMENT 'IP desde donde se originó el evento',
  `user_agent` text DEFAULT NULL COMMENT 'User agent del navegador',
  `additional_data` longtext DEFAULT NULL COMMENT 'Datos adicionales en formato JSON' CHECK (json_valid(`additional_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs de eventos de seguridad';

--
-- Dumping data for table `security_logs`
--

INSERT INTO `security_logs` (`id`, `user_id`, `event_type`, `event_description`, `ip_address`, `user_agent`, `additional_data`, `created_at`) VALUES
(1, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: any_role, Requerido: super_admin,admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"any_role\",\"required\":\"super_admin,admin\",\"page\":\"\\/hospital\\/hms\\/admin\\/rbac-example.php\",\"method\":\"GET\"}', '2025-10-21 12:40:11'),
(2, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: roles.manage', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"roles.manage\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 04:23:24'),
(3, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: roles.manage', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"roles.manage\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 04:23:54'),
(4, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: roles.manage', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"roles.manage\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 04:25:29'),
(5, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: roles.manage', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"roles.manage\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 04:26:07'),
(6, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: roles.admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"roles.admin\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 04:35:05'),
(7, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: roles.manage', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"roles.manage\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 12:27:42'),
(8, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 13:23:58'),
(9, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 13:24:04'),
(10, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 13:24:29'),
(11, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 13:25:06'),
(12, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 23:23:30'),
(13, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 23:23:55'),
(14, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 23:24:07'),
(15, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 23:24:08'),
(16, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 23:24:17'),
(17, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 23:24:23'),
(18, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?action=delete&id=10\",\"method\":\"GET\"}', '2025-10-22 23:24:33'),
(19, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?action=delete&id=18\",\"method\":\"GET\"}', '2025-10-22 23:24:33'),
(20, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=\",\"method\":\"GET\"}', '2025-10-22 23:24:35'),
(21, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?type=admin\",\"method\":\"GET\"}', '2025-10-22 23:24:36'),
(22, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?type=doctor\",\"method\":\"GET\"}', '2025-10-22 23:24:36'),
(23, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?type=patient\",\"method\":\"GET\"}', '2025-10-22 23:24:37'),
(24, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=\",\"method\":\"GET\"}', '2025-10-22 23:24:38'),
(25, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?type=patient\",\"method\":\"GET\"}', '2025-10-22 23:24:38'),
(26, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=blocked&type=patient\",\"method\":\"GET\"}', '2025-10-22 23:24:39'),
(27, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=blocked&\",\"method\":\"GET\"}', '2025-10-22 23:24:39'),
(28, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=inactive&\",\"method\":\"GET\"}', '2025-10-22 23:24:39'),
(29, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=active&\",\"method\":\"GET\"}', '2025-10-22 23:24:40'),
(30, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=\",\"method\":\"GET\"}', '2025-10-22 23:24:40'),
(31, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=gus%40gmail.com\",\"method\":\"GET\"}', '2025-10-22 23:24:40'),
(32, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=\",\"method\":\"GET\"}', '2025-10-22 23:24:40'),
(33, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=gus\",\"method\":\"GET\"}', '2025-10-22 23:24:41'),
(34, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=\",\"method\":\"GET\"}', '2025-10-22 23:24:41'),
(35, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=gus\",\"method\":\"GET\"}', '2025-10-22 23:24:41'),
(36, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 23:24:41'),
(37, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 23:24:41'),
(38, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 23:24:42'),
(39, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 23:24:42'),
(40, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 23:24:45'),
(41, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 23:24:45'),
(42, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?action=delete&id=17\",\"method\":\"GET\"}', '2025-10-22 23:24:45'),
(43, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?action=delete&id=17\",\"method\":\"GET\"}', '2025-10-22 23:24:46'),
(44, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 23:45:02'),
(45, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 23:45:08'),
(46, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 23:45:13'),
(47, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 23:45:18'),
(48, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 23:45:28'),
(49, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 23:45:39'),
(50, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 23:45:45'),
(51, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 23:48:54'),
(52, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 23:48:57'),
(53, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-22 23:49:19'),
(54, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-22 23:49:41'),
(55, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?action=delete&id=20\",\"method\":\"GET\"}', '2025-10-23 01:31:18'),
(56, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-23 01:31:22'),
(57, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-23 01:31:28'),
(58, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?type=doctor\",\"method\":\"GET\"}', '2025-10-23 01:31:35'),
(59, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?type=patient\",\"method\":\"GET\"}', '2025-10-23 01:31:36'),
(60, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-23 01:31:36'),
(61, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=inactive\",\"method\":\"GET\"}', '2025-10-23 01:31:37'),
(62, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=blocked\",\"method\":\"GET\"}', '2025-10-23 01:31:37'),
(63, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=active\",\"method\":\"GET\"}', '2025-10-23 01:31:38'),
(64, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=inactive\",\"method\":\"GET\"}', '2025-10-23 01:31:38'),
(65, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=blocked\",\"method\":\"GET\"}', '2025-10-23 01:31:39'),
(66, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=inactive\",\"method\":\"GET\"}', '2025-10-23 01:31:40'),
(67, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=active\",\"method\":\"GET\"}', '2025-10-23 01:31:40'),
(68, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-23 01:31:40'),
(69, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=kenta\",\"method\":\"GET\"}', '2025-10-23 01:31:40'),
(70, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=kenta&type=patient\",\"method\":\"GET\"}', '2025-10-23 01:31:40'),
(71, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=kenta&status=active&type=patient\",\"method\":\"GET\"}', '2025-10-23 01:31:41'),
(72, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=gus&status=active&type=patient\",\"method\":\"GET\"}', '2025-10-23 01:31:41'),
(73, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=gus&status=active\",\"method\":\"GET\"}', '2025-10-23 01:31:41'),
(74, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=gus&status=active\",\"method\":\"GET\"}', '2025-10-23 01:31:42'),
(75, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=gus\",\"method\":\"GET\"}', '2025-10-23 01:31:42'),
(76, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-23 01:31:42'),
(77, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-23 01:31:42'),
(78, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=active&type=patient\",\"method\":\"GET\"}', '2025-10-23 01:31:43'),
(79, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-23 01:31:52'),
(80, NULL, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-23 01:32:08'),
(81, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-23 01:36:01'),
(82, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"POST\"}', '2025-10-23 01:37:27'),
(83, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-23 01:37:44'),
(84, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"POST\"}', '2025-10-23 01:37:53'),
(85, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"POST\"}', '2025-10-23 01:38:34'),
(86, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-23 01:38:36'),
(87, 24, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?sort_by=last_login&sort_order=ASC\",\"method\":\"POST\"}', '2025-10-24 00:45:37'),
(88, 24, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?sort_by=last_login&sort_order=ASC\",\"method\":\"GET\"}', '2025-10-24 00:45:42'),
(89, 24, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?sort_by=user_type&sort_order=ASC\",\"method\":\"GET\"}', '2025-10-24 00:45:48'),
(90, 24, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?sort_by=full_name&sort_order=ASC\",\"method\":\"GET\"}', '2025-10-24 00:45:52'),
(91, 24, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?sort_by=last_login&sort_order=ASC\",\"method\":\"GET\"}', '2025-10-24 00:45:56'),
(92, 24, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-24 00:45:59'),
(93, 24, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=inactive\",\"method\":\"GET\"}', '2025-10-24 00:46:01'),
(94, 27, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-24 01:22:45'),
(95, 27, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=will\",\"method\":\"GET\"}', '2025-10-24 01:22:55'),
(96, 27, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=gus\",\"method\":\"GET\"}', '2025-10-24 01:23:01'),
(97, 27, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?search=gus&type=admin\",\"method\":\"GET\"}', '2025-10-24 01:23:03'),
(98, 27, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?type=admin\",\"method\":\"GET\"}', '2025-10-24 01:23:06'),
(99, 27, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?type=patient\",\"method\":\"GET\"}', '2025-10-24 01:23:08'),
(100, 27, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=inactive\",\"method\":\"GET\"}', '2025-10-24 01:23:10'),
(101, 27, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php?status=active\",\"method\":\"GET\"}', '2025-10-24 01:23:12'),
(102, 27, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-24 01:23:14'),
(103, 27, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-24 01:23:16'),
(104, 27, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-24 01:23:18'),
(105, 28, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-24 01:47:58'),
(106, 28, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-24 01:48:14'),
(107, 25, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-25 01:53:03'),
(108, 25, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-25 01:53:09'),
(109, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-26 23:34:46'),
(110, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-26 23:34:56'),
(111, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-27 00:20:06'),
(112, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-27 00:20:18'),
(113, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-27 00:39:53'),
(114, 8, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: view_users', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"view_users\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-users.php\",\"method\":\"GET\"}', '2025-10-27 01:53:28'),
(115, 31, 'unauthorized_access', 'Intento de acceso no autorizado - Tipo: permission, Requerido: manage_roles', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '{\"access_type\":\"permission\",\"required\":\"manage_roles\",\"page\":\"\\/hospital\\/hms\\/admin\\/manage-roles.php\",\"method\":\"GET\"}', '2025-10-27 01:55:05');

-- --------------------------------------------------------

--
-- Table structure for table `tblcontactus`
--

CREATE TABLE `tblcontactus` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contactno` bigint(12) DEFAULT NULL,
  `message` longtext DEFAULT NULL,
  `PostingDate` timestamp NULL DEFAULT current_timestamp(),
  `AdminRemark` longtext DEFAULT NULL,
  `LastupdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `IsRead` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblcontactus`
--

INSERT INTO `tblcontactus` (`id`, `fullname`, `email`, `contactno`, `message`, `PostingDate`, `AdminRemark`, `LastupdationDate`, `IsRead`) VALUES
(1, 'gus', 'gustavo.quisbert.c@ucb.edu.bo', 76587463, ' Necesito una cita', '2025-10-23 21:30:25', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tblmedicalhistory`
--

CREATE TABLE `tblmedicalhistory` (
  `ID` int(10) NOT NULL,
  `PatientID` int(10) DEFAULT NULL,
  `BloodPressure` varchar(200) DEFAULT NULL,
  `BloodSugar` varchar(200) NOT NULL,
  `Weight` varchar(100) DEFAULT NULL,
  `Temperature` varchar(200) DEFAULT NULL,
  `MedicalPres` longtext DEFAULT NULL,
  `CreationDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `unauthorized_access_summary`
-- (See below for the actual view)
--
CREATE TABLE `unauthorized_access_summary` (
`user_id` int(11)
,`email` varchar(255)
,`full_name` varchar(255)
,`total_attempts` bigint(21)
,`last_attempt` timestamp
,`attempted_actions` mediumtext
);

-- --------------------------------------------------------

--
-- Table structure for table `userlog`
--

CREATE TABLE `userlog` (
  `id` int(11) NOT NULL,
  `uid` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `userip` binary(16) DEFAULT NULL,
  `loginTime` timestamp NULL DEFAULT current_timestamp(),
  `logout` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('patient','doctor','admin') NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `status` enum('active','inactive','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0 COMMENT 'Contador de intentos fallidos consecutivos',
  `account_locked_until` datetime DEFAULT NULL COMMENT 'Fecha/hora hasta cuando la cuenta permanece bloqueada',
  `password_expires_at` datetime DEFAULT NULL COMMENT 'Fecha de expiración de la contraseña (90 días)',
  `password_changed_at` datetime DEFAULT NULL COMMENT 'Fecha del último cambio de contraseña',
  `last_login_ip` varchar(45) DEFAULT NULL COMMENT 'Dirección IP del último inicio de sesión (IPv4 o IPv6)',
  `force_password_change` tinyint(1) DEFAULT 0 COMMENT '1 = Debe cambiar contraseña en próximo login, 0 = No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `user_type`, `full_name`, `status`, `created_at`, `updated_at`, `last_login`, `failed_login_attempts`, `account_locked_until`, `password_expires_at`, `password_changed_at`, `last_login_ip`, `force_password_change`) VALUES
(8, 'willy.z@clinica.muelitas.com', '$2y$12$xpRZx/B1nX.nHu9uV0eY6ecbfxFDfbJhibXgKsW9xtoWcbk4Za7m.', 'admin', 'Willy Perez Marco', 'active', '2025-10-21 00:57:55', '2025-10-26 23:23:29', '2025-10-26 23:23:29', 0, NULL, '2026-01-18 20:57:55', '2025-10-20 20:57:55', '::1', 0),
(24, 'pablo.s@clinica.muelitas.com', '$2y$10$VZZC5SxyYa.XPJ00cED9MeaDtEDZJo3RVNfAzaIzfmpsoVIZhEU6a', 'patient', 'Pablo Chávez Sánchez', 'active', '2025-10-24 00:37:22', '2025-10-24 02:45:30', '2025-10-24 00:44:09', 0, NULL, NULL, NULL, '::1', 0),
(25, 'juan.t@clinica.muelitas.com', '$2y$10$1bj.m68c2pFWtXie1tvonOR7dAJwlgK5NiGe2q7WMoZeinJIFeUHG', 'patient', 'Juan Andres Torrez', 'active', '2025-10-24 00:48:37', '2025-10-25 01:41:01', '2025-10-25 01:41:01', 0, NULL, NULL, NULL, '::1', 0),
(26, 'marcos.t@clinica.muelitas.com', '$2y$10$HFyVWzWRyM8/lpmgM0zO/eIjCphn1ucufvwrG8UASRzd/tqmi1sD2', 'doctor', 'Marcos Torrico Gutiérrez', 'inactive', '2025-10-24 00:55:25', '2025-10-24 02:48:05', NULL, 0, NULL, NULL, NULL, NULL, 0),
(27, 'quenta.f@clinica.muelitas.com', '$2y$10$UVW7UQllD36gM4xJaU79v.dzh5XQ/9MexqMpVC8Yl1XRhja7wVbt6', 'patient', 'Quenta Flores Ramos', 'inactive', '2025-10-24 01:22:17', '2025-10-24 01:49:51', '2025-10-24 01:22:32', 0, NULL, NULL, NULL, '::1', 0),
(28, 'paul.g@clinica.muelitas.com', '$2y$10$FT/ugM8GMFsQ4nUUUM9tD.ie6tAWDjym8fVgeoh/K2T85MgKlO/Wu', 'patient', 'Paul Gomez Lopez', 'active', '2025-10-24 01:47:03', '2025-10-24 01:47:46', '2025-10-24 01:47:46', 0, NULL, NULL, NULL, '::1', 0),
(29, 'carlos.m@clinica.muelitas.com', '$2y$10$iQ1/PtzCFJJjwUr/P5AywOMZOSp8nQCFJwGktK6lxeaJCUFw2Bu.i', 'doctor', 'Carlos Molina Vázquez', 'active', '2025-10-24 01:52:23', '2025-10-25 01:32:34', NULL, 3, '2025-10-24 22:02:34', NULL, NULL, NULL, 0),
(30, 'adrian.m@clinica.muelitas.com', '$2y$10$PIrEjHVJ2F71XWBf5LcnSeuTLlXGTKfDHuXjF8oAOqmSgQ7JRbTrq', 'patient', 'Adrian Medina Jiménez', 'active', '2025-10-24 02:36:18', '2025-10-24 02:36:18', NULL, 0, NULL, NULL, NULL, NULL, 0),
(31, 'fredy.y@clinica.muelitas.com', '$2y$10$AHhB3AXA1xKXqGivbbWlpOXjWqus8Rmz/naa4fEHFudKCvgdVzNgO', 'admin', 'Fredy Yousaf Lon', 'active', '2025-10-26 23:30:14', '2025-10-27 01:54:05', '2025-10-27 01:54:05', 0, NULL, NULL, NULL, '::1', 0);

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `after_user_creation` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    INSERT INTO user_change_history (user_id, changed_by, change_type, change_reason, ip_address)
    VALUES (NEW.id, COALESCE(@current_user_id, NEW.id), 'create', 'Usuario creado', @current_user_ip);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_user_deactivation` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
    IF NEW.status = 0 AND OLD.status = 1 THEN
        -- Cerrar todas las sesiones activas del usuario
        UPDATE user_sessions
        SET is_active = 0, logout_at = NOW()
        WHERE user_id = NEW.id AND is_active = 1;

        -- Registrar el cambio de estado
        INSERT INTO user_change_history (user_id, changed_by, change_type, field_changed, old_value, new_value, change_reason)
        VALUES (NEW.id, @current_user_id, 'status_change', 'status', '1', '0', 'Usuario desactivado');
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `users_password_expiring_soon`
-- (See below for the actual view)
--
CREATE TABLE `users_password_expiring_soon` (
`id` int(11)
,`email` varchar(255)
,`full_name` varchar(255)
,`user_type` enum('patient','doctor','admin')
,`password_expires_at` datetime
,`days_until_expiry` int(7)
,`last_password_change` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_changes_detailed`
-- (See below for the actual view)
--
CREATE TABLE `user_changes_detailed` (
`id` int(11)
,`user_id` int(11)
,`user_name` varchar(255)
,`user_email` varchar(255)
,`changed_by` int(11)
,`changed_by_name` varchar(255)
,`changed_by_email` varchar(255)
,`change_type` enum('create','update','delete','status_change','role_change','password_change')
,`field_changed` varchar(50)
,`old_value` text
,`new_value` text
,`change_reason` varchar(255)
,`ip_address` varchar(45)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `user_change_history`
--

CREATE TABLE `user_change_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Usuario que fue modificado',
  `changed_by` int(11) DEFAULT NULL COMMENT 'Usuario que realizó el cambio',
  `change_type` enum('create','update','delete','status_change','role_change','password_change') NOT NULL,
  `field_changed` varchar(50) DEFAULT NULL COMMENT 'Campo específico que cambió',
  `old_value` text DEFAULT NULL COMMENT 'Valor anterior',
  `new_value` text DEFAULT NULL COMMENT 'Valor nuevo',
  `change_reason` varchar(255) DEFAULT NULL COMMENT 'Razón del cambio',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP desde donde se hizo el cambio',
  `user_agent` text DEFAULT NULL COMMENT 'Navegador/dispositivo usado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial completo de cambios en usuarios';

--
-- Dumping data for table `user_change_history`
--

INSERT INTO `user_change_history` (`id`, `user_id`, `changed_by`, `change_type`, `field_changed`, `old_value`, `new_value`, `change_reason`, `ip_address`, `user_agent`, `created_at`) VALUES
(57, 8, 8, 'update', 'full_name', 'Administrador Sistema', 'Willy', 'Usuario actualizado desde panel de administración', '0', NULL, '2025-10-24 00:26:05'),
(58, 8, 8, 'update', 'email', 'admin@hospital.com', 'admin@clinica.muelitas.com', 'Usuario actualizado desde panel de administración', '0', NULL, '2025-10-24 00:26:05'),
(62, 8, 8, 'update', 'email', 'admin@clinica.muelitas.com', 'willy.z@clinica.muelitas.com', 'Usuario actualizado desde panel de administración', '0', NULL, '2025-10-24 00:29:55'),
(63, 24, 24, 'create', NULL, NULL, NULL, 'Usuario creado', NULL, NULL, '2025-10-24 00:37:22'),
(64, 24, 8, 'create', NULL, NULL, NULL, 'Usuario creado desde panel de administración', '0', NULL, '2025-10-24 00:37:22'),
(65, 8, 8, 'update', 'full_name', 'Willy', 'Willy Perez Marco', 'Usuario actualizado desde panel de administración', '0', NULL, '2025-10-24 00:40:25'),
(66, 25, 25, 'create', NULL, NULL, NULL, 'Usuario creado', NULL, NULL, '2025-10-24 00:48:37'),
(67, 25, 8, 'create', NULL, NULL, NULL, 'Usuario creado desde panel de administración', '0', NULL, '2025-10-24 00:48:37'),
(68, 26, 26, 'create', NULL, NULL, NULL, 'Usuario creado', NULL, NULL, '2025-10-24 00:55:25'),
(69, 26, 8, 'create', NULL, NULL, NULL, 'Usuario creado desde panel de administración', '0', NULL, '2025-10-24 00:55:25'),
(70, 27, 27, 'create', NULL, NULL, NULL, 'Usuario creado', NULL, NULL, '2025-10-24 01:22:17'),
(71, 28, 28, 'create', NULL, NULL, NULL, 'Usuario creado', NULL, NULL, '2025-10-24 01:47:03'),
(72, 27, 8, 'status_change', 'status', 'active', 'inactive', 'Usuario eliminado desde panel de administración', '0', NULL, '2025-10-24 01:49:51'),
(73, 29, 29, 'create', NULL, NULL, NULL, 'Usuario creado', NULL, NULL, '2025-10-24 01:52:23'),
(74, 29, 8, 'create', NULL, NULL, NULL, 'Usuario creado desde panel de administración', '0', NULL, '2025-10-24 01:52:23'),
(75, 30, 30, 'create', NULL, NULL, NULL, 'Usuario creado', NULL, NULL, '2025-10-24 02:36:18'),
(76, 30, 8, 'create', NULL, NULL, NULL, 'Usuario creado desde panel de administración', '0', NULL, '2025-10-24 02:36:18'),
(77, 24, 8, 'update', 'full_name', 'Pablo Chávez Velasco', 'Pablo Chávez Sánchez', 'Usuario actualizado desde panel de administración', '0', NULL, '2025-10-24 02:45:30'),
(78, 24, 8, 'update', 'email', 'pablo.c@clinica.muelitas.com', 'pablo.s@clinica.muelitas.com', 'Usuario actualizado desde panel de administración', '0', NULL, '2025-10-24 02:45:30'),
(79, 26, 8, 'status_change', 'status', 'active', 'inactive', 'Usuario eliminado desde panel de administración', '0', NULL, '2025-10-24 02:48:05'),
(80, 31, 31, 'create', NULL, NULL, NULL, 'Usuario creado', NULL, NULL, '2025-10-26 23:30:14'),
(81, 31, 8, 'create', NULL, NULL, NULL, 'Usuario creado desde panel de administración', '0', NULL, '2025-10-26 23:30:14');

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_effective_permissions`
-- (See below for the actual view)
--
CREATE TABLE `user_effective_permissions` (
`user_id` int(11)
,`email` varchar(255)
,`full_name` varchar(255)
,`role_id` int(11)
,`role_name` varchar(50)
,`permission_id` int(11)
,`permission_name` varchar(100)
,`module` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `user_notes`
--

CREATE TABLE `user_notes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Usuario sobre el que se escribe la nota',
  `created_by` int(11) DEFAULT NULL COMMENT 'Admin que creó la nota',
  `note_text` text NOT NULL,
  `note_type` enum('general','warning','restriction','important') DEFAULT 'general',
  `is_pinned` tinyint(1) DEFAULT 0 COMMENT 'Mostrar siempre arriba',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notas administrativas sobre usuarios';

-- --------------------------------------------------------

--
-- Table structure for table `user_profile_photos`
--

CREATE TABLE `user_profile_photos` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `photo_path` varchar(255) NOT NULL COMMENT 'Ruta al archivo original',
  `thumbnail_path` varchar(255) DEFAULT NULL COMMENT 'Ruta al thumbnail (150x150)',
  `file_size` int(11) DEFAULT NULL COMMENT 'Tamaño en bytes',
  `mime_type` varchar(50) DEFAULT NULL COMMENT 'image/jpeg, image/png, etc.',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fotos de perfil de usuarios';

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_by` int(11) DEFAULT NULL COMMENT 'ID del admin que asignó el rol',
  `expires_at` datetime DEFAULT NULL COMMENT 'Fecha de expiración del rol (NULL = permanente)',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1 = Activo, 0 = Desactivado temporalmente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Asignación de roles a usuarios';

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role_id`, `assigned_at`, `assigned_by`, `expires_at`, `is_active`) VALUES
(2, 8, 1, '2025-10-21 13:22:30', 8, NULL, 1),
(19, 25, 4, '2025-10-24 01:00:00', 8, NULL, 1),
(20, 26, 3, '2025-10-24 01:15:16', 8, NULL, 1),
(22, 28, 4, '2025-10-24 01:47:03', 28, NULL, 1),
(23, 29, 3, '2025-10-24 01:52:23', 8, NULL, 1),
(26, 24, 4, '2025-10-24 03:29:01', 8, NULL, 1),
(28, 31, 10, '2025-10-26 23:30:14', 8, NULL, 1),
(29, 30, 4, '2025-10-26 23:30:47', 8, NULL, 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_roles_summary`
-- (See below for the actual view)
--
CREATE TABLE `user_roles_summary` (
`user_id` int(11)
,`email` varchar(255)
,`full_name` varchar(255)
,`user_type` enum('patient','doctor','admin')
,`roles` mediumtext
,`roles_display` mediumtext
,`total_roles` bigint(21)
,`total_permissions` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(128) NOT NULL COMMENT 'ID de sesión de PHP',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL COMMENT 'Navegador/dispositivo',
  `login_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Momento del login',
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Cuándo expira la sesión',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1=activa, 0=cerrada',
  `logout_at` timestamp NULL DEFAULT NULL COMMENT 'Momento del logout'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sesiones activas de usuarios';

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_statistics_by_role`
-- (See below for the actual view)
--
CREATE TABLE `user_statistics_by_role` (
`role_id` int(11)
,`role_name` varchar(50)
,`display_name` varchar(100)
,`total_users` bigint(21)
,`active_users` bigint(21)
,`inactive_users` bigint(21)
,`temporary_assignments` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `access_attempts_by_ip`
--
DROP TABLE IF EXISTS `access_attempts_by_ip`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `access_attempts_by_ip`  AS SELECT `sl`.`ip_address` AS `ip_address`, count(0) AS `total_attempts`, count(distinct `sl`.`user_id`) AS `unique_users`, max(`sl`.`created_at`) AS `last_attempt`, sum(case when `sl`.`event_type` = 'unauthorized_access' then 1 else 0 end) AS `unauthorized_attempts` FROM `security_logs` AS `sl` GROUP BY `sl`.`ip_address` ORDER BY count(0) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `active_sessions_view`
--
DROP TABLE IF EXISTS `active_sessions_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_sessions_view`  AS SELECT `s`.`id` AS `session_id`, `s`.`user_id` AS `user_id`, `u`.`full_name` AS `full_name`, `u`.`email` AS `email`, group_concat(distinct `r`.`display_name` order by `r`.`priority` ASC separator ', ') AS `roles`, `s`.`ip_address` AS `ip_address`, `s`.`login_at` AS `login_at`, `s`.`last_activity` AS `last_activity`, `s`.`expires_at` AS `expires_at`, timestampdiff(MINUTE,`s`.`last_activity`,current_timestamp()) AS `minutes_idle` FROM (((`user_sessions` `s` join `users` `u` on(`s`.`user_id` = `u`.`id`)) left join `user_roles` `ur` on(`u`.`id` = `ur`.`user_id` and `ur`.`is_active` = 1)) left join `roles` `r` on(`ur`.`role_id` = `r`.`id` and `r`.`status` = 'active')) WHERE `s`.`is_active` = 1 AND `s`.`expires_at` > current_timestamp() GROUP BY `s`.`id`, `s`.`user_id`, `u`.`full_name`, `u`.`email`, `s`.`ip_address`, `s`.`login_at`, `s`.`last_activity`, `s`.`expires_at` ORDER BY `s`.`last_activity` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `active_users_summary`
--
DROP TABLE IF EXISTS `active_users_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_users_summary`  AS SELECT `u`.`id` AS `id`, `u`.`full_name` AS `full_name`, `u`.`email` AS `email`, `u`.`status` AS `status`, group_concat(distinct `r`.`display_name` order by `r`.`priority` ASC separator ', ') AS `roles`, count(distinct `s`.`id`) AS `active_sessions`, max(`s`.`last_activity`) AS `last_seen`, (select count(0) from `user_change_history` where `user_change_history`.`user_id` = `u`.`id`) AS `total_changes` FROM (((`users` `u` left join `user_roles` `ur` on(`u`.`id` = `ur`.`user_id` and `ur`.`is_active` = 1)) left join `roles` `r` on(`ur`.`role_id` = `r`.`id` and `r`.`status` = 'active')) left join `user_sessions` `s` on(`u`.`id` = `s`.`user_id` and `s`.`is_active` = 1 and `s`.`expires_at` > current_timestamp())) WHERE `u`.`status` = 1 GROUP BY `u`.`id`, `u`.`full_name`, `u`.`email`, `u`.`status` ;

-- --------------------------------------------------------

--
-- Structure for view `expiring_user_roles`
--
DROP TABLE IF EXISTS `expiring_user_roles`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `expiring_user_roles`  AS SELECT `ur`.`id` AS `id`, `ur`.`user_id` AS `user_id`, `u`.`full_name` AS `full_name`, `u`.`email` AS `email`, `ur`.`role_id` AS `role_id`, `r`.`display_name` AS `role_name`, `ur`.`assigned_at` AS `assigned_at`, `ur`.`expires_at` AS `expires_at`, to_days(`ur`.`expires_at`) - to_days(current_timestamp()) AS `days_until_expiration` FROM ((`user_roles` `ur` join `users` `u` on(`ur`.`user_id` = `u`.`id`)) join `roles` `r` on(`ur`.`role_id` = `r`.`id`)) WHERE `ur`.`is_active` = 1 AND `ur`.`expires_at` is not null AND `ur`.`expires_at` <= current_timestamp() + interval 30 day ORDER BY `ur`.`expires_at` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `locked_accounts`
--
DROP TABLE IF EXISTS `locked_accounts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `locked_accounts`  AS SELECT `u`.`id` AS `id`, `u`.`email` AS `email`, `u`.`full_name` AS `full_name`, `u`.`user_type` AS `user_type`, `u`.`failed_login_attempts` AS `failed_login_attempts`, `u`.`account_locked_until` AS `account_locked_until`, `u`.`last_login` AS `last_login`, `u`.`last_login_ip` AS `last_login_ip`, CASE WHEN `u`.`account_locked_until` > current_timestamp() THEN 'LOCKED' ELSE 'UNLOCKED' END AS `lock_status`, timestampdiff(MINUTE,current_timestamp(),`u`.`account_locked_until`) AS `minutes_remaining` FROM `users` AS `u` WHERE `u`.`account_locked_until` is not null AND `u`.`account_locked_until` > current_timestamp() ORDER BY `u`.`account_locked_until` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `recent_changes_timeline`
--
DROP TABLE IF EXISTS `recent_changes_timeline`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `recent_changes_timeline`  AS SELECT `uch`.`id` AS `id`, `uch`.`user_id` AS `user_id`, `u1`.`full_name` AS `user_name`, `uch`.`changed_by` AS `changed_by`, `u2`.`full_name` AS `changed_by_name`, `uch`.`change_type` AS `change_type`, `uch`.`field_changed` AS `field_changed`, `uch`.`change_reason` AS `change_reason`, `uch`.`created_at` AS `created_at`, cast(`uch`.`created_at` as date) AS `change_date` FROM ((`user_change_history` `uch` join `users` `u1` on(`uch`.`user_id` = `u1`.`id`)) left join `users` `u2` on(`uch`.`changed_by` = `u2`.`id`)) WHERE `uch`.`created_at` >= current_timestamp() - interval 30 day ORDER BY `uch`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `role_permission_matrix`
--
DROP TABLE IF EXISTS `role_permission_matrix`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `role_permission_matrix`  AS SELECT `r`.`role_name` AS `role_name`, `r`.`display_name` AS `role_display_name`, `p`.`module` AS `module`, `p`.`permission_name` AS `permission_name`, `p`.`display_name` AS `permission_display_name`, `rp`.`granted_at` AS `granted_at` FROM ((`roles` `r` join `role_permissions` `rp` on(`r`.`id` = `rp`.`role_id`)) join `permissions` `p` on(`rp`.`permission_id` = `p`.`id`)) WHERE `r`.`status` = 'active' ORDER BY `r`.`priority` ASC, `p`.`module` ASC, `p`.`permission_name` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `unauthorized_access_summary`
--
DROP TABLE IF EXISTS `unauthorized_access_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `unauthorized_access_summary`  AS SELECT `u`.`id` AS `user_id`, `u`.`email` AS `email`, `u`.`full_name` AS `full_name`, count(0) AS `total_attempts`, max(`sl`.`created_at`) AS `last_attempt`, group_concat(distinct `sl`.`event_description` separator '; ') AS `attempted_actions` FROM (`security_logs` `sl` join `users` `u` on(`sl`.`user_id` = `u`.`id`)) WHERE `sl`.`event_type` = 'unauthorized_access' GROUP BY `u`.`id`, `u`.`email`, `u`.`full_name` ORDER BY count(0) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `users_password_expiring_soon`
--
DROP TABLE IF EXISTS `users_password_expiring_soon`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `users_password_expiring_soon`  AS SELECT `u`.`id` AS `id`, `u`.`email` AS `email`, `u`.`full_name` AS `full_name`, `u`.`user_type` AS `user_type`, `u`.`password_expires_at` AS `password_expires_at`, to_days(`u`.`password_expires_at`) - to_days(current_timestamp()) AS `days_until_expiry`, `u`.`password_changed_at` AS `last_password_change` FROM `users` AS `u` WHERE `u`.`status` = 'active' AND `u`.`password_expires_at` is not null AND `u`.`password_expires_at` > current_timestamp() AND to_days(`u`.`password_expires_at`) - to_days(current_timestamp()) <= 7 ORDER BY to_days(`u`.`password_expires_at`) - to_days(current_timestamp()) ASC ;

-- --------------------------------------------------------

--
-- Structure for view `user_changes_detailed`
--
DROP TABLE IF EXISTS `user_changes_detailed`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_changes_detailed`  AS SELECT `uch`.`id` AS `id`, `uch`.`user_id` AS `user_id`, `u1`.`full_name` AS `user_name`, `u1`.`email` AS `user_email`, `uch`.`changed_by` AS `changed_by`, `u2`.`full_name` AS `changed_by_name`, `u2`.`email` AS `changed_by_email`, `uch`.`change_type` AS `change_type`, `uch`.`field_changed` AS `field_changed`, `uch`.`old_value` AS `old_value`, `uch`.`new_value` AS `new_value`, `uch`.`change_reason` AS `change_reason`, `uch`.`ip_address` AS `ip_address`, `uch`.`created_at` AS `created_at` FROM ((`user_change_history` `uch` join `users` `u1` on(`uch`.`user_id` = `u1`.`id`)) left join `users` `u2` on(`uch`.`changed_by` = `u2`.`id`)) ORDER BY `uch`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `user_effective_permissions`
--
DROP TABLE IF EXISTS `user_effective_permissions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_effective_permissions`  AS SELECT DISTINCT `u`.`id` AS `user_id`, `u`.`email` AS `email`, `u`.`full_name` AS `full_name`, `r`.`id` AS `role_id`, `r`.`role_name` AS `role_name`, `p`.`id` AS `permission_id`, `p`.`permission_name` AS `permission_name`, `p`.`module` AS `module` FROM ((((`users` `u` join `user_roles` `ur` on(`u`.`id` = `ur`.`user_id`)) join `roles` `r` on(`ur`.`role_id` = `r`.`id`)) join `role_permissions` `rp` on(`r`.`id` = `rp`.`role_id`)) join `permissions` `p` on(`rp`.`permission_id` = `p`.`id`)) WHERE `u`.`status` = 'active' AND `ur`.`is_active` = 1 AND (`ur`.`expires_at` is null OR `ur`.`expires_at` > current_timestamp()) AND `r`.`status` = 'active' ;

-- --------------------------------------------------------

--
-- Structure for view `user_roles_summary`
--
DROP TABLE IF EXISTS `user_roles_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_roles_summary`  AS SELECT `u`.`id` AS `user_id`, `u`.`email` AS `email`, `u`.`full_name` AS `full_name`, `u`.`user_type` AS `user_type`, group_concat(`r`.`role_name` order by `r`.`priority` ASC separator ', ') AS `roles`, group_concat(`r`.`display_name` order by `r`.`priority` ASC separator ', ') AS `roles_display`, count(distinct `r`.`id`) AS `total_roles`, count(distinct `p`.`id`) AS `total_permissions` FROM ((((`users` `u` left join `user_roles` `ur` on(`u`.`id` = `ur`.`user_id` and `ur`.`is_active` = 1)) left join `roles` `r` on(`ur`.`role_id` = `r`.`id` and `r`.`status` = 'active')) left join `role_permissions` `rp` on(`r`.`id` = `rp`.`role_id`)) left join `permissions` `p` on(`rp`.`permission_id` = `p`.`id`)) WHERE `u`.`status` = 'active' GROUP BY `u`.`id`, `u`.`email`, `u`.`full_name`, `u`.`user_type` ;

-- --------------------------------------------------------

--
-- Structure for view `user_statistics_by_role`
--
DROP TABLE IF EXISTS `user_statistics_by_role`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_statistics_by_role`  AS SELECT `r`.`id` AS `role_id`, `r`.`role_name` AS `role_name`, `r`.`display_name` AS `display_name`, count(distinct `ur`.`user_id`) AS `total_users`, count(distinct case when `u`.`status` = 1 then `ur`.`user_id` end) AS `active_users`, count(distinct case when `u`.`status` = 0 then `ur`.`user_id` end) AS `inactive_users`, count(distinct case when `ur`.`expires_at` is not null then `ur`.`user_id` end) AS `temporary_assignments` FROM ((`roles` `r` left join `user_roles` `ur` on(`r`.`id` = `ur`.`role_id` and `ur`.`is_active` = 1)) left join `users` `u` on(`ur`.`user_id` = `u`.`id`)) GROUP BY `r`.`id`, `r`.`role_name`, `r`.`display_name` ORDER BY `r`.`priority` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_role_changes`
--
ALTER TABLE `audit_role_changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_performed_by` (`performed_by`),
  ADD KEY `idx_performed_at` (`performed_at`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `doctorslog`
--
ALTER TABLE `doctorslog`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctorspecilization`
--
ALTER TABLE `doctorspecilization`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_attempted_at` (`attempted_at`),
  ADD KEY `idx_result` (`attempt_result`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_history`
--
ALTER TABLE `password_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_changed_at` (`changed_at`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `password_policy_config`
--
ALTER TABLE `password_policy_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_used` (`used`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`),
  ADD KEY `idx_permission_name` (`permission_name`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `permission_categories`
--
ALTER TABLE `permission_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`),
  ADD KEY `idx_role_name` (`role_name`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `role_hierarchy`
--
ALTER TABLE `role_hierarchy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_hierarchy` (`parent_role_id`,`child_role_id`),
  ADD KEY `child_role_id` (`child_role_id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  ADD KEY `idx_role_id` (`role_id`),
  ADD KEY `idx_permission_id` (`permission_id`),
  ADD KEY `granted_by` (`granted_by`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `tblcontactus`
--
ALTER TABLE `tblcontactus`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblmedicalhistory`
--
ALTER TABLE `tblmedicalhistory`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `userlog`
--
ALTER TABLE `userlog`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_type` (`user_type`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_account_locked` (`account_locked_until`),
  ADD KEY `idx_password_expires` (`password_expires_at`),
  ADD KEY `idx_status_type` (`status`,`user_type`),
  ADD KEY `idx_full_name` (`full_name`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `user_change_history`
--
ALTER TABLE `user_change_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_changed_by` (`changed_by`),
  ADD KEY `idx_change_type` (`change_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `user_notes`
--
ALTER TABLE `user_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_note_type` (`note_type`),
  ADD KEY `idx_is_pinned` (`is_pinned`);

--
-- Indexes for table `user_profile_photos`
--
ALTER TABLE `user_profile_photos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_role` (`user_id`,`role_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_role_id` (`role_id`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `appointment`
--
ALTER TABLE `appointment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_role_changes`
--
ALTER TABLE `audit_role_changes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `doctorslog`
--
ALTER TABLE `doctorslog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctorspecilization`
--
ALTER TABLE `doctorspecilization`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `password_history`
--
ALTER TABLE `password_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `password_policy_config`
--
ALTER TABLE `password_policy_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `permission_categories`
--
ALTER TABLE `permission_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `role_hierarchy`
--
ALTER TABLE `role_hierarchy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=573;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `tblcontactus`
--
ALTER TABLE `tblcontactus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblmedicalhistory`
--
ALTER TABLE `tblmedicalhistory`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `userlog`
--
ALTER TABLE `userlog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `user_change_history`
--
ALTER TABLE `user_change_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `user_notes`
--
ALTER TABLE `user_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_profile_photos`
--
ALTER TABLE `user_profile_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_role_changes`
--
ALTER TABLE `audit_role_changes`
  ADD CONSTRAINT `audit_role_changes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `audit_role_changes_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `audit_role_changes_ibfk_3` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD CONSTRAINT `login_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_history`
--
ALTER TABLE `password_history`
  ADD CONSTRAINT `password_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `password_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `password_policy_config`
--
ALTER TABLE `password_policy_config`
  ADD CONSTRAINT `password_policy_config_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `permissions`
--
ALTER TABLE `permissions`
  ADD CONSTRAINT `permissions_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `permission_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `roles_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_hierarchy`
--
ALTER TABLE `role_hierarchy`
  ADD CONSTRAINT `role_hierarchy_ibfk_1` FOREIGN KEY (`parent_role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_hierarchy_ibfk_2` FOREIGN KEY (`child_role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_3` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD CONSTRAINT `security_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_change_history`
--
ALTER TABLE `user_change_history`
  ADD CONSTRAINT `user_change_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_change_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_notes`
--
ALTER TABLE `user_notes`
  ADD CONSTRAINT `user_notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_notes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_profile_photos`
--
ALTER TABLE `user_profile_photos`
  ADD CONSTRAINT `user_profile_photos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
