# ✅ GUÍA COMPLETA: Instalación y Pruebas RBAC (CORREGIDA)

## 📋 Información
Esta guía corrige los problemas con los stored procedures y te lleva paso a paso.

**Tiempo estimado:** 20-30 minutos

---

# 🔧 PARTE 1: REPARAR INSTALACIÓN

## ✅ Paso 1: Verificar Estado Actual

En **phpMyAdmin** → Base de datos `hms_v2` → Pestaña "SQL", ejecuta:

```sql
-- Verificar tablas
SELECT COUNT(*) as tablas FROM information_schema.tables
WHERE table_schema = 'hms_v2'
AND table_name IN ('roles', 'permissions', 'role_permissions', 'user_roles',
                    'permission_categories', 'role_hierarchy', 'audit_role_changes', 'security_logs');

-- Verificar roles
SELECT COUNT(*) as roles FROM roles;

-- Verificar permisos
SELECT COUNT(*) as permisos FROM permissions;

-- Verificar stored procedures
SELECT COUNT(*) as procedures FROM information_schema.routines
WHERE routine_schema = 'hms_v2' AND routine_type = 'PROCEDURE';
```

**Anota los resultados:**
- Tablas: _____ (esperado: 8)
- Roles: _____ (esperado: 7)
- Permisos: _____ (esperado: 58-60)
- Procedures: _____ (esperado: 5) ⚠️ **PROBLEMA AQUÍ**

---

## ✅ Paso 2: Instalar Stored Procedures Faltantes

Como **solo tienes 1 procedure**, debes ejecutar los otros 4.

### **Importante:** Ejecuta estos archivos **UNO POR UNO**

#### 📄 Procedure 1: assign_role_to_user

1. Abre en editor de texto:
   ```
   C:\xampp\htdocs\hospital\database\stored-procedures\01_assign_role_to_user.sql
   ```

2. Copia **TODO** el contenido (Ctrl+A, Ctrl+C)

3. En phpMyAdmin:
   - Pestaña "SQL"
   - Pega el contenido
   - Clic en **"Continuar"**

4. ✅ Verifica que diga algo como "Procedure creado" o "1 row affected"

---

#### 📄 Procedure 2: revoke_role_from_user

1. Abre:
   ```
   C:\xampp\htdocs\hospital\database\stored-procedures\02_revoke_role_from_user.sql
   ```

2. Copia TODO el contenido

3. Pega en phpMyAdmin → Ejecuta

4. ✅ Verifica éxito

---

#### 📄 Procedure 3: user_has_permission

1. Abre:
   ```
   C:\xampp\htdocs\hospital\database\stored-procedures\03_user_has_permission.sql
   ```

2. Copia y ejecuta en phpMyAdmin

3. ✅ Verifica

---

#### 📄 Procedure 4: get_user_permissions

1. Abre:
   ```
   C:\xampp\htdocs\hospital\database\stored-procedures\04_get_user_permissions.sql
   ```

2. Copia y ejecuta

3. ✅ Verifica

---

#### 📄 Procedure 5: cleanup_old_security_data

1. Abre:
   ```
   C:\xampp\htdocs\hospital\database\stored-procedures\05_cleanup_old_security_data.sql
   ```

2. Copia y ejecuta

3. ✅ Verifica

---

## ✅ Paso 3: Verificar Que Ahora Hay 5 Procedures

En phpMyAdmin, ejecuta:

```sql
SELECT routine_name
FROM information_schema.routines
WHERE routine_schema = 'hms_v2'
AND routine_type = 'PROCEDURE'
ORDER BY routine_name;
```

**Debe mostrar:**
```
assign_role_to_user
cleanup_old_security_data
get_user_permissions
revoke_role_from_user
user_has_permission
```

✅ **¡Perfecto! Ahora SÍ puedes continuar.**

---

# 🎯 PARTE 2: ASIGNAR ROLES

## ✅ Paso 4: Ver Usuarios Disponibles

```sql
SELECT id, email, full_name, user_type, status
FROM users
WHERE status = 'active'
LIMIT 10;
```

**Anota el ID del usuario que usarás:**
- Usuario ID: _____ (ejemplo: 1)
- Email: _____

---

## ✅ Paso 5: Asignar Rol Super Admin

**REEMPLAZA `1` con tu user_id si es diferente:**

```sql
-- Asignar Super Admin (rol_id = 1) al usuario 1
CALL assign_role_to_user(1, 1, 1, NULL);
```

**Debe mostrar:**
```
message: Rol asignado exitosamente
success: 1
```

---

## ✅ Paso 6: Verificar Asignación

```sql
SELECT
    u.id,
    u.email,
    u.full_name,
    r.role_name,
    r.display_name as rol,
    ur.assigned_at,
    ur.is_active
FROM users u
INNER JOIN user_roles ur ON u.id = ur.user_id
INNER JOIN roles r ON ur.role_id = r.id
WHERE u.id = 1;  -- CAMBIA 1 por tu user_id
```

**Debe mostrar:**
```
rol: Super Administrador
is_active: 1
```

✅ **¡Rol asignado exitosamente!**

---

# 🧪 PARTE 3: PRUEBAS DEL SISTEMA

## ✅ Paso 7: Probar Stored Procedures

### Test 1: Verificar Permiso

```sql
-- Verificar si usuario 1 tiene permiso 'view_patients'
CALL user_has_permission(1, 'view_patients');
```

**Debe mostrar:** `has_permission = 1` ✅

---

### Test 2: Ver Todos los Permisos del Usuario

```sql
CALL get_user_permissions(1);
```

**Debe mostrar:** Lista de 58-60 permisos

---

### Test 3: Ver Permisos Efectivos (Vista)

```sql
SELECT
    permission_name,
    module,
    role_name
FROM user_effective_permissions
WHERE user_id = 1
ORDER BY module, permission_name
LIMIT 20;
```

**Debe mostrar:** Permisos agrupados por módulo

---

## ✅ Paso 8: Probar Funciones PHP

### Crear Archivo de Prueba

**Crea archivo:** `C:\xampp\htdocs\hospital\hms\test-rbac-sistema.php`

**Contenido:**

```php
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simular sesión del usuario 1 (Super Admin)
$_SESSION['id'] = 1; // CAMBIA por tu user_id

require_once('include/config.php');
require_once('include/rbac-functions.php');

echo "<pre>";
echo "============================================\n";
echo "PRUEBAS DEL SISTEMA RBAC\n";
echo "============================================\n\n";

// Test 1: hasPermission()
echo "TEST 1: hasPermission('view_patients')\n";
$result = hasPermission('view_patients');
echo "Resultado: " . ($result ? '✅ PASS' : '❌ FAIL') . "\n";
echo "Esperado: ✅ PASS\n\n";

// Test 2: hasRole()
echo "TEST 2: hasRole('super_admin')\n";
$result = hasRole('super_admin');
echo "Resultado: " . ($result ? '✅ PASS' : '❌ FAIL') . "\n";
echo "Esperado: ✅ PASS\n\n";

// Test 3: isSuperAdmin()
echo "TEST 3: isSuperAdmin()\n";
$result = isSuperAdmin();
echo "Resultado: " . ($result ? '✅ PASS' : '❌ FAIL') . "\n";
echo "Esperado: ✅ PASS\n\n";

// Test 4: isAdmin()
echo "TEST 4: isAdmin()\n";
$result = isAdmin();
echo "Resultado: " . ($result ? '✅ PASS' : '❌ FAIL') . "\n";
echo "Esperado: ✅ PASS\n\n";

// Test 5: getUserPermissions()
echo "TEST 5: getUserPermissions()\n";
$perms = getUserPermissions();
$total = count($perms);
echo "Total de permisos: $total\n";
echo "Esperado: >= 58\n";
echo "Resultado: " . ($total >= 58 ? '✅ PASS' : '❌ FAIL') . "\n\n";

// Test 6: getUserRoles()
echo "TEST 6: getUserRoles()\n";
$roles = getUserRoles();
echo "Total de roles: " . count($roles) . "\n";
foreach ($roles as $role) {
    echo "  - {$role['display_name']} (prioridad: {$role['priority']})\n";
}
echo "Resultado: " . (count($roles) >= 1 ? '✅ PASS' : '❌ FAIL') . "\n\n";

// Test 7: Clase RBAC
echo "TEST 7: Clase RBAC - getRoleInfo()\n";
$rbac = new RBAC($con);
$role_info = $rbac->getRoleInfo(1); // Super Admin
echo "Rol ID 1: {$role_info['display_name']}\n";
echo "Resultado: " . ($role_info['role_name'] === 'super_admin' ? '✅ PASS' : '❌ FAIL') . "\n\n";

// Test 8: getRolePermissions()
echo "TEST 8: getRolePermissions(1) - Super Admin\n";
$role_perms = $rbac->getRolePermissions(1);
echo "Total de permisos: " . count($role_perms) . "\n";
echo "Esperado: >= 58\n";
echo "Resultado: " . (count($role_perms) >= 58 ? '✅ PASS' : '❌ FAIL') . "\n\n";

// Resumen
echo "============================================\n";
echo "RESUMEN DE PRUEBAS\n";
echo "============================================\n";
$tests_passed = 0;
if (hasPermission('view_patients')) $tests_passed++;
if (hasRole('super_admin')) $tests_passed++;
if (isSuperAdmin()) $tests_passed++;
if (isAdmin()) $tests_passed++;
if (count($perms) >= 58) $tests_passed++;
if (count($roles) >= 1) $tests_passed++;
if ($role_info['role_name'] === 'super_admin') $tests_passed++;
if (count($role_perms) >= 58) $tests_passed++;

echo "Pruebas pasadas: $tests_passed / 8\n";
if ($tests_passed === 8) {
    echo "\n✅ ¡TODAS LAS PRUEBAS PASARON!\n";
    echo "El sistema RBAC está funcionando correctamente.\n";
} else {
    echo "\n⚠️ Algunas pruebas fallaron.\n";
    echo "Revisa los resultados arriba.\n";
}

echo "\n============================================\n";
echo "</pre>";
?>
```

---

## ✅ Paso 9: Ejecutar Prueba PHP

**Abre en navegador:**
```
http://localhost/hospital/hms/test-rbac-sistema.php
```

**Debe mostrar:**
```
TEST 1: hasPermission('view_patients')
Resultado: ✅ PASS

TEST 2: hasRole('super_admin')
Resultado: ✅ PASS

TEST 3: isSuperAdmin()
Resultado: ✅ PASS

TEST 4: isAdmin()
Resultado: ✅ PASS

TEST 5: getUserPermissions()
Total de permisos: 58 (o más)
Resultado: ✅ PASS

TEST 6: getUserRoles()
Total de roles: 1
  - Super Administrador (prioridad: 1)
Resultado: ✅ PASS

TEST 7: Clase RBAC - getRoleInfo()
Rol ID 1: Super Administrador
Resultado: ✅ PASS

TEST 8: getRolePermissions(1) - Super Admin
Total de permisos: 58 (o más)
Resultado: ✅ PASS

RESUMEN DE PRUEBAS
Pruebas pasadas: 8 / 8

✅ ¡TODAS LAS PRUEBAS PASARON!
El sistema RBAC está funcionando correctamente.
```

---

## ✅ Paso 10: Demo Interactiva

**Abre en navegador:**
```
http://localhost/hospital/hms/admin/rbac-example.php
```

**Debe mostrar:**
- ✅ Tus datos de usuario
- ✅ Tus roles (Super Administrador)
- ✅ Lista de tus 58+ permisos
- ✅ Tabla de todos los roles del sistema
- ✅ Ejemplos de código

Si sale **Error 403**, asegúrate de que tu usuario tenga el rol asignado.

---

## ✅ Paso 11: Probar Middleware de Protección

### Test A: Página Protegida (Acceso Permitido)

**Crear:** `C:\xampp\htdocs\hospital\hms\test-protected.php`

```php
<?php
session_start();
$_SESSION['id'] = 1; // Tu user_id

require_once('include/config.php');
require_once('include/permission-check.php');

requirePermission('view_patients');

echo "<h1 style='color: green;'>✅ Acceso Permitido</h1>";
echo "<p>El middleware funciona correctamente.</p>";
echo "<p>Tienes el permiso 'view_patients'.</p>";
?>
```

**Abrir:**
```
http://localhost/hospital/hms/test-protected.php
```

**Debe mostrar:** "✅ Acceso Permitido"

---

### Test B: Página Denegada (Redirige a Error 403)

**Crear:** `C:\xampp\htdocs\hospital\hms\test-denied.php`

```php
<?php
session_start();
$_SESSION['id'] = 1;

require_once('include/config.php');
require_once('include/permission-check.php');

requirePermission('permiso_falso_inexistente_123');

echo "No deberías ver esto";
?>
```

**Abrir:**
```
http://localhost/hospital/hms/test-denied.php
```

**Debe redirigir a:** `access-denied.php` con mensaje de error 403

---

# ✅ CHECKLIST FINAL

## Instalación
- [ ] Ejecuté los 5 stored procedures
- [ ] Verifiqué que hay 5 procedures en BD
- [ ] Asigné rol Super Admin a mi usuario
- [ ] Verifiqué asignación con query

## Pruebas SQL
- [ ] `CALL user_has_permission(1, 'view_patients')` → retorna 1
- [ ] `CALL get_user_permissions(1)` → muestra 58+ permisos
- [ ] Vista `user_effective_permissions` funciona

## Pruebas PHP
- [ ] `test-rbac-sistema.php` → 8/8 pruebas pasadas
- [ ] `rbac-example.php` → carga correctamente
- [ ] `test-protected.php` → permite acceso
- [ ] `test-denied.php` → redirige a error 403

---

# 🎉 ¡SISTEMA COMPLETAMENTE FUNCIONAL!

Si todas las pruebas pasaron, el sistema RBAC está **100% operativo**.

---

# 📊 RESUMEN DE LO QUE PROBAMOS

| Componente | Estado |
|------------|--------|
| ✅ Tablas (8) | Creadas |
| ✅ Roles (7) | Insertados |
| ✅ Permisos (58+) | Insertados |
| ✅ Vistas (6) | Creadas |
| ✅ Stored Procedures (5) | **REPARADOS** |
| ✅ Asignación de roles | Funciona |
| ✅ Funciones PHP | Funcionan |
| ✅ Middleware | Funciona |
| ✅ Demo interactiva | Funciona |

---

# 🚀 PRÓXIMOS PASOS

Ahora puedes:

1. ✅ **Usar el sistema RBAC** en tus páginas
2. ✅ **Asignar roles** a otros usuarios
3. ✅ **Proteger páginas** con `requirePermission()`
4. ✅ **Iniciar FASE 3**: ABM de Usuarios

---

**¿Dudas?** Revisa la documentación completa en `docs/RBAC_USAGE_GUIDE.md`

**¡Éxito! 🎉**
