# Migraciones de Base de Datos - HMS v2

Este directorio contiene las migraciones de base de datos del proyecto Hospital Management System.

## 📋 Índice de Migraciones

| Migración | Descripción | Versión | Estado |
|-----------|-------------|---------|--------|
| `001_initial_schema.sql` | Esquema inicial normalizado 3NF | 2.0.0 | ✅ Aplicado |
| `002_password_security.sql` | Políticas de seguridad de contraseñas | 2.1.0 | ⏳ Pendiente |

---

## 🚀 Cómo Ejecutar las Migraciones

### **Opción 1: Usando línea de comandos (Recomendado)**

```bash
# Navegar al directorio de migraciones
cd C:\xampp\htdocs\hospital\database\migrations

# Ejecutar la migración
mysql -u root -p hms_v2 < 002_password_security.sql
```

Si no tienes contraseña en MySQL (configuración por defecto XAMPP):
```bash
mysql -u root hms_v2 < 002_password_security.sql
```

### **Opción 2: Usando phpMyAdmin**

1. Abrir phpMyAdmin: `http://localhost/phpmyadmin`
2. Seleccionar la base de datos `hms_v2`
3. Click en la pestaña **SQL**
4. Copiar todo el contenido de `002_password_security.sql`
5. Pegar en el área de texto
6. Click en **"Continuar"** o **"Go"**
7. Verificar que aparezcan mensajes de éxito

### **Opción 3: Usando HeidiSQL / MySQL Workbench**

1. Conectarse a la base de datos `hms_v2`
2. File → Load SQL File
3. Seleccionar `002_password_security.sql`
4. Ejecutar

---

## ✅ Verificar que la Migración se Ejecutó Correctamente

Después de ejecutar la migración, verifica con estos comandos SQL:

```sql
-- Verificar campos nuevos en tabla users
DESCRIBE users;
-- Deberías ver: failed_login_attempts, account_locked_until, password_expires_at, etc.

-- Verificar tablas nuevas
SHOW TABLES LIKE 'password%';
-- Deberías ver: password_history, password_reset_tokens, password_policy_config

-- Verificar tabla login_attempts
SHOW TABLES LIKE 'login_attempts';

-- Verificar configuración por defecto
SELECT * FROM password_policy_config;
-- Deberías ver 13 registros con las políticas

-- Verificar vistas
SHOW FULL TABLES WHERE TABLE_TYPE LIKE 'VIEW';
-- Deberías ver: users_password_expiring_soon, locked_accounts

-- Verificar stored procedures
SHOW PROCEDURE STATUS WHERE Db = 'hms_v2';
-- Deberías ver: cleanup_old_security_data
```

---

## 📊 Qué Agrega Esta Migración

### **Campos Nuevos en Tabla `users`:**
- `failed_login_attempts` - Contador de intentos fallidos
- `account_locked_until` - Fecha de desbloqueo automático
- `password_expires_at` - Fecha de expiración de contraseña
- `password_changed_at` - Última vez que cambió contraseña
- `last_login_ip` - IP del último login
- `force_password_change` - Forzar cambio en próximo login

### **Tablas Nuevas:**
1. **`password_history`** - Historial de últimas 5 contraseñas
2. **`password_reset_tokens`** - Tokens de recuperación de contraseña
3. **`login_attempts`** - Registro de todos los intentos de login
4. **`password_policy_config`** - Configuración dinámica de políticas

### **Vistas Creadas:**
1. **`users_password_expiring_soon`** - Usuarios con contraseñas por expirar en 7 días
2. **`locked_accounts`** - Cuentas actualmente bloqueadas

### **Stored Procedures:**
1. **`cleanup_old_security_data()`** - Limpia datos antiguos (>90 días)

---

## 🔄 Rollback (Deshacer Cambios)

Si necesitas deshacer esta migración:

```bash
mysql -u root hms_v2 < 002_password_security_rollback.sql
```

⚠️ **ADVERTENCIA:** Esto eliminará TODAS las tablas y datos relacionados con seguridad de contraseñas.

---

## 🛠️ Mantenimiento

### **Limpieza Periódica de Datos**

Ejecutar cada mes para mantener la BD limpia:

```sql
CALL cleanup_old_security_data();
```

Esto elimina:
- Intentos de login > 90 días
- Tokens de recuperación > 7 días
- Historial de contraseñas excedente (mantiene solo últimos 5)

### **Consultar Cuentas Bloqueadas**

```sql
SELECT * FROM locked_accounts;
```

### **Consultar Contraseñas por Expirar**

```sql
SELECT * FROM users_password_expiring_soon;
```

---

## 📝 Notas Importantes

1. **Backup antes de ejecutar:** Siempre haz backup de la BD antes de ejecutar migraciones
   ```bash
   mysqldump -u root hms_v2 > backup_before_002.sql
   ```

2. **Usuarios existentes:** La migración automáticamente:
   - Establece `password_changed_at` = `created_at`
   - Calcula `password_expires_at` = `created_at` + 90 días
   - Inicializa `failed_login_attempts` = 0

3. **Políticas configurables:** Todas las políticas están en la tabla `password_policy_config` y se pueden modificar desde el admin panel (próximamente)

4. **Compatibilidad:** Requiere MySQL 5.7+ o MariaDB 10.2+

---

## 🐛 Troubleshooting

### Error: "Table already exists"
```sql
-- Verificar si ya ejecutaste la migración
SHOW TABLES LIKE 'password_history';
-- Si existe, ya fue ejecutada
```

### Error: "Unknown column"
```sql
-- Verificar estructura actual
DESCRIBE users;
-- Ejecutar rollback y volver a aplicar
```

### Error: "Access denied"
```bash
# Asegúrate de usar el usuario correcto
mysql -u root -p hms_v2 < 002_password_security.sql
# Te pedirá la contraseña
```

---

## 📞 Soporte

Para problemas con las migraciones, revisar:
- [README.md](../../README.md) - Documentación principal
- [database/README.md](../README.md) - Setup de base de datos
- [docs/](../../docs/) - Documentación técnica

---

**Proyecto:** SIS 321 - Seguridad de Sistemas
**Versión:** 2.1.0
**Fecha:** Octubre 2025
