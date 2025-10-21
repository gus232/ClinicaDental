# ⚡ EMPEZAR AQUÍ - Instalación de Stored Procedures

## 🎯 MÉTODO MÁS FÁCIL (2 minutos)

### **Opción A: Instalador PHP Automático** ⭐ RECOMENDADO

1. **Asegúrate de que Apache y MySQL estén corriendo en XAMPP**

2. **Abre en tu navegador:**
   ```
   http://localhost/hospital/database/instalar-sp.php
   ```

3. **Espera 5 segundos**

4. **Deberías ver:**
   ```
   ✅ ¡INSTALACIÓN EXITOSA!
   Los 5 stored procedures están instalados correctamente.
   ```

5. **¡LISTO!** Salta al **Paso 2: Asignar Roles** más abajo.

---

### **Opción B: Desde phpMyAdmin**

Si la Opción A no funciona, prueba esto:

1. **Abre phpMyAdmin:** `http://localhost/phpmyadmin`

2. **Selecciona** base de datos `hms_v2`

3. **Clic en pestaña "SQL"**

4. **Abre este archivo en un editor de texto:**
   ```
   C:\xampp\htdocs\hospital\database\stored-procedures\EJECUTAR_TODOS_LOS_SP.sql
   ```

5. **Copia TODO** el contenido (Ctrl+A, Ctrl+C)

6. **Pega en phpMyAdmin** y clic en "Continuar"

7. **Espera 5-10 segundos**

8. **Deberías ver:** Lista de 5 procedures creados

---

## ✅ VERIFICAR QUE FUNCIONÓ

En phpMyAdmin, ejecuta:

```sql
SELECT COUNT(*) as total
FROM information_schema.routines
WHERE routine_schema = 'hms_v2'
AND routine_type = 'PROCEDURE';
```

**Debe mostrar:** `total = 5` ✅

---

## 🎯 PASO 2: ASIGNAR ROL A TU USUARIO

En phpMyAdmin, ejecuta:

```sql
-- Ver tus usuarios
SELECT id, email, full_name FROM users LIMIT 5;
```

**Anota el ID de tu usuario** (ejemplo: 1)

Luego ejecuta (reemplaza `1` con tu user_id):

```sql
-- Asignar Super Admin al usuario 1
CALL assign_role_to_user(1, 1, 1, NULL);
```

**Debe mostrar:**
```
message: Rol asignado exitosamente
success: 1
```

✅ **¡Perfecto! Rol asignado.**

---

## 🧪 PASO 3: PROBAR EL SISTEMA

### Test Rápido (SQL):

```sql
-- Verificar que tienes el permiso
CALL user_has_permission(1, 'view_patients');
```

**Debe mostrar:** `has_permission = 1` ✅

---

### Test Completo (PHP):

Abre en navegador:
```
http://localhost/hospital/hms/test-rbac-sistema.php
```

**Debe mostrar:** `8/8 pruebas pasadas` ✅

---

### Demo Interactiva:

Abre en navegador:
```
http://localhost/hospital/hms/admin/rbac-example.php
```

**Debe mostrar:** Tu información, roles y permisos ✅

---

## ✅ CHECKLIST

- [ ] Ejecuté Opción A o B para instalar stored procedures
- [ ] Verifiqué que hay 5 procedures
- [ ] Asigné rol Super Admin con `CALL assign_role_to_user(...)`
- [ ] Probé con `CALL user_has_permission(...)`  → retorna 1
- [ ] Abrí `test-rbac-sistema.php` → 8/8 tests pasados
- [ ] Abrí `rbac-example.php` → muestra mi información

---

## 🆘 SI ALGO FALLA

### Error en Opción A (instalador PHP):
- Verifica que Apache esté corriendo
- Verifica que MySQL esté corriendo
- Revisa errores en la página

### Error en Opción B (phpMyAdmin):
- Lee el mensaje de error
- Si dice "DELIMITER", consulta: `SOLUCION_DEFINITIVA_SP.md`

### Error al asignar rol:
- Verifica que el stored procedure existe:
  ```sql
  SHOW PROCEDURE STATUS WHERE Db = 'hms_v2';
  ```

---

## 📚 DOCUMENTACIÓN COMPLETA

| Para... | Archivo |
|---------|---------|
| **Empezar** | Este archivo (EMPEZAR_AQUI.md) |
| **Problemas con SP** | SOLUCION_DEFINITIVA_SP.md |
| **Guía completa** | PASOS_COMPLETOS_INSTALACION_Y_PRUEBAS.md |
| **Aprender RBAC** | docs/RBAC_USAGE_GUIDE.md |

---

## 🎉 ¿TODO FUNCIONÓ?

Si completaste el checklist, **¡FELICIDADES!**

El sistema RBAC está **100% funcional** y listo para usar.

**Próximo paso:** Implementar RBAC en tus páginas PHP usando `requirePermission()`.

---

**¿Necesitas ayuda?** Abre `SOLUCION_DEFINITIVA_SP.md` para más opciones.
