-- ============================================
-- MIGRACIÓN Y NORMALIZACIÓN DE BASE DE DATOS HMS
-- Fecha: 2025-10-12
-- Objetivo: Unificar autenticación en una tabla users
-- ============================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

USE hms;

-- ============================================
-- PASO 1: RENOMBRAR TABLA USERS ACTUAL
-- ============================================

RENAME TABLE users TO users_old;

-- ============================================
-- PASO 2: CREAR NUEVA TABLA USERS UNIFICADA
-- ============================================

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('patient','doctor','admin') NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `status` enum('active','inactive','blocked') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_user_type` (`user_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- PASO 3: MIGRAR PACIENTES (desde users_old)
-- ============================================

INSERT INTO `users` (`email`, `password`, `user_type`, `full_name`, `status`, `created_at`, `updated_at`)
SELECT
    email,
    password,
    'patient' as user_type,
    fullName,
    'active' as status,
    regDate,
    updationDate
FROM users_old
WHERE email IS NOT NULL AND email != '' AND email NOT IN (SELECT email FROM users);

-- ============================================
-- PASO 4: MIGRAR DOCTORES
-- ============================================

INSERT INTO `users` (`email`, `password`, `user_type`, `full_name`, `status`, `created_at`, `updated_at`)
SELECT
    docEmail,
    password,
    'doctor' as user_type,
    doctorName,
    'active' as status,
    creationDate,
    updationDate
FROM doctors
WHERE docEmail IS NOT NULL
  AND docEmail != ''
  AND docEmail NOT IN (SELECT email FROM users);

-- ============================================
-- PASO 5: MIGRAR ADMINISTRADORES
-- ============================================

INSERT INTO `users` (`email`, `password`, `user_type`, `full_name`, `status`, `created_at`)
SELECT
    CONCAT(username, '@hospital.com') as email,  -- Crear email si no existe
    password,
    'admin' as user_type,
    username as full_name,
    'active' as status,
    NOW()
FROM admin
WHERE CONCAT(username, '@hospital.com') NOT IN (SELECT email FROM users);

-- ============================================
-- PASO 6: CREAR TABLA PATIENTS (info específica)
-- ============================================

CREATE TABLE `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `address` longtext,
  `city` varchar(255) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `emergency_contact` varchar(255) DEFAULT NULL,
  `insurance_number` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `medical_notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_patients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migrar datos de pacientes
INSERT INTO `patients` (`user_id`, `address`, `city`, `gender`)
SELECT
    u.id,
    uo.address,
    uo.city,
    uo.gender
FROM users_old uo
INNER JOIN users u ON u.email = uo.email
WHERE u.user_type = 'patient';

-- ============================================
-- PASO 7: ACTUALIZAR TABLA DOCTORS
-- ============================================

-- Agregar columna user_id
ALTER TABLE `doctors` ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `id`;
ALTER TABLE `doctors` ADD KEY `idx_user_id` (`user_id`);
ALTER TABLE `doctors` ADD CONSTRAINT `fk_doctors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

-- Vincular doctors existentes con users
UPDATE doctors d
INNER JOIN users u ON u.email = d.docEmail
SET d.user_id = u.id
WHERE u.user_type = 'doctor';

-- ============================================
-- PASO 8: CREAR TABLA ADMINS (info específica)
-- ============================================

CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `access_level` enum('super','standard') DEFAULT 'standard',
  `permissions` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_admins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migrar admins
INSERT INTO `admins` (`user_id`, `access_level`)
SELECT
    u.id,
    'super' as access_level
FROM admin a
INNER JOIN users u ON u.email = CONCAT(a.username, '@hospital.com')
WHERE u.user_type = 'admin';

-- ============================================
-- PASO 9: ACTUALIZAR TABLA APPOINTMENT
-- ============================================

-- Agregar columnas para nuevas FK
ALTER TABLE `appointment` ADD COLUMN `patient_user_id` int(11) DEFAULT NULL AFTER `userId`;
ALTER TABLE `appointment` ADD COLUMN `doctor_user_id` int(11) DEFAULT NULL AFTER `doctorId`;

-- Actualizar patient_user_id
UPDATE appointment a
INNER JOIN users_old uo ON a.userId = uo.id
INNER JOIN users u ON u.email = uo.email
SET a.patient_user_id = u.id
WHERE u.user_type = 'patient';

-- Actualizar doctor_user_id
UPDATE appointment a
INNER JOIN doctors d ON a.doctorId = d.id
INNER JOIN users u ON u.id = d.user_id
SET a.doctor_user_id = u.id
WHERE u.user_type = 'doctor';

-- ============================================
-- PASO 10: ACTUALIZAR TABLA USERLOG
-- ============================================

ALTER TABLE `userlog` ADD COLUMN `user_id_new` int(11) DEFAULT NULL AFTER `userId`;

UPDATE userlog ul
INNER JOIN users_old uo ON ul.userId = uo.id
INNER JOIN users u ON u.email = uo.email
SET ul.user_id_new = u.id;

-- ============================================
-- PASO 11: ACTUALIZAR TABLA TBLMEDICALHISTORY
-- ============================================

ALTER TABLE `tblmedicalhistory` ADD COLUMN `patient_user_id` int(11) DEFAULT NULL AFTER `PatientID`;

UPDATE tblmedicalhistory tmh
INNER JOIN users_old uo ON tmh.PatientID = uo.id
INNER JOIN users u ON u.email = uo.email
SET tmh.patient_user_id = u.id
WHERE u.user_type = 'patient';

-- ============================================
-- PASO 12: ACTUALIZAR TABLA TBLPATIENT
-- ============================================

ALTER TABLE `tblpatient` ADD COLUMN `patient_user_id` int(11) DEFAULT NULL AFTER `ID`;

UPDATE tblpatient tp
INNER JOIN users_old uo ON tp.Docid = uo.id  -- Asumo que Docid es el doctor
INNER JOIN users u ON u.email = uo.email
SET tp.patient_user_id = u.id;

-- ============================================
-- VERIFICACIÓN
-- ============================================

SELECT '=== VERIFICACIÓN DE MIGRACIÓN ===' as '';

SELECT 'Total users en nueva tabla:' as 'Descripción', COUNT(*) as 'Cantidad' FROM users
UNION ALL
SELECT 'Pacientes:', COUNT(*) FROM users WHERE user_type = 'patient'
UNION ALL
SELECT 'Doctores:', COUNT(*) FROM users WHERE user_type = 'doctor'
UNION ALL
SELECT 'Administradores:', COUNT(*) FROM users WHERE user_type = 'admin'
UNION ALL
SELECT '', ''
UNION ALL
SELECT 'Pacientes con info detallada:', COUNT(*) FROM patients
UNION ALL
SELECT 'Doctores vinculados:', COUNT(*) FROM doctors WHERE user_id IS NOT NULL
UNION ALL
SELECT 'Admins vinculados:', COUNT(*) FROM admins;

-- Mostrar algunos registros de ejemplo
SELECT '=== EJEMPLOS DE USUARIOS ===' as '';
SELECT id, email, user_type, full_name, status FROM users LIMIT 10;

SET FOREIGN_KEY_CHECKS=1;

-- ============================================
-- NOTAS IMPORTANTES:
-- ============================================
-- 1. La tabla users_old se mantiene como respaldo
-- 2. Las tablas doctors y admin originales se mantienen
-- 3. Todas las FK antiguas aún funcionan
-- 4. Puedes eliminar las columnas antiguas después de verificar que todo funciona:
--    - ALTER TABLE doctors DROP COLUMN docEmail, DROP COLUMN password;
--    - DROP TABLE users_old;
--    - DROP TABLE admin;
-- ============================================
