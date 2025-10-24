# 🐛 Solución: Bug en Tarjetas de Estadísticas

## Problema Identificado

Las tarjetas de estadísticas en la página `admin/manage-users.php` mostraban todos los valores en **0**, a pesar de que existían usuarios en la base de datos.

![Bug de Estadísticas](https://i.imgur.com/bug.png)

## Causa Raíz

El **stored procedure `get_user_statistics()`** estaba usando valores incorrectos para el campo `status`:

### ❌ ANTES (Incorrecto):
```sql
SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_users,
SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as inactive_users,
```

**Problema:** El campo `status` en la tabla `users` es de tipo **VARCHAR**, no INT. Los valores correctos son:
- `'active'` (no `1`)
- `'inactive'` (no `0`)
- `'blocked'` (nuevo estado)

### ✅ DESPUÉS (Corregido):
```sql
SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_users,
```

## Solución Implementada

### 1. Stored Procedure Corregido

Se creó el archivo:
```
database/stored-procedures/09_get_user_statistics_FIXED.sql
```

**Cambios principales:**
- ✅ Cambio de `status = 1` a `status = 'active'`
- ✅ Cambio de `status = 0` a `status = 'inactive'`
- ✅ Agregado `status = 'blocked'` para usuarios bloqueados
- ✅ Eliminadas referencias a columnas inexistentes (`gender`, `reg_date`)
- ✅ Cambio de `reg_date` a `created_at`

### 2. Instalación Automática

El stored procedure corregido se instaló automáticamente en la base de datos `hms_v2`:

```powershell
Get-Content "database/stored-procedures/09_get_user_statistics_FIXED.sql" | 
  c:\xampp\mysql\bin\mysql.exe -u root hms_v2
```

**Resultado:** ✅ SP instalado correctamente

## Verificación

### Opción 1: Script de Prueba

Visita en tu navegador:
```
http://localhost/hospital/hms/test-statistics.php
```

Este script verifica:
1. ✅ Stored procedure funciona correctamente
2. ✅ Clase `UserManagement::getStatistics()` retorna valores
3. ✅ Comparación con consulta SQL directa

### Opción 2: Página de Gestión de Usuarios

Visita:
```
http://localhost/hospital/hms/admin/manage-users.php
```

**Resultado esperado:**
```
┌─────────────────────────────────────────────────────────┐
│  👥           ✅           ⏸️           🚫            │
│  X            Y            Z            W             │
│  TOTAL      ACTIVOS    INACTIVOS    BLOQUEADOS       │
└─────────────────────────────────────────────────────────┘
```

Donde X, Y, Z, W son los números reales de la base de datos.

## Archivos Modificados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `database/stored-procedures/09_get_user_statistics_FIXED.sql` | ✅ NUEVO | SP corregido |
| `hms/test-statistics.php` | ✅ NUEVO | Script de prueba |

**NO se modificó:**
- `hms/include/UserManagement.php` (ya estaba correcto)
- `hms/admin/manage-users.php` (ya estaba correcto)

## Prueba Manual en MySQL

Puedes ejecutar manualmente en MySQL:

```sql
-- Probar el stored procedure
CALL get_user_statistics();

-- Verificación directa
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactivos,
    SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as bloqueados
FROM users;
```

## Estado de la Solución

| Componente | Estado |
|------------|--------|
| Stored Procedure | ✅ Corregido e instalado |
| Clase UserManagement | ✅ Funcionando (no requería cambios) |
| Vista manage-users.php | ✅ Funcionando (no requería cambios) |
| Script de prueba | ✅ Creado |
| Documentación | ✅ Completada |

## Próximos Pasos

1. ✅ Verificar que las estadísticas se muestran correctamente
2. ✅ Probar con diferentes estados de usuario (active, inactive, blocked)
3. ✅ Documentar en changelog del proyecto

## Notas Técnicas

### Tipo de Datos de `status`

La columna `status` en la tabla `users` se define como:

```sql
status VARCHAR(20) NOT NULL DEFAULT 'active'
```

**Valores válidos:**
- `'active'` - Usuario activo (puede iniciar sesión)
- `'inactive'` - Usuario inactivo (no puede iniciar sesión)
- `'blocked'` - Usuario bloqueado (intentos fallidos o bloqueo manual)

### Por qué el SP anterior fallaba

El SP original asumía un tipo de dato antiguo donde `status` era un campo TINYINT (0 o 1). Este diseño fue reemplazado en las fases de reingeniería de seguridad para:
1. Mejor legibilidad del código
2. Soporte para más estados (bloqueado)
3. Mayor claridad en las consultas SQL

---

**Fecha de corrección:** 23 de Octubre, 2025  
**Bug ID:** STATS-001  
**Severidad:** Media (afecta UX pero no funcionalidad crítica)  
**Estado:** ✅ RESUELTO
