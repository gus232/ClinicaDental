# 🧪 GUÍA SIMPLE DE PRUEBAS - SISTEMA RBAC

## ✅ ESTADO ACTUAL
- ✅ Tablas creadas
- ✅ Roles y permisos insertados
- ✅ Stored procedures instalados
- ⏳ **FALTA:** Asignar rol y probar

---

## 🎯 LO QUE TIENES QUE HACER (6 PASOS)

### **PASO 1: Asignar Rol Super Admin al Usuario 8**

**Abre phpMyAdmin** → Base de datos `hms_v2` → Pestaña "SQL"

**Copia y ejecuta esto:**

```sql
-- Asignar Super Admin al usuario 8 (admin@hospital.com)
INSERT INTO user_roles (user_id, role_id, assigned_by, is_active)
VALUES (8, 1, 8, 1)
ON DUPLICATE KEY UPDATE is_active = 1;
```

**Presiona:** "Continuar"

**✅ Éxito si ves:** "1 row inserted" o "1 row affected"

---

### **PASO 2: Verificar que el Rol Está Asignado**

**Ejecuta esto en phpMyAdmin:**

```sql
SELECT
    u.id,
    u.email,
    r.display_name as rol,
    ur.is_active
FROM users u
INNER JOIN user_roles ur ON u.id = ur.user_id
INNER JOIN roles r ON ur.role_id = r.id
WHERE u.id = 8;
```

**✅ Debe mostrar:**
```
id: 8
email: admin@hospital.com
rol: Super Administrador
is_active: 1
```

**Si NO muestra nada:** Vuelve al Paso 1

---

### **PASO 3: Probar Stored Procedure (phpMyAdmin)**

**En phpMyAdmin:**

1. Menú izquierdo → Clic en **"Routines"**
2. Busca `user_has_permission`
3. Clic en **"Execute"**
4. **Llena los campos:**
   - `p_user_id`: **8**
   - `p_permission_name`: **view_patients**
5. Clic en **"Go"**

**✅ Debe mostrar:**
```
has_permission: 1
```

**Si muestra 0:** El rol no está bien asignado, vuelve al Paso 1

---

### **PASO 4: Ver Cuántos Permisos Tienes**

**Ejecuta en phpMyAdmin:**

```sql
SELECT COUNT(*) as total_permisos
FROM user_effective_permissions
WHERE user_id = 8;
```

**✅ Debe mostrar:**
```
total_permisos: 58 (o más)
```

---

### **PASO 5: Editar Archivo de Pruebas PHP**

**Abre en tu editor:**
```
C:\xampp\htdocs\hospital\hms\test-rbac-sistema.php
```

**Busca la línea 11:**
```php
$_SESSION['id'] = 1; // CAMBIA por tu user_id
```

**Cámbiala a:**
```php
$_SESSION['id'] = 8; // Usuario admin@hospital.com
```

**Guarda el archivo** (Ctrl+S)

---

### **PASO 6: Ejecutar Pruebas en Navegador**

**Abre en tu navegador:**
```
http://localhost/hospital/hms/test-rbac-sistema.php
```

**✅ Debe mostrar:**
```
TEST 1: hasPermission("view_patients")
Resultado: ✅ PASS (TRUE)

TEST 2: hasRole("super_admin")
Resultado: ✅ PASS (TRUE)

TEST 3: isSuperAdmin()
Resultado: ✅ PASS (TRUE)

TEST 4: isAdmin()
Resultado: ✅ PASS (TRUE)

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

📊 RESUMEN DE PRUEBAS
Pruebas pasadas: 8 / 8 (100%)

✅ ¡TODAS LAS PRUEBAS PASARON!
El sistema RBAC está funcionando correctamente.
```

---

## 🎉 PRUEBA EXTRA: Demo Interactiva

**Abre en navegador:**
```
http://localhost/hospital/hms/admin/rbac-example.php
```

**✅ Debe mostrar:**
- Tu información (admin@hospital.com)
- Tus roles (Super Administrador)
- Lista de 58+ permisos agrupados por módulo
- Tabla de todos los roles del sistema
- Ejemplos de código

---

## 📋 RESUMEN DE COMANDOS SQL (Copia/Pega)

```sql
-- 1. ASIGNAR ROL
INSERT INTO user_roles (user_id, role_id, assigned_by, is_active)
VALUES (8, 1, 8, 1)
ON DUPLICATE KEY UPDATE is_active = 1;

-- 2. VERIFICAR ROL
SELECT u.email, r.display_name as rol
FROM users u
INNER JOIN user_roles ur ON u.id = ur.user_id
INNER JOIN roles r ON ur.role_id = r.id
WHERE u.id = 8;

-- 3. CONTAR PERMISOS
SELECT COUNT(*) as total_permisos
FROM user_effective_permissions
WHERE user_id = 8;

-- 4. VER PRIMEROS 10 PERMISOS
SELECT permission_name, module
FROM user_effective_permissions
WHERE user_id = 8
ORDER BY module, permission_name
LIMIT 10;
```

---

## ✅ CHECKLIST (Marca lo que completes)

- [ ] **PASO 1:** Ejecuté INSERT INTO user_roles
- [ ] **PASO 2:** Verifiqué que muestra "Super Administrador"
- [ ] **PASO 3:** Probé SP user_has_permission → retorna 1
- [ ] **PASO 4:** Verifiqué 58+ permisos
- [ ] **PASO 5:** Edité test-rbac-sistema.php → user_id = 8
- [ ] **PASO 6:** test-rbac-sistema.php → 8/8 tests pasados
- [ ] **EXTRA:** rbac-example.php muestra mi info

---

## 🆘 SI ALGO FALLA

### Error en Paso 1:
```
Vuelve a ejecutar:
TRUNCATE TABLE user_roles;
INSERT INTO user_roles (user_id, role_id, assigned_by, is_active)
VALUES (8, 1, 8, 1);
```

### Error en Paso 3 (retorna 0):
```
Verifica que el rol está asignado con el query del Paso 2
```

### Error en Paso 6 (página en blanco):
```
Verifica que Apache está corriendo
Abre: http://localhost/hospital/hms/
```

### Error: "Call to undefined function hasPermission"
```
Verifica que test-rbac-sistema.php tiene esta línea:
require_once('include/rbac-functions.php');
```

---

## 🎯 ORDEN CORRECTO

1. ✅ phpMyAdmin → Ejecutar INSERT
2. ✅ phpMyAdmin → Verificar con SELECT
3. ✅ phpMyAdmin → Probar SP user_has_permission
4. ✅ phpMyAdmin → Contar permisos
5. ✅ Editor → Editar test-rbac-sistema.php
6. ✅ Navegador → Abrir test-rbac-sistema.php
7. ✅ Navegador → Abrir rbac-example.php

---

**EMPIEZA CON EL PASO 1 Y VE UNO POR UNO** ⬆️

No te saltes pasos. Si uno falla, avísame cuál y te ayudo.
