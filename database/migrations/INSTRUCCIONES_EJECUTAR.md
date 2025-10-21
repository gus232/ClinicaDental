# 🚀 Instrucciones para Ejecutar la Migración 002

## ⚠️ IMPORTANTE: Lee esto primero

Antes de ejecutar la migración, asegúrate de:

1. ✅ Tener **XAMPP ejecutándose** (Apache y MySQL activos)
2. ✅ Tener acceso a **phpMyAdmin** en `http://localhost/phpmyadmin`
3. ✅ La base de datos **`hms_v2`** debe existir

---

## 📝 MÉTODO 1: phpMyAdmin (RECOMENDADO - Más fácil)

### Paso 1: Abrir phpMyAdmin
```
http://localhost/phpmyadmin
```

### Paso 2: Seleccionar base de datos
- En el panel izquierdo, click en **`hms_v2`**

### Paso 3: Ir a la pestaña SQL
- Click en la pestaña **"SQL"** (arriba del panel principal)

### Paso 4: Copiar el archivo SQL
- Abre el archivo: `002_password_security.sql`
- **Selecciona TODO** el contenido (Ctrl+A)
- **Copia** (Ctrl+C)

### Paso 5: Pegar y ejecutar
- **Pega** en el área de texto grande en phpMyAdmin (Ctrl+V)
- Click en el botón **"Continuar"** o **"Go"** (abajo a la derecha)

### Paso 6: Verificar éxito
Deberías ver mensajes en **verde** como:
```
✓ Migración 002_password_security.sql ejecutada exitosamente
✓ Campos agregados a tabla users: 6 columnas nuevas
✓ Tablas creadas: password_history, password_reset_tokens, login_attempts, password_policy_config
✓ Políticas configuradas: 13 settings por defecto
✓ Vistas creadas: users_password_expiring_soon, locked_accounts
✓ Procedures creados: cleanup_old_security_data
✓ Sistema listo para implementar políticas de contraseñas
```

---

## 🖥️ MÉTODO 2: Línea de comandos

### Opción A: Usando el script .bat (Windows)

1. Haz **doble click** en el archivo:
   ```
   run-migration.bat
   ```

2. Verás una ventana que ejecuta la migración automáticamente

### Opción B: Comando manual

1. Abre **CMD** (Símbolo del sistema) o **PowerShell**

2. Ejecuta:
   ```bash
   cd C:\xampp\mysql\bin
   mysql -u root hms_v2 < "C:\xampp\htdocs\hospital\database\migrations\002_password_security.sql"
   ```

---

## ✅ VERIFICAR QUE LA MIGRACIÓN SE EJECUTÓ CORRECTAMENTE

Después de ejecutar, verifica en phpMyAdmin:

### 1. Verificar campos nuevos en tabla `users`

Click en la tabla `users` → pestaña **"Estructura"**

Deberías ver estos **campos nuevos**:
- ✅ `failed_login_attempts`
- ✅ `account_locked_until`
- ✅ `password_expires_at`
- ✅ `password_changed_at`
- ✅ `last_login_ip`
- ✅ `force_password_change`

### 2. Verificar tablas nuevas

En el panel izquierdo deberías ver estas **tablas nuevas**:
- ✅ `login_attempts`
- ✅ `password_history`
- ✅ `password_policy_config`
- ✅ `password_reset_tokens`

### 3. Verificar configuración

Click en la tabla `password_policy_config` → pestaña **"Examinar"**

Deberías ver **13 registros** con configuraciones como:
- min_length = 8
- require_uppercase = 1
- max_failed_attempts = 3
- etc.

---

## ❌ SOLUCIÓN DE PROBLEMAS

### Error: "Table already exists"
**Causa:** Ya ejecutaste la migración antes

**Solución:**
1. Revisa si las tablas ya existen
2. Si quieres volver a ejecutar, primero ejecuta el rollback:
   ```sql
   -- En phpMyAdmin, ejecuta:
   source C:\xampp\htdocs\hospital\database\migrations\002_password_security_rollback.sql
   ```
3. Luego ejecuta nuevamente la migración

### Error: "Access denied for user 'root'"
**Causa:** Contraseña de MySQL incorrecta

**Solución:**
- En XAMPP por defecto no hay contraseña
- Si configuraste una contraseña, agrégala al comando:
  ```bash
  mysql -u root -p hms_v2 < 002_password_security.sql
  ```

### Error: "Unknown database 'hms_v2'"
**Causa:** La base de datos no existe

**Solución:**
1. Abre phpMyAdmin
2. Verifica que existe la base de datos `hms_v2`
3. Si no existe, créala o verifica que estés usando el nombre correcto

### Error: "Column already exists"
**Causa:** Algunos campos ya fueron agregados

**Solución:**
1. Ejecuta el rollback primero
2. Luego ejecuta la migración completa nuevamente

---

## 🎯 SIGUIENTE PASO

Una vez que la migración se ejecute **exitosamente**, avísame y continuaremos con:

1. ✅ Modificar `login.php` para implementar bloqueo al 3er intento
2. ✅ Actualizar `change-password.php` para usar las nuevas políticas
3. ✅ Crear módulo de desbloqueo de cuentas para admin
4. ✅ Crear sistema de recuperación de contraseñas

---

## 📞 ¿NECESITAS AYUDA?

Si tienes algún error:
1. Copia el mensaje de error completo
2. Toma captura de pantalla
3. Comparte el error para ayudarte a resolverlo

---

**Proyecto:** SIS 321 - Seguridad de Sistemas
**Fecha:** 2025-10-20
**Versión:** 2.1.0
