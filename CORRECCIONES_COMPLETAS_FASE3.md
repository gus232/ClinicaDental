# CORRECCIONES COMPLETAS - FASE 3
## Todos los Archivos con Problemas de Columnas Inexistentes

**Fecha:** 21 de Octubre, 2025
**Problema:** Referencias a campos que NO existen en tabla `users`
**Estado:** ✅ TODOS CORREGIDOS

---

## 📊 Estructura REAL de la Tabla users

```sql
+-------------------------+------------------------------------------+------+-----+---------+
| Campo                   | Tipo                                     | Null | Key | Extra   |
+-------------------------+------------------------------------------+------+-----+---------+
| id                      | int(11)                                  | NO   | PRI | auto_increment |
| email                   | varchar(255)                             | NO   | UNI |         |
| password                | varchar(255)                             | NO   |     |         |
| user_type               | enum('patient','doctor','admin')         | NO   | MUL |         |
| full_name               | varchar(255)                             | NO   | MUL |         |
| status                  | enum('active','inactive','blocked')      | YES  | MUL |         |
| created_at              | timestamp                                | NO   |     |         |
| updated_at              | timestamp                                | YES  |     | on update |
| last_login              | timestamp                                | YES  |     |         |
| failed_login_attempts   | int(11)                                  | YES  |     |         |
| account_locked_until    | datetime                                 | YES  | MUL |         |
| password_expires_at     | datetime                                 | YES  | MUL |         |
| password_changed_at     | datetime                                 | YES  |     |         |
| last_login_ip           | varchar(45)                              | YES  |     |         |
| force_password_change   | tinyint(1)                               | YES  |     |         |
+-------------------------+------------------------------------------+------+-----+---------+
```

### ❌ Columnas que NO EXISTEN:
- `contactno` (teléfono)
- `city` (ciudad)
- `address` (dirección)
- `gender` (género)

---

## 📋 Archivos Corregidos

### 1. ✅ database/migrations/005_user_management_enhancements_FIXED.sql
**Estado:** Ya estaba corregido
**Cambios:**
- Eliminadas referencias a `contactno` en vistas
- Foreign Keys ajustados (NULL compatible con ON DELETE SET NULL)

### 2. ✅ database/stored-procedures/INSTALAR_SP_FASE3_ULTRA_FIXED.sql
**Estado:** NUEVO ARCHIVO CREADO
**Ubicación:** `database/stored-procedures/INSTALAR_SP_FASE3_ULTRA_FIXED.sql`

**Cambios principales:**

#### SP 1: create_user_with_audit
```sql
-- ANTES (9 parámetros):
CREATE PROCEDURE create_user_with_audit(
    IN p_full_name VARCHAR(255),
    IN p_address VARCHAR(255),      -- ❌ ELIMINADO
    IN p_city VARCHAR(255),         -- ❌ ELIMINADO
    IN p_gender VARCHAR(10),        -- ❌ ELIMINADO
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_created_by INT,
    IN p_ip_address VARCHAR(45),
    IN p_reason VARCHAR(255),
    OUT p_new_user_id INT
)

-- DESPUÉS (7 parámetros):
CREATE PROCEDURE create_user_with_audit(
    IN p_full_name VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_user_type ENUM('patient', 'doctor', 'admin'),
    IN p_created_by INT,
    IN p_ip_address VARCHAR(45),
    IN p_reason VARCHAR(255),
    OUT p_new_user_id INT
)
```

#### SP 2: update_user_with_history
```sql
-- ANTES (10 parámetros):
IN p_address VARCHAR(255),      -- ❌ ELIMINADO
IN p_city VARCHAR(255),         -- ❌ ELIMINADO
IN p_gender VARCHAR(10),        -- ❌ ELIMINADO

-- DESPUÉS (7 parámetros):
CREATE PROCEDURE update_user_with_history(
    IN p_user_id INT,
    IN p_full_name VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_status ENUM('active', 'inactive', 'blocked'),
    IN p_updated_by INT,
    IN p_ip_address VARCHAR(45),
    IN p_reason VARCHAR(255),
    OUT p_result INT
)
```

#### SP 3: search_users
```sql
-- ANTES:
IN p_gender VARCHAR(10),        -- ❌ ELIMINADO
IN p_city VARCHAR(255),         -- ❌ ELIMINADO
WHERE u.city LIKE v_search_pattern  -- ❌ ELIMINADO
AND u.gender = p_gender         -- ❌ ELIMINADO

-- DESPUÉS:
CREATE PROCEDURE search_users(
    IN p_search_term VARCHAR(255),
    IN p_role_id INT,
    IN p_status VARCHAR(20),
    IN p_user_type VARCHAR(20),  -- ✅ AGREGADO
    IN p_limit INT,
    IN p_offset INT
)
```

#### SP 4: get_user_statistics
```sql
-- ANTES:
SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as male_users,     -- ❌ ELIMINADO
SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female_users, -- ❌ ELIMINADO
SUM(CASE WHEN gender = 'Other' THEN 1 ELSE 0 END) as other_gender,  -- ❌ ELIMINADO

-- DESPUÉS:
SUM(CASE WHEN user_type = 'patient' THEN 1 ELSE 0 END) as patients,  -- ✅ AGREGADO
SUM(CASE WHEN user_type = 'doctor' THEN 1 ELSE 0 END) as doctors,    -- ✅ AGREGADO
SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as admins,      -- ✅ AGREGADO
```

### 3. ✅ hms/include/UserManagement_ULTRA_FIXED.php
**Estado:** NUEVO ARCHIVO CREADO
**Ubicación:** `hms/include/UserManagement_ULTRA_FIXED.php`

**Cambios principales:**

#### Método createUser()
```php
// ANTES (línea 53-74):
$stmt = $this->db->prepare("CALL create_user_with_audit(?, ?, ?, ?, ?, ?, ?, ?, ?, @new_user_id)");
$address = $data['address'] ?? '';      // ❌ ELIMINADO
$city = $data['city'] ?? '';            // ❌ ELIMINADO
$gender = $data['gender'] ?? 'Male';    // ❌ ELIMINADO

// DESPUÉS:
$stmt = $this->db->prepare("CALL create_user_with_audit(?, ?, ?, ?, ?, ?, ?, @new_user_id)");
$user_type = $data['user_type'] ?? 'patient';  // ✅ AGREGADO
$stmt->bind_param("ssssiis",
    $full_name,
    $email,
    $hashed_password,
    $user_type,      // ✅ NUEVO
    $created_by,
    $ip,
    $reason_text
);
```

#### Método updateUser()
```php
// ANTES (línea 49-72):
$stmt = $this->db->prepare("CALL update_user_with_history(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @result)");
$address = $data['address'] ?? null;    // ❌ ELIMINADO
$city = $data['city'] ?? null;          // ❌ ELIMINADO
$gender = $data['gender'] ?? null;      // ❌ ELIMINADO

// DESPUÉS:
$stmt = $this->db->prepare("CALL update_user_with_history(?, ?, ?, ?, ?, ?, ?, @result)");
$stmt->bind_param("isssiis",
    $user_id,
    $full_name,
    $email,
    $status,
    $updated_by,
    $ip,
    $reason_text
);
```

#### Método getAllUsers()
```php
// ANTES (línea 152):
$sql = "SELECT u.id, u.full_name, u.email, u.city, u.gender, u.status, u.reg_date,

// DESPUÉS:
$sql = "SELECT u.id, u.full_name, u.email, u.user_type, u.status, u.created_at, u.last_login,
```

#### Método searchUsers()
```php
// ANTES (línea 320):
$stmt = $this->db->prepare("CALL search_users(?, ?, ?, ?, ?, ?, ?)");
$gender = $filters['gender'] ?? null;   // ❌ ELIMINADO
$city = $filters['city'] ?? null;       // ❌ ELIMINADO

// DESPUÉS:
$stmt = $this->db->prepare("CALL search_users(?, ?, ?, ?, ?, ?)");
$user_type = $filters['user_type'] ?? null;  // ✅ AGREGADO
$stmt->bind_param("sissii", $search_term, $role_id, $status, $user_type, $limit, $offset);
```

#### Método validateUserData()
```php
// ANTES (línea 512):
if (isset($data['gender']) && !in_array($data['gender'], ['Male', 'Female', 'Other'])) {
    return ['valid' => false, 'message' => 'Género inválido'];
}

// DESPUÉS:
if (isset($data['user_type']) && !in_array($data['user_type'], ['patient', 'doctor', 'admin'])) {
    return ['valid' => false, 'message' => 'Tipo de usuario inválido'];
}
```

---

## 🚀 PASOS DE INSTALACIÓN

### PASO 1: Ejecutar Migración FIXED
```bash
1. Abre phpMyAdmin
2. Selecciona la base de datos "hms_v2"
3. Ve a la pestaña "SQL"
4. Carga y ejecuta: database/migrations/005_user_management_enhancements_FIXED.sql
5. Verifica que no hay errores
```

### PASO 2: Ejecutar Stored Procedures ULTRA-FIXED
```bash
1. Aún en phpMyAdmin, pestaña "SQL"
2. Carga y ejecuta: database/stored-procedures/INSTALAR_SP_FASE3_ULTRA_FIXED.sql
3. Verifica que los 4 SPs se crearon correctamente:
   - create_user_with_audit
   - update_user_with_history
   - search_users
   - get_user_statistics
```

**Verificación:**
```sql
SHOW PROCEDURE STATUS WHERE Db = 'hms_v2';
```

### PASO 3: Reemplazar Archivo PHP
```bash
1. Copia el archivo ULTRA-FIXED:
   DESDE: hms/include/UserManagement_ULTRA_FIXED.php
   HACIA: hms/include/UserManagement.php

2. O renombra el archivo:
   - Renombra UserManagement.php a UserManagement_OLD.php
   - Renombra UserManagement_ULTRA_FIXED.php a UserManagement.php
```

### PASO 4: Actualizar Archivo de Tests
Ahora necesitas actualizar `test-user-management.php` para eliminar referencias a columnas inexistentes.

**Ubicaciones a cambiar en test-user-management.php:**

```php
// ❌ ANTES (ejemplo):
$data = [
    'full_name' => 'Usuario Test',
    'email' => 'test@hospital.com',
    'password' => 'TestPass123!',
    'address' => 'Dirección de prueba',   // ❌ ELIMINAR
    'city' => 'La Paz',                   // ❌ ELIMINAR
    'gender' => 'Male'                     // ❌ ELIMINAR
];

// ✅ DESPUÉS:
$data = [
    'full_name' => 'Usuario Test',
    'email' => 'test@hospital.com',
    'password' => 'TestPass123!',
    'user_type' => 'patient'               // ✅ AGREGAR
];
```

---

## 📝 Cambios en los Tests

Los tests necesitan ajustarse para NO esperar las columnas eliminadas:

### Test 9: Crear Usuario
```php
// Eliminar de $data:
unset($data['address']);
unset($data['city']);
unset($data['gender']);

// Agregar:
$data['user_type'] = 'patient';
```

### Test 10: Leer Usuario
```php
// NO verificar existencia de:
// - $user['address']
// - $user['city']
// - $user['gender']

// SÍ verificar:
// - $user['full_name']
// - $user['email']
// - $user['user_type']
// - $user['status']
```

### Test 11: Actualizar Usuario
```php
// Eliminar de $update_data:
unset($update_data['city']);
unset($update_data['address']);

// Solo actualizar:
$update_data = [
    'full_name' => 'Usuario Actualizado',
    'email' => 'nuevo@email.com'
];
```

### Test 20: Estadísticas
```php
// NO esperar:
// - $stats['male_users']
// - $stats['female_users']

// SÍ esperar:
// - $stats['patients']
// - $stats['doctors']
// - $stats['admins']
```

---

## ✅ Resumen de Correcciones

### Columnas ELIMINADAS de todo el código:
1. ❌ `contactno` (teléfono de contacto)
2. ❌ `city` (ciudad)
3. ❌ `address` (dirección)
4. ❌ `gender` (género: Male/Female/Other)

### Columnas AGREGADAS/USADAS:
1. ✅ `user_type` (patient/doctor/admin)
2. ✅ `status` (active/inactive/blocked)
3. ✅ `created_at` (en lugar de reg_date)
4. ✅ `updated_at` (en lugar de updation_date)

### Archivos ULTRA-FIXED disponibles:
1. ✅ `INSTALAR_SP_FASE3_ULTRA_FIXED.sql` - Stored Procedures corregidos
2. ✅ `UserManagement_ULTRA_FIXED.php` - Clase PHP corregida
3. ✅ `005_user_management_enhancements_FIXED.sql` - Ya estaba OK

### Archivo que FALTA corregir:
- ⏳ `test-user-management.php` - Necesitas hacer los cambios manualmente

---

## 🎯 ¿Qué esperar después de las correcciones?

### Tests que deberían PASAR (21/21):
1. ✅ Test 1-8: Verificación de estructura (tablas, SPs, clases)
2. ✅ Test 9-12: CRUD de usuarios (crear, leer, actualizar, eliminar)
3. ✅ Test 13: Obtener roles de usuario
4. ✅ Test 14-16: Gestión de roles (asignar, revocar, verificar)
5. ✅ Test 17-19: Búsquedas y filtros
6. ✅ Test 20: Estadísticas generales
7. ✅ Test 21: Listar todos los usuarios

---

## 💡 Siguiente Paso RECOMENDADO

**¿Quieres que te cree el archivo test-user-management_ULTRA_FIXED.php completo?**

Puedo generar la versión completa del archivo de tests ya corregida, para que solo tengas que:
1. Reemplazar el archivo
2. Ejecutar los tests
3. Ver las 21 pruebas pasar ✅

**Dime si procedo con crear test-user-management_ULTRA_FIXED.php**

---

**Documento creado:** 21 de Octubre, 2025
**Versión:** 2.0 ULTRA-FIXED
**Estado:** ⏳ ESPERANDO CONFIRMACIÓN PARA TESTS
