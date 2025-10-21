# 🚨 Solución de Problemas Críticos

## ⚠️ Problemas Detectados en las Pruebas

### **PROBLEMA 1: Tiempo de bloqueo sigue siendo 6 horas** ❌
### **PROBLEMA 2: Historial de contraseñas no funciona** ❌

---

## 🔍 **PASO 1: DIAGNÓSTICO**

He creado 2 scripts de debug para identificar exactamente qué está mal:

### **Script 1: Debug del Sistema de Bloqueo**
```
Abre en tu navegador:
http://localhost/hospital/tests/debug-lockout.php
```

**Este script te mostrará:**
- ✅ Configuración actual en `password_policy_config`
- ✅ Cuentas bloqueadas y tiempo restante REAL
- ✅ Simulación de bloqueo para verificar cálculos
- ✅ Botones para corregir automáticamente

**Acciones disponibles:**
1. Botón: "Actualizar lockout_duration_minutes a 30"
2. Botón: "Desbloquear Todas las Cuentas"

---

### **Script 2: Debug del Historial de Contraseñas**
```
Abre en tu navegador:
http://localhost/hospital/tests/debug-password-history.php
```

**Este script te mostrará:**
- ✅ Si hay contraseñas guardadas en `password_history`
- ✅ Prueba en vivo: verificar si una contraseña está en historial
- ✅ Verificar que change-password.php incluye el código correcto
- ✅ Botón para forzar guardado de prueba

---

## 🔧 **PASO 2: APLICAR CORRECCIONES**

### **CORRECCIÓN A: Tiempo de Bloqueo**

#### Opción 1: Usar el script de debug
```
1. Ir a: http://localhost/hospital/tests/debug-lockout.php
2. Scroll hasta "Acciones de Corrección"
3. Click en: [Actualizar lockout_duration_minutes a 30]
4. Verificar que dice: ✅ Configuración actualizada a 30 minutos
5. Click en: [Desbloquear Todas las Cuentas]
```

#### Opción 2: Manual en phpMyAdmin
```sql
-- Ir a phpMyAdmin → SQL y ejecutar:

UPDATE password_policy_config
SET setting_value = '30'
WHERE setting_name = 'lockout_duration_minutes';

-- Verificar:
SELECT * FROM password_policy_config
WHERE setting_name = 'lockout_duration_minutes';
-- Debe mostrar: 30
```

#### Opción 3: Verificar si es un problema de zona horaria
Si después de corregir sigue mostrando 6 horas, podría ser un problema de TIMEZONE.

**Verificar:**
```sql
SELECT NOW() as hora_mysql;
```

Compara con tu hora actual. Si hay diferencia de ~6 horas, el problema es la zona horaria.

**Solución:**
```php
// En hms/include/config.php, agregar después de la conexión:
mysqli_query($con, "SET time_zone = '-04:00'"); // Ajusta según tu zona
```

---

### **CORRECCIÓN B: Historial de Contraseñas**

Este problema es más complejo. Vamos a verificar paso a paso:

#### Paso 1: Verificar que la tabla existe
```
1. Ir a: http://localhost/hospital/tests/debug-password-history.php
2. Ver sección "2. Historial de Contraseñas en BD"
3. Debe decir: "Total en historial: X contraseña(s)"
```

Si dice "0", hay un problema con saveToHistory().

#### Paso 2: Probar guardado manual
```
1. En el mismo script, scroll a "5. Prueba Manual de Guardar en Historial"
2. Click en: [Guardar Contraseña Actual en Historial]
3. Recargar página
4. Ahora debería decir: "Total en historial: 1"
```

Si esto funciona, el problema está en change-password.php.

#### Paso 3: Verificar change-password.php incluye password-policy.php
```
1. Ver sección "4. Verificar Código de change-password.php"
2. Todos deben estar ✅:
   - include password-policy.php
   - new PasswordPolicy
   - changePassword()
```

Si alguno está ❌, hay que corregir el archivo.

#### Paso 4: Agregar logging para debug
Voy a modificar change-password.php para agregar logs.

---

## 🐛 **PASO 3: DEBUGGING AVANZADO**

Si los scripts de arriba no resuelven el problema, hagamos debugging más profundo.

### Debug de changePassword()

Abre: `hms/include/password-policy.php`

Busca la función `changePassword()` (línea ~120)

Agrega estos logs:

```php
public function changePassword($user_id, $new_password, $changed_by = null) {
    // Agregar al inicio:
    error_log("=== DEBUG changePassword ===");
    error_log("User ID: {$user_id}");
    error_log("Changed by: {$changed_by}");

    // ... código existente ...

    // Antes de guardar en historial (línea ~180):
    if ($current_password_hash) {
        error_log("Guardando en historial: user_id={$user_id}");
        $this->saveToHistory($user_id, $current_password_hash, $changed_by);
        error_log("Historial guardado exitosamente");
    } else {
        error_log("ERROR: No se encontró current_password_hash");
    }

    // ... resto del código ...
}
```

Luego, revisa los logs en:
```
C:\xampp\apache\logs\error.log
```

---

## 📊 **DIAGNÓSTICO PROBABLE**

### **Problema de Timezone (más probable)**

Si el bloqueo dice "6 horas" en vez de "30 minutos", y tu zona horaria tiene -6 horas de diferencia con UTC, el problema es el TIMEZONE del servidor.

**Verificación:**
```php
<?php
echo "PHP timezone: " . date_default_timezone_get() . "<br>";
echo "PHP hora: " . date('Y-m-d H:i:s') . "<br>";

include('hms/include/config.php');
$result = mysqli_query($con, "SELECT NOW() as mysql_time");
$row = mysqli_fetch_assoc($result);
echo "MySQL hora: " . $row['mysql_time'] . "<br>";
?>
```

Si las horas son diferentes, ese es el problema.

**Solución definitiva:**

Agregar en `hms/include/config.php` después de la línea de conexión:

```php
$con = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// AGREGAR ESTAS LÍNEAS:
date_default_timezone_set('America/La_Paz'); // Bolivia GMT-4
mysqli_query($con, "SET time_zone = '-04:00'");
```

---

## ✅ **VERIFICACIÓN FINAL**

Después de aplicar las correcciones:

### Test 1: Bloqueo de 30 minutos
```
1. Logout
2. Intentar login 3 veces con contraseña incorrecta
3. Ver mensaje: "Cuenta bloqueada... 30 minutos"
4. Login como admin → unlock-accounts.php
5. Verificar que dice: "~30 minutos restantes" (no 380)
```

### Test 2: Historial de contraseñas
```
1. Login como paciente
2. Cambiar contraseña de "Pass1@Aa" a "Pass2@Bb"
3. Ir a phpMyAdmin → password_history
4. Debe haber 1 registro nuevo con el hash de "Pass1@Aa"
5. Intentar cambiar de nuevo a "Pass1@Aa"
6. Debe rechazar: "Esta contraseña ya fue utilizada recientemente"
```

---

## 📞 **QUÉ REPORTAR**

Ejecuta los 2 scripts de debug y repórtame:

### Para el bloqueo:
```
1. ¿Qué valor tiene lockout_duration_minutes? _____
2. ¿Cuántos minutos muestra "Tiempo Restante"? _____
3. ¿Qué hora muestra PHP vs MySQL? _____
```

### Para el historial:
```
1. ¿Cuántos registros hay en password_history? _____
2. ¿El botón "Forzar guardado" funciona? SÍ / NO
3. ¿change-password.php incluye password-policy.php? SÍ / NO
```

---

## 🔗 Enlaces a Scripts de Debug

- **Debug Bloqueo:** http://localhost/hospital/tests/debug-lockout.php
- **Debug Historial:** http://localhost/hospital/tests/debug-password-history.php
- **Verificar Migración:** http://localhost/hospital/database/migrations/verify-migration.php

---

**IMPORTANTE:** Ejecuta primero los scripts de debug antes de hacer cambios manuales. Necesitamos ver exactamente qué está pasando.

**¿Listo? Abre el primero script ahora y copia aquí lo que ves.** 🔍
