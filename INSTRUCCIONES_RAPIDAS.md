# ⚡ INSTRUCCIONES RÁPIDAS - Instalación y Pruebas RBAC

## 🚀 PASO 1: INSTALAR MIGRACIONES (5 minutos)

### Opción A: phpMyAdmin (RECOMENDADO)

1. **Abre phpMyAdmin**: `http://localhost/phpmyadmin`
2. **Selecciona base de datos**: `hms_v2` (panel izquierdo)
3. **Haz clic en pestaña "SQL"**
4. **Ejecuta estos 3 archivos EN ORDEN:**

#### 📄 Archivo 1: Sistema RBAC
```
Ubicación: C:\xampp\htdocs\hospital\database\migrations\003_rbac_system.sql
```
- Abre el archivo en un editor de texto
- Copia TODO el contenido (Ctrl+A, Ctrl+C)
- Pega en phpMyAdmin
- Haz clic en "Continuar" o "Go"
- ✅ Espera mensaje de éxito

#### 📄 Archivo 2: Security Logs
```
Ubicación: C:\xampp\htdocs\hospital\database\migrations\004_security_logs.sql
```
- Repite el proceso anterior
- ✅ Espera mensaje de éxito

#### 📄 Archivo 3: Datos Iniciales
```
Ubicación: C:\xampp\htdocs\hospital\database\seeds\003_default_roles_permissions.sql
```
- Repite el proceso
- ✅ Espera mensaje de éxito

### ✅ Verificación Rápida

Ejecuta esto en phpMyAdmin:
```sql
SELECT COUNT(*) as roles FROM roles;
SELECT COUNT(*) as permisos FROM permissions;
```

**Debe mostrar:**
- roles: 7
- permisos: 60+

---

## 🎯 PASO 2: ASIGNAR ROL A TU USUARIO (1 minuto)

En phpMyAdmin, ejecuta:

```sql
-- Ver tus usuarios
SELECT id, email, full_name FROM users LIMIT 5;

-- Asignar Super Admin al usuario 1 (ajusta el ID según corresponda)
INSERT INTO user_roles (user_id, role_id, assigned_by, is_active)
VALUES (1, 1, 1, 1);

-- Verificar
SELECT u.email, r.display_name as rol
FROM users u
INNER JOIN user_roles ur ON u.id = ur.user_id
INNER JOIN roles r ON ur.role_id = r.id
WHERE u.id = 1;
```

**Debe mostrar:** Tu email con rol "Super Administrador"

---

## 🧪 PASO 3: PRUEBAS RÁPIDAS (10 minutos)

### Prueba 1: Demo Interactiva

**Abre en navegador:**
```
http://localhost/hospital/hms/admin/rbac-example.php
```

**Debe mostrar:**
- ✅ Tus datos de usuario
- ✅ Tus roles asignados
- ✅ Lista de tus permisos
- ✅ Tabla de roles del sistema

**Si sale error 403:** Verifica que tu usuario tenga el rol asignado (Paso 2)

---

### Prueba 2: Funciones PHP

**Crea archivo:** `C:\xampp\htdocs\hospital\hms\test-quick.php`

```php
<?php
session_start();
$_SESSION['id'] = 1; // Tu user ID

require_once('include/config.php');
require_once('include/rbac-functions.php');

echo "<pre>";
echo "TEST 1: hasPermission('view_patients'): ";
echo hasPermission('view_patients') ? "✅ PASS" : "❌ FAIL";

echo "\nTEST 2: hasRole('super_admin'): ";
echo hasRole('super_admin') ? "✅ PASS" : "❌ FAIL";

echo "\nTEST 3: isSuperAdmin(): ";
echo isSuperAdmin() ? "✅ PASS" : "❌ FAIL";

echo "\nTEST 4: Total permisos: " . count(getUserPermissions());

echo "\n\n✅ Si ves esto, el sistema RBAC funciona correctamente!";
echo "</pre>";
?>
```

**Abre en navegador:**
```
http://localhost/hospital/hms/test-quick.php
```

**Debe mostrar:**
```
TEST 1: hasPermission('view_patients'): ✅ PASS
TEST 2: hasRole('super_admin'): ✅ PASS
TEST 3: isSuperAdmin(): ✅ PASS
TEST 4: Total permisos: 60+

✅ Si ves esto, el sistema RBAC funciona correctamente!
```

---

### Prueba 3: Middleware de Protección

**Crea archivo:** `C:\xampp\htdocs\hospital\hms\test-protected.php`

```php
<?php
session_start();
$_SESSION['id'] = 1;

require_once('include/config.php');
require_once('include/permission-check.php');

requirePermission('view_patients');

echo "<h1>✅ Acceso Permitido</h1>";
echo "<p>El middleware funciona correctamente!</p>";
?>
```

**Abre en navegador:**
```
http://localhost/hospital/hms/test-protected.php
```

**Debe mostrar:** "✅ Acceso Permitido"

---

### Prueba 4: Página de Acceso Denegado

**Crea archivo:** `C:\xampp\htdocs\hospital\hms\test-denied.php`

```php
<?php
session_start();
$_SESSION['id'] = 1;

require_once('include/config.php');
require_once('include/permission-check.php');

requirePermission('permiso_falso_inexistente');

echo "No deberías ver esto";
?>
```

**Abre en navegador:**
```
http://localhost/hospital/hms/test-denied.php
```

**Debe redirigir a:** `access-denied.php` con mensaje de error 403

---

## ✅ CHECKLIST RÁPIDO

- [ ] Ejecuté las 3 migraciones SQL
- [ ] Verifiqué que hay 7 roles
- [ ] Verifiqué que hay 60+ permisos
- [ ] Asigné Super Admin a mi usuario
- [ ] Demo interactiva funciona
- [ ] test-quick.php muestra todos PASS
- [ ] test-protected.php permite acceso
- [ ] test-denied.php redirige a error 403

---

## 📚 DOCUMENTACIÓN COMPLETA

Si necesitas más detalles:

1. **Instalación detallada:** [INSTALACION_MANUAL_RBAC.md](INSTALACION_MANUAL_RBAC.md)
2. **Plan de pruebas completo (21 pruebas):** [PLAN_PRUEBAS_FASE2.md](PLAN_PRUEBAS_FASE2.md)
3. **Guía de uso:** [docs/RBAC_USAGE_GUIDE.md](docs/RBAC_USAGE_GUIDE.md)
4. **Resumen de FASE 2:** [FASE2_RBAC_COMPLETADO.md](FASE2_RBAC_COMPLETADO.md)

---

## 🆘 SOLUCIÓN DE PROBLEMAS

### Error: "Table already exists"
✅ **Normal** - Ignora el error, la tabla ya existe

### Error: "Access denied" en rbac-example.php
❌ **Solución:** Asigna el rol a tu usuario (Paso 2)

### Error: "Call to undefined function hasPermission()"
❌ **Solución:** Verifica que incluyes `rbac-functions.php`

### No aparecen permisos
❌ **Solución:** Ejecuta el seed `003_default_roles_permissions.sql`

### Página en blanco
❌ **Solución:** Activa `error_reporting` en PHP o revisa logs de Apache

---

## 🎉 ¡TODO LISTO!

Si todas las pruebas pasan, **¡FELICIDADES!** El sistema RBAC está 100% funcional.

### Próximos Pasos:

1. ✅ **FASE 1 COMPLETADA:** Políticas de Contraseñas
2. ✅ **FASE 2 COMPLETADA:** Sistema RBAC
3. 🔜 **FASE 3 PRÓXIMA:** ABM de Usuarios Completo

---

**¿Dudas?** Revisa los archivos de documentación listados arriba.

**¡Éxito! 🚀**
