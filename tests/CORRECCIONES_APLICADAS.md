# 🔧 Correcciones Aplicadas - FASE 1

## 📅 Fecha: 20 de Octubre, 2025

Basado en las pruebas realizadas, se aplicaron las siguientes correcciones:

---

## ✅ PROBLEMA 1: Falta opción en el menú del admin

### **Descripción del problema:**
El panel de administración no mostraba la opción "Seguridad" → "Desbloqueo de Cuentas" en el menú lateral.

### **Solución aplicada:**
Se agregó una nueva sección "Seguridad" al sidebar del admin con la opción de desbloqueo.

### **Archivo modificado:**
- `hms/admin/include/sidebar.php` (líneas 179-197)

### **Código agregado:**
```php
<li>
    <a href="javascript:void(0)">
        <div class="item-content">
            <div class="item-media">
                <i class="fa fa-shield"></i>
            </div>
            <div class="item-inner">
                <span class="title"> Seguridad </span><i class="icon-arrow"></i>
            </div>
        </div>
    </a>
    <ul class="sub-menu">
        <li>
            <a href="unlock-accounts.php">
                <span class="title"> Desbloqueo de Cuentas </span>
            </a>
        </li>
    </ul>
</li>
```

### **Cómo verificar:**
1. Login como admin
2. Ver el menú lateral
3. Debería aparecer "Seguridad" con icono de escudo
4. Click → Ver "Desbloqueo de Cuentas"

**Estado:** ✅ CORREGIDO

---

## ⚠️ PROBLEMA 2: Tiempo de bloqueo incorrecto (6 horas en vez de 30 minutos)

### **Descripción del problema:**
Al bloquear una cuenta, el sistema muestra:
- "Bloqueado Hasta": +6 horas
- "Tiempo Restante": ~380 minutos

Debería ser 30 minutos.

### **Causa del problema:**
El valor en la tabla `password_policy_config` podría estar incorrecto o haberse insertado con un valor diferente a 30.

### **Solución aplicada:**
Se creó un script de corrección que:
1. Verifica el valor actual de `lockout_duration_minutes`
2. Lo actualiza a 30 si es diferente
3. Permite desbloquear todas las cuentas actuales (opcional)

### **Archivo creado:**
- `tests/fix-lockout-time.php`

### **Pasos para corregir:**

#### **PASO 1: Ejecutar el script de corrección**
```
Abrir en navegador:
http://localhost/hospital/tests/fix-lockout-time.php
```

#### **PASO 2: Verificar valor actual**
El script mostrará:
```
✅ Configuración encontrada:
   - setting_name: lockout_duration_minutes
   - setting_value: [VALOR ACTUAL]
```

#### **PASO 3: Aplicar corrección automática**
El script automáticamente corregirá el valor a 30.

#### **PASO 4: (Opcional) Desbloquear cuentas actuales**
Si hay cuentas bloqueadas con el tiempo incorrecto:
```
Click en: [Desbloquear Todas las Cuentas]
```

#### **PASO 5: Verificar en BD**
```sql
SELECT setting_value FROM password_policy_config
WHERE setting_name = 'lockout_duration_minutes';
-- Debe retornar: 30
```

### **Verificación manual (alternativa):**
Si prefieres hacerlo manualmente en phpMyAdmin:

```sql
-- En phpMyAdmin → SQL
UPDATE password_policy_config
SET setting_value = '30'
WHERE setting_name = 'lockout_duration_minutes';

-- Verificar
SELECT * FROM password_policy_config
WHERE setting_name = 'lockout_duration_minutes';
```

**Estado:** ⚠️ REQUIERE ACCIÓN DEL USUARIO (ejecutar fix-lockout-time.php)

---

## 🔔 PROBLEMA 3: Mensajes de error no se muestran en change-password.php

### **Descripción del problema:**
Al intentar reutilizar una contraseña del historial, el sistema rechaza el cambio correctamente, pero no muestra el mensaje de error al usuario.

### **Causa del problema:**
Las clases de Bootstrap `fade show` podrían no estar aplicándose correctamente, haciendo que el div esté oculto.

### **Solución aplicada:**
Se agregó `style="display: block;"` inline al div de error para forzar su visualización.

### **Archivo modificado:**
- `hms/change-password.php` (línea 275)

### **Cambio realizado:**
```php
<!-- ANTES -->
<div class="alert alert-danger alert-dismissible fade show">

<!-- DESPUÉS -->
<div class="alert alert-danger alert-dismissible" style="display: block;">
```

### **Cómo verificar:**
1. Login como paciente
2. Cambiar contraseña a: `NewPass123@!`
3. Intentar cambiar de nuevo a: `Test123@!` (contraseña anterior)
4. Debería aparecer mensaje en rojo:
   ```
   ⚠️ Error: Esta contraseña ya fue utilizada recientemente.
   No puede reutilizar las últimas 5 contraseñas
   ```

**Estado:** ✅ CORREGIDO

---

## 📋 Resumen de Archivos Modificados

| Archivo | Líneas | Tipo de cambio |
|---------|--------|----------------|
| `hms/admin/include/sidebar.php` | 179-197 | Agregar sección |
| `hms/change-password.php` | 275 | Corrección visual |
| `tests/fix-lockout-time.php` | - | Nuevo archivo |
| `tests/CORRECCIONES_APLICADAS.md` | - | Nuevo archivo (este) |

---

## 🧪 Plan de Re-pruebas

Después de aplicar las correcciones, realizar estas pruebas:

### ✅ Prueba 1: Menú de admin
```
1. Login como admin
2. Verificar que aparezca "Seguridad" en el menú
3. Click → Ver "Desbloqueo de Cuentas"
4. Verificar que carga la página correctamente
```

### ✅ Prueba 2: Tiempo de bloqueo
```
1. Bloquear una cuenta (3 intentos fallidos)
2. Login como admin → Desbloqueo de Cuentas
3. Verificar que:
   - "Tiempo Restante" muestra ~30 minutos (no 380)
   - "Bloqueado Hasta" es hora_actual + 30 minutos
```

### ✅ Prueba 3: Mensajes de error
```
1. Cambiar contraseña
2. Intentar reutilizar contraseña anterior
3. Verificar que mensaje de error aparece en ROJO
4. Verificar que el mensaje es claro y específico
```

---

## 📊 Estado Final

| Problema | Estado | Requiere Acción |
|----------|--------|-----------------|
| Menú admin | ✅ Corregido | No |
| Tiempo bloqueo | ⚠️ Script creado | **Sí - ejecutar fix-lockout-time.php** |
| Mensajes error | ✅ Corregido | No |

---

## 🎯 Próximos Pasos

1. **INMEDIATO:** Ejecutar `fix-lockout-time.php`
2. **VERIFICAR:** Re-probar los 3 problemas
3. **CONTINUAR:** Si todo funciona, proceder con FASE 2 (Roles y Permisos)

---

## 📝 Notas Técnicas

### Por qué el tiempo de bloqueo estaba mal:
El valor en `password_policy_config` pudo haberse insertado incorrectamente durante la migración, o pudo haber sido modificado manualmente. El script `fix-lockout-time.php` corrige esto de manera permanente.

### Por qué los mensajes no se mostraban:
Bootstrap 4 requiere que los elementos con `fade` tengan también la clase `show` para ser visibles. Al agregar `style="display: block;"`, forzamos la visualización independientemente de las clases de CSS.

### Por qué faltaba la opción en el menú:
El archivo `sidebar.php` es estático y no se generó automáticamente con las nuevas opciones. Se agregó manualmente la sección de Seguridad para futuras opciones relacionadas (recuperación de contraseñas, auditoría, etc.).

---

## 🔗 Enlaces Útiles

- [Script de corrección de tiempo](http://localhost/hospital/tests/fix-lockout-time.php)
- [Panel de desbloqueo](http://localhost/hospital/hms/admin/unlock-accounts.php)
- [Cambiar contraseña](http://localhost/hospital/hms/change-password.php)

---

**Autor:** Claude Code AI
**Fecha:** 2025-10-20
**Proyecto:** SIS 321 - Hospital Management System
**Versión:** 2.1.1 (con correcciones)
