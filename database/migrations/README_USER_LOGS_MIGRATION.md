# Migración: userlog → user_logs

## 📋 Resumen

Migración de la tabla antigua `userlog` a la nueva tabla `user_logs` con estructura moderna que incluye tracking detallado de dispositivos, navegadores y sesiones.

## ✅ Archivos Creados/Modificados

### Archivos Nuevos:
1. **database/migrations/005_user_logs_migration.sql**
   - Script de migración completa
   - Crea tabla `user_logs` con estructura moderna
   - Migra datos de `userlog` a `user_logs`
   - Crea vistas útiles para análisis
   - Crea procedimiento de limpieza de sesiones

2. **hms/include/UserActivityLogger.php**
   - Clase PHP para manejo de logs de actividad
   - Métodos para login/logout
   - Detección automática de dispositivo/navegador/OS
   - Gestión de sesiones activas

### Archivos Modificados:
3. **hms/login.php**
   - Agregado: `require_once("include/UserActivityLogger.php");`
   - Agregado: Registro de sesión al hacer login exitoso (líneas 259-261)

4. **hms/logout.php**
   - Reemplazado: UPDATE directo a userlog por llamada a `UserActivityLogger::logLogout`
   - Ahora usa la nueva tabla `user_logs`

5. **hms/admin/manage-users.php**
   - Actualizado: Tab de "Logs de Actividad" para usar tabla `user_logs`
   - Cambiadas referencias de columnas:
     - `userlog` → `user_logs`
     - `ul.uid` → `ul.user_id`
     - `ul.loginTime` → `ul.login_time`
     - `ul.logout` → `ul.logout_time`
     - `ul.userip` → `ul.ip_address`
   - Usa `session_duration` precalculado de la BD

---

## 🚀 Pasos de Implementación

### PASO 1: Ejecutar Migración SQL

**IMPORTANTE:** Hacer backup de la base de datos primero

```bash
# Backup de seguridad
mysqldump -u root -p hms_v2 > backup_antes_migracion_user_logs.sql

# Ejecutar migración
mysql -u root -p hms_v2 < database/migrations/005_user_logs_migration.sql
```

**O desde MySQL Workbench / phpMyAdmin:**
1. Abrir `database/migrations/005_user_logs_migration.sql`
2. Ejecutar todo el script
3. Verificar que aparezcan los mensajes de éxito

### PASO 2: Verificar Migración

Ejecutar en MySQL para verificar que los datos se migraron correctamente:

```sql
-- Verificar que la tabla existe
SHOW TABLES LIKE 'user_logs';

-- Contar registros migrados
SELECT COUNT(*) as total_migrados FROM user_logs;
SELECT COUNT(*) as total_originales FROM userlog WHERE uid IS NOT NULL;

-- Comparar totales (deben ser iguales)
SELECT
    (SELECT COUNT(*) FROM userlog WHERE uid IS NOT NULL) as origen,
    (SELECT COUNT(*) FROM user_logs) as destino,
    CASE
        WHEN (SELECT COUNT(*) FROM userlog WHERE uid IS NOT NULL) = (SELECT COUNT(*) FROM user_logs)
        THEN 'MIGRACIÓN EXITOSA ✓'
        ELSE 'REVISAR DIFERENCIAS'
    END as resultado;

-- Ver algunos registros migrados
SELECT * FROM user_logs ORDER BY login_time DESC LIMIT 10;
```

### PASO 3: Probar Login/Logout

1. **Cerrar sesión** actual si la hay
2. **Hacer login** nuevamente
3. **Verificar** que se creó registro en `user_logs`:

```sql
SELECT * FROM user_logs
WHERE user_id = [TU_USER_ID]
ORDER BY login_time DESC LIMIT 1;
```

Deberías ver:
- ✅ `user_id` correcto
- ✅ `login_time` reciente
- ✅ `ip_address` tu IP
- ✅ `device_type`, `browser`, `os` detectados
- ✅ `is_active = 1`
- ✅ `logout_time = NULL`

4. **Hacer logout**
5. **Verificar** que se actualizó el registro:

```sql
SELECT * FROM user_logs
WHERE user_id = [TU_USER_ID]
ORDER BY login_time DESC LIMIT 1;
```

Deberías ver:
- ✅ `logout_time` con fecha/hora del logout
- ✅ `session_duration` calculado en segundos
- ✅ `logout_reason = 'manual'`
- ✅ `is_active = 0`

### PASO 4: Verificar Tab de Logs en Admin

1. Ir a `admin/manage-users.php?tab=logs`
2. Verificar que se muestran los logs correctamente
3. Verificar que las columnas muestran datos:
   - Usuario
   - Email
   - Tipo
   - IP Address (ahora legible, no binario)
   - Fecha de Ingreso
   - Fecha de Salida
   - Duración

### PASO 5: Limpiar Sesiones Inactivas (Opcional)

Ejecutar manualmente el procedimiento de limpieza:

```sql
-- Cerrar sesiones inactivas de más de 30 minutos
CALL cleanup_inactive_sessions(30);
```

### PASO 6: Renombrar Tabla Antigua (Después de Verificar)

**⚠️ SOLO después de verificar que todo funciona correctamente:**

```sql
-- Renombrar tabla antigua como backup
ALTER TABLE userlog RENAME TO userlog_deprecated;

-- Opcional: Eliminar después de un periodo prudencial (1-2 semanas)
-- DROP TABLE userlog_deprecated;
```

---

## 📊 Nuevas Funcionalidades

### Vistas Creadas

1. **active_sessions** - Sesiones actualmente activas
```sql
SELECT * FROM active_sessions;
```

2. **user_session_summary** - Resumen por usuario
```sql
SELECT * FROM user_session_summary ORDER BY total_sessions DESC;
```

3. **sessions_by_device** - Estadísticas por tipo de dispositivo
```sql
SELECT * FROM sessions_by_device;
```

4. **sessions_by_browser** - Estadísticas por navegador
```sql
SELECT * FROM sessions_by_browser;
```

### Procedimiento de Limpieza

```sql
-- Limpiar sesiones inactivas cada hora (ejemplo)
CALL cleanup_inactive_sessions(60);
```

---

## 🔍 Estructura de la Nueva Tabla

### Campos Principales:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT | ID autoincremental |
| `user_id` | INT | ID del usuario (FK a users) |
| `login_time` | TIMESTAMP | Fecha/hora de inicio de sesión |
| `logout_time` | TIMESTAMP | Fecha/hora de cierre (NULL si activa) |
| `session_duration` | INT | Duración en segundos |
| `ip_address` | VARCHAR(45) | IP legible (IPv4/IPv6) |
| `user_agent` | TEXT | User agent completo |
| `device_type` | ENUM | desktop, mobile, tablet, other |
| `browser` | VARCHAR(50) | Chrome, Firefox, Safari, etc. |
| `os` | VARCHAR(50) | Windows, macOS, Linux, etc. |
| `session_id` | VARCHAR(128) | ID de sesión PHP |
| `logout_reason` | ENUM | manual, timeout, forced, error |
| `is_active` | TINYINT(1) | 1=activa, 0=cerrada |

### Índices Creados:

- `idx_user_id` - Búsquedas por usuario
- `idx_login_time` - Ordenar por fecha
- `idx_is_active` - Filtrar sesiones activas
- `idx_ip_address` - Búsquedas por IP
- `idx_user_active` - Compuesto para queries comunes

---

## 🐛 Troubleshooting

### Problema: Error "Table 'user_logs' doesn't exist"

**Solución:** Verificar que se ejecutó el script de migración
```sql
SHOW TABLES LIKE 'user_logs';
```

### Problema: No se registra login

**Verificar:**
1. Clase UserActivityLogger incluida correctamente
2. Ruta del include es correcta: `include/UserActivityLogger.php`
3. Permisos de archivo

**Debug en login.php:**
```php
$result = $logger->logLogin($user['id'], session_id(), $ip_address, $user_agent);
var_dump($result); // Ver si hay error
exit();
```

### Problema: IP aparece como 0.0.0.0

**Causa:** `$_SERVER['REMOTE_ADDR']` no disponible

**Solución:** Verificar configuración del servidor web

### Problema: device_type/browser/os aparecen NULL

**Causa:** User agent vacío o no detectado

**Normal para:**
- Registros migrados de `userlog` antigua
- Requests desde scripts/bots

**Solución:** Nuevos logins tendrán información completa

---

## 📈 Monitoreo Post-Migración

### Queries Útiles:

```sql
-- Sesiones activas ahora mismo
SELECT COUNT(*) as sesiones_activas FROM user_logs WHERE is_active = 1;

-- Logins de hoy
SELECT COUNT(*) as logins_hoy
FROM user_logs
WHERE DATE(login_time) = CURDATE();

-- Usuarios más activos
SELECT u.full_name, COUNT(*) as total_sesiones
FROM user_logs ul
JOIN users u ON ul.user_id = u.id
WHERE ul.login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY u.id, u.full_name
ORDER BY total_sesiones DESC
LIMIT 10;

-- Distribución por dispositivo
SELECT device_type, COUNT(*) as total
FROM user_logs
WHERE login_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY device_type;
```

---

## ✅ Checklist de Verificación

- [ ] Backup de base de datos realizado
- [ ] Script SQL ejecutado sin errores
- [ ] Datos migrados correctamente (count match)
- [ ] Nuevo login registra en user_logs
- [ ] Nuevo logout actualiza session_duration
- [ ] Tab de logs muestra datos correctos
- [ ] IP address aparece legible (no binario)
- [ ] device_type/browser/os se detectan en nuevos logins
- [ ] Vistas funcionan correctamente
- [ ] Procedimiento cleanup_inactive_sessions funciona
- [ ] Tabla antigua renombrada a userlog_deprecated

---

## 📞 Soporte

Si encuentras problemas:

1. Revisar logs de MySQL para errores
2. Verificar que todos los archivos PHP fueron actualizados
3. Probar con diferentes navegadores/dispositivos
4. Revisar permisos de archivos

---

## 🎯 Próximos Pasos (Opcional)

1. **Automatizar limpieza de sesiones:**
   - Descomentar evento automático en el script SQL
   - O crear cron job que ejecute el procedimiento

2. **Agregar más información:**
   - Geolocalización por IP
   - Tracking de páginas visitadas
   - Duración por página

3. **Dashboards:**
   - Gráficos de sesiones por hora/día
   - Mapa de conexiones por ubicación
   - Alertas de sesiones sospechosas

---

**Fecha de Migración:** 2025-10-30
**Versión:** 2.3.0
**Autor:** Sistema HMS
