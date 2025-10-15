# 🏥 Hospital Management System (HMS)

**Clínica Dental Muelitas - Sistema de Gestión Hospitalaria**

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-4.5-purple)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## 📋 Tabla de Contenidos

- [Descripción General](#-descripción-general)
- [Estado Actual del Proyecto](#-estado-actual-del-proyecto)
- [Tecnologías Utilizadas](#-tecnologías-utilizadas)
- [Arquitectura del Sistema](#-arquitectura-del-sistema)
- [Cambios y Mejoras Realizadas](#-cambios-y-mejoras-realizadas)
- [Estructura de la Base de Datos](#-estructura-de-la-base-de-datos)
- [Instalación y Configuración](#-instalación-y-configuración)
- [Guía de Uso](#-guía-de-uso)
- [Problemas Identificados y Pendientes](#-problemas-identificados-y-pendientes)
- [Próximos Pasos](#-próximos-pasos)
- [Contribución](#-contribución)
- [Créditos](#-créditos)
- [Licencia](#-licencia)

---

## 📖 Descripción General

**Hospital Management System (HMS)** es un sistema integral de gestión hospitalaria desarrollado en PHP procedural, diseñado originalmente para la Clínica Dental Muelitas. El sistema permite la gestión de pacientes, doctores, citas médicas, historiales clínicos y administración general de una clínica u hospital.

### 🎯 Objetivo del Sistema

Facilitar la gestión administrativa y clínica de instituciones de salud mediante:
- Registro y gestión de pacientes
- Programación y seguimiento de citas médicas
- Gestión de doctores y especialidades
- Historial médico digital
- Reportes y estadísticas
- Sistema de roles (Paciente, Doctor, Administrador)

---

## 🚀 Estado Actual del Proyecto

### ✅ Funcionalidades Completadas

#### 1. **Sistema de Autenticación Unificado** (Nuevo - Oct 2025)
- ✅ Login único para todos los tipos de usuarios
- ✅ Detección automática de rol (Paciente/Doctor/Admin)
- ✅ Migración de MD5 a Bcrypt para seguridad
- ✅ Prepared statements para prevenir SQL Injection
- ✅ Registro de último login

#### 2. **Base de Datos Normalizada** (Nuevo - Oct 2025)
- ✅ Tabla `users` unificada (pacientes, doctores, admins)
- ✅ Tablas relacionales: `patients`, `doctors`, `admins`
- ✅ Normalización a Tercera Forma Normal (3FN)
- ✅ 16 usuarios migrados exitosamente

#### 3. **Módulos Funcionales**
- ✅ 35 vistas implementadas (100% con código)
- ✅ Sistema de citas médicas
- ✅ Gestión de pacientes
- ✅ Gestión de doctores
- ✅ Historial médico
- ✅ Reportes básicos
- ✅ Logs de acceso (básico)

### ⚠️ Funcionalidades Parciales

- ⚠️ Sistema de roles (columna existe pero sin gestión dinámica)
- ⚠️ Validación de contraseñas (bcrypt implementado pero sin políticas)

### ❌ Funcionalidades Pendientes

- ❌ Gestión completa de contraseñas (complejidad, histórico, bloqueo)
- ❌ Sistema de roles y permisos granular
- ❌ Matriz de accesos
- ❌ Logs de seguridad completos
- ❌ Corrección de vulnerabilidades OWASP
- ❌ CSRF tokens en formularios
- ❌ Sanitización XSS completa

---

## 🛠️ Tecnologías Utilizadas

### Backend
- **PHP 7.4+** (Procedural)
- **MySQL 5.7+** / MariaDB
- **Apache** (XAMPP)

### Frontend
- **HTML5 / CSS3**
- **Bootstrap 4.5.2**
- **jQuery 3.5.1**
- **Font Awesome 5.15.4**
- **JavaScript**

### Herramientas de Desarrollo
- **Composer** (gestión de dependencias)
- **Playwright** (testing automatizado)
- **Git** (control de versiones)

### Seguridad
- **Bcrypt** (hashing de contraseñas)
- **Prepared Statements** (prevención de SQL Injection)
- **Sessions** (gestión de autenticación)

---

## 🏗️ Arquitectura del Sistema

### Estructura de Directorios

```
hospital/
├── index.html                  # Página home pública
├── contact.php                 # Página de contacto
├── README.md                   # Este archivo
│
├── docs/                       # 📁 Documentación técnica (NUEVO)
│   ├── RESUMEN_PROYECTO_SEGURIDAD.md
│   ├── FLUJO_COMPLETO_VISTAS.md
│   ├── INFORME_VISTAS_Y_PROBLEMAS.md
│   ├── ANALISIS_LOGIN_UNIFICADO.md
│   └── RESUMEN_SESION_LOGIN_UNIFICADO.md
│
└── hms/                        # Sistema principal
    ├── login.php              # ✅ Login unificado (NUEVO)
    ├── registration.php       # Registro de pacientes
    ├── dashboard1.php         # Dashboard paciente
    ├── book-appointment.php   # Agendar citas
    ├── appointment-history.php # Historial de citas
    ├── edit-profile.php       # Editar perfil
    ├── change-password.php    # Cambiar contraseña
    ├── logout.php             # Cerrar sesión
    │
    ├── include/               # Archivos compartidos
    │   ├── config.php        # Configuración BD
    │   ├── checklogin.php    # Verificación de sesión
    │   ├── header.php        # Header común
    │   ├── sidebar.php       # Sidebar común
    │   └── footer.php        # Footer común
    │
    ├── doctor/                # Módulo de doctores
    │   ├── dashboard.php     # Dashboard doctor
    │   ├── appointment-history.php
    │   ├── manage-patient.php
    │   ├── add-patient.php
    │   ├── edit-patient.php
    │   ├── view-patient.php
    │   ├── search.php
    │   └── include/          # Includes específicos
    │
    ├── admin/                 # Módulo de administración
    │   ├── dashboard.php     # Dashboard admin
    │   ├── manage-users.php
    │   ├── manage-doctors.php
    │   ├── add-doctor.php
    │   ├── edit-doctor.php
    │   ├── doctor-specilization.php
    │   ├── manage-patient.php
    │   ├── view-patient.php
    │   ├── appointment-history.php
    │   ├── between-dates-reports.php
    │   ├── user-logs.php
    │   ├── doctor-logs.php
    │   └── include/          # Includes específicos
    │
    ├── assets/                # Recursos estáticos
    │   ├── css/
    │   ├── js/
    │   └── images/
    │
    ├── vendor/                # Librerías de terceros
    │   ├── bootstrap/
    │   ├── fontawesome/
    │   └── jquery/
    │
    ├── backups/               # 📁 Backups de BD y archivos (NUEVO)
    │   ├── backup_hms_2025-10-12_01-50-41.sql
    │   ├── dashboard.php.backup.20251012030820
    │   ├── checklogin.php.backup.20251012030820
    │   ├── doctor-include-checklogin.php.backup.20251012030820
    │   └── root-include-checklogin.php.backup.20251012030820
    │
    └── migration-scripts/     # 📁 Scripts de migración (NUEVO)
        ├── backup-database.php
        ├── hash-admin-passwords.php
        ├── migrate-step-by-step.php
        └── migrate-normalize-database.sql
```

**Archivos Obsoletos Eliminados:**

- ❌ `hms/user-login.php` (reemplazado por login.php)
- ❌ `hms/admin/index.php` (reemplazado por login.php)
- ❌ `hms/doctor/index.php` (reemplazado por login.php)
- ❌ `SQL File/` (directorio completo eliminado)

---

## 🔄 Cambios y Mejoras Realizadas

### 📅 Refactorización Final: 12 de Octubre, 2025

#### 🧹 **Limpieza y Organización del Proyecto**

**Acciones Realizadas:**

1. **Creación de carpeta `docs/`**
   - ✅ Movidos 5 archivos de documentación markdown
   - ✅ Centralización de toda la documentación técnica
   - ✅ Estructura más profesional y organizada

2. **Creación de carpeta `migration-scripts/`**
   - ✅ Movidos 4 scripts de migración a carpeta dedicada
   - ✅ Scripts disponibles para referencia histórica
   - ✅ Separación de código de producción vs utilidades

3. **Organización de backups**
   - ✅ Consolidados todos los archivos `.backup.*` en carpeta `backups/`
   - ✅ Renombrados para evitar conflictos
   - ✅ Backup SQL preservado

4. **Eliminación de archivos obsoletos**
   - ✅ Eliminados 3 logins antiguos (user-login.php, admin/index.php, doctor/index.php)
   - ✅ Eliminado directorio `SQL File/` completo (2 archivos SQL obsoletos)
   - ✅ Código limpio y sin duplicados

**Resultado:**
```
✅ Proyecto más limpio y organizado
✅ Documentación centralizada en docs/
✅ Scripts de migración separados del código principal
✅ Backups organizados en una sola carpeta
✅ Eliminados 5+ archivos obsoletos
```

---

### 📅 Sesión de Mejoras: 11-12 de Octubre, 2025

#### 1. 🔐 **Migración de Sistema de Autenticación**

**ANTES:**
```
❌ 3 páginas de login separadas:
   - hms/user-login.php (pacientes)
   - hms/admin/index.php (admin)
   - hms/doctor/index.php (doctores)

❌ Contraseñas en diferentes formatos:
   - users: Bcrypt ✅
   - doctors: Bcrypt ✅
   - admin: TEXTO PLANO ❌

❌ Vulnerabilidades críticas:
   - SQL Injection en login
   - No usa password_verify()
   - Campo 'tipo' inexistente en BD
```

**DESPUÉS:**
```
✅ UN SOLO login unificado:
   - hms/login.php (para todos)

✅ Detección automática de rol:
   - Redirige a dashboard según user_type

✅ Seguridad mejorada:
   - Todas las contraseñas en Bcrypt
   - Prepared statements
   - password_verify() implementado
   - Registro de last_login
```

**Archivo creado:** `hms/login.php` (490 líneas)

**Características:**
- Diseño moderno con gradiente
- Responsive (móviles y tablets)
- Mensajes de error claros
- Iconos Font Awesome
- Animaciones suaves

---

#### 2. 🗄️ **Normalización de Base de Datos**

**ANTES:**
```
❌ 3 tablas separadas con datos duplicados:

TABLE users:              TABLE doctors:           TABLE admin:
- id                      - id                     - id
- fullName               - doctorName             - username
- email                  - docEmail               - password
- password               - password
- role (solo pacientes)
```

**DESPUÉS:**
```
✅ Estructura normalizada (3FN):

┌─────────────────────────────┐
│    TABLE: users (PRINCIPAL) │
├─────────────────────────────┤
│ id (PK)                     │
│ email (UNIQUE)              │
│ password (bcrypt)           │
│ user_type (ENUM)            │ ← 'patient','doctor','admin'
│ full_name                   │
│ status                      │ ← 'active','inactive','blocked'
│ created_at                  │
│ updated_at                  │
│ last_login                  │
└─────────────────────────────┘
         │
         ├──────────┬──────────┐
         ▼          ▼          ▼
    ┌────────┐ ┌────────┐ ┌────────┐
    │patients│ │doctors │ │admins  │
    ├────────┤ ├────────┤ ├────────┤
    │user_id │ │user_id │ │user_id │
    │address │ │special.│ │dept.   │
    │city    │ │fees    │ │access  │
    │gender  │ │phone   │ └────────┘
    └────────┘ └────────┘
```

**Ventajas:**
- ✅ Un email = un usuario (sin duplicados)
- ✅ Escalable (fácil agregar roles)
- ✅ Gestión centralizada de contraseñas
- ✅ Cumple 3FN (Tercera Forma Normal)

---

#### 3. 📊 **Migración de Datos Ejecutada**

```sql
-- Datos migrados exitosamente:

✅ 5 Pacientes  (users_old → users)
✅ 9 Doctores   (doctors → users)
✅ 2 Admins     (admin → users)
──────────────────────────────
   16 usuarios totales
```

**Script ejecutado:** `hms/migrate-step-by-step.php`

**Backups creados:**
- `hms/backups/backup_hms_2025-10-12_01-50-41.sql` (21.34 KB)
- Backups automáticos de archivos modificados (*.backup.*)

---

#### 4. 🔒 **Mejoras de Seguridad Implementadas**

##### A. **Migración de Contraseñas Admin a Bcrypt**

**Archivo:** `hms/hash-admin-passwords.php`

```php
// ANTES:
admin:      Test@12345    ❌ Texto plano
nuevoadmin: admin12345    ❌ Texto plano

// DESPUÉS:
admin:      $2y$10$ADbsQzfD...  ✅ Bcrypt
nuevoadmin: $2y$10$mUcOLz3u...  ✅ Bcrypt
```

##### B. **Corrección de SQL Injection**

**ANTES (VULNERABLE):**
```php
$sql = "SELECT * FROM users WHERE email='$username' AND password='$password'";
mysqli_query($con, $sql);  ❌
```

**DESPUÉS (SEGURO):**
```php
$sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($user && password_verify($password, $user['password'])) {
    // Login exitoso ✅
}
```

##### C. **Sanitización de Inputs**

```php
$email = mysqli_real_escape_string($con, trim($_POST['email']));
```

---

#### 5. 🔀 **Actualización de Referencias**

**Archivos actualizados:**

| Archivo | Cambios | Backups |
|---------|---------|---------|
| `index.html` | 3 enlaces actualizados | ✅ |
| `registration.php` | 2 enlaces actualizados | ✅ |
| `forgot-password.php` | 1 enlace actualizado | ✅ |
| `reset-password.php` | 2 enlaces actualizados | ✅ |
| `dashboard1.php` | 1 enlace actualizado | ✅ |
| `doctor/dashboard.php` | URL hardcodeada corregida | ✅ |

**Cambio realizado:**
```html
<!-- ANTES -->
<a href="hms/user-login.php">Iniciar sesión</a>

<!-- DESPUÉS -->
<a href="hms/login.php">Iniciar sesión</a>
```

---

#### 6. 🐛 **Bugs Corregidos**

##### Bug #1: Campo 'tipo' No Existía
```php
// ANTES (línea 77 de user-login.php):
$user_type = $num['tipo'];  ❌ Columna no existe

// DESPUÉS (en login.php):
$user_type = $user['user_type'];  ✅ Correcto
```

##### Bug #2: URL Hardcodeada en Doctor Dashboard
```javascript
// ANTES (doctor/dashboard.php línea 116):
window.location.href = 'http://localhost:8080/hospital56/hospital/hms/user-login.php';  ❌

// DESPUÉS:
window.location.href = '../login.php';  ✅
```

##### Bug #3: No Verificaba Bcrypt
```php
// ANTES:
$sql = "SELECT * WHERE email='$u' AND password='$p'";  ❌

// DESPUÉS:
password_verify($password, $user['password'])  ✅
```

---

## 🗄️ Estructura de la Base de Datos

### Tablas Principales

#### 1. **users** (Nueva - Unificada)
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('patient','doctor','admin') NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    status ENUM('active','inactive','blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);
```

**Datos actuales:**
- 5 Pacientes
- 9 Doctores
- 2 Administradores

#### 2. **patients** (Nueva - Info Específica)
```sql
CREATE TABLE patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    address LONGTEXT,
    city VARCHAR(255),
    gender ENUM('Male','Female','Other'),
    phone VARCHAR(20),
    blood_type VARCHAR(10),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 3. **doctors** (Modificada - Con user_id)
```sql
-- Columna agregada:
ALTER TABLE doctors ADD COLUMN user_id INT;
ALTER TABLE doctors ADD FOREIGN KEY (user_id) REFERENCES users(id);

-- Columnas originales mantenidas:
-- specilization, doctorName, address, docFees, contactno, etc.
```

#### 4. **admins** (Nueva - Info Administrativa)
```sql
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    department VARCHAR(100),
    access_level ENUM('super','standard') DEFAULT 'standard',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 5. **appointment** (Existente)
```sql
-- Tabla para citas médicas
-- Vinculada a users (paciente) y doctors
```

#### 6. **tblmedicalhistory** (Existente)
```sql
-- Historial médico de pacientes
```

#### 7. **userlog, doctorslog** (Existentes)
```sql
-- Logs de acceso básicos
```

#### 8. **users_old** (Backup)
```sql
-- Tabla original renombrada como respaldo
-- NO eliminar hasta verificar que todo funciona
```

### Diagrama ER (Entity-Relationship)

```
     ┌─────────┐
     │  users  │
     └────┬────┘
          │
    ┌─────┼─────┬─────────┐
    │     │     │         │
    ▼     ▼     ▼         ▼
┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐
│patients│ │doctors │ │admins  │ │userlog │
└────────┘ └───┬────┘ └────────┘ └────────┘
               │
               ▼
        ┌──────────────┐
        │ appointment  │
        └──────┬───────┘
               │
               ▼
        ┌──────────────────┐
        │tblmedicalhistory │
        └──────────────────┘
```

---

## 🔧 Instalación y Configuración

### Requisitos Previos

- **XAMPP** (o LAMP/WAMP/MAMP)
  - PHP 7.4 o superior
  - MySQL 5.7 o superior
  - Apache 2.4
- **Composer** (opcional, para dependencias)
- **Navegador** moderno (Chrome, Firefox, Edge)

### Pasos de Instalación

#### 1. **Clonar o Descargar el Proyecto**

```bash
git clone https://github.com/TU_USUARIO/hospital-management-system.git
cd hospital-management-system
```

O descargar ZIP y extraer en `C:\xampp\htdocs\hospital\`

#### 2. **Configurar Base de Datos**

```bash
# Iniciar XAMPP
# Abrir phpMyAdmin: http://localhost/phpmyadmin

# Crear base de datos:
CREATE DATABASE hms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 3. **Importar Estructura de BD**

```bash
# Opción A: Usar backup completo (RECOMENDADO)
mysql -u root -p hms < hms/backups/backup_hms_2025-10-12_01-50-41.sql

# Opción B: Importar desde phpMyAdmin
# - Ir a http://localhost/phpmyadmin
# - Seleccionar BD 'hms'
# - Importar archivo SQL del backup
```

#### 4. **Configurar Conexión a BD**

Editar `hms/include/config.php`:

```php
<?php
define('DB_SERVER','localhost');
define('DB_USER','root');
define('DB_PASS' ,'');  // Tu contraseña de MySQL (vacía por defecto)
define('DB_NAME', 'hms');
$con = mysqli_connect(DB_SERVER,DB_USER,DB_PASS,DB_NAME);
?>
```

#### 5. **Verificar Permisos**

```bash
# En Linux/Mac:
chmod 755 hospital/
chmod 644 hospital/hms/*.php

# En Windows (XAMPP):
# No requiere permisos especiales
```

#### 6. **Acceder al Sistema**

Abrir navegador en:
```
http://localhost/hospital/index.html
```

---

## 📖 Guía de Uso

### 🔐 Credenciales de Acceso

#### Pacientes
```
Email: test@gmail.com
Password: Hospital@2024

Email: rahul@gmail.com
Password: Hospital@2024

Email: amit12@gmail.com
Password: Hospital@2024
```

#### Doctores
```
Email: anuj.lpu1@gmail.com
Password: Hospital@2024

Email: sarita@gmail.com
Password: Hospital@2024

Email: nitesh@gmail.com
Password: Hospital@2024
```

#### Administradores
```
Email: admin@hospital.com
Password: Test@12345

Email: nuevoadmin@hospital.com
Password: admin12345
```

### 🚀 Flujo de Uso

#### Para Pacientes:

1. **Registrarse:**
   - Ir a: `http://localhost/hospital/index.html`
   - Click en "Iniciar Sesión" o "Pacientes"
   - Click en "Regístrese aquí"
   - Completar formulario

2. **Iniciar Sesión:**
   - Email y contraseña
   - El sistema detecta automáticamente que eres paciente
   - Redirige a tu dashboard

3. **Agendar Cita:**
   - Dashboard → "Book Appointment"
   - Seleccionar especialidad
   - Seleccionar doctor
   - Elegir fecha y hora
   - Describir síntomas

4. **Ver Historial:**
   - Dashboard → "Appointment History"
   - Ver estado de citas
   - Cancelar citas si es necesario

#### Para Doctores:

1. **Iniciar Sesión:**
   - Email y contraseña
   - Redirige a dashboard doctor

2. **Ver Citas:**
   - Dashboard → Lista de citas asignadas
   - Confirmar o rechazar citas

3. **Gestionar Pacientes:**
   - Ver lista de pacientes
   - Agregar historial médico
   - Ver detalles completos

#### Para Administradores:

1. **Iniciar Sesión:**
   - Email y contraseña
   - Redirige a dashboard admin

2. **Gestionar Sistema:**
   - Agregar/editar doctores
   - Ver todos los usuarios
   - Generar reportes
   - Ver logs del sistema

---

## ⚠️ Problemas Identificados y Pendientes

### 🔴 CRÍTICOS (Requieren atención inmediata)

#### 1. **Sin Validación de Complejidad de Contraseñas**

**Estado Actual:**
- ✅ Bcrypt implementado
- ❌ No valida longitud mínima
- ❌ No valida complejidad (mayúsculas, números, especiales)
- ❌ No hay histórico de contraseñas
- ❌ Sin expiración de contraseñas

**Pendiente:** Implementar gestión completa de contraseñas

---

#### 2. **Sin Sistema de Roles Granular**

**Estado Actual:**
- ✅ Columna `user_type` existe
- ✅ Separación básica por tipo
- ❌ No hay gestión de permisos
- ❌ No hay matriz de accesos
- ❌ No se puede asignar roles dinámicamente

**Pendiente:** Crear tablas `roles`, `permissions`, `role_permissions`

---

### 🟡 MEDIOS (Importantes pero no bloquean el sistema)

#### 3. **Sin Protección CSRF**

**Riesgo:** Formularios vulnerables a Cross-Site Request Forgery

**Solución:**
```php
// Generar token:
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// En formularios:
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

// Validar:
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF token invalid');
}
```

---

#### 4. **Sanitización XSS Incompleta**

**Riesgo:** Posible inyección de scripts maliciosos

**Solución:**
```php
function safe_output($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Usar en todas las salidas:
echo safe_output($user_input);
```

---

#### 5. **Sin Bloqueo por Intentos Fallidos**

**Estado Actual:**
- ❌ Permite intentos ilimitados de login
- ❌ Sin detección de fuerza bruta

**Solución Pendiente:**
- Crear tabla `login_attempts`
- Bloquear cuenta después de 3 intentos
- Auto-desbloqueo después de 15 minutos

---

### 🟢 BAJOS (Mejoras opcionales)

#### 6. **Logs de Seguridad Básicos**

**Estado Actual:**
- ✅ Tablas `userlog` y `doctorslog` existen
- ❌ No registran acciones críticas
- ❌ No registran cambios en datos sensibles

**Mejora Sugerida:**
```sql
CREATE TABLE security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

#### 7. **Sin Timeout de Sesión**

**Riesgo:** Sesiones permanecen activas indefinidamente

**Solución:**
```php
// En checklogin.php:
$timeout_duration = 1800; // 30 minutos

if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("location:login.php");
    exit();
}
$_SESSION['last_activity'] = time();
```

---

## 🎯 Próximos Pasos

### Fase 1: Corrección de Bugs Críticos (Prioridad Alta)

- [ ] **Implementar gestión completa de contraseñas**
  - Validación de complejidad
  - Tabla `password_history`
  - Bloqueo al 3er intento
  - Sistema de desbloqueo

- [ ] **Crear sistema de roles y permisos**
  - Tablas: `roles`, `permissions`, `role_permissions`
  - Matriz de accesos
  - Función `hasPermission()`
  - Módulo de gestión de roles (CRUD)

### Fase 2: Mejoras de Seguridad (Prioridad Media)

- [ ] **Implementar protección CSRF**
  - Generar tokens
  - Validar en todos los formularios

- [ ] **Sanitizar salidas (XSS)**
  - Crear función `safe_output()`
  - Aplicar en todas las vistas

- [ ] **Agregar timeout de sesión**
  - 30 minutos de inactividad

- [ ] **Logs de seguridad completos**
  - Tabla `security_logs`
  - Registrar acciones críticas

### Fase 3: Corrección de Vulnerabilidades OWASP (Prioridad Media-Alta)

- [ ] **A01: Broken Access Control**
  - Verificar permisos en todas las páginas
  - Implementar sistema de autorización

- [ ] **A03: Injection**
  - ✅ SQL Injection corregido
  - [ ] Verificar otros puntos de entrada

- [ ] **A05: Security Misconfiguration**
  - Desactivar `display_errors` en producción
  - Ocultar versión de PHP
  - Configurar headers de seguridad

- [ ] **A07: Authentication Failures**
  - ✅ Bcrypt implementado
  - [ ] Agregar 2FA (opcional)
  - [ ] Implementar bloqueo de cuentas

### Fase 4: Testing y Documentación (Prioridad Baja)

- [ ] **Testing completo**
  - Probar todas las 35 vistas
  - Verificar flujos completos
  - Pruebas de seguridad (OWASP ZAP, Nikto)

- [ ] **Documentación del proyecto**
  - Completar puntos 1-10 del informe
  - Capturas de pantalla
  - Diagramas de flujo
  - Manual de usuario

- [ ] **Optimización**
  - Refactorizar código repetido
  - Mejorar performance de consultas
  - Comprimir assets (CSS/JS)

---

## 🤝 Contribución

### Convenciones de Código

#### PHP
```php
// Nombres de archivos: kebab-case
// manage-users.php ✅
// ManageUsers.php ❌

// Nombres de funciones: camelCase
function validatePassword($password) { }  ✅
function validate_password($password) { }  ❌

// Nombres de clases: PascalCase
class UserManager { }  ✅

// Constantes: UPPER_CASE
define('MAX_LOGIN_ATTEMPTS', 3);  ✅
```

#### Base de Datos
```sql
-- Nombres de tablas: snake_case singular/plural según contexto
users ✅
user_roles ✅

-- Nombres de columnas: snake_case
user_id ✅
userId ❌

-- Claves foráneas: tabla_id
user_id, doctor_id, appointment_id ✅
```

#### JavaScript
```javascript
// Variables: camelCase
const userName = 'John';  ✅

// Constantes: UPPER_CASE
const MAX_ATTEMPTS = 3;  ✅

// Funciones: camelCase
function validateForm() { }  ✅
```

### Flujo de Trabajo Git

```bash
# 1. Crear rama para nueva funcionalidad
git checkout -b feature/nombre-funcionalidad

# 2. Hacer commits descriptivos
git commit -m "feat: agregar validación de contraseñas"
git commit -m "fix: corregir dashboard de doctor"
git commit -m "docs: actualizar README con nuevas credenciales"

# 3. Push a GitHub
git push origin feature/nombre-funcionalidad

# 4. Crear Pull Request
# Describir cambios realizados
# Mencionar issues resueltos
```

### Prefijos de Commits

- `feat:` Nueva funcionalidad
- `fix:` Corrección de bug
- `docs:` Cambios en documentación
- `style:` Formato de código (sin cambios funcionales)
- `refactor:` Refactorización de código
- `test:` Agregar o modificar tests
- `chore:` Tareas de mantenimiento

---

## 👥 Créditos

### Desarrollador Original
- **[Tu Amigo]** - Desarrollo inicial del sistema (PHP procedural)
- Implementación de funcionalidades core
- Diseño de base de datos original

### Colaboradores
- **[Tu Nombre]** - Refactorización de seguridad (Oct 2025)
  - Login unificado
  - Normalización de BD
  - Migración a Bcrypt
  - Corrección de vulnerabilidades

### Agradecimientos
- **Claude AI** (Anthropic) - Asistencia en análisis y refactorización
- **Bootstrap** - Framework CSS
- **Font Awesome** - Iconografía
- **OWASP** - Guías de seguridad

---

## 📜 Licencia

Este proyecto está bajo la Licencia MIT.

```
MIT License

Copyright (c) 2025 [Tu Nombre/Organización]

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 📞 Contacto y Soporte

### Reportar Bugs
- Crear un issue en GitHub con:
  - Descripción detallada del problema
  - Pasos para reproducir
  - Capturas de pantalla (si aplica)
  - Navegador y versión

### Solicitar Funcionalidades
- Crear un issue con etiqueta `enhancement`
- Describir la funcionalidad deseada
- Explicar el caso de uso

### Documentación Adicional

Revisa estos archivos en la carpeta **`docs/`**:

1. **[RESUMEN_PROYECTO_SEGURIDAD.md](docs/RESUMEN_PROYECTO_SEGURIDAD.md)**
   - Plan completo del proyecto de seguridad
   - Requisitos del informe (puntos 1-16)
   - Checklist de implementación
   - Estado de cada punto del proyecto SIS 321

2. **[FLUJO_COMPLETO_VISTAS.md](docs/FLUJO_COMPLETO_VISTAS.md)**
   - Mapa visual de todas las 35 vistas del sistema
   - Flujo de navegación por rol
   - Referencias actualizadas
   - Diagramas de arquitectura

3. **[INFORME_VISTAS_Y_PROBLEMAS.md](docs/INFORME_VISTAS_Y_PROBLEMAS.md)**
   - Análisis detallado de cada vista
   - Estado funcional actual (funcional/parcial/no funcional)
   - Problemas específicos identificados
   - Plan de corrección sugerido

4. **[ANALISIS_LOGIN_UNIFICADO.md](docs/ANALISIS_LOGIN_UNIFICADO.md)**
   - Decisiones de arquitectura (Opción A vs B)
   - Comparación antes/después
   - Proceso de implementación completo
   - Lecciones aprendidas

5. **[RESUMEN_SESION_LOGIN_UNIFICADO.md](docs/RESUMEN_SESION_LOGIN_UNIFICADO.md)**
   - Bitácora completa de cambios
   - Cronología de trabajo
   - Archivos modificados
   - Backups creados
   - Estadísticas completas

---

## 🔄 Changelog

### [2.0.2] - 2025-10-15 (Corrección de Dashboards)

#### Fixed (v2.0.2)

- ✅ **Dashboard de pacientes en proyecto `hms` renderizando correctamente**
  - Corregida consulta SQL en `include/header.php` (cambio de `fullName` a `full_name`)
  - Problema: Columna inexistente causaba fallo silencioso que impedía renderizado

- ✅ **Dashboards de admin y doctor en proyecto `hms-t` ahora funcionales**
  - Corregida configuración de base de datos (puerto 3307→3306, base de datos `hms1`→`hms`)
  - Corregidas variables de sesión (`$_SESSION['dlogin']` para doctores)
  - Agregado `checklogin.php` en dashboard de doctor

#### Changed (v2.0.2)

- ✅ Actualizado `hms/include/header.php` - query corregida
- ✅ Actualizado `hms-t/admin/include/config.php` - conexión BD corregida
- ✅ Actualizado `hms-t/doctor/include/config.php` - conexión BD corregida
- ✅ Actualizado `hms-t/user-login.php` - sesiones por tipo de usuario
- ✅ Actualizado `hms-t/doctor/dashboard.php` - agregado sistema de autenticación

#### Details (v2.0.2)

**Problema Identificado:**
1. En proyecto `hms`: Query SQL buscaba columna `fullName` pero la tabla usa `full_name`
2. En proyecto `hms-t`: Configuración de BD apuntaba a puerto y base de datos incorrectos
3. En proyecto `hms-t`: Sesiones no se establecían correctamente para doctores

**Impacto:**
- Los dashboards se cargaban pero mostraban páginas en blanco
- La consulta fallaba silenciosamente debido a `error_reporting(0)`
- Conexión a BD rechazada por puerto incorrecto (3307 vs 3306)

**Solución:**
- Actualizada query en header.php línea 35-38
- Corregida configuración de BD en ambos proyectos
- Implementado sistema de sesiones diferenciado por rol

---

### [2.0.1] - 2025-10-12 (Refactorización y Limpieza)

#### Added (v2.0.1)

- ✅ Carpeta `docs/` para documentación centralizada
- ✅ Carpeta `migration-scripts/` para scripts históricos
- ✅ 5 documentos markdown completos (~5,000 líneas)

#### Changed (v2.0.1)

- ✅ Reorganización de archivos backup en `hms/backups/`
- ✅ Estructura de proyecto más profesional y limpia

#### Removed (v2.0.1)

- ✅ `hms/user-login.php` (obsoleto)
- ✅ `hms/admin/index.php` (obsoleto)
- ✅ `hms/doctor/index.php` (obsoleto)
- ✅ `SQL File/` directorio completo (2 archivos SQL antiguos)

---

### [2.0.0] - 2025-10-12 (Refactorización Mayor)

#### Added (v2.0.0)

- ✅ Login unificado (`login.php`)
- ✅ Tabla `users` normalizada
- ✅ Tabla `patients` para info específica
- ✅ Tabla `admins` para info administrativa
- ✅ Columna `user_id` en tabla `doctors`
- ✅ Sistema de backups automáticos
- ✅ Documentación completa del proyecto

#### Changed
- ✅ Migración de MD5 a Bcrypt (admin)
- ✅ Referencias de login actualizadas (6 archivos)
- ✅ Estructura de BD normalizada a 3FN
- ✅ 16 usuarios migrados a nueva estructura

#### Fixed
- ✅ SQL Injection en login
- ✅ Campo `tipo` inexistente
- ✅ Contraseñas admin en texto plano
- ✅ URL hardcodeada en doctor/dashboard.php
- ✅ No verificaba bcrypt en autenticación

#### Security
- ✅ Prepared statements implementados
- ✅ password_verify() en login
- ✅ Sanitización de inputs
- ✅ Registro de last_login

### [1.0.0] - 2024 (Versión Original)

#### Added
- ✅ Sistema completo de gestión hospitalaria
- ✅ 35 vistas implementadas
- ✅ 3 roles (Paciente, Doctor, Admin)
- ✅ Sistema de citas médicas
- ✅ Gestión de pacientes y doctores
- ✅ Historial médico
- ✅ Reportes básicos
- ✅ Logs de acceso

---

## 📚 Recursos Adicionales

### Tecnologías Utilizadas

- [PHP Manual](https://www.php.net/manual/es/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Bootstrap 4 Docs](https://getbootstrap.com/docs/4.5/)
- [jQuery Documentation](https://api.jquery.com/)

### Seguridad

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://www.php.net/manual/es/security.php)
- [Password Hashing](https://www.php.net/manual/es/function.password-hash.php)

### Base de Datos

- [Database Normalization](https://en.wikipedia.org/wiki/Database_normalization)
- [MySQL Best Practices](https://dev.mysql.com/doc/refman/8.0/en/best-practices.html)

---

## ⭐ Agradecimientos

Si este proyecto te resultó útil, considera:
- ⭐ Dar una estrella en GitHub
- 🍴 Hacer un fork y contribuir
- 📢 Compartir con otros desarrolladores
- 💬 Reportar bugs o sugerir mejoras

---

**Desarrollado con ❤️ para la Clínica Dental Muelitas**

**Última actualización:** 12 de Octubre, 2025

**Versión:** 2.0.0 (Refactorización de Seguridad)

---

