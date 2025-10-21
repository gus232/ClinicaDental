# 🧪 PRUEBAS DEL SISTEMA RBAC - DESDE CERO

## 📊 ESTADO ACTUAL

✅ **Lo que YA tienes:**
- Base de datos `hms_v2` creada
- Tablas RBAC creadas (roles, permissions, etc.)
- 7 roles insertados
- 58 permisos insertados
- 5 stored procedures instalados

⏳ **Lo que FALTA hacer:**
- Asignar un rol a tu usuario
- Probar que el sistema funciona

---

## 🎯 ACLARACIÓN IMPORTANTE

Hay **2 tipos de pruebas** que puedes hacer:

### **Tipo 1: Pruebas en phpMyAdmin (SQL directo)** 🗄️
- Ejecutas comandos SQL directamente
- Usas los stored procedures con `CALL`
- Verificas datos con `SELECT`

### **Tipo 2: Pruebas en PHP (navegador)** 🌐
- Abres páginas `.php` en el navegador
- Usas las funciones PHP como `hasPermission()`
- Ves resultados en pantalla con interfaz bonita

---

## ✅ VAMOS A HACER AMBAS

---

# 🗄️ PARTE 1: PRUEBAS EN phpMyAdmin (SQL)

## **Paso 1.1: Verificar que tienes roles y permisos**

**Abre phpMyAdmin** → Base de datos `hms_v2` → Pestaña "SQL"

**Ejecuta estos 3 comandos UNO POR UNO:**

### Comando 1: Ver roles disponibles
```sql
SELECT id, role_name, display_name, priority
FROM roles
ORDER BY priority;
```

**✅ Debe mostrar 7 roles:**
```
1  super_admin        Super Administrador      1
2  admin              Administrador           10
3  doctor             Doctor                  20
4  patient            Paciente                40
5  receptionist       Recepcionista           30
6  nurse              Enfermera               25
7  lab_technician     Técnico de Laboratorio  35
```

---

### Comando 2: Ver cuántos permisos hay
```sql
SELECT COUNT(*) as total_permisos
FROM permissions;
```

**✅ Debe mostrar:**
```
total_permisos: 58 (o similar)
```

---

### Comando 3: Ver stored procedures
```sql
SELECT routine_name
FROM information_schema.routines
WHERE routine_schema = 'hms_v2'
AND routine_type = 'PROCEDURE'
ORDER BY routine_name;
```

**✅ Debe mostrar 5 procedures:**
```
assign_role_to_user
cleanup_old_security_data
get_user_permissions
revoke_role_from_user
user_has_permission
```

---

## **Paso 1.2: Asignar rol Super Admin a tu usuario**

Tu usuario es **ID 8** (admin@hospital.com)

**Ejecuta en phpMyAdmin:**

```sql
-- Asignar Super Admin (rol_id = 1) al usuario 8
INSERT INTO user_roles (user_id, role_id, assigned_by, is_active)
VALUES (8, 1, 8, 1)
ON DUPLICATE KEY UPDATE is_active = 1;
```

**✅ Debe mostrar:**
```
1 row inserted
```
o
```
1 row affected
```

---

## **Paso 1.3: Verificar que el rol se asignó correctamente**

**Ejecuta:**

```sql
SELECT
    u.id,
    u.email,
    r.role_name,
    r.display_name as rol,
    ur.is_active,
    ur.assigned_at
FROM users u
INNER JOIN user_roles ur ON u.id = ur.user_id
INNER JOIN roles r ON ur.role_id = r.id
WHERE u.id = 8;
```

**✅ Debe mostrar:**
```
id: 8
email: admin@hospital.com
role_name: super_admin
rol: Super Administrador
is_active: 1
assigned_at: 2025-10-21 (fecha actual)
```

**❌ Si NO muestra nada:** El INSERT falló, repite el Paso 1.2

---

## **Paso 1.4: Verificar permisos efectivos del usuario**

**Ejecuta:**

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

## **Paso 1.5: Ver algunos permisos del usuario**

**Ejecuta:**

```sql
SELECT
    permission_name,
    module,
    role_name
FROM user_effective_permissions
WHERE user_id = 8
ORDER BY module, permission_name
LIMIT 10;
```

**✅ Debe mostrar 10 permisos como:**
```
view_appointments    appointments    super_admin
create_appointment   appointments    super_admin
view_patients        patients        super_admin
create_patient       patients        super_admin
...
```

---

## **Paso 1.6: Probar Stored Procedure con CALL**

Ahora SÍ, vamos a **usar los stored procedures**:

### Prueba A: Verificar si tiene un permiso específico

**Ejecuta:**

```sql
CALL user_has_permission(8, 'view_patients');
```

**✅ Debe mostrar:**
```
has_permission: 1
```

---

### Prueba B: Obtener todos los permisos del usuario

**Ejecuta:**

```sql
CALL get_user_permissions(8);
```

**✅ Debe mostrar:** Una tabla con 58+ filas de permisos

---

## **Paso 1.7: Usar la interfaz de phpMyAdmin para ejecutar SP**

**También puedes hacerlo así (lo que intentaste antes):**

1. En phpMyAdmin, menú izquierdo → Clic en **"Routines"**
2. Busca `user_has_permission`
3. Clic en **"Execute"**
4. **Llena los campos:**
   - `p_user_id`: **8**
   - `p_permission_name`: **view_patients**
5. Clic **"Go"**

**✅ Debe mostrar:**
```
has_permission: 1
```

---

# 🌐 PARTE 2: PRUEBAS EN PHP (Navegador)

## **Paso 2.1: Editar archivo de pruebas**

**Abre en tu editor de código:**
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

## **Paso 2.2: Ejecutar pruebas en navegador**

**Abre en tu navegador:**
```
http://localhost/hospital/hms/test-rbac-sistema.php
```

**✅ Debe mostrar una página con:**

```
🧪 PRUEBAS DEL SISTEMA RBAC

TEST 1: hasPermission("view_patients")
Resultado: ✅ PASS (TRUE)
Esperado: ✅ TRUE

TEST 2: hasRole("super_admin")
Resultado: ✅ PASS (TRUE)
Esperado: ✅ TRUE

TEST 3: isSuperAdmin()
Resultado: ✅ PASS (TRUE)
Esperado: ✅ TRUE

TEST 4: isAdmin()
Resultado: ✅ PASS (TRUE)
Esperado: ✅ TRUE

TEST 5: getUserPermissions()
Total de permisos: 58
Resultado: ✅ PASS
Esperado: >= 58 permisos

TEST 6: getUserRoles()
Total de roles: 1
Roles asignados:
  • Super Administrador (prioridad: 1)
Resultado: ✅ PASS

TEST 7: Clase RBAC - getRoleInfo(1)
Rol ID 1: Super Administrador
Resultado: ✅ PASS

TEST 8: getRolePermissions(1) - Super Admin
Total de permisos del rol: 58
Resultado: ✅ PASS

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📊 RESUMEN DE PRUEBAS
Pruebas pasadas: 8 / 8 (100%)

✅ ¡TODAS LAS PRUEBAS PASARON!
El sistema RBAC está funcionando correctamente.
```

---

## **Paso 2.3: Probar demo interactiva**

**Abre en navegador:**
```
http://localhost/hospital/hms/admin/rbac-example.php
```

**✅ Debe mostrar:**
- 👤 Tu información (admin@hospital.com)
- 🎯 Tus roles (Super Administrador)
- 🔑 Lista de tus 58+ permisos agrupados por módulo
- 📋 Tabla de todos los roles del sistema
- 💻 Ejemplos de código

---

# 📋 RESUMEN COMPLETO

## PARTE 1: phpMyAdmin (SQL)
1. ✅ Ver roles → 7 roles
2. ✅ Ver permisos → 58 permisos
3. ✅ Ver stored procedures → 5 procedures
4. ✅ Asignar rol con INSERT
5. ✅ Verificar asignación con SELECT
6. ✅ Ver permisos efectivos con SELECT
7. ✅ Probar SP con `CALL user_has_permission(8, 'view_patients')`
8. ✅ Probar SP con `CALL get_user_permissions(8)`

## PARTE 2: Navegador (PHP)
1. ✅ Editar test-rbac-sistema.php → cambiar user_id a 8
2. ✅ Abrir test-rbac-sistema.php → 8/8 tests pasados
3. ✅ Abrir rbac-example.php → ver información completa

---

# ✅ CHECKLIST FINAL

**Marca cada paso que completes:**

## phpMyAdmin:
- [ ] Ejecuté `SELECT` para ver 7 roles
- [ ] Ejecuté `SELECT` para ver 58 permisos
- [ ] Ejecuté `SELECT` para ver 5 stored procedures
- [ ] Ejecuté `INSERT INTO user_roles...` para asignar rol
- [ ] Verifiqué con `SELECT` que muestra "Super Administrador"
- [ ] Ejecuté `SELECT COUNT(*)` y muestra 58+ permisos
- [ ] Ejecuté `CALL user_has_permission(8, 'view_patients')` → retorna 1
- [ ] Ejecuté `CALL get_user_permissions(8)` → muestra lista de permisos

## Navegador:
- [ ] Edité test-rbac-sistema.php línea 11 → `$_SESSION['id'] = 8;`
- [ ] Abrí test-rbac-sistema.php → 8/8 tests pasados
- [ ] Abrí rbac-example.php → muestra mi información

---

# 🎯 DIFERENCIA CLAVE

## ❌ ANTES (Confusión):
"¿Debo usar CALL o SELECT? ¿phpMyAdmin o navegador?"

## ✅ AHORA (Claro):

**Puedes hacer AMBOS:**

1. **En phpMyAdmin:** Usas `CALL` para ejecutar stored procedures
   ```sql
   CALL user_has_permission(8, 'view_patients');
   ```

2. **En PHP/navegador:** Usas funciones PHP que internamente usan esos SP
   ```php
   hasPermission('view_patients'); // Llama al SP por ti
   ```

**Ambos hacen lo mismo, pero:**
- phpMyAdmin = Pruebas técnicas de BD
- Navegador = Pruebas de aplicación real

---

# 🚀 EMPIEZA AQUÍ

**Paso 1:** Ve a phpMyAdmin y ejecuta el Paso 1.2 (el INSERT)

**Paso 2:** Verifica con el Paso 1.3 (el SELECT)

**Paso 3:** Ejecuta el Paso 1.6 Prueba A (CALL user_has_permission)

**Si esos 3 funcionan, todo lo demás funcionará.**

---

**¿Listo? Empieza con el Paso 1.2** ⬆️
