-- =============================================
-- Tabla: system_settings
-- Descripción: Configuraciones generales del sistema
-- Fecha: 2025-10-28
-- =============================================

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

-- =============================================
-- Insertar configuraciones iniciales
-- =============================================

-- Categoría: general (Información del hospital)
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_category`, `description`) VALUES
('hospital_name', 'Clínica Dental Muelitas', 'general', 'Nombre oficial del hospital o clínica'),
('hospital_address', '', 'general', 'Dirección física del hospital'),
('hospital_phone', '', 'general', 'Teléfono de contacto principal'),
('hospital_email', '', 'general', 'Email de contacto del hospital');

-- Categoría: hours (Horarios de atención)
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_category`, `description`) VALUES
('start_time', '08:00', 'hours', 'Hora de inicio de atención'),
('end_time', '18:00', 'hours', 'Hora de cierre de atención');

-- Categoría: notifications (Configuración de notificaciones)
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_category`, `description`) VALUES
('email_notifications', '1', 'notifications', 'Enviar notificaciones por email (1=activo, 0=inactivo)'),
('sms_notifications', '0', 'notifications', 'Enviar notificaciones por SMS (1=activo, 0=inactivo)');

-- Categoría: email (Formato de correos corporativos)
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_category`, `description`) VALUES
('email_domain', 'clinica.dental.muelitas', 'email', 'Dominio corporativo para emails'),
('email_format_template', '{firstname}.{lastname_initial}@{domain}', 'email', 'Plantilla de formato de email. Tokens: {firstname}, {lastname}, {firstname_initial}, {lastname_initial}, {domain}'),
('email_auto_generate', '1', 'email', 'Auto-generar emails al crear usuarios (1=sí, 0=no)'),
('email_allow_custom', '0', 'email', 'Permitir emails personalizados fuera del formato (1=sí, 0=no)');

-- =============================================
-- Fin del script
-- =============================================
