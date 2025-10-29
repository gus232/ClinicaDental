-- ============================================================================
-- MIGRACIÓN 004: REDISEÑO DE TABLA DOCTORS
-- ============================================================================
-- Descripción: Elimina duplicación con tabla users y agrega campos específicos
--              para funcionalidad profesional de doctores
-- Fecha: 2025-10-28
-- Proyecto: SIS 321 - Sistema Hospital Muelitas
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- 1. BACKUP DE DATOS EXISTENTES (POR SI ACASO)
-- ============================================================================

-- Crear tabla temporal con datos actuales
CREATE TABLE IF NOT EXISTS doctors_backup_20251028 AS
SELECT * FROM doctors;

SELECT CONCAT('✓ Backup creado: ', COUNT(*), ' registros guardados') AS status
FROM doctors_backup_20251028;

-- ============================================================================
-- 2. ELIMINAR TABLA ACTUAL
-- ============================================================================

DROP TABLE IF EXISTS doctors;

SELECT '✓ Tabla doctors antigua eliminada' AS status;

-- ============================================================================
-- 3. CREAR NUEVA TABLA DOCTORS (REDISEÑADA)
-- ============================================================================

CREATE TABLE doctors (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL UNIQUE COMMENT 'FK a users - Un usuario solo puede ser un doctor',

  -- 🩺 DATOS PROFESIONALES MÉDICOS
  specialization_id INT DEFAULT NULL COMMENT 'FK a doctorspecilization',
  license_number VARCHAR(50) UNIQUE DEFAULT NULL COMMENT 'Número de licencia médica (único)',
  years_of_experience INT DEFAULT 0 COMMENT 'Años de experiencia profesional',
  consultation_fee DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Honorarios por consulta',

  -- 📅 DATOS DE HORARIO Y AGENDA
  working_hours JSON DEFAULT NULL COMMENT 'Horarios de trabajo: {"lunes": "08:00-17:00", ...}',
  max_daily_appointments INT DEFAULT 20 COMMENT 'Máximo de citas por día',
  consultation_duration INT DEFAULT 30 COMMENT 'Duración de consulta en minutos',

  -- 📊 DATOS DE RENDIMIENTO (para view_doctor_performance)
  total_appointments INT DEFAULT 0 COMMENT 'Total de citas históricas',
  completed_appointments INT DEFAULT 0 COMMENT 'Citas completadas exitosamente',
  cancelled_appointments INT DEFAULT 0 COMMENT 'Citas canceladas',
  rating DECIMAL(3,2) DEFAULT 0.00 COMMENT 'Calificación promedio (0.00-5.00)',
  total_ratings INT DEFAULT 0 COMMENT 'Cantidad de evaluaciones recibidas',

  -- 🏥 DATOS ADICIONALES
  bio TEXT DEFAULT NULL COMMENT 'Biografía/Descripción profesional',
  languages VARCHAR(255) DEFAULT 'Español' COMMENT 'Idiomas que habla (separados por comas)',

  -- 📌 METADATOS
  status ENUM('active','on_leave','retired','suspended') DEFAULT 'active' COMMENT 'Estado del doctor',
  hire_date DATE DEFAULT NULL COMMENT 'Fecha de contratación',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- CONSTRAINTS
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (specialization_id) REFERENCES doctorspecilization(id) ON DELETE SET NULL,

  -- INDEXES para performance
  INDEX idx_user_id (user_id),
  INDEX idx_specialization (specialization_id),
  INDEX idx_status (status),
  INDEX idx_rating (rating),
  INDEX idx_consultation_fee (consultation_fee)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Datos profesionales específicos de doctores';

SELECT '✓ Tabla doctors rediseñada creada exitosamente' AS status;

-- ============================================================================
-- 4. MIGRAR DATOS EXISTENTES (SI HAY ALGUNO CON user_id VÁLIDO)
-- ============================================================================

-- Insertar solo los doctores que tengan user_id válido en la tabla users
-- Primero: Migrar datos básicos con JOIN a specialization
INSERT INTO doctors (
    user_id,
    specialization_id,
    consultation_fee,
    hire_date,
    status
)
SELECT
    d.user_id,
    ds.id AS specialization_id,
    CAST(d.docFees AS DECIMAL(10,2)) AS consultation_fee,
    d.creationDate AS hire_date,
    'active' AS status
FROM doctors_backup_20251028 d
LEFT JOIN doctorspecilization ds ON ds.specilization = d.specilization
WHERE d.user_id IS NOT NULL
  AND EXISTS (SELECT 1 FROM users WHERE id = d.user_id)
ON DUPLICATE KEY UPDATE
    specialization_id = VALUES(specialization_id),
    consultation_fee = VALUES(consultation_fee);

SELECT CONCAT('✓ Migrados ', ROW_COUNT(), ' doctores con user_id válido') AS status;

-- ============================================================================
-- 5. VERIFICACIÓN DE INTEGRIDAD
-- ============================================================================

-- Verificar que todos los doctors tienen un user_id válido
SELECT
    COUNT(*) AS total_doctors,
    SUM(CASE WHEN specialization_id IS NOT NULL THEN 1 ELSE 0 END) AS doctors_con_especialidad,
    SUM(CASE WHEN consultation_fee > 0 THEN 1 ELSE 0 END) AS doctors_con_honorarios
FROM doctors;

-- Verificar integridad referencial
SELECT '✓ Verificando integridad referencial...' AS status;

SELECT
    CASE
        WHEN COUNT(*) = 0 THEN '✓ OK: Todos los doctors tienen user_id válido'
        ELSE CONCAT('❌ ERROR: ', COUNT(*), ' doctors sin user_id válido')
    END AS integrity_check
FROM doctors d
LEFT JOIN users u ON d.user_id = u.id
WHERE u.id IS NULL;

-- ============================================================================
-- MIGRACIÓN COMPLETADA
-- ============================================================================

SELECT '========================================' AS '';
SELECT '✓ MIGRACIÓN 004 COMPLETADA' AS status;
SELECT '✓ Tabla doctors rediseñada' AS '';
SELECT '✓ Campos duplicados eliminados' AS '';
SELECT '✓ Campos profesionales agregados' AS '';
SELECT '✓ Integridad referencial verificada' AS '';
SELECT '========================================' AS '';

-- NOTA: Si todo salió bien, puedes eliminar el backup:
-- DROP TABLE IF EXISTS doctors_backup_20251028;
