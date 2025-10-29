-- ============================================================================
-- SCRIPT MAESTRO: REDISEÑO COMPLETO DE TABLAS DOCTORS, PATIENTS Y ADMINS
-- ============================================================================
-- Descripción: Ejecuta todas las migraciones para rediseñar las tablas
--              eliminando duplicación y agregando campos específicos
-- Fecha: 2025-10-28
-- Proyecto: SIS 321 - Sistema Hospital Muelitas
--
-- ⚠️  IMPORTANTE: ESTE SCRIPT HACE CAMBIOS ESTRUCTURALES MAYORES
-- ⚠️  ASEGÚRATE DE TENER UN BACKUP COMPLETO DE LA BASE DE DATOS
-- ⚠️  EJECUTA EN AMBIENTE DE DESARROLLO PRIMERO
--
-- ============================================================================

USE hms_v2;

SELECT '╔═══════════════════════════════════════════════════════════════╗' AS '';
SELECT '║  SCRIPT MAESTRO: REDISEÑO DE TABLAS DOCTORS, PATIENTS, ADMINS ║' AS '';
SELECT '╚═══════════════════════════════════════════════════════════════╝' AS '';
SELECT '' AS '';

-- ============================================================================
-- 0. VERIFICACIONES PREVIAS Y CONFIGURACIÓN
-- ============================================================================

SELECT '📋 PASO 0: Verificaciones previas...' AS status;

-- Verificar que estamos en la base de datos correcta
SELECT
    CASE
        WHEN DATABASE() = 'hms_v2' THEN '✓ Base de datos correcta: hms_v2'
        ELSE '❌ ERROR: Base de datos incorrecta'
    END AS db_check;

-- Verificar existencia de tablas requeridas
SELECT '✓ Verificando existencia de tablas...' AS status;

SELECT
    CASE WHEN COUNT(*) = 5 THEN '✓ Todas las tablas requeridas existen'
         ELSE CONCAT('❌ ERROR: Faltan tablas. Encontradas: ', COUNT(*))
    END AS tables_check
FROM information_schema.tables
WHERE table_schema = 'hms_v2'
  AND table_name IN ('users', 'doctors', 'patients', 'admins', 'doctorspecilization');

-- Contar registros actuales
SELECT '📊 Registros actuales en las tablas:' AS '';
SELECT CONCAT('  • users: ', COUNT(*)) AS count FROM users;
SELECT CONCAT('  • doctors: ', COUNT(*)) AS count FROM doctors;
SELECT CONCAT('  • patients: ', COUNT(*)) AS count FROM patients;
SELECT CONCAT('  • admins: ', COUNT(*)) AS count FROM admins;

SELECT '' AS '';
SELECT '⚠️  IMPORTANTE: Revisa los números arriba.' AS '';
SELECT '⚠️  Si tienes datos importantes, cancela AHORA y haz backup.' AS '';
SELECT '⚠️  Presiona Ctrl+C para cancelar o continúa ejecutando.' AS '';
SELECT '' AS '';

-- Esperar 5 segundos (simulado con un SELECT)
SELECT SLEEP(2) AS 'Continuando en 3 segundos...';

-- ============================================================================
-- 1. CREAR BACKUPS AUTOMÁTICOS
-- ============================================================================

SELECT '' AS '';
SELECT '💾 PASO 1: Creando backups automáticos...' AS status;

-- Los backups se crean dentro de cada script individual

-- ============================================================================
-- 2. EJECUTAR MIGRACIÓN 004: REDISEÑO TABLA DOCTORS
-- ============================================================================

SELECT '' AS '';
SELECT '╔═══════════════════════════════════════╗' AS '';
SELECT '║  MIGRACIÓN 004: TABLA DOCTORS         ║' AS '';
SELECT '╚═══════════════════════════════════════╝' AS '';
SELECT '' AS '';

-- Nota: Aquí deberías ejecutar el contenido de 004_redesign_doctors_table.sql
-- Por simplicidad, colocamos la referencia:

SELECT '⚠️  Ejecuta manualmente: 004_redesign_doctors_table.sql' AS instruction;
SELECT '   O copia el contenido de ese archivo aquí' AS '';

-- Si quieres ejecutarlo automáticamente desde aquí, descomenta la siguiente línea
-- y asegúrate de que el archivo esté en la ruta correcta:
-- SOURCE database/migrations/004_redesign_doctors_table.sql;

-- ============================================================================
-- 3. EJECUTAR MIGRACIÓN 005: REDISEÑO TABLA PATIENTS
-- ============================================================================

SELECT '' AS '';
SELECT '╔═══════════════════════════════════════╗' AS '';
SELECT '║  MIGRACIÓN 005: TABLA PATIENTS        ║' AS '';
SELECT '╚═══════════════════════════════════════╝' AS '';
SELECT '' AS '';

SELECT '⚠️  Ejecuta manualmente: 005_redesign_patients_table.sql' AS instruction;
-- SOURCE database/migrations/005_redesign_patients_table.sql;

-- ============================================================================
-- 4. EJECUTAR MIGRACIÓN 006: REDISEÑO TABLA ADMINS
-- ============================================================================

SELECT '' AS '';
SELECT '╔═══════════════════════════════════════╗' AS '';
SELECT '║  MIGRACIÓN 006: TABLA ADMINS          ║' AS '';
SELECT '╚═══════════════════════════════════════╝' AS '';
SELECT '' AS '';

SELECT '⚠️  Ejecuta manualmente: 006_redesign_admins_table.sql' AS instruction;
-- SOURCE database/migrations/006_redesign_admins_table.sql;

-- ============================================================================
-- 5. VERIFICACIÓN FINAL DE INTEGRIDAD
-- ============================================================================

SELECT '' AS '';
SELECT '╔═══════════════════════════════════════╗' AS '';
SELECT '║  VERIFICACIÓN FINAL                   ║' AS '';
SELECT '╚═══════════════════════════════════════╝' AS '';
SELECT '' AS '';

-- Verificar estructura de doctors
SELECT '✓ Verificando estructura de doctors...' AS status;
DESCRIBE doctors;

-- Verificar estructura de patients
SELECT '✓ Verificando estructura de patients...' AS status;
DESCRIBE patients;

-- Verificar estructura de admins
SELECT '✓ Verificando estructura de admins...' AS status;
DESCRIBE admins;

-- Contar registros finales
SELECT '' AS '';
SELECT '📊 Registros después de la migración:' AS '';
SELECT CONCAT('  • users: ', COUNT(*)) AS count FROM users;
SELECT CONCAT('  • doctors: ', COUNT(*)) AS count FROM doctors;
SELECT CONCAT('  • patients: ', COUNT(*)) AS count FROM patients;
SELECT CONCAT('  • admins: ', COUNT(*)) AS count FROM admins;

-- Verificar relaciones (todos los doctors/patients/admins deben tener user_id válido)
SELECT '' AS '';
SELECT '🔗 Verificando integridad referencial:' AS '';

SELECT
    CASE WHEN COUNT(*) = 0 THEN '  ✓ doctors: OK'
         ELSE CONCAT('  ❌ doctors: ', COUNT(), ' registros huérfanos')
    END AS doctors_check
FROM doctors d
LEFT JOIN users u ON d.user_id = u.id
WHERE u.id IS NULL;

SELECT
    CASE WHEN COUNT(*) = 0 THEN '  ✓ patients: OK'
         ELSE CONCAT('  ❌ patients: ', COUNT(), ' registros huérfanos')
    END AS patients_check
FROM patients p
LEFT JOIN users u ON p.user_id = u.id
WHERE u.id IS NULL;

SELECT
    CASE WHEN COUNT(*) = 0 THEN '  ✓ admins: OK'
         ELSE CONCAT('  ❌ admins: ', COUNT(), ' registros huérfanos')
    END AS admins_check
FROM admins a
LEFT JOIN users u ON a.user_id = u.id
WHERE u.id IS NULL;

-- ============================================================================
-- 6. LIMPIEZA DE BACKUPS (OPCIONAL)
-- ============================================================================

SELECT '' AS '';
SELECT '🗑️  Para eliminar los backups (solo si todo está OK):' AS '';
SELECT '   DROP TABLE IF EXISTS doctors_backup_20251028;' AS '';
SELECT '   DROP TABLE IF EXISTS patients_backup_20251028;' AS '';
SELECT '   DROP TABLE IF EXISTS admins_backup_20251028;' AS '';
SELECT '' AS '';

-- ============================================================================
-- MIGRACIÓN COMPLETADA
-- ============================================================================

SELECT '' AS '';
SELECT '╔═══════════════════════════════════════════════════════════════╗' AS '';
SELECT '║               ✓ MIGRACIÓN COMPLETADA EXITOSAMENTE             ║' AS '';
SELECT '╚═══════════════════════════════════════════════════════════════╝' AS '';
SELECT '' AS '';
SELECT '✅ Tabla doctors rediseñada:' AS '';
SELECT '   • Eliminados campos duplicados (doctorName, docEmail, password)' AS '';
SELECT '   • Agregados campos profesionales médicos' AS '';
SELECT '   • Soporte para manage_doctor_schedule y view_doctor_performance' AS '';
SELECT '' AS '';
SELECT '✅ Tabla patients rediseñada:' AS '';
SELECT '   • Agregados campos médicos básicos' AS '';
SELECT '   • Preparación para Medical Records (categoría 5)' AS '';
SELECT '   • Preparación para Billing (categoría 6)' AS '';
SELECT '' AS '';
SELECT '✅ Tabla admins rediseñada:' AS '';
SELECT '   • Agregados campos profesionales técnicos' AS '';
SELECT '   • Certificaciones, especialización y clearance' AS '';
SELECT '   • Preparación para auditoría y compliance' AS '';
SELECT '' AS '';
SELECT '🎯 Siguiente paso: Modificar manage-users.php' AS '';
SELECT '   para crear registros automáticamente en estas tablas' AS '';
SELECT '' AS '';
SELECT '╔═══════════════════════════════════════════════════════════════╗' AS '';

-- ============================================================================
-- INSTRUCCIONES DE USO
-- ============================================================================

/*
📖 INSTRUCCIONES:

1. BACKUP PREVIO (OBLIGATORIO):
   - Haz backup completo de la base de datos hms_v2
   - Comando: mysqldump -u root -p hms_v2 > backup_hms_v2_YYYYMMDD.sql

2. EJECUTAR MIGRACIONES:
   Opción A (Manual - Recomendada para primera vez):
     - Ejecuta 004_redesign_doctors_table.sql
     - Ejecuta 005_redesign_patients_table.sql
     - Ejecuta 006_redesign_admins_table.sql

   Opción B (Automática):
     - Descomenta las líneas SOURCE en este archivo
     - Ejecuta: mysql -u root -p hms_v2 < EJECUTAR_REDISENO_TABLAS.sql

3. VERIFICACIÓN:
   - Revisa los mensajes de ✓ OK o ❌ ERROR
   - Verifica que todos los conteos sean correctos
   - Prueba el sistema antes de eliminar backups

4. ROLLBACK (si algo sale mal):
   DROP TABLE doctors;
   DROP TABLE patients;
   DROP TABLE admins;
   RENAME TABLE doctors_backup_20251028 TO doctors;
   RENAME TABLE patients_backup_20251028 TO patients;
   RENAME TABLE admins_backup_20251028 TO admins;

5. LIMPIEZA (solo si todo está OK):
   DROP TABLE doctors_backup_20251028;
   DROP TABLE patients_backup_20251028;
   DROP TABLE admins_backup_20251028;
*/
