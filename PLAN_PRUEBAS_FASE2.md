# 🧪 Plan de Pruebas - FASE 2: Sistema RBAC

## 📋 Información del Plan

**Proyecto:** SIS 321 - Seguridad de Sistemas
**Fase:** 2 - Sistema RBAC
**Fecha:** 2025-10-20
**Duración Estimada:** 30-45 minutos

---

## 🎯 Objetivos de las Pruebas

1. ✅ Verificar que todas las tablas se crearon correctamente
2. ✅ Validar que los roles y permisos están asignados
3. ✅ Probar funciones PHP de verificación
4. ✅ Validar middleware de protección de páginas
5. ✅ Comprobar sistema de auditoría
6. ✅ Verificar permisos efectivos por usuario

---

## 📝 Pre-requisitos

Antes de empezar las pruebas, asegúrate de:

- [x] XAMPP está corriendo (Apache + MySQL)
- [x] Has ejecutado las 3 migraciones SQL (ver `INSTALACION_MANUAL_RBAC.md`)
- [x] La base de datos `hms_v2` existe y está activa
- [x] Tienes al menos un usuario creado en la tabla `users`

---

# 🔬 PRUEBAS DE BASE DE DATOS

## Prueba 1: Verificar Estructura de Tablas

### **Objetivo:** Confirmar que todas las tablas RBAC se crearon

**SQL a ejecutar:**
```sql
SHOW TABLES LIKE '%role%';
SHOW TABLES LIKE '%permission%';
SHOW TABLES LIKE 'audit_%';
SHOW TABLES LIKE 'security_%';
```

**Resultado Esperado:**
```
roles
role_permissions
role_hierarchy
user_roles
permissions
permission_categories
audit_role_changes
security_logs
```

**Estado:** [ ]

---

## Prueba 2: Verificar Columnas de Tabla `roles`

**SQL a ejecutar:**
```sql
DESCRIBE roles;
```

**Columnas Esperadas:**
- `id` (PK)
- `role_name` (VARCHAR UNIQUE)
- `display_name`
- `description`
- `is_system_role`
- `priority`
- `status`
- `created_at`
- `updated_at`
- `created_by`

**Estado:** [ ]

---

## Prueba 3: Contar Roles Creados

**SQL a ejecutar:**
```sql
SELECT COUNT(*) as total FROM roles;
```

**Resultado Esperado:** `total = 7`

**SQL detallado:**
```sql
SELECT id, role_name, display_name, priority, status
FROM roles
ORDER BY priority;
```

**Roles Esperados:**
1. super_admin (prioridad 1)
2. admin (prioridad 10)
3. doctor (prioridad 20)
4. nurse (prioridad 25)
5. receptionist (prioridad 30)
6. lab_technician (prioridad 35)
7. patient (prioridad 40)

**Estado:** [ ]

---

## Prueba 4: Contar Permisos Creados

**SQL a ejecutar:**
```sql
SELECT COUNT(*) as total FROM permissions;
```

**Resultado Esperado:** `total >= 60`

**SQL por categoría:**
```sql
SELECT
    pc.display_name as categoria,
    COUNT(p.id) as total_permisos
FROM permission_categories pc
LEFT JOIN permissions p ON pc.id = p.category_id
GROUP BY pc.id, pc.display_name
ORDER BY pc.sort_order;
```

**Categorías Esperadas:**
- Gestión de Usuarios: ~8 permisos
- Gestión de Pacientes: ~7 permisos
- Gestión de Doctores: ~6 permisos
- Gestión de Citas: ~7 permisos
- Registros Médicos: ~7 permisos
- Facturación: ~7 permisos
- Reportes: ~5 permisos
- Sistema: ~7 permisos
- Seguridad: ~4 permisos

**Estado:** [ ]

---

## Prueba 5: Verificar Asignaciones Rol-Permiso

**SQL a ejecutar:**
```sql
SELECT
    r.display_name AS Rol,
    COUNT(rp.permission_id) AS Permisos
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.display_name
ORDER BY r.priority;
```

**Resultado Esperado:**
- Super Admin: 60+ permisos (TODOS)
- Admin: ~55 permisos
- Doctor: ~25 permisos
- Recepcionista: ~20 permisos
- Enfermera: ~15 permisos
- Paciente: ~8 permisos
- Lab Technician: ~10 permisos

**Estado:** [ ]

---

## Prueba 6: Verificar Vistas SQL

**SQL a ejecutar:**
```sql
SELECT table_name
FROM information_schema.views
WHERE table_schema = 'hms_v2'
ORDER BY table_name;
```

**Vistas Esperadas (6):**
1. `access_attempts_by_ip`
2. `expiring_user_roles`
3. `role_permission_matrix`
4. `unauthorized_access_summary`
5. `user_effective_permissions`
6. `user_roles_summary`

**Estado:** [ ]

---

## Prueba 7: Verificar Stored Procedures

**SQL a ejecutar:**
```sql
SELECT routine_name, routine_type
FROM information_schema.routines
WHERE routine_schema = 'hms_v2'
AND routine_type = 'PROCEDURE'
ORDER BY routine_name;
```

**Procedures Esperados (5):**
1. `assign_role_to_user`
2. `cleanup_old_security_data`
3. `get_user_permissions`
4. `revoke_role_from_user`
5. `user_has_permission`

**Estado:** [ ]

---

# 👥 PRUEBAS DE ASIGNACIÓN DE ROLES

## Prueba 8: Asignar Super Admin a Usuario

**SQL a ejecutar:**
```sql
-- Paso 1: Ver usuarios disponibles
SELECT id, email, full_name FROM users LIMIT 5;

-- Paso 2: Asignar Super Admin al usuario 1
INSERT INTO user_roles (user_id, role_id, assigned_by, is_active)
VALUES (1, 1, 1, 1);

-- Paso 3: Verificar asignación
SELECT
    u.id,
    u.email,
    u.full_name,
    r.role_name,
    r.display_name,
    ur.assigned_at
FROM users u
INNER JOIN user_roles ur ON u.id = ur.user_id
INNER JOIN roles r ON ur.role_id = r.id
WHERE u.id = 1;
```

**Resultado Esperado:**
- Usuario 1 tiene rol `super_admin`
- `is_active = 1`
- Fecha de asignación es la actual

**Estado:** [ ]

---

## Prueba 9: Verificar Permisos Efectivos del Usuario

**SQL a ejecutar:**
```sql
-- Ver todos los permisos del usuario 1
SELECT
    permission_name,
    module,
    role_name
FROM user_effective_permissions
WHERE user_id = 1
ORDER BY module, permission_name
LIMIT 20;
```

**Resultado Esperado:**
- Si el usuario tiene rol `super_admin`, debe tener TODOS los permisos (60+)
- Deben estar agrupados por módulo

**SQL para contar:**
```sql
SELECT COUNT(*) as permisos_totales
FROM user_effective_permissions
WHERE user_id = 1;
```

**Estado:** [ ]

---

## Prueba 10: Probar Stored Procedure - user_has_permission

**SQL a ejecutar:**
```sql
-- Verificar si usuario 1 tiene permiso 'view_patients'
CALL user_has_permission(1, 'view_patients');

-- Verificar permiso inexistente
CALL user_has_permission(1, 'permiso_falso');
```

**Resultado Esperado:**
- `view_patients`: `has_permission = 1` (true)
- `permiso_falso`: `has_permission = 0` (false)

**Estado:** [ ]

---

## Prueba 11: Probar Stored Procedure - get_user_permissions

**SQL a ejecutar:**
```sql
CALL get_user_permissions(1);
```

**Resultado Esperado:**
- Lista completa de permisos del usuario
- Agrupados por módulo
- Muestra el rol del que proviene cada permiso

**Estado:** [ ]

---

## Prueba 12: Asignar Rol con Expiración

**SQL a ejecutar:**
```sql
-- Crear usuario de prueba temporal (si no tienes usuario 2)
-- O usar un usuario existente

-- Asignar rol Doctor con expiración en 7 días
CALL assign_role_to_user(
    2,                              -- user_id
    3,                              -- role_id (Doctor)
    1,                              -- assigned_by
    DATE_ADD(NOW(), INTERVAL 7 DAY) -- expires_at
);

-- Verificar
SELECT
    u.email,
    r.display_name as rol,
    ur.assigned_at,
    ur.expires_at,
    DATEDIFF(ur.expires_at, NOW()) as dias_restantes
FROM user_roles ur
INNER JOIN users u ON ur.user_id = u.id
INNER JOIN roles r ON ur.role_id = r.id
WHERE u.id = 2;
```

**Resultado Esperado:**
- Rol asignado exitosamente
- `expires_at` es dentro de 7 días
- `dias_restantes = 7`

**Estado:** [ ]

---

# 💻 PRUEBAS DE FUNCIONES PHP

## Prueba 13: Crear Archivo de Prueba PHP

**Crear archivo:** `C:\xampp\htdocs\hospital\hms\test-rbac-functions.php`

```php
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('include/config.php');
require_once('include/rbac-functions.php');

// Simular sesión de usuario 1 (Super Admin)
$_SESSION['id'] = 1;

echo "<pre>";
echo "=== PRUEBAS DE FUNCIONES RBAC ===\n\n";

// Test 1: hasPermission()
echo "Test 1: hasPermission('view_patients')\n";
$result = hasPermission('view_patients');
echo "Resultado: " . ($result ? '✓ TRUE' : '✗ FALSE') . "\n";
echo "Esperado: ✓ TRUE\n\n";

// Test 2: hasRole()
echo "Test 2: hasRole('super_admin')\n";
$result = hasRole('super_admin');
echo "Resultado: " . ($result ? '✓ TRUE' : '✗ FALSE') . "\n";
echo "Esperado: ✓ TRUE\n\n";

// Test 3: isSuperAdmin()
echo "Test 3: isSuperAdmin()\n";
$result = isSuperAdmin();
echo "Resultado: " . ($result ? '✓ TRUE' : '✗ FALSE') . "\n";
echo "Esperado: ✓ TRUE\n\n";

// Test 4: isAdmin()
echo "Test 4: isAdmin()\n";
$result = isAdmin();
echo "Resultado: " . ($result ? '✓ TRUE' : '✗ FALSE') . "\n";
echo "Esperado: ✓ TRUE\n\n";

// Test 5: getUserPermissions()
echo "Test 5: getUserPermissions()\n";
$perms = getUserPermissions();
echo "Total de permisos: " . count($perms) . "\n";
echo "Esperado: >= 60\n";
echo "Primeros 5: " . implode(', ', array_slice($perms, 0, 5)) . "\n\n";

// Test 6: getUserRoles()
echo "Test 6: getUserRoles()\n";
$roles = getUserRoles();
echo "Total de roles: " . count($roles) . "\n";
foreach ($roles as $role) {
    echo "  - {$role['display_name']} (prioridad: {$role['priority']})\n";
}
echo "\n";

// Test 7: Clase RBAC - assignRoleToUser()
echo "Test 7: Asignar rol Doctor a usuario 2 (si existe)\n";
$rbac = new RBAC($con);
$result = $rbac->assignRoleToUser(2, 3, 1); // user_id=2, role_id=3 (Doctor), assigned_by=1
if ($result['success']) {
    echo "✓ " . $result['message'] . "\n";
} else {
    echo "✗ " . $result['message'] . "\n";
}
echo "\n";

// Test 8: Verificar rol recién asignado
echo "Test 8: hasRole('doctor', 2)\n";
$result = hasRole('doctor', 2);
echo "Resultado: " . ($result ? '✓ TRUE' : '✗ FALSE') . "\n";
echo "Esperado: ✓ TRUE\n\n";

// Test 9: getRolePermissions()
echo "Test 9: getRolePermissions(3) - Permisos del rol Doctor\n";
$perms = $rbac->getRolePermissions(3);
echo "Total de permisos del Doctor: " . count($perms) . "\n";
echo "Esperado: ~25 permisos\n";
echo "Primeros 5:\n";
foreach (array_slice($perms, 0, 5) as $perm) {
    echo "  - {$perm['display_name']} ({$perm['permission_name']})\n";
}
echo "\n";

// Test 10: getUserPrimaryRole()
echo "Test 10: getUserPrimaryRole(1)\n";
$primary = $rbac->getUserPrimaryRole(1);
echo "Rol principal: {$primary['display_name']}\n";
echo "Esperado: Super Administrador\n\n";

echo "=== FIN DE PRUEBAS ===\n";
echo "</pre>";
?>
```

**Acceder desde navegador:**
```
http://localhost/hospital/hms/test-rbac-functions.php
```

**Resultado Esperado:**
- Todos los tests deben pasar (✓ TRUE)
- No debe haber errores de PHP
- Los conteos deben coincidir

**Estado:** [ ]

---

# 🛡️ PRUEBAS DE MIDDLEWARE

## Prueba 14: Página Protegida por Permiso

**Crear archivo:** `C:\xampp\htdocs\hospital\hms\test-protected-page.php`

```php
<?php
session_start();
require_once('include/config.php');
require_once('include/permission-check.php');

// Proteger esta página - Solo usuarios con 'view_patients'
requirePermission('view_patients');

echo "<h1>✓ Acceso Permitido</h1>";
echo "<p>Si ves este mensaje, tienes el permiso 'view_patients'</p>";
echo "<p><a href='test-denied-page.php'>Ir a página denegada</a></p>";
?>
```

**Acceder desde navegador:**
```
http://localhost/hospital/hms/test-protected-page.php
```

**Resultado Esperado (con Super Admin):**
- ✓ Página se muestra correctamente
- Mensaje: "Acceso Permitido"

**Estado:** [ ]

---

## Prueba 15: Página sin Permiso (Acceso Denegado)

**Crear archivo:** `C:\xampp\htdocs\hospital\hms\test-denied-page.php`

```php
<?php
session_start();
require_once('include/config.php');
require_once('include/permission-check.php');

// Requiere permiso que no existe
requirePermission('permiso_inexistente_falso_123');

echo "<h1>✗ ERROR - No deberías ver esto</h1>";
?>
```

**Acceder desde navegador:**
```
http://localhost/hospital/hms/test-denied-page.php
```

**Resultado Esperado:**
- ✗ Redirige a `access-denied.php`
- Muestra mensaje de error 403
- Indica el permiso requerido

**Estado:** [ ]

---

## Prueba 16: Página Protegida por Rol

**Crear archivo:** `C:\xampp\htdocs\hospital\hms\test-admin-only.php`

```php
<?php
session_start();
require_once('include/config.php');
require_once('include/permission-check.php');

// Solo admins
requireAnyRole(['super_admin', 'admin']);

echo "<h1>✓ Panel de Administración</h1>";
echo "<p>Solo admins pueden ver esto</p>";
?>
```

**Acceder desde navegador:**
```
http://localhost/hospital/hms/test-admin-only.php
```

**Resultado Esperado (con Super Admin):**
- ✓ Página se muestra
- Mensaje: "Panel de Administración"

**Estado:** [ ]

---

# 📊 PRUEBAS DE AUDITORÍA

## Prueba 17: Verificar Logs de Auditoría

**SQL a ejecutar:**
```sql
-- Ver cambios de roles registrados
SELECT
    u.email as usuario_afectado,
    r.display_name as rol,
    arc.action,
    admin.email as realizado_por,
    arc.performed_at,
    arc.ip_address
FROM audit_role_changes arc
INNER JOIN users u ON arc.user_id = u.id
INNER JOIN roles r ON arc.role_id = r.id
LEFT JOIN users admin ON arc.performed_by = admin.id
ORDER BY arc.performed_at DESC
LIMIT 10;
```

**Resultado Esperado:**
- Al menos 1-2 registros de asignaciones de roles
- Acción: `assigned`
- IP y timestamp registrados

**Estado:** [ ]

---

## Prueba 18: Generar Log de Acceso No Autorizado

**Crear archivo:** `C:\xampp\htdocs\hospital\hms\test-unauthorized.php`

```php
<?php
session_start();
$_SESSION['id'] = 2; // Usuario sin permisos de admin

require_once('include/config.php');
require_once('include/permission-check.php');

// Intentar acceder sin permiso
requirePermission('manage_system_settings');

echo "No deberías ver esto";
?>
```

**Acceder desde navegador:**
```
http://localhost/hospital/hms/test-unauthorized.php
```

**Luego verificar en SQL:**
```sql
SELECT
    u.email,
    sl.event_type,
    sl.event_description,
    sl.ip_address,
    sl.created_at
FROM security_logs sl
LEFT JOIN users u ON sl.user_id = u.id
WHERE sl.event_type = 'unauthorized_access'
ORDER BY sl.created_at DESC
LIMIT 5;
```

**Resultado Esperado:**
- Nuevo registro en `security_logs`
- `event_type = 'unauthorized_access'`
- Descripción indica el permiso requerido

**Estado:** [ ]

---

# 🌐 PRUEBAS DE INTERFAZ WEB

## Prueba 19: Demo Interactiva RBAC

**Acceder desde navegador:**
```
http://localhost/hospital/hms/admin/rbac-example.php
```

**Verificar:**
- [ ] Página carga sin errores
- [ ] Muestra información del usuario actual
- [ ] Lista todos tus roles
- [ ] Lista todos tus permisos (agrupados por módulo)
- [ ] Muestra ejemplos de verificación
- [ ] Tabla de roles del sistema visible
- [ ] Código de ejemplo visible

**Estado:** [ ]

---

## Prueba 20: Página Access Denied

**Acceder desde navegador:**
```
http://localhost/hospital/hms/access-denied.php?permission=view_patients
```

**Verificar:**
- [ ] Página carga sin errores
- [ ] Diseño atractivo (gradiente morado)
- [ ] Icono de error (🚫)
- [ ] Mensaje claro "Acceso Denegado"
- [ ] Muestra el permiso requerido
- [ ] Botón "Volver Atrás" funciona
- [ ] Botón "Ir al Dashboard" visible

**Estado:** [ ]

---

# 📈 PRUEBAS DE PERFORMANCE

## Prueba 21: Caché de Permisos

**Ejecutar archivo de prueba 2 veces seguidas:**
```
http://localhost/hospital/hms/test-rbac-functions.php
```

**Objetivo:** Verificar que el sistema usa caché

**Verificar en código:**
```php
// Primera llamada (consulta BD)
$start = microtime(true);
$perms1 = getUserPermissions(1);
$time1 = microtime(true) - $start;

// Segunda llamada (desde caché)
$start = microtime(true);
$perms2 = getUserPermissions(1);
$time2 = microtime(true) - $start;

echo "Primera llamada: " . ($time1 * 1000) . " ms\n";
echo "Segunda llamada: " . ($time2 * 1000) . " ms (caché)\n";
echo "Mejora: " . round(($time1 / $time2), 2) . "x más rápido\n";
```

**Resultado Esperado:**
- Segunda llamada debe ser significativamente más rápida (>2x)

**Estado:** [ ]

---

# 📊 RESUMEN DE PRUEBAS

## Checklist General

### Base de Datos
- [ ] Prueba 1: Tablas creadas (8 tablas)
- [ ] Prueba 2: Columnas de roles correctas
- [ ] Prueba 3: 7 roles creados
- [ ] Prueba 4: 60+ permisos creados
- [ ] Prueba 5: Asignaciones correctas
- [ ] Prueba 6: 6 vistas creadas
- [ ] Prueba 7: 5 stored procedures

### Asignación de Roles
- [ ] Prueba 8: Asignar Super Admin
- [ ] Prueba 9: Permisos efectivos
- [ ] Prueba 10: SP user_has_permission
- [ ] Prueba 11: SP get_user_permissions
- [ ] Prueba 12: Rol con expiración

### Funciones PHP
- [ ] Prueba 13: Todas las funciones RBAC

### Middleware
- [ ] Prueba 14: Página protegida (acceso permitido)
- [ ] Prueba 15: Página denegada (redirige)
- [ ] Prueba 16: Protección por rol

### Auditoría
- [ ] Prueba 17: Logs de cambios de roles
- [ ] Prueba 18: Logs de accesos no autorizados

### Interfaz Web
- [ ] Prueba 19: Demo interactiva funciona
- [ ] Prueba 20: Página access-denied

### Performance
- [ ] Prueba 21: Sistema de caché funciona

---

## 🎯 Criterios de Aceptación

Para considerar la FASE 2 como **EXITOSA**, debes completar:

- ✅ Mínimo 18 de 21 pruebas pasadas (85%)
- ✅ Todas las pruebas críticas (1-12) pasadas
- ✅ Sin errores de PHP en archivos de prueba
- ✅ Demo interactiva funcionando

---

## 📝 Registro de Resultados

**Fecha de Pruebas:** _______________

**Pruebas Pasadas:** _____ / 21 (___%)

**Pruebas Fallidas:**
- [ ] Prueba #___ - Razón: _________________________
- [ ] Prueba #___ - Razón: _________________________

**Observaciones:**
```
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________
```

**Aprobado por:** _______________

**Firma:** _______________

---

## 🚀 Siguiente Paso

Una vez completadas las pruebas exitosamente:

👉 **Iniciar FASE 3: ABM de Usuarios Completo**

---

**¡Éxito con las pruebas! 🎉**
