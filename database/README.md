# Database Setup

## 📊 Estructura de la Base de Datos

Este proyecto utiliza una base de datos MySQL normalizada a **3FN (Third Normal Form)**.

## 🚀 Instalación

### Opción 1: phpMyAdmin (Recomendado para principiantes)

1. Abre phpMyAdmin en tu navegador: `http://localhost/phpmyadmin`
2. Haz clic en "Import" (Importar)
3. Selecciona el archivo `schema.sql`
4. Haz clic en "Go" (Ejecutar)

### Opción 2: Línea de comandos

```bash
# Navega a la carpeta del proyecto
cd C:\xampp\htdocs\hospital\database

# Importa el schema
mysql -u root -p < schema.sql

# O si no tienes contraseña:
mysql -u root < schema.sql
```

### Opción 3: XAMPP Shell

```bash
# Abre XAMPP Shell
cd C:\xampp\htdocs\hospital\database
C:\xampp\mysql\bin\mysql.exe -u root < schema.sql
```

## 🔑 Configuración

Después de importar la base de datos, actualiza el archivo de configuración:

1. Copia `hms/include/config.php.example` a `hms/include/config.php`
2. Edita las credenciales si es necesario:

```php
define('DB_SERVER','localhost');
define('DB_USER','root');
define('DB_PASS' ,'');  // Cambia si tienes contraseña
define('DB_NAME', 'hms');
```

## 👤 Crear el Primer Usuario Administrador

### Opción A: Registrarse y luego promover a admin

1. Ve a: `http://localhost/hospital/hms/registration.php`
2. Regístrate como paciente con tu email
3. Ejecuta este SQL en phpMyAdmin:

```sql
-- Reemplaza 'tuemail@test.com' con tu email
UPDATE users SET user_type='admin' WHERE email='tuemail@test.com';

-- Obtén el user_id primero
SELECT id FROM users WHERE email='tuemail@test.com';

-- Luego crea el registro en admins (reemplaza 1 con tu user_id)
INSERT INTO admins (user_id, username) VALUES (1, 'admin');
```

### Opción B: Crear directamente en la base de datos

```sql
-- 1. Crear usuario admin (la contraseña es: Test@123)
INSERT INTO users (email, password, user_type, full_name, status) VALUES
('admin@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrador Principal', 'active');

-- 2. Obtener el ID del usuario recién creado
SET @admin_user_id = LAST_INSERT_ID();

-- 3. Crear registro en tabla admins
INSERT INTO admins (user_id, username) VALUES (@admin_user_id, 'admin');
```

## 📋 Estructura de Tablas

### Tablas Principales

1. **users** - Autenticación unificada (16 usuarios después de migración)
   - `id`, `email`, `password`, `user_type`, `full_name`, `status`
   - Tipos: `patient`, `doctor`, `admin`

2. **patients** - Información específica de pacientes
   - `id`, `user_id` (FK), `address`, `city`, `gender`

3. **doctors** - Información específica de doctores
   - `id`, `user_id` (FK), `specilization`, `doctorName`, `address`, `docFees`

4. **admins** - Información específica de administradores
   - `id`, `user_id` (FK), `username`, `permissions`

### Tablas de Soporte

5. **doctorspecilization** - Especialidades médicas
6. **appointment** - Citas médicas
7. **tblcontactus** - Consultas de contacto
8. **tblmedicalhistory** - Historia médica de pacientes
9. **userlog** - Logs de sesión de pacientes
10. **doctorslog** - Logs de sesión de doctores

## 🔄 Diagrama de Relaciones

```
users (tabla principal)
├── patients (1:1 con user_id FK)
├── doctors (1:1 con user_id FK)
└── admins (1:1 con user_id FK)

appointment
├── doctorId → doctors.id
└── userId → users.id

tblmedicalhistory
└── PatientID → patients.id
```

## ✅ Verificar Instalación

Ejecuta este SQL para verificar que todo está correcto:

```sql
-- Verificar que las tablas existan
SHOW TABLES;

-- Debe mostrar 10 tablas:
-- users, patients, doctors, admins, doctorspecilization,
-- appointment, tblcontactus, tblmedicalhistory, userlog, doctorslog

-- Verificar Foreign Keys
SELECT
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'hms'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Debe mostrar 3 Foreign Keys:
-- patients.user_id → users.id
-- doctors.user_id → users.id
-- admins.user_id → users.id
```

## 🎯 Datos de Prueba (Opcional)

Si quieres datos de prueba, puedes crear usuarios manualmente:

```sql
-- Crear un paciente de prueba (contraseña: Test@123)
INSERT INTO users (email, password, user_type, full_name, status) VALUES
('paciente@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', 'Juan Pérez', 'active');

SET @patient_id = LAST_INSERT_ID();

INSERT INTO patients (user_id, address, city, gender) VALUES
(@patient_id, 'Av. Ejemplo 123', 'La Paz', 'Male');

-- Crear un doctor de prueba (contraseña: Test@123)
INSERT INTO users (email, password, user_type, full_name, status) VALUES
('doctor@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'Dr. María García', 'active');

SET @doctor_id = LAST_INSERT_ID();

INSERT INTO doctors (user_id, specilization, doctorName, address, docFees, contactno, docEmail) VALUES
(@doctor_id, 'Dentist', 'Dr. María García', 'Calle Médica 456', '150', 77012345, 'doctor@test.com');
```

## 🔐 Contraseñas

Todas las contraseñas en este sistema están hasheadas con **bcrypt**.

La contraseña de prueba `Test@123` tiene este hash:
```
$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

## 📝 Notas Importantes

1. **NO subas backups** a GitHub - están en el .gitignore por seguridad
2. **Cambia las contraseñas** en producción
3. **Usa siempre HTTPS** en producción
4. **Haz backups regulares** de tu base de datos
5. **Este schema es para desarrollo**, ajusta según necesites

## 🆘 Problemas Comunes

### Error: "Access denied for user 'root'@'localhost'"
**Solución:** Verifica tus credenciales en `config.php`

### Error: "Unknown database 'hms'"
**Solución:** El schema crea la BD automáticamente, pero puedes crearla manualmente:
```sql
CREATE DATABASE hms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Error: "Can't create table (errno: 150)"
**Solución:** Problema de Foreign Keys. Importa el schema completo desde cero.

## 📚 Recursos

- [Documentación MySQL](https://dev.mysql.com/doc/)
- [phpMyAdmin Docs](https://docs.phpmyadmin.net/)
- [README principal del proyecto](../README.md)
- [Documentación completa](../docs/)

---

**Última actualización:** 12 de Octubre, 2025
