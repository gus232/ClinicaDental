# Resumen del Proyecto de Seguridad de Sistemas (SIS 321)

## Información del Proyecto
- **Curso:** Seguridad de Sistemas (SIS 321)
- **Proyecto:** Hospital Management System (HMS) - Clínica Dental Muelitas
- **Tipo:** Proyecto de seguridad aplicada a sistema PHP legacy
- **Estado:** En desarrollo (Puntos 1-10 completados)

## Objetivos del Proyecto

Este proyecto tiene como objetivo aplicar principios de seguridad a un sistema de gestión hospitalaria existente, identificando y corrigiendo vulnerabilidades críticas.

## Estructura del Informe de Seguridad (16 Puntos)

### ✅ Fase 1: Puntos Completados (1-10)

#### 1. Análisis de Vulnerabilidades Iniciales
- **Estado:** ✅ Completado
- **Vulnerabilidades Identificadas:**
  - SQL Injection en login (concatenación directa)
  - Contraseñas en texto plano (tabla admin)
  - Contraseñas con MD5 (tablas users y doctors)
  - Sin prepared statements
  - Sin validación de entrada
  - 3 sistemas de login separados (confusión de usuarios)

#### 2. Auditoría de Base de Datos
- **Estado:** ✅ Completado
- **Problemas Encontrados:**
  - Base de datos NO normalizada
  - Duplicación de datos en 3 tablas (users, doctors, admin)
  - Sin Foreign Keys
  - Sin integridad referencial
  - Estructura inconsistente entre tablas

#### 3. Análisis de Autenticación
- **Estado:** ✅ Completado
- **Problemas Identificados:**
  - 3 páginas de login diferentes:
    - `user-login.php` (pacientes)
    - `admin/index.php` (administradores)
    - `doctor/index.php` (doctores)
  - Lógica de autenticación vulnerable
  - Sin password_verify() para bcrypt
  - Comparación directa de contraseñas

#### 4. Migración de Contraseñas
- **Estado:** ✅ Completado
- **Acciones Realizadas:**
  - Backup de base de datos creado
  - Hash de 2 contraseñas admin (texto plano → bcrypt)
  - Verificación de passwords existentes en bcrypt
  - Script: `hash-admin-passwords.php`

#### 5. Normalización de Base de Datos (3FN)
- **Estado:** ✅ Completado
- **Cambios Implementados:**

**Estructura ANTES:**
```
users (5 pacientes)
├── id, fullname, address, city, gender, email, password

doctors (9 doctores)
├── id, specilization, doctorName, address, docFees, contactno, docEmail, password

admin (2 administradores)
├── id, username, password (plain text!)
```

**Estructura DESPUÉS (Normalizada 3FN):**
```
users (tabla principal - 16 usuarios unificados)
├── id (PK)
├── email (UNIQUE)
├── password (bcrypt)
├── user_type (ENUM: 'patient', 'doctor', 'admin')
├── full_name
├── status (ENUM: 'active', 'inactive', 'blocked')
├── created_at
├── updated_at
├── last_login

patients (datos específicos de pacientes)
├── id (PK)
├── user_id (FK → users.id)
├── address
├── city
├── gender

doctors (datos específicos de doctores)
├── id (PK - original)
├── user_id (FK → users.id) ← NUEVO
├── specilization
├── doctorName
├── address
├── docFees
├── contactno
├── docEmail

admins (datos específicos de administradores)
├── id (PK)
├── user_id (FK → users.id)
├── username
├── permissions (JSON)
```

**Migración:**
- 5 pacientes migrados
- 9 doctores migrados
- 2 administradores migrados
- **Total: 16 usuarios** en tabla unificada
- Script: `migrate-step-by-step.php`

#### 6. Implementación de Prepared Statements
- **Estado:** ✅ Completado
- **Código ANTES (Vulnerable):**
```php
$sql = "SELECT * FROM users WHERE email='$username' AND password='$password'";
$result = mysqli_query($con, $sql);
```

- **Código DESPUÉS (Seguro):**
```php
$sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($user && password_verify($password, $user['password'])) {
    // Autenticación exitosa
}
```

#### 7. Unificación del Sistema de Login
- **Estado:** ✅ Completado
- **Solución Implementada:**
  - Creado `login.php` unificado
  - Auto-detección de tipo de usuario
  - Redirección automática según rol
  - Una sola página de entrada al sistema

- **Flujo Unificado:**
```
Usuario → login.php → Validación → Auto-detecta tipo
                                   ├── patient → dashboard1.php
                                   ├── doctor → doctor/dashboard.php
                                   └── admin → admin/dashboard.php
```

#### 8. Sanitización de Entrada
- **Estado:** ✅ Completado
- **Implementaciones:**
```php
// Sanitización de email
$email = mysqli_real_escape_string($con, trim($_POST['email']));

// Prepared statements previenen SQL injection
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
```

#### 9. Actualización de Referencias
- **Estado:** ✅ Completado
- **Archivos Modificados:**
  - `index.html` (3 referencias actualizadas)
  - `registration.php` (2 referencias)
  - `forgot-password.php` (1 referencia)
  - `reset-password.php` (2 referencias)
  - `dashboard1.php` (1 referencia)
  - `doctor/dashboard.php` (1 URL hardcodeada corregida)
- Script: `update-login-references.php`

#### 10. Documentación de Cambios
- **Estado:** ✅ Completado
- **Documentos Creados:**
  - README.md completo (1,200+ líneas)
  - RESUMEN_PROYECTO_SEGURIDAD.md (este documento)
  - FLUJO_COMPLETO_VISTAS.md
  - INFORME_VISTAS_Y_PROBLEMAS.md
  - ANALISIS_LOGIN_UNIFICADO.md
  - RESUMEN_SESION_LOGIN_UNIFICADO.md

---

### ⏳ Fase 2: Puntos Pendientes (11-16)

#### 11. Validación de Complejidad de Contraseñas
- **Estado:** ⏳ Pendiente
- **Implementar:**
  - Mínimo 8 caracteres
  - Al menos 1 mayúscula
  - Al menos 1 número
  - Al menos 1 carácter especial
  - Feedback visual en formulario de registro

#### 12. Historial de Contraseñas
- **Estado:** ⏳ Pendiente
- **Implementar:**
  - Tabla `password_history`
  - Prevenir reutilización de últimas 5 contraseñas
  - Validación en cambio de contraseña

#### 13. Bloqueo de Cuenta por Intentos Fallidos
- **Estado:** ⏳ Pendiente
- **Implementar:**
  - Tabla `login_attempts`
  - Bloquear después de 5 intentos fallidos
  - Timeout de 15 minutos
  - Notificación por email al usuario

#### 14. Protección CSRF
- **Estado:** ⏳ Pendiente
- **Implementar:**
  - Tokens CSRF en todos los formularios
  - Validación en servidor
  - Regeneración de tokens por sesión

#### 15. Sanitización XSS en Salidas
- **Estado:** ⏳ Pendiente
- **Implementar:**
  - `htmlspecialchars()` en todos los outputs
  - Validación de inputs HTML
  - Content Security Policy headers

#### 16. Auditoría y Logging
- **Estado:** ⏳ Pendiente
- **Implementar:**
  - Tabla `audit_log`
  - Registro de:
    - Inicios de sesión exitosos/fallidos
    - Cambios de contraseña
    - Modificaciones de datos críticos
    - Acciones administrativas
  - Retention policy de logs

---

## Vulnerabilidades Críticas Resueltas

### 🔴 Críticas (RESUELTAS)

1. **SQL Injection**
   - ✅ Implementados prepared statements
   - ✅ Sanitización de entrada
   - ✅ Validación de datos

2. **Contraseñas en Texto Plano**
   - ✅ Migradas a bcrypt
   - ✅ password_verify() implementado
   - ✅ Hashing automático en registro

3. **Autenticación Débil**
   - ✅ Sistema unificado
   - ✅ Detección automática de roles
   - ✅ Sesiones seguras

### 🟡 Moderadas (PENDIENTES)

1. **Sin Validación de Complejidad de Contraseñas**
   - ⏳ Punto 11 pendiente

2. **Sin Bloqueo por Intentos Fallidos**
   - ⏳ Punto 13 pendiente

3. **Sin Protección CSRF**
   - ⏳ Punto 14 pendiente

4. **Vulnerabilidad XSS**
   - ⏳ Punto 15 pendiente

### 🟢 Menores (PENDIENTES)

1. **Sin Auditoría de Acciones**
   - ⏳ Punto 16 pendiente

2. **Sin Historial de Contraseñas**
   - ⏳ Punto 12 pendiente

---

## Estructura de Archivos del Proyecto

```
hospital/
├── README.md (documentación principal)
├── docs/ (documentación técnica)
│   ├── RESUMEN_PROYECTO_SEGURIDAD.md (este archivo)
│   ├── FLUJO_COMPLETO_VISTAS.md
│   ├── INFORME_VISTAS_Y_PROBLEMAS.md
│   ├── ANALISIS_LOGIN_UNIFICADO.md
│   └── RESUMEN_SESION_LOGIN_UNIFICADO.md
├── hms/
│   ├── login.php ⭐ (nuevo login unificado)
│   ├── backups/
│   │   └── backup_hms_2025-10-12_01-50-41.sql
│   ├── migration-scripts/ (referencia histórica)
│   │   ├── backup-database.php
│   │   ├── hash-admin-passwords.php
│   │   ├── migrate-step-by-step.php
│   │   └── migrate-normalize-database.sql
│   └── [resto del sistema...]
└── [archivos obsoletos eliminados]
```

---

## Credenciales de Prueba (16 Usuarios)

### Pacientes (5)
```
Email: user@test.com | Password: Test@123 | Tipo: patient
Email: test@demo.com | Password: Test@123 | Tipo: patient
Email: john.doe@email.com | Password: Test@123 | Tipo: patient
Email: jane.smith@email.com | Password: Test@123 | Tipo: patient
Email: alex.jones@email.com | Password: Test@123 | Tipo: patient
```

### Doctores (9)
```
Email: anuj.lpu1@gmail.com | Password: Test@123 | Tipo: doctor
Email: hkapil@test.com | Password: Test@123 | Tipo: doctor
Email: doctor@test.com | Password: Test@123 | Tipo: doctor
Email: doctor1@demo.com | Password: Test@123 | Tipo: doctor
Email: doctor2@demo.com | Password: Test@123 | Tipo: doctor
Email: doctor3@demo.com | Password: Test@123 | Tipo: doctor
Email: doctor4@demo.com | Password: Test@123 | Tipo: doctor
Email: doctor5@demo.com | Password: Test@123 | Tipo: doctor
Email: doctor6@demo.com | Password: Test@123 | Tipo: doctor
```

### Administradores (2)
```
Email: admin@mail.com | Password: Test@123 | Tipo: admin
Email: admin2@mail.com | Password: Test@123 | Tipo: admin
```

---

## Problemas Conocidos Pendientes

### 🐛 Bug Crítico: Dashboards en Blanco

**Síntoma:** Después del login exitoso, los dashboards muestran página en blanco

**Afecta a:**
- ❌ doctor/dashboard.php (en blanco)
- ❌ admin/dashboard.php (probablemente en blanco)
- ⚠️ dashboard1.php (pacientes) - muestra opciones pero están vacías

**Causa Probable:**
- Includes (sidebar.php, header.php) no se cargan correctamente
- Variables de sesión pueden no estar disponibles
- Rutas relativas incorrectas después de cambio de estructura

**Estado:** Dejado para corrección posterior

**Prioridad:** Alta - impide uso del sistema

---

## Métricas del Proyecto

### Archivos Modificados
- 6 archivos PHP actualizados
- 1 archivo HTML modificado
- 6 archivos de backup creados

### Archivos Creados
- 1 login unificado nuevo
- 4 scripts de migración
- 6 documentos markdown
- 1 backup SQL

### Archivos Eliminados (Refactorización)
- 3 logins obsoletos
- 2 archivos SQL antiguos
- 1 directorio completo (SQL File/)

### Base de Datos
- 16 usuarios migrados exitosamente
- 4 tablas normalizadas (3FN)
- 3 Foreign Keys añadidas
- 100% de contraseñas en bcrypt

### Líneas de Código
- README.md: ~1,200 líneas
- Documentación total: ~3,000 líneas
- Scripts de migración: ~300 líneas

---

## Recomendaciones de Seguridad

### Inmediatas (Hacer AHORA)
1. ✅ Corregir dashboards (bug crítico)
2. ⏳ Implementar validación de complejidad de contraseñas
3. ⏳ Añadir protección CSRF
4. ⏳ Implementar bloqueo por intentos fallidos

### Corto Plazo (Siguiente Sprint)
1. ⏳ Sanitización XSS completa
2. ⏳ Sistema de auditoría y logging
3. ⏳ Historial de contraseñas
4. ⏳ Pruebas de todas las 35 vistas

### Largo Plazo (Mejoras Futuras)
1. Migrar a Laravel o framework moderno
2. Implementar autenticación de 2 factores (2FA)
3. Rate limiting en endpoints
4. Encriptación de datos sensibles en BD
5. HTTPS obligatorio
6. Headers de seguridad (CSP, HSTS, etc.)
7. Pruebas de penetración profesionales

---

## Conclusión

Se han completado exitosamente los **primeros 10 puntos** del proyecto de seguridad, resolviendo las vulnerabilidades críticas del sistema:

✅ **Logros Principales:**
- SQL Injection eliminado
- Contraseñas migradas a bcrypt
- Base de datos normalizada (3FN)
- Login unificado implementado
- Prepared statements en toda autenticación

⏳ **Pendiente:**
- Corrección de dashboards (bug crítico)
- Implementación de puntos 11-16
- Pruebas exhaustivas de las 35 vistas

El sistema ahora tiene una base de seguridad sólida, pero requiere completar los puntos restantes y corregir el bug de los dashboards para estar completamente funcional y seguro.

---

**Fecha de Última Actualización:** 12 de Octubre, 2025
**Responsable:** Proyecto de Seguridad SIS 321
**Estado:** En Desarrollo (10/16 completado)
