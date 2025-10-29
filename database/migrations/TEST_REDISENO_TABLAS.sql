-- ============================================================================
-- SCRIPT DE TESTING: VERIFICACIÓN DEL REDISEÑO DE TABLAS
-- ============================================================================
-- Descripción: Verifica que el rediseño de doctors, patients y admins
--              funcione correctamente con datos de prueba
-- Fecha: 2025-10-28
-- Proyecto: SIS 321 - Sistema Hospital Muelitas
--
-- ⚠️  IMPORTANTE: Este script es para TESTING
-- ⚠️  Ejecuta DESPUÉS de aplicar las migraciones
-- ⚠️  NO ejecutar en producción con datos reales
--
-- ============================================================================

USE hms_v2;

SELECT '╔═══════════════════════════════════════════════════════════════╗' AS '';
SELECT '║          SCRIPT DE TESTING - REDISEÑO DE TABLAS               ║' AS '';
SELECT '╚═══════════════════════════════════════════════════════════════╝' AS '';
SELECT '' AS '';

-- ============================================================================
-- 1. VERIFICAR ESTRUCTURA DE TABLAS
-- ============================================================================

SELECT '📋 PASO 1: Verificando estructura de tablas...' AS status;
SELECT '' AS '';

-- Verificar que las tablas existen
SELECT
    CASE
        WHEN COUNT(*) = 3 THEN '✓ Todas las tablas rediseñadas existen'
        ELSE CONCAT('❌ ERROR: Faltan tablas. Encontradas: ', COUNT(*))
    END AS structure_check
FROM information_schema.tables
WHERE table_schema = 'hms_v2'
  AND table_name IN ('doctors', 'patients', 'admins');

-- Verificar campos específicos de doctors
SELECT '✓ Campos de doctors:' AS '';
SELECT
    CASE
        WHEN COUNT(*) >= 10 THEN CONCAT('  ✓ doctors tiene ', COUNT(*), ' campos (esperado: >=10)')
        ELSE CONCAT('  ❌ doctors tiene solo ', COUNT(*), ' campos')
    END AS doctors_fields
FROM information_schema.columns
WHERE table_schema = 'hms_v2'
  AND table_name = 'doctors';

-- Verificar campos específicos de patients
SELECT
    CASE
        WHEN COUNT(*) >= 15 THEN CONCAT('  ✓ patients tiene ', COUNT(*), ' campos (esperado: >=15)')
        ELSE CONCAT('  ❌ patients tiene solo ', COUNT(*), ' campos')
    END AS patients_fields
FROM information_schema.columns
WHERE table_schema = 'hms_v2'
  AND table_name = 'patients';

-- Verificar campos específicos de admins
SELECT
    CASE
        WHEN COUNT(*) >= 20 THEN CONCAT('  ✓ admins tiene ', COUNT(*), ' campos (esperado: >=20)')
        ELSE CONCAT('  ❌ admins tiene solo ', COUNT(*), ' campos')
    END AS admins_fields
FROM information_schema.columns
WHERE table_schema = 'hms_v2'
  AND table_name = 'admins';

-- ============================================================================
-- 2. CREAR USUARIOS DE PRUEBA
-- ============================================================================

SELECT '' AS '';
SELECT '🧪 PASO 2: Creando usuarios de prueba...' AS status;

-- Limpiar usuarios de prueba anteriores si existen
DELETE FROM users WHERE email LIKE 'test_%@test.com';

SELECT '  ✓ Usuarios de prueba anteriores eliminados' AS '';

-- Crear usuario de prueba tipo DOCTOR
INSERT INTO users (email, password, user_type, full_name, status)
VALUES (
    'test_doctor@test.com',
    '$2y$10$abcdefghijklmnopqrstuv',  -- Hash de prueba
    'doctor',
    'Dr. Juan Pérez Test',
    'active'
);

SET @test_doctor_id = LAST_INSERT_ID();
SELECT CONCAT('  ✓ Usuario doctor creado con ID: ', @test_doctor_id) AS '';

-- Crear registro en tabla doctors
INSERT INTO doctors (
    user_id,
    specialization_id,
    license_number,
    consultation_fee,
    years_of_experience,
    status
) VALUES (
    @test_doctor_id,
    1,  -- Asume que existe especialización con ID 1
    'LIC-TEST-001',
    150.00,
    5,
    'active'
);

SELECT '  ✓ Registro de doctor creado en tabla doctors' AS '';

-- Crear usuario de prueba tipo PATIENT
INSERT INTO users (email, password, user_type, full_name, status)
VALUES (
    'test_patient@test.com',
    '$2y$10$abcdefghijklmnopqrstuv',
    'patient',
    'María García Test',
    'active'
);

SET @test_patient_id = LAST_INSERT_ID();
SELECT CONCAT('  ✓ Usuario patient creado con ID: ', @test_patient_id) AS '';

-- Crear registro en tabla patients
INSERT INTO patients (
    user_id,
    address,
    city,
    gender,
    date_of_birth,
    blood_type,
    status
) VALUES (
    @test_patient_id,
    'Calle Test 123',
    'La Paz',
    'female',
    '1990-05-15',
    'O+',
    'active'
);

SELECT '  ✓ Registro de patient creado en tabla patients' AS '';

-- Crear usuario de prueba tipo ADMIN
INSERT INTO users (email, password, user_type, full_name, status)
VALUES (
    'test_admin@test.com',
    '$2y$10$abcdefghijklmnopqrstuv',
    'admin',
    'Carlos Admin Test',
    'active'
);

SET @test_admin_id = LAST_INSERT_ID();
SELECT CONCAT('  ✓ Usuario admin creado con ID: ', @test_admin_id) AS '';

-- Crear registro en tabla admins
INSERT INTO admins (
    user_id,
    employee_id,
    department,
    admin_level,
    technical_area,
    certifications,
    status
) VALUES (
    @test_admin_id,
    CONCAT('EMP', LPAD(@test_admin_id, 5, '0')),
    'security',
    'security',
    'Seguridad de Sistemas',
    'CISSP, CEH',
    'active'
);

SELECT '  ✓ Registro de admin creado en tabla admins' AS '';

-- ============================================================================
-- 3. VERIFICAR INTEGRIDAD REFERENCIAL
-- ============================================================================

SELECT '' AS '';
SELECT '🔗 PASO 3: Verificando integridad referencial...' AS status;

-- Verificar que el doctor tiene relación válida con users
SELECT
    CASE
        WHEN COUNT(*) = 1 THEN '  ✓ Doctor: Relación users ↔ doctors OK'
        ELSE '  ❌ Doctor: Problema en relación'
    END AS doctor_integrity
FROM doctors d
INNER JOIN users u ON d.user_id = u.id
WHERE u.id = @test_doctor_id;

-- Verificar que el patient tiene relación válida con users
SELECT
    CASE
        WHEN COUNT(*) = 1 THEN '  ✓ Patient: Relación users ↔ patients OK'
        ELSE '  ❌ Patient: Problema en relación'
    END AS patient_integrity
FROM patients p
INNER JOIN users u ON p.user_id = u.id
WHERE u.id = @test_patient_id;

-- Verificar que el admin tiene relación válida con users
SELECT
    CASE
        WHEN COUNT(*) = 1 THEN '  ✓ Admin: Relación users ↔ admins OK'
        ELSE '  ❌ Admin: Problema en relación'
    END AS admin_integrity
FROM admins a
INNER JOIN users u ON a.user_id = u.id
WHERE u.id = @test_admin_id;

-- ============================================================================
-- 4. VERIFICAR CAMPOS ESPECÍFICOS
-- ============================================================================

SELECT '' AS '';
SELECT '✅ PASO 4: Verificando campos específicos...' AS status;

-- Verificar campos de doctors
SELECT
    u.full_name,
    d.license_number,
    d.consultation_fee,
    d.years_of_experience,
    d.status
FROM doctors d
INNER JOIN users u ON d.user_id = u.id
WHERE u.id = @test_doctor_id;

SELECT '  ✓ Campos de doctor verificados' AS '';

-- Verificar campos de patients
SELECT
    u.full_name,
    p.address,
    p.city,
    p.gender,
    p.blood_type,
    p.status
FROM patients p
INNER JOIN users u ON p.user_id = u.id
WHERE u.id = @test_patient_id;

SELECT '  ✓ Campos de patient verificados' AS '';

-- Verificar campos de admins
SELECT
    u.full_name,
    a.employee_id,
    a.department,
    a.admin_level,
    a.technical_area,
    a.certifications,
    a.status
FROM admins a
INNER JOIN users u ON a.user_id = u.id
WHERE u.id = @test_admin_id;

SELECT '  ✓ Campos de admin verificados' AS '';

-- ============================================================================
-- 5. PROBAR CASCADE DELETE
-- ============================================================================

SELECT '' AS '';
SELECT '🗑️  PASO 5: Probando CASCADE DELETE...' AS status;

-- Eliminar usuario doctor (debe eliminar registro de doctors automáticamente)
DELETE FROM users WHERE id = @test_doctor_id;

SELECT
    CASE
        WHEN COUNT(*) = 0 THEN '  ✓ CASCADE DELETE: doctors eliminado automáticamente'
        ELSE '  ❌ CASCADE DELETE: registro de doctors no se eliminó'
    END AS cascade_test_doctor
FROM doctors WHERE user_id = @test_doctor_id;

-- Eliminar usuario patient
DELETE FROM users WHERE id = @test_patient_id;

SELECT
    CASE
        WHEN COUNT(*) = 0 THEN '  ✓ CASCADE DELETE: patients eliminado automáticamente'
        ELSE '  ❌ CASCADE DELETE: registro de patients no se eliminó'
    END AS cascade_test_patient
FROM patients WHERE user_id = @test_patient_id;

-- Eliminar usuario admin
DELETE FROM users WHERE id = @test_admin_id;

SELECT
    CASE
        WHEN COUNT(*) = 0 THEN '  ✓ CASCADE DELETE: admins eliminado automáticamente'
        ELSE '  ❌ CASCADE DELETE: registro de admins no se eliminó'
    END AS cascade_test_admin
FROM admins WHERE user_id = @test_admin_id;

-- ============================================================================
-- 6. ESTADÍSTICAS FINALES
-- ============================================================================

SELECT '' AS '';
SELECT '📊 PASO 6: Estadísticas finales...' AS status;

SELECT CONCAT('  • Total users: ', COUNT(*)) AS count FROM users;
SELECT CONCAT('  • Total doctors: ', COUNT(*)) AS count FROM doctors;
SELECT CONCAT('  • Total patients: ', COUNT(*)) AS count FROM patients;
SELECT CONCAT('  • Total admins: ', COUNT(*)) AS count FROM admins;

-- Verificar que no hay registros huérfanos
SELECT
    CASE
        WHEN COUNT(*) = 0 THEN '  ✓ Sin registros huérfanos en doctors'
        ELSE CONCAT('  ⚠️  ', COUNT(*), ' doctors sin user_id válido')
    END AS orphan_check_doctors
FROM doctors d
LEFT JOIN users u ON d.user_id = u.id
WHERE u.id IS NULL;

SELECT
    CASE
        WHEN COUNT(*) = 0 THEN '  ✓ Sin registros huérfanos en patients'
        ELSE CONCAT('  ⚠️  ', COUNT(*), ' patients sin user_id válido')
    END AS orphan_check_patients
FROM patients p
LEFT JOIN users u ON p.user_id = u.id
WHERE u.id IS NULL;

SELECT
    CASE
        WHEN COUNT(*) = 0 THEN '  ✓ Sin registros huérfanos en admins'
        ELSE CONCAT('  ⚠️  ', COUNT(*), ' admins sin user_id válido')
    END AS orphan_check_admins
FROM admins a
LEFT JOIN users u ON a.user_id = u.id
WHERE u.id IS NULL;

-- ============================================================================
-- TESTING COMPLETADO
-- ============================================================================

SELECT '' AS '';
SELECT '╔═══════════════════════════════════════════════════════════════╗' AS '';
SELECT '║                 ✓ TESTING COMPLETADO                          ║' AS '';
SELECT '╚═══════════════════════════════════════════════════════════════╝' AS '';
SELECT '' AS '';
SELECT '✅ Verificaciones realizadas:' AS '';
SELECT '   1. Estructura de tablas' AS '';
SELECT '   2. Creación de usuarios de prueba' AS '';
SELECT '   3. Integridad referencial' AS '';
SELECT '   4. Campos específicos' AS '';
SELECT '   5. CASCADE DELETE' AS '';
SELECT '   6. Registros huérfanos' AS '';
SELECT '' AS '';
SELECT '🎯 Siguiente paso: Probar desde manage-users.php' AS '';
SELECT '   1. Abrir http://localhost/hospital/hms/admin/manage-users.php' AS '';
SELECT '   2. Crear un nuevo usuario (doctor/patient/admin)' AS '';
SELECT '   3. Verificar que se crea el registro en la tabla correspondiente' AS '';
SELECT '' AS '';
SELECT '╔═══════════════════════════════════════════════════════════════╗' AS '';

-- ============================================================================
-- QUERIES ÚTILES PARA DEBUGGING
-- ============================================================================

/*
-- Ver todos los doctors con sus usuarios
SELECT
    u.id, u.full_name, u.email, u.user_type,
    d.license_number, d.consultation_fee, d.specialization_id
FROM users u
LEFT JOIN doctors d ON u.id = d.user_id
WHERE u.user_type = 'doctor';

-- Ver todos los patients con sus usuarios
SELECT
    u.id, u.full_name, u.email, u.user_type,
    p.address, p.city, p.blood_type, p.gender
FROM users u
LEFT JOIN patients p ON u.id = p.user_id
WHERE u.user_type = 'patient';

-- Ver todos los admins con sus usuarios
SELECT
    u.id, u.full_name, u.email, u.user_type,
    a.employee_id, a.department, a.admin_level, a.certifications
FROM users u
LEFT JOIN admins a ON u.id = a.user_id
WHERE u.user_type = 'admin';
*/
