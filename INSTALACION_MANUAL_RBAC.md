# 📋 Guía de Instalación Manual - Sistema RBAC

## ⚠️ IMPORTANTE
Los stored procedures tienen problemas al ejecutarse via PHP. La mejor forma es ejecutar las migraciones desde **phpMyAdmin**.

---

## 🚀 Pasos de Instalación

### **Paso 1: Abrir phpMyAdmin**

1. Abre tu navegador
2. Ve a: `http://localhost/phpmyadmin`
3. Login con usuario `root` (sin contraseña)

---

### **Paso 2: Seleccionar Base de Datos**

1. En el panel izquierdo, haz clic en `hms_v2`
2. Haz clic en la pestaña **"SQL"** en la parte superior

---

### **Paso 3: Ejecutar Migración 1 - Sistema RBAC**

1. Abre el archivo: `C:\xampp\htdocs\hospital\database\migrations\003_rbac_system.sql`
2. Copia TODO el contenido del archivo
3. Pégalo en el cuadro de texto de phpMyAdmin
4. Haz clic en el botón **"Continuar"** o **"Go"**
5. Espera a que termine (puede tardar 10-15 segundos)

**Resultado esperado:**
```
✓ Migración 003_rbac_system.sql ejecutada exitosamente
✓ Campos agregados a tabla users: 6 columnas nuevas
✓ Tablas creadas: roles, permissions, role_permissions, user_roles, permission_categories, role_hierarchy, audit_role_changes
✓ Vistas creadas: user_effective_permissions, user_roles_summary, role_permission_matrix, expiring_user_roles
✓ Procedures creados: assign_role_to_user, revoke_role_from_user, user_has_permission, get_user_permissions
✓ Sistema listo para implementar políticas de contraseñas
```

---

### **Paso 4: Ejecutar Migración 2 - Security Logs**

1. Limpia el cuadro de texto de phpMyAdmin
2. Abre el archivo: `C:\xampp\htdocs\hospital\database\migrations\004_security_logs.sql`
3. Copia TODO el contenido
4. Pégalo en phpMyAdmin
5. Haz clic en **"Continuar"**

**Resultado esperado:**
```
✓ Migración 004_security_logs.sql ejecutada exitosamente
```

---

### **Paso 5: Ejecutar Seed - Datos Iniciales**

1. Limpia el cuadro de texto
2. Abre el archivo: `C:\xampp\htdocs\hospital\database\seeds\003_default_roles_permissions.sql`
3. Copia TODO el contenido
4. Pégalo en phpMyAdmin
5. Haz clic en **"Continuar"**

**Resultado esperado:**
```
✓ Seed 003_default_roles_permissions.sql ejecutado exitosamente
✓ Roles creados: 7 roles del sistema
✓ Permisos creados: 60+ permisos granulares
✓ Asignaciones creadas: 200+ permisos asignados a roles
✓ Sistema RBAC completamente configurado
```

---

## ✅ Verificación de Instalación

### **1. Verificar Tablas Creadas**

En phpMyAdmin, ejecuta:

```sql
SELECT COUNT(*) as total_tablas
FROM information_schema.tables
WHERE table_schema = 'hms_v2'
AND table_name IN (
    'roles',
    'permissions',
    'role_permissions',
    'user_roles',
    'permission_categories',
    'role_hierarchy',
    'audit_role_changes',
    'security_logs'
);
```

**Resultado esperado:** `total_tablas = 8`

---

### **2. Verificar Roles Creados**

```sql
SELECT * FROM roles ORDER BY priority;
```

**Resultado esperado:** 7 roles

| id | role_name | display_name | priority |
|----|-----------|--------------|----------|
| 1 | super_admin | Super Administrador | 1 |
| 2 | admin | Administrador | 10 |
| 3 | doctor | Doctor | 20 |
| 4 | patient | Paciente | 40 |
| 5 | receptionist | Recepcionista | 30 |
| 6 | nurse | Enfermera | 25 |
| 7 | lab_technician | Técnico de Laboratorio | 35 |

---

### **3. Verificar Permisos Creados**

```sql
SELECT COUNT(*) as total_permisos FROM permissions;
```

**Resultado esperado:** `total_permisos > 60`

---

### **4. Verificar Asignaciones**

```sql
SELECT
    r.display_name AS Rol,
    COUNT(rp.permission_id) AS Total_Permisos
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.display_name
ORDER BY r.priority;
```

**Resultado esperado:**

| Rol | Total_Permisos |
|-----|----------------|
| Super Administrador | 60+ |
| Administrador | 55+ |
| Doctor | 25+ |
| Recepcionista | 20+ |
| Enfermera | 15+ |
| Paciente | 8+ |
| Técnico de Laboratorio | 10+ |

---

### **5. Verificar Vistas**

```sql
SELECT table_name
FROM information_schema.views
WHERE table_schema = 'hms_v2'
AND table_name LIKE '%permission%' OR table_name LIKE '%role%'
ORDER BY table_name;
```

**Resultado esperado:** 6 vistas

---

### **6. Verificar Stored Procedures**

```sql
SELECT routine_name
FROM information_schema.routines
WHERE routine_schema = 'hms_v2'
AND routine_type = 'PROCEDURE';
```

**Resultado esperado:** 5 procedures

---

## 🎯 Asignar Rol a un Usuario

Una vez instalado, asigna el rol de Super Admin al usuario con ID 1:

```sql
-- Asignar Super Admin al usuario 1
INSERT INTO user_roles (user_id, role_id, assigned_by, is_active)
VALUES (1, 1, 1, 1);

-- Verificar asignación
SELECT
    u.id,
    u.email,
    u.full_name,
    r.display_name AS rol
FROM users u
INNER JOIN user_roles ur ON u.id = ur.user_id
INNER JOIN roles r ON ur.role_id = r.id
WHERE u.id = 1;
```

---

## 🧪 Probar el Sistema

### **1. Ver Permisos de un Usuario**

```sql
-- Ver todos los permisos del usuario 1
SELECT
    permission_name,
    module,
    role_name
FROM user_effective_permissions
WHERE user_id = 1
ORDER BY module, permission_name;
```

---

### **2. Probar Función de Verificación**

```sql
-- Verificar si usuario 1 tiene permiso 'view_patients'
CALL user_has_permission(1, 'view_patients');
```

**Resultado esperado:** `has_permission = 1` (true)

---

### **3. Obtener Todos los Permisos**

```sql
-- Obtener permisos del usuario 1
CALL get_user_permissions(1);
```

---

## 🌐 Probar en la Interfaz Web

1. Asegúrate de tener un usuario en sesión
2. Abre: `http://localhost/hospital/hms/admin/rbac-example.php`
3. Deberías ver:
   - Tus datos de usuario
   - Tus roles asignados
   - Tus permisos efectivos
   - Ejemplos de verificación
   - Matriz de roles y permisos

---

## 🚨 Solución de Problemas

### **Error: "Table already exists"**
✅ **Normal** - Significa que la tabla ya está creada. Puedes ignorar este error.

### **Error: "Duplicate entry"**
✅ **Normal** - Los datos ya existen. Puedes ignorar.

### **Error: Syntax error near 'DELIMITER'**
❌ **Solución**: Ejecutar desde phpMyAdmin, no desde terminal/PHP

### **No aparece ninguna tabla**
❌ **Solución**: Verifica que estés en la base de datos correcta (`hms_v2`)

---

## 📚 Siguiente Paso

Una vez completada la instalación, sigue el plan de pruebas:

👉 **Abre:** `PLAN_PRUEBAS_FASE2.md`

---

## ✅ Checklist de Instalación

- [ ] Ejecutar `003_rbac_system.sql`
- [ ] Ejecutar `004_security_logs.sql`
- [ ] Ejecutar `003_default_roles_permissions.sql`
- [ ] Verificar 8 tablas creadas
- [ ] Verificar 7 roles creados
- [ ] Verificar 60+ permisos creados
- [ ] Verificar 200+ asignaciones
- [ ] Verificar 6 vistas creadas
- [ ] Verificar 5 stored procedures
- [ ] Asignar rol Super Admin a usuario 1
- [ ] Probar en navegador: rbac-example.php

---

**¡Buena suerte! 🚀**
