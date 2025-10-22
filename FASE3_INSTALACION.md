# 🚀 FASE 3: Instalación y Pruebas
## Sistema de Gestión de Usuarios con Auditoría Completa

**Versión:** 2.3.0
**Fecha:** 21 de Octubre, 2025
**Proyecto:** Hospital Management System - SIS 321

---

## 📋 Tabla de Contenidos

1. [Requisitos Previos](#requisitos-previos)
2. [Archivos Creados](#archivos-creados)
3. [Instalación Paso a Paso](#instalación-paso-a-paso)
4. [Verificación con Tests](#verificación-con-tests)
5. [Uso del Sistema](#uso-del-sistema)
6. [Solución de Problemas](#solución-de-problemas)

---

## ✅ Requisitos Previos

Antes de comenzar, asegúrate de haber completado:

- ✅ **FASE 1**: Políticas de Contraseñas (implementado y funcionando)
- ✅ **FASE 2**: Sistema RBAC (implementado y funcionando)
- ✅ **XAMPP** corriendo (Apache + MySQL)
- ✅ **Base de datos** `hospital` creada
- ✅ **Usuario admin** con ID 8 y rol Super Admin

---

## 📁 Archivos Creados

### Base de Datos

```
database/
├── migrations/
│   └── 005_user_management_enhancements.sql  (NUEVO)
│
└── stored-procedures/
    ├── 06_create_user_with_audit.sql          (NUEVO)
    ├── 07_update_user_with_history.sql        (NUEVO)
    ├── 08_search_users.sql                    (NUEVO)
    ├── 09_get_user_statistics.sql             (NUEVO)
    └── INSTALAR_SP_FASE3.sql                  (NUEVO - Instalador completo)
```

### Backend PHP

```
hms/
├── include/
│   ├── UserManagement.php         (NUEVO - Clase principal)
│   └── csrf-protection.php        (NUEVO - Protección CSRF)
│
├── admin/
│   └── api/
│       └── users-api.php          (NUEVO - API REST)
│
└── test-user-management.php       (NUEVO - Suite de 21 pruebas)
```

---

## 🔧 Instalación Paso a Paso

### PASO 1: Ejecutar Migración de Base de Datos

1. **Abre phpMyAdmin** en tu navegador:
   ```
   http://localhost/phpmyadmin
   ```

2. **Selecciona la base de datos** `hospital`

3. **Ejecuta la migración:**
   - Click en la pestaña **SQL**
   - Abre el archivo: `database/migrations/005_user_management_enhancements.sql`
   - Copia todo el contenido
   - Pégalo en el editor SQL de phpMyAdmin
   - Click en **Continuar** o **Go**

4. **Verifica el resultado:**
   ```
   Migration 005: User Management Enhancements - COMPLETADA
   Tablas creadas: user_change_history, user_sessions, user_profile_photos, user_notes
   Vistas creadas: 6 vistas para consultas optimizadas
   Triggers creados: 2 triggers para auditoría automática
   Event creado: cleanup_expired_sessions (cada 1 hora)
   ```

5. **Confirma las tablas creadas:**
   - Click en la pestaña **Estructura**
   - Deberías ver 4 nuevas tablas:
     - `user_change_history`
     - `user_sessions`
     - `user_profile_photos`
     - `user_notes`

---

### PASO 2: Instalar Stored Procedures

**OPCIÓN A: Instalación Automática (Recomendado)**

1. En phpMyAdmin, pestaña **SQL**
2. Abre el archivo: `database/stored-procedures/INSTALAR_SP_FASE3.sql`
3. Copia y pega todo el contenido
4. Click en **Continuar**

**OPCIÓN B: Instalación Individual**

Si la opción A falla, instala cada SP por separado:

1. `database/stored-procedures/06_create_user_with_audit.sql`
2. `database/stored-procedures/07_update_user_with_history.sql`
3. `database/stored-procedures/08_search_users.sql`
4. `database/stored-procedures/09_get_user_statistics.sql`

**Verificación:**

```sql
SHOW PROCEDURE STATUS WHERE Db = 'hospital';
```

Deberías ver al menos estos 4 SPs:
- `create_user_with_audit`
- `update_user_with_history`
- `search_users`
- `get_user_statistics`

---

### PASO 3: Verificar Archivos PHP

Asegúrate de que estos archivos existan:

```bash
# Desde la carpeta del proyecto
ls hms/include/UserManagement.php
ls hms/include/csrf-protection.php
ls hms/admin/api/users-api.php
ls hms/test-user-management.php
```

O verifica manualmente que los archivos existan en tu explorador de archivos.

---

## 🧪 Verificación con Tests

### EJECUTAR SUITE DE PRUEBAS

1. **Abre tu navegador** y ve a:
   ```
   http://localhost/hospital/hms/test-user-management.php
   ```

2. **Deberías ver:**
   - 21 pruebas totales
   - Tarjetas de estadísticas (Total, Pasadas, Fallidas, Porcentaje)
   - Barra de progreso
   - Resultados detallados por sección

3. **Secciones de Pruebas:**

   #### 1. Verificación de Base de Datos (Tests 1-4)
   - ✓ Tablas de Base de Datos
   - ✓ Stored Procedures
   - ✓ Vistas SQL
   - ✓ Índices de Optimización

   #### 2. Verificación de Clases PHP (Tests 5-8)
   - ✓ Clase UserManagement
   - ✓ Protección CSRF
   - ✓ Funciones RBAC
   - ✓ API REST (users-api.php)

   #### 3. Operaciones CRUD (Tests 9-12)
   - ✓ Crear Usuario
   - ✓ Leer Usuario
   - ✓ Actualizar Usuario
   - ✓ Eliminar Usuario (Soft Delete)

   #### 4. Gestión de Roles (Tests 13-16)
   - ✓ Obtener Roles de Usuario
   - ✓ Asignar Roles
   - ✓ Revocar Roles
   - ✓ Historial de Cambios

   #### 5. Búsqueda y Filtros (Tests 17-19)
   - ✓ Búsqueda sin Filtros
   - ✓ Búsqueda con Filtros
   - ✓ Búsqueda por Término

   #### 6. Estadísticas y Listados (Tests 20-21)
   - ✓ Estadísticas Generales
   - ✓ Obtener Todos los Usuarios

4. **Objetivo:**
   - **Tasa de Éxito Esperada:** 100% (21/21 pruebas pasadas)
   - Si alguna prueba falla, revisa la sección "Solución de Problemas"

---

## 💻 Uso del Sistema

### Acceder al Panel de Gestión de Usuarios

Una vez que todas las pruebas pasen:

1. **Login como Admin:**
   ```
   http://localhost/hospital/hms/admin/
   Email: admin@hospital.com
   Password: (tu contraseña de admin)
   ```

2. **Ir al Panel de Usuarios:**
   - En el sidebar, busca **"Gestión de Usuarios"** o **"Manage Users"**
   - O ve directo a: `http://localhost/hospital/hms/admin/manage-users.php`

### Funcionalidades Disponibles

#### 📊 Dashboard de Usuarios
- Estadísticas en tiempo real (Total, Activos, Inactivos, Nuevos)
- Tabla con DataTables (paginación, ordenamiento, búsqueda)
- Filtros avanzados (Estado, Género, Rol, Ciudad)

#### 🔍 Búsqueda Avanzada
```php
// Ejemplo de uso en código
$userManager = new UserManagement($con, $_SESSION['id']);

// Búsqueda simple
$users = $userManager->searchUsers('admin');

// Búsqueda con filtros
$users = $userManager->searchUsers('', [
    'status' => 1,        // Solo activos
    'gender' => 'Male',   // Solo masculino
    'role_id' => 2,       // Solo admins
    'city' => 'La Paz',   // Solo de La Paz
    'limit' => 50         // Máximo 50 resultados
]);
```

#### 👤 Crear Usuario
```php
$userManager = new UserManagement($con, $_SESSION['id']);

$data = [
    'full_name' => 'Juan Pérez',
    'email' => 'juan@hospital.com',
    'password' => 'SecurePass123!',  // Será hasheado automáticamente
    'address' => 'Av. 16 de Julio',
    'city' => 'La Paz',
    'gender' => 'Male',
    'contactno' => '71234567'
];

$result = $userManager->createUser($data, 'Usuario creado desde admin');

if ($result['success']) {
    echo "Usuario creado con ID: " . $result['user_id'];
} else {
    echo "Error: " . $result['message'];
}
```

#### ✏️ Actualizar Usuario
```php
$data = [
    'full_name' => 'Juan Carlos Pérez',  // Nuevo nombre
    'city' => 'Cochabamba'                // Nueva ciudad
];

$result = $userManager->updateUser(
    $user_id,
    $data,
    'Actualización de datos personales'
);
```

#### 🗑️ Eliminar Usuario (Soft Delete)
```php
$result = $userManager->deleteUser(
    $user_id,
    'Usuario dado de baja por inactividad'
);

// El usuario NO se borra físicamente, solo se marca status = 0
```

#### 🛡️ Gestionar Roles
```php
// Asignar múltiples roles
$result = $userManager->assignRoles(
    $user_id,
    [2, 4],  // IDs de roles (Admin, Patient)
    'Asignación de roles adicionales'
);

// Revocar roles
$result = $userManager->revokeRoles(
    $user_id,
    [4],  // ID del rol a revocar
    'Ya no es paciente'
);

// Ver roles de un usuario
$roles = $userManager->getUserRoles($user_id);
```

#### 📜 Ver Historial de Cambios
```php
// Obtener historial completo
$history = $userManager->getUserHistory($user_id);

// Cada entrada incluye:
// - change_type: create, update, delete, status_change, role_change
// - field_changed: campo específico
// - old_value: valor anterior
// - new_value: valor nuevo
// - changed_by: quién hizo el cambio
// - change_reason: razón del cambio
// - ip_address: IP desde donde se hizo
// - created_at: timestamp
```

#### 📊 Estadísticas
```php
$stats = $userManager->getStatistics();

/*
Retorna:
- total_users
- active_users
- inactive_users
- male_users
- female_users
- other_gender_users
- users_last_7_days
- users_last_30_days
- active_sessions
- changes_last_24h
- changes_last_7_days
*/
```

---

## 🔒 Seguridad Implementada

### 1. Protección CSRF
Todos los formularios y llamadas AJAX requieren token CSRF:

```php
// En formularios HTML
<?php echo csrf_token_field(); ?>

// En AJAX
$.ajax({
    url: 'api/users-api.php',
    method: 'POST',
    data: {
        action: 'create_user',
        csrf_token: '<?php echo csrf_token(); ?>',
        // ... más datos
    }
});
```

### 2. Protección SQL Injection
Todas las queries usan prepared statements:

```php
// ✅ CORRECTO (Prepared Statement)
$stmt = $con->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);

// ❌ INCORRECTO (Vulnerable)
$result = mysqli_query($con, "SELECT * FROM users WHERE id = $user_id");
```

### 3. Validación de Permisos RBAC
Todas las páginas están protegidas:

```php
// En manage-users.php
requirePermission('manage_users');

// En users-api.php
if (!hasPermission('manage_users')) {
    http_response_code(403);
    exit();
}
```

### 4. Auditoría Completa
Todos los cambios se registran automáticamente:

```sql
SELECT * FROM user_change_history
WHERE user_id = 8
ORDER BY created_at DESC;

-- Muestra:
-- - Qué cambió
-- - Quién lo cambió
-- - Cuándo
-- - Por qué
-- - Desde qué IP
```

---

## 🐛 Solución de Problemas

### Problema 1: "Tabla no existe"

**Error:**
```
Table 'hospital.user_change_history' doesn't exist
```

**Solución:**
1. Verifica que ejecutaste la migración 005
2. En phpMyAdmin, verifica la pestaña "Estructura"
3. Si falta, ejecuta nuevamente `005_user_management_enhancements.sql`

---

### Problema 2: "Stored procedure no existe"

**Error:**
```
PROCEDURE hospital.create_user_with_audit does not exist
```

**Solución:**
1. Ejecuta `INSTALAR_SP_FASE3.sql`
2. Si falla, instala cada SP individualmente
3. Verifica con: `SHOW PROCEDURE STATUS WHERE Db = 'hospital';`

---

### Problema 3: "Class UserManagement not found"

**Error:**
```
Fatal error: Class 'UserManagement' not found
```

**Solución:**
1. Verifica que el archivo existe: `hms/include/UserManagement.php`
2. Asegúrate de incluirlo:
   ```php
   require_once('include/UserManagement.php');
   ```

---

### Problema 4: "CSRF token inválido"

**Error:**
```
CSRF_VALIDATION_FAILED
```

**Solución:**
1. Verifica que `csrf-protection.php` está incluido
2. Asegúrate de enviar el token en cada POST:
   ```php
   csrf_token: '<?php echo csrf_token(); ?>'
   ```

---

### Problema 5: Tests fallan en creación de usuario

**Error:**
```
TEST 9: Crear Usuario - FAILED
Error al crear usuario
```

**Solución Posible:**

1. **Email duplicado:**
   - El test usa un timestamp para el email
   - Si ejecutas muy rápido, puede duplicarse
   - Espera 1 segundo y ejecuta nuevamente

2. **Permisos:**
   - Verifica que el usuario ID 8 tenga permiso `manage_users`
   - Ejecuta:
     ```sql
     SELECT * FROM user_permissions WHERE user_id = 8;
     ```

3. **Políticas de Contraseña (FASE 1):**
   - Verifica que FASE 1 esté bien configurada
   - La password `TestPass123!` debe cumplir las políticas

---

### Problema 6: "No autorizado" al acceder a manage-users.php

**Error:**
```
No tiene permisos para gestionar usuarios
```

**Solución:**
```sql
-- Asignar permiso manage_users al usuario admin
CALL assign_permission_to_role(1, 24);  -- Super Admin + manage_users

-- O verificar que ya lo tiene
SELECT * FROM role_permissions
WHERE role_id = 1 AND permission_id = 24;
```

---

## ✅ Checklist de Verificación

Usa este checklist para confirmar que todo está instalado:

### Base de Datos
- [ ] Tabla `user_change_history` existe
- [ ] Tabla `user_sessions` existe
- [ ] Tabla `user_profile_photos` existe
- [ ] Tabla `user_notes` existe
- [ ] Vista `active_users_summary` existe
- [ ] Vista `user_changes_detailed` existe
- [ ] Vista `active_sessions_view` existe
- [ ] Vista `user_statistics_by_role` existe
- [ ] SP `create_user_with_audit` existe
- [ ] SP `update_user_with_history` existe
- [ ] SP `search_users` existe
- [ ] SP `get_user_statistics` existe

### Archivos PHP
- [ ] `hms/include/UserManagement.php` existe
- [ ] `hms/include/csrf-protection.php` existe
- [ ] `hms/admin/api/users-api.php` existe
- [ ] `hms/test-user-management.php` existe

### Pruebas
- [ ] Test Suite accesible en navegador
- [ ] 21/21 pruebas pasadas (100%)
- [ ] Todas las secciones en verde

### Funcionalidad
- [ ] Puedo crear usuarios desde código
- [ ] Puedo actualizar usuarios
- [ ] Puedo buscar usuarios
- [ ] Puedo asignar/revocar roles
- [ ] Puedo ver historial de cambios
- [ ] Puedo obtener estadísticas

---

## 🎯 Próximos Pasos

Una vez que todo esté funcionando (21/21 tests pasados):

### 1. Explorar el Sistema
- Juega con el test suite
- Crea usuarios de prueba
- Asigna diferentes roles
- Observa el historial de cambios

### 2. Integración con Frontend
Los archivos `manage-users.php` y `user-form.php` ya tienen estructura base.
Para mejorarlos, necesitarás:
- Integrar DataTables con la API
- Crear modales para asignación de roles
- Implementar formularios con validación

### 3. Documentación
Lee el código de `UserManagement.php` para entender todos los métodos disponibles.

### 4. Siguiente Fase
Prepárate para FASE 4 (si aplica) o mejora las interfaces de usuario.

---

## 📞 Soporte

Si encuentras problemas:

1. **Revisa los logs de PHP:**
   ```
   C:\xampp\apache\logs\error.log
   ```

2. **Revisa los logs de MySQL:**
   ```
   C:\xampp\mysql\data\mysql_error.log
   ```

3. **Habilita error reporting en PHP:**
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

4. **Ejecuta queries de diagnóstico:**
   ```sql
   -- Ver estructura de tabla
   DESCRIBE user_change_history;

   -- Ver SPs instalados
   SHOW PROCEDURE STATUS WHERE Db = 'hospital';

   -- Ver últimos cambios
   SELECT * FROM user_change_history ORDER BY created_at DESC LIMIT 10;
   ```

---

## 🎉 ¡Felicidades!

Si llegaste hasta aquí y todas las pruebas pasan, has implementado exitosamente:

✅ **Sistema completo de gestión de usuarios**
✅ **Auditoría completa de cambios**
✅ **CRUD con validación y seguridad**
✅ **Integración con RBAC (FASE 2)**
✅ **Integración con Políticas de Contraseñas (FASE 1)**
✅ **API REST funcional**
✅ **21 casos de prueba automatizados**

---

**Versión del Documento:** 1.0
**Última Actualización:** 21 de Octubre, 2025
**Autor:** Claude (Anthropic)
**Proyecto:** Hospital Management System - FASE 3
