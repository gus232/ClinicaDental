# 🔧 REPARAR INSTALACIÓN: Stored Procedures Faltantes

## 🚨 Problema Detectado

Tienes **solo 1 stored procedure** cuando deberían ser **5**.

Esto ocurre porque los `DELIMITER` en SQL causan problemas al ejecutarse desde phpMyAdmin de una sola vez.

---

## ✅ SOLUCIÓN: Ejecutar Archivos Individuales

### **Paso 1: Verificar el Problema**

En phpMyAdmin, ejecuta:

```sql
SELECT routine_name
FROM information_schema.routines
WHERE routine_schema = 'hms_v2'
AND routine_type = 'PROCEDURE';
```

**Deberías ver:**
- `assign_role_to_user`
- `revoke_role_from_user`
- `user_has_permission`
- `get_user_permissions`
- `cleanup_old_security_data`

Si faltan algunos, continúa con el Paso 2.

---

### **Paso 2: Ejecutar Stored Procedures Individuales**

**IMPORTANTE:** Ejecuta estos archivos **UNO POR UNO** en phpMyAdmin.

#### 📄 Procedure 1: assign_role_to_user

1. Abre: `C:\xampp\htdocs\hospital\database\stored-procedures\01_assign_role_to_user.sql`
2. Copia TODO el contenido
3. Pega en phpMyAdmin (pestaña SQL)
4. Haz clic en **"Continuar"**
5. ✅ Verifica que diga "Procedure creado"

#### 📄 Procedure 2: revoke_role_from_user

1. Abre: `C:\xampp\htdocs\hospital\database\stored-procedures\02_revoke_role_from_user.sql`
2. Copia TODO el contenido
3. Pega en phpMyAdmin
4. Haz clic en **"Continuar"**
5. ✅ Verifica éxito

#### 📄 Procedure 3: user_has_permission

1. Abre: `C:\xampp\htdocs\hospital\database\stored-procedures\03_user_has_permission.sql`
2. Copia y pega
3. Ejecuta
4. ✅ Verifica

#### 📄 Procedure 4: get_user_permissions

1. Abre: `C:\xampp\htdocs\hospital\database\stored-procedures\04_get_user_permissions.sql`
2. Copia y pega
3. Ejecuta
4. ✅ Verifica

#### 📄 Procedure 5: cleanup_old_security_data

1. Abre: `C:\xampp\htdocs\hospital\database\stored-procedures\05_cleanup_old_security_data.sql`
2. Copia y pega
3. Ejecuta
4. ✅ Verifica

---

### **Paso 3: Verificar Instalación Completa**

En phpMyAdmin, ejecuta:

```sql
SELECT COUNT(*) as total
FROM information_schema.routines
WHERE routine_schema = 'hms_v2'
AND routine_type = 'PROCEDURE';
```

**Debe mostrar:** `total = 5` ✅

---

## 🎯 Ahora SÍ Puedes Asignar Roles

Una vez que los 5 procedures estén instalados, ejecuta:

```sql
-- Ver tus usuarios
SELECT id, email, full_name FROM users LIMIT 5;

-- Asignar Super Admin al usuario 1
CALL assign_role_to_user(1, 1, 1, NULL);

-- Verificar asignación
SELECT
    u.email,
    r.display_name as rol
FROM users u
INNER JOIN user_roles ur ON u.id = ur.user_id
INNER JOIN roles r ON ur.role_id = r.id
WHERE u.id = 1;
```

**Debe mostrar:** Tu email con rol "Super Administrador"

---

## 📊 Script de Verificación Completa

Si quieres ver un reporte completo de la instalación, ejecuta:

```sql
SOURCE C:/xampp/htdocs/hospital/database/REPARAR_INSTALACION.sql
```

O copia y pega el contenido del archivo en phpMyAdmin.

---

## ✅ Checklist de Reparación

- [ ] Ejecuté `01_assign_role_to_user.sql`
- [ ] Ejecuté `02_revoke_role_from_user.sql`
- [ ] Ejecuté `03_user_has_permission.sql`
- [ ] Ejecuté `04_get_user_permissions.sql`
- [ ] Ejecuté `05_cleanup_old_security_data.sql`
- [ ] Verifiqué que hay 5 procedures
- [ ] Asigné rol con `CALL assign_role_to_user(...)`
- [ ] Verifiqué asignación exitosa

---

## 🆘 Solución de Problemas

### Error: "Procedure already exists"
✅ **Normal** - El procedure ya está creado. Continúa con el siguiente.

### Error: "Column 'category_id' not found"
❌ **Ejecuta primero:**
```sql
ALTER TABLE permissions
ADD COLUMN category_id INT NULL AFTER module;
```

### Error: "Table 'permission_categories' doesn't exist"
❌ **Ejecuta primero:**
```sql
CREATE TABLE IF NOT EXISTS permission_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) NULL,
    sort_order INT DEFAULT 0,
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

**¡Éxito! 🚀**
