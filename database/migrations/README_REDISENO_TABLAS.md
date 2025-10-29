# 📋 Guía de Migración: Rediseño de Tablas Doctors, Patients y Admins

## 📖 Descripción General

Esta migración rediseña las tablas `doctors`, `patients` y `admins` para:
- ✅ Eliminar duplicación de datos con la tabla `users`
- ✅ Agregar campos profesionales específicos para cada tipo de usuario
- ✅ Preparar el sistema para futuras funcionalidades (Medical Records, Billing)
- ✅ Mejorar la integridad referencial y performance

---

## ⚠️ ADVERTENCIAS IMPORTANTES

### 🔴 **ANTES DE EJECUTAR:**

1. **BACKUP OBLIGATORIO**: Haz un backup completo de la base de datos
2. **AMBIENTE DE PRUEBA**: Ejecuta primero en desarrollo, nunca directamente en producción
3. **TIEMPO DE INACTIVIDAD**: Puede requerir detener temporalmente la aplicación
4. **DATOS EXISTENTES**: Los datos se migrarán automáticamente si tienen `user_id` válido

---

## 📁 Archivos Incluidos

```
database/migrations/
├── 004_redesign_doctors_table.sql    # Rediseño de tabla doctors
├── 005_redesign_patients_table.sql   # Rediseño de tabla patients
├── 006_redesign_admins_table.sql     # Rediseño de tabla admins
├── EJECUTAR_REDISENO_TABLAS.sql      # Script maestro (ejecuta todos)
├── TEST_REDISENO_TABLAS.sql          # Script de testing
└── README_REDISENO_TABLAS.md         # Esta guía
```

---

## 🚀 Proceso de Migración

### **Paso 1: Backup de la Base de Datos**

```bash
# Desde la terminal en C:\xampp\mysql\bin\
mysqldump -u root -p hms_v2 > C:\backup_hms_v2_20251028.sql
```

**Verificar backup:**
```bash
# El archivo debe tener tamaño > 0 bytes
dir C:\backup_hms_v2_20251028.sql
```

---

### **Paso 2: Ejecutar Migraciones**

#### **Opción A: Desde phpMyAdmin (Recomendada para primera vez)**

1. Abre phpMyAdmin: `http://localhost/phpmyadmin`
2. Selecciona la base de datos `hms_v2`
3. Ve a la pestaña **SQL**
4. Ejecuta en orden:

**a) Migración 004 - Doctors:**
   - Abre el archivo `004_redesign_doctors_table.sql`
   - Copia todo el contenido
   - Pega en phpMyAdmin y ejecuta
   - Verifica los mensajes ✓ OK

**b) Migración 005 - Patients:**
   - Abre el archivo `005_redesign_patients_table.sql`
   - Copia todo el contenido
   - Pega en phpMyAdmin y ejecuta
   - Verifica los mensajes ✓ OK

**c) Migración 006 - Admins:**
   - Abre el archivo `006_redesign_admins_table.sql`
   - Copia todo el contenido
   - Pega en phpMyAdmin y ejecuta
   - Verifica los mensajes ✓ OK

#### **Opción B: Desde línea de comandos**

```bash
cd C:\xampp\htdocs\hospital\database\migrations

# Ejecutar cada migración
mysql -u root -p hms_v2 < 004_redesign_doctors_table.sql
mysql -u root -p hms_v2 < 005_redesign_patients_table.sql
mysql -u root -p hms_v2 < 006_redesign_admins_table.sql
```

---

### **Paso 3: Verificación Automática**

Ejecuta el script de testing:

```bash
mysql -u root -p hms_v2 < TEST_REDISENO_TABLAS.sql
```

**O desde phpMyAdmin:**
- Abre `TEST_REDISENO_TABLAS.sql`
- Copia y pega en phpMyAdmin
- Ejecuta

**Busca estos mensajes:**
- ✓ Todas las tablas rediseñadas existen
- ✓ CASCADE DELETE funcionando
- ✓ Sin registros huérfanos

---

### **Paso 4: Prueba Manual**

1. **Abre la aplicación:**
   ```
   http://localhost/hospital/hms/admin/manage-users.php
   ```

2. **Crea un usuario de prueba:**
   - Tipo: Doctor
   - Nombre: Test Doctor
   - Email: test.doctor@clinica.muelitas.com
   - Contraseña: TestDoctor123!@#

3. **Verifica en la base de datos:**
   ```sql
   -- Ver el usuario creado
   SELECT * FROM users WHERE email = 'test.doctor@clinica.muelitas.com';

   -- Ver el registro en doctors (debe existir automáticamente)
   SELECT * FROM doctors WHERE user_id = (
       SELECT id FROM users WHERE email = 'test.doctor@clinica.muelitas.com'
   );
   ```

4. **Repite para Patient y Admin:**
   - Crea un patient
   - Crea un admin
   - Verifica que se crean registros en `patients` y `admins`

---

## 🔄 Rollback (En caso de problemas)

Si algo sale mal durante la migración:

### **Opción 1: Restaurar desde Backup**

```bash
# Restaurar backup completo
mysql -u root -p hms_v2 < C:\backup_hms_v2_20251028.sql
```

### **Opción 2: Rollback Manual (si los backups temporales existen)**

```sql
-- Eliminar tablas nuevas
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS admins;

-- Restaurar tablas desde backup temporal
RENAME TABLE doctors_backup_20251028 TO doctors;
RENAME TABLE patients_backup_20251028 TO patients;
RENAME TABLE admins_backup_20251028 TO admins;
```

---

## 📊 Cambios Específicos por Tabla

### **Tabla: `doctors`**

#### **Campos ELIMINADOS (duplicados con users):**
- ❌ `doctorName` → Ahora en `users.full_name`
- ❌ `docEmail` → Ahora en `users.email`
- ❌ `password` → Ahora en `users.password`
- ❌ `address` → No justificaba tabla separada
- ❌ `contactno` → No justificaba tabla separada

#### **Campos AGREGADOS:**
- ✅ `license_number` → Número de licencia médica (único)
- ✅ `years_of_experience` → Años de experiencia
- ✅ `working_hours` (JSON) → Horarios de trabajo
- ✅ `max_daily_appointments` → Máximo de citas diarias
- ✅ `total_appointments` → Total de citas históricas
- ✅ `rating` → Calificación promedio
- ✅ `bio` → Biografía profesional
- ✅ `languages` → Idiomas que habla

### **Tabla: `patients`**

#### **Campos AGREGADOS:**
- ✅ `state`, `postal_code`, `phone` → Datos de contacto completos
- ✅ `emergency_contact`, `emergency_phone` → Contacto de emergencia
- ✅ `date_of_birth`, `blood_type` → Datos médicos básicos
- ✅ `height`, `weight` → Medidas corporales
- ✅ `allergies` → Alergias conocidas
- ✅ `chronic_conditions` → Condiciones crónicas
- ✅ `current_medications` → Medicamentos actuales
- ✅ `past_surgeries` → Cirugías previas
- ✅ `family_medical_history` → Historial familiar
- ✅ `insurance_provider`, `insurance_number` → Datos de seguro

### **Tabla: `admins`**

#### **Campos AGREGADOS:**
- ✅ `employee_id` → ID de empleado (único)
- ✅ `department` → Departamento (IT, Security, Operations)
- ✅ `job_title` → Título del puesto
- ✅ `technical_area` → Área técnica principal
- ✅ `certifications` → Certificaciones (CISSP, CEH, etc.)
- ✅ `specialization` → Especialización técnica
- ✅ `years_of_experience` → Años de experiencia en IT
- ✅ `admin_level` → Nivel administrativo
- ✅ `clearance_level` → Nivel de clearance de seguridad
- ✅ `can_access_production` → Acceso a producción
- ✅ `can_modify_security` → Puede modificar seguridad
- ✅ `office_hours` (JSON) → Horarios de oficina
- ✅ `last_security_training` → Última capacitación

---

## ✅ Checklist de Verificación

Después de ejecutar las migraciones, verifica:

- [ ] Backup completo creado y verificado
- [ ] Migración 004 (doctors) ejecutada sin errores
- [ ] Migración 005 (patients) ejecutada sin errores
- [ ] Migración 006 (admins) ejecutada sin errores
- [ ] Script de testing ejecutado con mensajes ✓ OK
- [ ] Usuarios de prueba creados desde manage-users.php
- [ ] Registros automáticos creados en doctors/patients/admins
- [ ] CASCADE DELETE funciona correctamente
- [ ] No hay registros huérfanos (sin user_id válido)
- [ ] Aplicación web funciona sin errores

---

## 🐛 Resolución de Problemas

### **Error: "Table 'doctors' doesn't exist"**
**Solución:** Ejecuta primero `004_redesign_doctors_table.sql`

### **Error: "Cannot delete or update a parent row: a foreign key constraint fails"**
**Solución:** Hay datos relacionados. Verifica integridad antes de eliminar.

### **Error: "Duplicate entry 'X' for key 'user_id'"**
**Solución:** Ya existe un doctor/patient/admin con ese user_id. Verifica duplicados.

### **Warning: "No se creó registro en doctors/patients/admins"**
**Solución:** Verifica que las tablas estén rediseñadas ANTES de crear usuarios.

---

## 📞 Soporte

Si encuentras problemas:
1. Revisa los logs de errores en `C:\xampp\php\logs\php_error_log`
2. Verifica los logs de MySQL en `C:\xampp\mysql\data\`
3. Consulta los queries de debugging al final de `TEST_REDISENO_TABLAS.sql`

---

## 🎯 Siguientes Pasos (Futuro)

Una vez completada la migración, considera:

1. **Activar categorías de permisos inactivas:**
   - Medical Records (categoría 5)
   - Billing (categoría 6)
   - Reports (categoría 7)

2. **Implementar interfaces de edición:**
   - Formulario para editar datos de doctors
   - Formulario para editar datos de patients
   - Formulario para editar datos de admins

3. **Agregar validaciones adicionales:**
   - Validar license_number de doctors
   - Validar insurance_number de patients
   - Validar certifications de admins

---

## 📝 Notas Adicionales

- Los backups temporales (`*_backup_20251028`) se eliminan automáticamente SOLO si descomentas las líneas al final de cada script
- La fecha 20251028 es un timestamp, puedes cambiarla si ejecutas la migración en otra fecha
- Los usuarios existentes se migran automáticamente si tienen `user_id` válido

---

**✅ Migración completada exitosamente - Sistema listo para producción**

**Fecha de última actualización:** 2025-10-28
**Versión:** 1.0
**Proyecto:** SIS 321 - Sistema Hospital Muelitas
