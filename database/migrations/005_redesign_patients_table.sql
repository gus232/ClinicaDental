-- ============================================================================
-- MIGRACIÓN 005: REDISEÑO DE TABLA PATIENTS
-- ============================================================================
-- Descripción: Agrega campos médicos y de facturación para preparar el sistema
--              para Medical Records (categoría 5) y Billing (categoría 6)
-- Fecha: 2025-10-28
-- Proyecto: SIS 321 - Sistema Hospital Muelitas
-- ============================================================================

USE hms_v2;

-- ============================================================================
-- 1. BACKUP DE DATOS EXISTENTES
-- ============================================================================

CREATE TABLE IF NOT EXISTS patients_backup_20251028 AS
SELECT * FROM patients;

SELECT CONCAT('✓ Backup creado: ', COUNT(*), ' registros guardados') AS status
FROM patients_backup_20251028;

-- ============================================================================
-- 2. ELIMINAR TABLA ACTUAL
-- ============================================================================

DROP TABLE IF EXISTS patients;

SELECT '✓ Tabla patients antigua eliminada' AS status;

-- ============================================================================
-- 3. CREAR NUEVA TABLA PATIENTS (REDISEÑADA)
-- ============================================================================

CREATE TABLE patients (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL UNIQUE COMMENT 'FK a users - Un usuario solo puede ser un paciente',

  -- 📍 DATOS DEMOGRÁFICOS Y DE CONTACTO
  address VARCHAR(255) DEFAULT NULL COMMENT 'Dirección completa',
  city VARCHAR(100) DEFAULT NULL COMMENT 'Ciudad',
  state VARCHAR(100) DEFAULT NULL COMMENT 'Estado/Departamento',
  postal_code VARCHAR(20) DEFAULT NULL COMMENT 'Código postal',
  phone VARCHAR(20) DEFAULT NULL COMMENT 'Teléfono principal',
  emergency_contact VARCHAR(255) DEFAULT NULL COMMENT 'Nombre del contacto de emergencia',
  emergency_phone VARCHAR(20) DEFAULT NULL COMMENT 'Teléfono de emergencia',

  -- 🧬 DATOS MÉDICOS BÁSICOS (para Medical Records - categoría 5)
  gender ENUM('male','female','other','prefer_not_to_say') DEFAULT NULL COMMENT 'Género del paciente',
  date_of_birth DATE DEFAULT NULL COMMENT 'Fecha de nacimiento',
  blood_type ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL COMMENT 'Tipo de sangre',
  height DECIMAL(5,2) DEFAULT NULL COMMENT 'Altura en cm',
  weight DECIMAL(5,2) DEFAULT NULL COMMENT 'Peso en kg',

  -- 🏥 HISTORIAL MÉDICO BÁSICO
  allergies TEXT DEFAULT NULL COMMENT 'Alergias conocidas (separadas por comas)',
  chronic_conditions TEXT DEFAULT NULL COMMENT 'Condiciones crónicas o enfermedades preexistentes',
  current_medications TEXT DEFAULT NULL COMMENT 'Medicamentos que toma actualmente',
  past_surgeries TEXT DEFAULT NULL COMMENT 'Cirugías previas',
  family_medical_history TEXT DEFAULT NULL COMMENT 'Historial médico familiar relevante',

  -- 💳 DATOS DE SEGURO/FACTURACIÓN (para Billing - categoría 6)
  has_insurance BOOLEAN DEFAULT FALSE COMMENT 'Tiene seguro médico',
  insurance_provider VARCHAR(100) DEFAULT NULL COMMENT 'Compañía de seguros',
  insurance_number VARCHAR(50) DEFAULT NULL COMMENT 'Número de póliza',
  insurance_expiry_date DATE DEFAULT NULL COMMENT 'Fecha de vencimiento del seguro',

  -- 📊 ESTADÍSTICAS Y METADATOS
  total_appointments INT DEFAULT 0 COMMENT 'Total de citas históricas',
  completed_appointments INT DEFAULT 0 COMMENT 'Citas completadas',
  cancelled_appointments INT DEFAULT 0 COMMENT 'Citas canceladas',
  last_appointment_date DATE DEFAULT NULL COMMENT 'Fecha de la última cita',

  -- 📌 METADATOS
  registration_date DATE DEFAULT (CURRENT_DATE) COMMENT 'Fecha de registro en el sistema',
  status ENUM('active','inactive','deceased') DEFAULT 'active' COMMENT 'Estado del paciente',
  notes TEXT DEFAULT NULL COMMENT 'Notas administrativas adicionales',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- CONSTRAINTS
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

  -- INDEXES para performance
  INDEX idx_user_id (user_id),
  INDEX idx_city (city),
  INDEX idx_blood_type (blood_type),
  INDEX idx_status (status),
  INDEX idx_date_of_birth (date_of_birth),
  INDEX idx_insurance_provider (insurance_provider)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Datos médicos y demográficos de pacientes';

SELECT '✓ Tabla patients rediseñada creada exitosamente' AS status;

-- ============================================================================
-- 4. MIGRAR DATOS EXISTENTES
-- ============================================================================

-- Insertar datos de pacientes existentes que tengan user_id válido
INSERT INTO patients (
    user_id,
    address,
    city,
    gender,
    status
)
SELECT
    p.user_id,
    p.address,
    p.city,
    CASE
        WHEN p.gender = 'male' THEN 'male'
        WHEN p.gender = 'female' THEN 'female'
        ELSE NULL
    END AS gender,
    'active' AS status
FROM patients_backup_20251028 p
WHERE p.user_id IS NOT NULL
  AND EXISTS (SELECT 1 FROM users WHERE id = p.user_id)
ON DUPLICATE KEY UPDATE
    address = VALUES(address),
    city = VALUES(city),
    gender = VALUES(gender);

SELECT CONCAT('✓ Migrados ', ROW_COUNT(), ' pacientes con user_id válido') AS status;

-- ============================================================================
-- 5. VERIFICACIÓN DE INTEGRIDAD
-- ============================================================================

-- Estadísticas de la nueva tabla
SELECT
    COUNT(*) AS total_patients,
    SUM(CASE WHEN gender IS NOT NULL THEN 1 ELSE 0 END) AS patients_con_genero,
    SUM(CASE WHEN address IS NOT NULL THEN 1 ELSE 0 END) AS patients_con_direccion,
    SUM(CASE WHEN has_insurance = TRUE THEN 1 ELSE 0 END) AS patients_con_seguro,
    SUM(CASE WHEN blood_type IS NOT NULL THEN 1 ELSE 0 END) AS patients_con_tipo_sangre
FROM patients;

-- Verificar integridad referencial
SELECT '✓ Verificando integridad referencial...' AS status;

SELECT
    CASE
        WHEN COUNT(*) = 0 THEN '✓ OK: Todos los patients tienen user_id válido'
        ELSE CONCAT('❌ ERROR: ', COUNT(*), ' patients sin user_id válido')
    END AS integrity_check
FROM patients p
LEFT JOIN users u ON p.user_id = u.id
WHERE u.id IS NULL;

-- ============================================================================
-- MIGRACIÓN COMPLETADA
-- ============================================================================

SELECT '========================================' AS '';
SELECT '✓ MIGRACIÓN 005 COMPLETADA' AS status;
SELECT '✓ Tabla patients rediseñada' AS '';
SELECT '✓ Campos médicos agregados' AS '';
SELECT '✓ Preparación para Medical Records (categoría 5)' AS '';
SELECT '✓ Preparación para Billing (categoría 6)' AS '';
SELECT '✓ Integridad referencial verificada' AS '';
SELECT '========================================' AS '';

-- NOTA: Si todo salió bien, puedes eliminar el backup:
-- DROP TABLE IF EXISTS patients_backup_20251028;
