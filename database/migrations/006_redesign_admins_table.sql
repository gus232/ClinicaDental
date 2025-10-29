-- ============================================================================
-- MIGRACIÓN 006: REDISEÑO DE TABLA ADMINS
-- ============================================================================
-- Descripción: Agrega campos profesionales técnicos para administradores IT
--              (seguridad, sistemas, desarrollo, infraestructura)
-- Fecha: 2025-10-28
-- Proyecto: SIS 321 - Sistema Hospital Muelitas
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- 1. BACKUP DE DATOS EXISTENTES
-- ============================================================================

CREATE TABLE IF NOT EXISTS admins_backup_20251028 AS
SELECT * FROM admins;

SELECT CONCAT('✓ Backup creado: ', COUNT(*), ' registros guardados') AS status
FROM admins_backup_20251028;

-- ============================================================================
-- 2. ELIMINAR TABLA ACTUAL
-- ============================================================================

DROP TABLE IF EXISTS admins;

SELECT '✓ Tabla admins antigua eliminada' AS status;

-- ============================================================================
-- 3. CREAR NUEVA TABLA ADMINS (REDISEÑADA)
-- ============================================================================

CREATE TABLE admins (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL UNIQUE COMMENT 'FK a users - Un usuario solo puede ser un admin',

  -- 👤 DATOS DE EMPLEADO
  employee_id VARCHAR(50) UNIQUE DEFAULT NULL COMMENT 'ID de empleado interno',
  department ENUM('it','security','operations','systems','development') DEFAULT 'operations' COMMENT 'Departamento técnico',
  job_title VARCHAR(100) DEFAULT NULL COMMENT 'Título del puesto (ej: Security Analyst, SysAdmin)',
  hire_date DATE DEFAULT NULL COMMENT 'Fecha de contratación',

  -- 🎓 DATOS PROFESIONALES TÉCNICOS
  technical_area VARCHAR(100) DEFAULT NULL COMMENT 'Área técnica principal (ej: Seguridad de Sistemas, Redes)',
  certifications TEXT DEFAULT NULL COMMENT 'Certificaciones profesionales (CISSP, CEH, CompTIA, etc.)',
  specialization VARCHAR(100) DEFAULT NULL COMMENT 'Especialización técnica específica',
  years_of_experience INT DEFAULT 0 COMMENT 'Años de experiencia en IT/Seguridad',
  education_level ENUM('technical','bachelor','master','doctorate') DEFAULT NULL COMMENT 'Nivel de educación',

  -- 📞 DATOS DE CONTACTO PROFESIONAL
  office_phone VARCHAR(20) DEFAULT NULL COMMENT 'Teléfono de oficina',
  extension VARCHAR(10) DEFAULT NULL COMMENT 'Extensión telefónica',
  office_location VARCHAR(100) DEFAULT NULL COMMENT 'Ubicación física de la oficina',
  reports_to INT DEFAULT NULL COMMENT 'ID del supervisor directo (FK a admins.id)',

  -- 🔐 NIVEL DE ACCESO ADMINISTRATIVO
  admin_level ENUM('technical','operational','security','super') DEFAULT 'operational' COMMENT 'Nivel administrativo',
  clearance_level ENUM('basic','elevated','critical') DEFAULT 'basic' COMMENT 'Nivel de clearance de seguridad',
  can_access_production BOOLEAN DEFAULT FALSE COMMENT 'Acceso a sistemas productivos',
  can_modify_security BOOLEAN DEFAULT FALSE COMMENT 'Puede modificar configuración de seguridad',

  -- 📅 HORARIOS Y DISPONIBILIDAD
  office_hours JSON DEFAULT NULL COMMENT 'Horario de oficina: {"lunes": "08:00-17:00", ...}',
  on_call_schedule JSON DEFAULT NULL COMMENT 'Horario de guardia/soporte',
  timezone VARCHAR(50) DEFAULT 'America/La_Paz' COMMENT 'Zona horaria',

  -- 🎯 RESPONSABILIDADES Y PROYECTOS
  main_responsibilities TEXT DEFAULT NULL COMMENT 'Responsabilidades principales',
  assigned_systems TEXT DEFAULT NULL COMMENT 'Sistemas asignados (separados por comas)',
  current_projects TEXT DEFAULT NULL COMMENT 'Proyectos actuales',

  -- 📊 CAPACITACIÓN Y COMPLIANCE
  last_security_training DATE DEFAULT NULL COMMENT 'Última capacitación de seguridad',
  security_training_expiry DATE DEFAULT NULL COMMENT 'Vencimiento de capacitación',
  background_check_date DATE DEFAULT NULL COMMENT 'Fecha de verificación de antecedentes',
  background_check_status ENUM('pending','approved','rejected') DEFAULT 'pending',

  -- 🏆 ESTADÍSTICAS Y EVALUACIÓN
  total_incidents_resolved INT DEFAULT 0 COMMENT 'Total de incidentes resueltos',
  average_resolution_time DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Tiempo promedio de resolución (horas)',
  performance_rating DECIMAL(3,2) DEFAULT 0.00 COMMENT 'Calificación de desempeño (0-5)',
  last_performance_review DATE DEFAULT NULL COMMENT 'Última evaluación de desempeño',

  -- 📌 METADATOS
  status ENUM('active','on_leave','suspended','terminated') DEFAULT 'active' COMMENT 'Estado del empleado',
  termination_date DATE DEFAULT NULL COMMENT 'Fecha de terminación (si aplica)',
  termination_reason VARCHAR(255) DEFAULT NULL COMMENT 'Razón de terminación',
  notes TEXT DEFAULT NULL COMMENT 'Notas administrativas',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- CONSTRAINTS
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reports_to) REFERENCES admins(id) ON DELETE SET NULL,

  -- INDEXES para performance
  INDEX idx_user_id (user_id),
  INDEX idx_department (department),
  INDEX idx_admin_level (admin_level),
  INDEX idx_clearance_level (clearance_level),
  INDEX idx_status (status),
  INDEX idx_employee_id (employee_id),
  INDEX idx_reports_to (reports_to)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Datos profesionales de administradores técnicos';

SELECT '✓ Tabla admins rediseñada creada exitosamente' AS status;

-- ============================================================================
-- 4. MIGRAR DATOS EXISTENTES
-- ============================================================================

-- Insertar admins existentes que tengan user_id válido
INSERT INTO admins (
    user_id,
    employee_id,
    department,
    admin_level,
    status
)
SELECT
    a.user_id,
    CONCAT('EMP', LPAD(a.user_id, 5, '0')) AS employee_id,  -- Generar employee_id temporal
    'operations' AS department,  -- Valor por defecto
    'operational' AS admin_level,  -- Valor por defecto
    'active' AS status
FROM admins_backup_20251028 a
WHERE a.user_id IS NOT NULL
  AND EXISTS (SELECT 1 FROM users WHERE id = a.user_id)
ON DUPLICATE KEY UPDATE
    employee_id = VALUES(employee_id);

SELECT CONCAT('✓ Migrados ', ROW_COUNT(), ' administradores con user_id válido') AS status;

-- ============================================================================
-- 5. ACTUALIZAR NIVELES SEGÚN ROLES RBAC
-- ============================================================================

-- Actualizar admin_level basado en los roles RBAC del usuario
UPDATE admins a
INNER JOIN user_roles ur ON a.user_id = ur.user_id
INNER JOIN roles r ON ur.role_id = r.id
SET
    a.admin_level = CASE
        WHEN r.role_name = 'admin_tecnico' THEN 'technical'
        WHEN r.role_name = 'oficial_seguridad_informacion' THEN 'security'
        WHEN r.role_name = 'admin_operativo' THEN 'operational'
        ELSE 'operational'
    END,
    a.clearance_level = CASE
        WHEN r.role_name IN ('admin_tecnico', 'oficial_seguridad_informacion') THEN 'critical'
        WHEN r.role_name = 'admin_operativo' THEN 'elevated'
        ELSE 'basic'
    END,
    a.department = CASE
        WHEN r.role_name = 'oficial_seguridad_informacion' THEN 'security'
        WHEN r.role_name = 'admin_tecnico' THEN 'systems'
        ELSE 'operations'
    END
WHERE ur.status = 'active';

SELECT CONCAT('✓ Actualizados niveles administrativos basados en roles RBAC') AS status;

-- ============================================================================
-- 6. VERIFICACIÓN DE INTEGRIDAD
-- ============================================================================

-- Estadísticas de la nueva tabla
SELECT
    COUNT(*) AS total_admins,
    SUM(CASE WHEN department = 'security' THEN 1 ELSE 0 END) AS admins_seguridad,
    SUM(CASE WHEN department = 'systems' THEN 1 ELSE 0 END) AS admins_sistemas,
    SUM(CASE WHEN department = 'operations' THEN 1 ELSE 0 END) AS admins_operaciones,
    SUM(CASE WHEN admin_level = 'technical' THEN 1 ELSE 0 END) AS nivel_tecnico,
    SUM(CASE WHEN admin_level = 'security' THEN 1 ELSE 0 END) AS nivel_seguridad,
    SUM(CASE WHEN clearance_level = 'critical' THEN 1 ELSE 0 END) AS clearance_critico
FROM admins;

-- Verificar integridad referencial
SELECT '✓ Verificando integridad referencial...' AS status;

SELECT
    CASE
        WHEN COUNT(*) = 0 THEN '✓ OK: Todos los admins tienen user_id válido'
        ELSE CONCAT('❌ ERROR: ', COUNT(*), ' admins sin user_id válido')
    END AS integrity_check
FROM admins a
LEFT JOIN users u ON a.user_id = u.id
WHERE u.id IS NULL;

-- ============================================================================
-- 7. DATOS DE EJEMPLO (OPCIONAL - COMENTADO)
-- ============================================================================

/*
-- Ejemplo: Actualizar datos de un administrador
UPDATE admins
SET
    technical_area = 'Seguridad de Sistemas',
    certifications = 'CISSP, CEH, CompTIA Security+',
    specialization = 'Seguridad de Aplicaciones Web',
    years_of_experience = 5,
    can_access_production = TRUE,
    can_modify_security = TRUE
WHERE user_id = 8; -- ID del admin principal
*/

-- ============================================================================
-- MIGRACIÓN COMPLETADA
-- ============================================================================

SELECT '========================================' AS '';
SELECT '✓ MIGRACIÓN 006 COMPLETADA' AS status;
SELECT '✓ Tabla admins rediseñada' AS '';
SELECT '✓ Campos profesionales técnicos agregados' AS '';
SELECT '✓ Niveles actualizados según roles RBAC' AS '';
SELECT '✓ Sistema preparado para auditoría y compliance' AS '';
SELECT '✓ Integridad referencial verificada' AS '';
SELECT '========================================' AS '';

-- NOTA: Si todo salió bien, puedes eliminar el backup:
-- DROP TABLE IF EXISTS admins_backup_20251028;
