-- ============================================================================
-- SEED 003: ROLES Y PERMISOS POR DEFECTO
-- ============================================================================
-- Descripción: Datos iniciales para el sistema RBAC
--   - 5 Roles principales: Super Admin, Admin, Doctor, Patient, Receptionist
--   - Permisos granulares organizados por módulos
--   - Asignación de permisos a roles
--
-- Versión: 2.2.0
-- Fecha: 2025-10-20
-- Proyecto: SIS 321 - Seguridad de Sistemas
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- 1. CATEGORÍAS DE PERMISOS
-- ============================================================================

INSERT INTO permission_categories (category_name, display_name, description, icon, sort_order) VALUES
('users', 'Gestión de Usuarios', 'Permisos relacionados con la administración de usuarios', 'fa-users', 1),
('patients', 'Gestión de Pacientes', 'Permisos para manejo de pacientes', 'fa-wheelchair', 2),
('doctors', 'Gestión de Doctores', 'Permisos para manejo de doctores', 'fa-user-md', 3),
('appointments', 'Gestión de Citas', 'Permisos para manejo de citas médicas', 'fa-calendar', 4),
('medical_records', 'Registros Médicos', 'Permisos para historiales médicos', 'fa-file-text-o', 5),
('billing', 'Facturación', 'Permisos para manejo de facturación', 'fa-usd', 6),
('reports', 'Reportes', 'Permisos para generación de reportes', 'fa-bar-chart', 7),
('system', 'Configuración del Sistema', 'Permisos de administración del sistema', 'fa-cogs', 8),
('security', 'Seguridad', 'Permisos de auditoría y seguridad', 'fa-shield', 9);

-- ============================================================================
-- 2. ROLES DEL SISTEMA
-- ============================================================================

INSERT INTO roles (role_name, display_name, description, is_system_role, priority, status) VALUES
('super_admin', 'Super Administrador', 'Acceso total al sistema sin restricciones', 1, 1, 'active'),
('admin', 'Administrador', 'Gestión completa excepto configuración crítica del sistema', 1, 10, 'active'),
('doctor', 'Doctor', 'Gestión de pacientes, citas y registros médicos', 1, 20, 'active'),
('patient', 'Paciente', 'Acceso limitado a sus propios datos y citas', 1, 40, 'active'),
('receptionist', 'Recepcionista', 'Gestión de citas y registro de pacientes', 1, 30, 'active'),
('nurse', 'Enfermera', 'Asistencia en registros médicos y gestión de pacientes', 1, 25, 'active'),
('lab_technician', 'Técnico de Laboratorio', 'Gestión de resultados de laboratorio', 1, 35, 'active');

-- ============================================================================
-- 3. PERMISOS DEL SISTEMA
-- ============================================================================

-- CATEGORÍA: USUARIOS (users)
INSERT INTO permissions (permission_name, display_name, description, module, category_id, is_system_permission) VALUES
('view_users', 'Ver Usuarios', 'Permite ver la lista de usuarios del sistema', 'users', 1, 1),
('create_user', 'Crear Usuario', 'Permite registrar nuevos usuarios', 'users', 1, 1),
('edit_user', 'Editar Usuario', 'Permite modificar información de usuarios', 'users', 1, 1),
('delete_user', 'Eliminar Usuario', 'Permite dar de baja usuarios', 'users', 1, 1),
('manage_user_roles', 'Gestionar Roles de Usuario', 'Permite asignar/revocar roles a usuarios', 'users', 1, 1),
('unlock_accounts', 'Desbloquear Cuentas', 'Permite desbloquear cuentas bloqueadas', 'users', 1, 1),
('reset_passwords', 'Resetear Contraseñas', 'Permite resetear contraseñas de otros usuarios', 'users', 1, 1),
('view_user_activity', 'Ver Actividad de Usuarios', 'Permite ver logs de actividad de usuarios', 'users', 1, 1);

-- CATEGORÍA: PACIENTES (patients)
INSERT INTO permissions (permission_name, display_name, description, module, category_id, is_system_permission) VALUES
('view_patients', 'Ver Pacientes', 'Permite ver la lista de pacientes', 'patients', 2, 1),
('view_patient_details', 'Ver Detalles de Paciente', 'Permite ver información detallada de pacientes', 'patients', 2, 1),
('create_patient', 'Registrar Paciente', 'Permite registrar nuevos pacientes', 'patients', 2, 1),
('edit_patient', 'Editar Paciente', 'Permite modificar información de pacientes', 'patients', 2, 1),
('delete_patient', 'Eliminar Paciente', 'Permite dar de baja pacientes', 'patients', 2, 1),
('view_own_patient_data', 'Ver Mis Datos', 'Permite al paciente ver su propia información', 'patients', 2, 1),
('export_patient_data', 'Exportar Datos de Pacientes', 'Permite exportar información de pacientes', 'patients', 2, 1);

-- CATEGORÍA: DOCTORES (doctors)
INSERT INTO permissions (permission_name, display_name, description, module, category_id, is_system_permission) VALUES
('view_doctors', 'Ver Doctores', 'Permite ver la lista de doctores', 'doctors', 3, 1),
('create_doctor', 'Registrar Doctor', 'Permite registrar nuevos doctores', 'doctors', 3, 1),
('edit_doctor', 'Editar Doctor', 'Permite modificar información de doctores', 'doctors', 3, 1),
('delete_doctor', 'Eliminar Doctor', 'Permite dar de baja doctores', 'doctors', 3, 1),
('manage_doctor_schedule', 'Gestionar Horarios de Doctor', 'Permite configurar horarios de doctores', 'doctors', 3, 1),
('view_doctor_performance', 'Ver Rendimiento de Doctores', 'Permite ver estadísticas de doctores', 'doctors', 3, 1);

-- CATEGORÍA: CITAS (appointments)
INSERT INTO permissions (permission_name, display_name, description, module, category_id, is_system_permission) VALUES
('view_appointments', 'Ver Citas', 'Permite ver todas las citas', 'appointments', 4, 1),
('view_own_appointments', 'Ver Mis Citas', 'Permite ver solo sus propias citas', 'appointments', 4, 1),
('create_appointment', 'Crear Cita', 'Permite agendar nuevas citas', 'appointments', 4, 1),
('edit_appointment', 'Editar Cita', 'Permite modificar citas existentes', 'appointments', 4, 1),
('cancel_appointment', 'Cancelar Cita', 'Permite cancelar citas', 'appointments', 4, 1),
('approve_appointment', 'Aprobar Cita', 'Permite aprobar/rechazar citas', 'appointments', 4, 1),
('reschedule_appointment', 'Reprogramar Cita', 'Permite cambiar fecha/hora de citas', 'appointments', 4, 1);

-- CATEGORÍA: REGISTROS MÉDICOS (medical_records)
INSERT INTO permissions (permission_name, display_name, description, module, category_id, is_system_permission) VALUES
('view_medical_records', 'Ver Registros Médicos', 'Permite ver historiales médicos', 'medical_records', 5, 1),
('view_own_medical_records', 'Ver Mi Historial Médico', 'Permite ver solo su propio historial', 'medical_records', 5, 1),
('create_medical_record', 'Crear Registro Médico', 'Permite crear nuevas entradas médicas', 'medical_records', 5, 1),
('edit_medical_record', 'Editar Registro Médico', 'Permite modificar registros médicos', 'medical_records', 5, 1),
('delete_medical_record', 'Eliminar Registro Médico', 'Permite eliminar registros médicos', 'medical_records', 5, 1),
('view_prescriptions', 'Ver Recetas', 'Permite ver recetas médicas', 'medical_records', 5, 1),
('create_prescription', 'Crear Receta', 'Permite generar recetas médicas', 'medical_records', 5, 1);

-- CATEGORÍA: FACTURACIÓN (billing)
INSERT INTO permissions (permission_name, display_name, description, module, category_id, is_system_permission) VALUES
('view_invoices', 'Ver Facturas', 'Permite ver facturas', 'billing', 6, 1),
('view_own_invoices', 'Ver Mis Facturas', 'Permite ver solo sus propias facturas', 'billing', 6, 1),
('create_invoice', 'Crear Factura', 'Permite generar nuevas facturas', 'billing', 6, 1),
('edit_invoice', 'Editar Factura', 'Permite modificar facturas', 'billing', 6, 1),
('delete_invoice', 'Eliminar Factura', 'Permite eliminar facturas', 'billing', 6, 1),
('process_payment', 'Procesar Pagos', 'Permite registrar pagos', 'billing', 6, 1),
('view_payment_reports', 'Ver Reportes de Pagos', 'Permite ver reportes financieros', 'billing', 6, 1);

-- CATEGORÍA: REPORTES (reports)
INSERT INTO permissions (permission_name, display_name, description, module, category_id, is_system_permission) VALUES
('view_reports', 'Ver Reportes', 'Permite ver reportes generales', 'reports', 7, 1),
('create_report', 'Crear Reporte', 'Permite generar nuevos reportes', 'reports', 7, 1),
('export_reports', 'Exportar Reportes', 'Permite exportar reportes a PDF/Excel', 'reports', 7, 1),
('view_analytics', 'Ver Analíticas', 'Permite ver dashboards analíticos', 'reports', 7, 1),
('view_audit_logs', 'Ver Logs de Auditoría', 'Permite ver registros de auditoría', 'reports', 7, 1);

-- CATEGORÍA: SISTEMA (system)
INSERT INTO permissions (permission_name, display_name, description, module, category_id, is_system_permission) VALUES
('manage_roles', 'Gestionar Roles', 'Permite crear/editar/eliminar roles', 'system', 8, 1),
('manage_permissions', 'Gestionar Permisos', 'Permite asignar permisos a roles', 'system', 8, 1),
('manage_system_settings', 'Gestionar Configuración', 'Permite modificar configuración del sistema', 'system', 8, 1),
('manage_password_policies', 'Gestionar Políticas de Contraseña', 'Permite configurar políticas de seguridad', 'system', 8, 1),
('view_system_logs', 'Ver Logs del Sistema', 'Permite ver logs técnicos', 'system', 8, 1),
('backup_database', 'Respaldar Base de Datos', 'Permite crear backups', 'system', 8, 1),
('restore_database', 'Restaurar Base de Datos', 'Permite restaurar desde backups', 'system', 8, 1);

-- CATEGORÍA: SEGURIDAD (security)
INSERT INTO permissions (permission_name, display_name, description, module, category_id, is_system_permission) VALUES
('view_security_logs', 'Ver Logs de Seguridad', 'Permite ver intentos de login y eventos de seguridad', 'security', 9, 1),
('manage_security_settings', 'Gestionar Seguridad', 'Permite configurar opciones de seguridad', 'security', 9, 1),
('view_failed_logins', 'Ver Intentos Fallidos', 'Permite ver intentos de login fallidos', 'security', 9, 1),
('manage_session_timeout', 'Gestionar Timeouts', 'Permite configurar tiempos de sesión', 'security', 9, 1);

-- ============================================================================
-- 4. ASIGNACIÓN DE PERMISOS A ROLES
-- ============================================================================

-- ========================================
-- ROL: SUPER ADMIN (Acceso Total)
-- ========================================
-- El Super Admin tiene TODOS los permisos
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- ========================================
-- ROL: ADMIN
-- ========================================
-- Admin tiene casi todos los permisos excepto algunos críticos del sistema
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions
WHERE permission_name NOT IN (
    'restore_database',
    'manage_system_settings',
    'backup_database'
);

-- ========================================
-- ROL: DOCTOR
-- ========================================
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions
WHERE permission_name IN (
    -- Pacientes
    'view_patients',
    'view_patient_details',
    'create_patient',
    'edit_patient',

    -- Citas
    'view_appointments',
    'create_appointment',
    'edit_appointment',
    'cancel_appointment',
    'approve_appointment',
    'reschedule_appointment',

    -- Registros Médicos
    'view_medical_records',
    'create_medical_record',
    'edit_medical_record',
    'view_prescriptions',
    'create_prescription',

    -- Reportes
    'view_reports',
    'create_report',
    'export_reports',

    -- Doctores
    'view_doctors',
    'manage_doctor_schedule'
);

-- ========================================
-- ROL: PATIENT
-- ========================================
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions
WHERE permission_name IN (
    -- Solo sus propios datos
    'view_own_patient_data',
    'view_own_appointments',
    'view_own_medical_records',
    'view_own_invoices',

    -- Acciones limitadas
    'create_appointment',
    'cancel_appointment',

    -- Ver información general
    'view_doctors'
);

-- ========================================
-- ROL: RECEPTIONIST
-- ========================================
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions
WHERE permission_name IN (
    -- Pacientes
    'view_patients',
    'view_patient_details',
    'create_patient',
    'edit_patient',

    -- Citas
    'view_appointments',
    'create_appointment',
    'edit_appointment',
    'cancel_appointment',
    'reschedule_appointment',

    -- Facturación
    'view_invoices',
    'create_invoice',
    'process_payment',

    -- Doctores
    'view_doctors',
    'manage_doctor_schedule',

    -- Reportes básicos
    'view_reports'
);

-- ========================================
-- ROL: NURSE
-- ========================================
INSERT INTO role_permissions (role_id, permission_id)
SELECT 6, id FROM permissions
WHERE permission_name IN (
    -- Pacientes
    'view_patients',
    'view_patient_details',
    'edit_patient',

    -- Citas
    'view_appointments',
    'edit_appointment',

    -- Registros Médicos
    'view_medical_records',
    'create_medical_record',
    'view_prescriptions',

    -- Doctores
    'view_doctors'
);

-- ========================================
-- ROL: LAB TECHNICIAN
-- ========================================
INSERT INTO role_permissions (role_id, permission_id)
SELECT 7, id FROM permissions
WHERE permission_name IN (
    -- Pacientes (solo lectura)
    'view_patients',
    'view_patient_details',

    -- Registros Médicos
    'view_medical_records',
    'create_medical_record',

    -- Reportes
    'view_reports',
    'create_report'
);

-- ============================================================================
-- 5. ASIGNAR ROLES A USUARIOS EXISTENTES
-- ============================================================================

-- Asignar rol de Admin al primer usuario (ID 1 - si existe)
-- Esto es solo un ejemplo, ajustar según necesidad
INSERT INTO user_roles (user_id, role_id, assigned_by)
SELECT 1, 1, 1 FROM users WHERE id = 1 LIMIT 1
ON DUPLICATE KEY UPDATE assigned_at = CURRENT_TIMESTAMP;

-- ============================================================================
-- 6. CONFIGURAR JERARQUÍA DE ROLES (Opcional)
-- ============================================================================

-- Super Admin hereda todos los permisos
-- Admin hereda de Doctor y Receptionist
INSERT INTO role_hierarchy (parent_role_id, child_role_id) VALUES
(2, 3), -- Admin hereda de Doctor
(2, 5), -- Admin hereda de Receptionist
(3, 6); -- Doctor hereda de Nurse

-- ============================================================================
-- SEED COMPLETADO EXITOSAMENTE
-- ============================================================================

SELECT '✓ Seed 003_default_roles_permissions.sql ejecutado exitosamente' AS status;
SELECT CONCAT('✓ Roles creados: ', COUNT(*), ' roles del sistema') AS status FROM roles;
SELECT CONCAT('✓ Permisos creados: ', COUNT(*), ' permisos granulares') AS status FROM permissions;
SELECT CONCAT('✓ Asignaciones creadas: ', COUNT(*), ' permisos asignados a roles') AS status FROM role_permissions;
SELECT '✓ Sistema RBAC completamente configurado' AS status;

-- ============================================================================
-- VERIFICACIÓN: Ver matriz de permisos
-- ============================================================================

SELECT
    r.display_name AS Rol,
    COUNT(rp.permission_id) AS 'Total Permisos'
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.display_name
ORDER BY r.priority;
