# 🔐 Guía de Uso: Sistema RBAC (Role-Based Access Control)

## 📖 Índice
1. [Introducción](#introducción)
2. [Instalación](#instalación)
3. [Conceptos Básicos](#conceptos-básicos)
4. [Uso en Páginas PHP](#uso-en-páginas-php)
5. [Funciones Disponibles](#funciones-disponibles)
6. [Ejemplos Prácticos](#ejemplos-prácticos)
7. [Gestión de Roles y Permisos](#gestión-de-roles-y-permisos)
8. [Auditoría y Logs](#auditoría-y-logs)

---

## 🎯 Introducción

El sistema RBAC implementado en HMS permite controlar el acceso a recursos del sistema mediante:
- **Roles**: Agrupación de permisos (ej: Admin, Doctor, Patient)
- **Permisos**: Acciones específicas (ej: view_patients, edit_appointments)
- **Asignación Flexible**: Un usuario puede tener múltiples roles

### Ventajas
✅ **Seguridad Granular**: Control preciso de qué puede hacer cada usuario
✅ **Mantenibilidad**: Fácil agregar/modificar permisos
✅ **Auditoría**: Registro completo de cambios de roles
✅ **Performance**: Sistema de caché de permisos

---

## 🚀 Instalación

### Paso 1: Ejecutar Migraciones

```bash
# En phpMyAdmin o MySQL CLI
mysql -u root -p hms_v2 < database/migrations/003_rbac_system.sql
mysql -u root -p hms_v2 < database/migrations/004_security_logs.sql
```

### Paso 2: Poblar Datos Iniciales

```bash
mysql -u root -p hms_v2 < database/seeds/003_default_roles_permissions.sql
```

### Paso 3: Verificar Instalación

```sql
-- Ver roles creados
SELECT * FROM roles;

-- Ver permisos por rol
SELECT r.display_name AS Rol, COUNT(rp.permission_id) AS Permisos
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.display_name;
```

---

## 📚 Conceptos Básicos

### Roles Predefinidos

| Rol | Prioridad | Descripción |
|-----|-----------|-------------|
| **Super Admin** | 1 | Acceso total al sistema |
| **Admin** | 10 | Gestión general (sin config crítica) |
| **Doctor** | 20 | Gestión de pacientes y citas |
| **Receptionist** | 30 | Citas y registro de pacientes |
| **Nurse** | 25 | Asistencia médica |
| **Patient** | 40 | Acceso limitado a sus datos |
| **Lab Technician** | 35 | Resultados de laboratorio |

### Categorías de Permisos

- **users**: Gestión de usuarios
- **patients**: Gestión de pacientes
- **doctors**: Gestión de doctores
- **appointments**: Gestión de citas
- **medical_records**: Historiales médicos
- **billing**: Facturación
- **reports**: Reportes
- **system**: Configuración del sistema
- **security**: Auditoría y seguridad

---

## 💻 Uso en Páginas PHP

### Método 1: Proteger Página Completa (Recomendado)

```php
<?php
session_start();
require_once('include/config.php');
require_once('include/permission-check.php');

// Requiere permiso específico
requirePermission('view_patients');

// O requiere un rol
requireRole('doctor');

// O requiere al menos uno de varios roles
requireAnyRole(['admin', 'doctor', 'receptionist']);
?>

<!DOCTYPE html>
<html>
<!-- Tu contenido HTML aquí -->
</html>
```

### Método 2: Verificar Sin Redireccionar

```php
<?php
// Verificar permiso sin redireccionar
if (hasPermission('edit_patients')) {
    echo '<button>Editar Paciente</button>';
} else {
    echo '<p>No tienes permiso para editar</p>';
}
?>
```

### Método 3: Mostrar Elementos Condicionalmente

```php
<!-- Mostrar botón solo si tiene permiso -->
<?php showIfHasPermission('delete_patient', '<button class="btn-danger">Eliminar</button>'); ?>

<!-- Deshabilitar input si no tiene permiso -->
<input type="text" name="diagnosis" <?php disableIfNoPermission('edit_medical_record'); ?>>
```

---

## 🛠️ Funciones Disponibles

### Funciones de Verificación

#### `hasPermission($permission_name, $user_id = null, $connection = null)`
```php
// Verificar si usuario tiene permiso
if (hasPermission('view_patients')) {
    // Mostrar lista de pacientes
}
```

#### `hasRole($role_name, $user_id = null, $connection = null)`
```php
// Verificar si usuario tiene un rol
if (hasRole('admin')) {
    // Mostrar panel de administración
}
```

#### `isSuperAdmin($user_id = null)`
```php
if (isSuperAdmin()) {
    echo "¡Eres el Super Admin!";
}
```

#### `isAdmin($user_id = null)`
```php
// Verifica si es Super Admin O Admin
if (isAdmin()) {
    echo "Panel de administración";
}
```

### Funciones Middleware (Protección de Páginas)

#### `requirePermission($permission_name, $redirect_url = null, $die = true)`
```php
// Requiere permiso, redirecciona si no lo tiene
requirePermission('manage_users');
```

#### `requireRole($role_name, $redirect_url = null, $die = true)`
```php
// Requiere rol específico
requireRole('doctor', 'dashboard.php');
```

#### `requireAnyRole($role_names, $redirect_url = null, $die = true)`
```php
// Requiere al menos uno de los roles
requireAnyRole(['admin', 'doctor', 'receptionist']);
```

#### `requireOwnDataOrPermission($resource_owner_id, $permission_override, $redirect_url = null)`
```php
// Permite acceder solo a datos propios O si tiene permiso especial
$patient_id = $_GET['id'];
requireOwnDataOrPermission($patient_id, 'view_all_patients');
```

### Funciones de Gestión

#### `getUserPermissions($user_id = null)`
```php
// Obtener todos los permisos del usuario
$permissions = getUserPermissions();
print_r($permissions);
// Output: ['view_patients', 'create_appointment', ...]
```

#### `getUserRoles($user_id = null)`
```php
// Obtener todos los roles del usuario
$roles = getUserRoles();
foreach ($roles as $role) {
    echo $role['display_name'] . '<br>';
}
```

### Funciones Helper para Vistas

#### `showIfHasPermission($permission_name, $html)`
```php
<?php showIfHasPermission('delete_user', '<button>Eliminar Usuario</button>'); ?>
```

#### `showIfHasRole($role_name, $html)`
```php
<?php showIfHasRole('admin', '<a href="admin-panel.php">Panel Admin</a>'); ?>
```

#### `disableIfNoPermission($permission_name)`
```php
<button <?php disableIfNoPermission('approve_appointment'); ?>>
    Aprobar Cita
</button>
```

---

## 📝 Ejemplos Prácticos

### Ejemplo 1: Página de Gestión de Pacientes

```php
<?php
session_start();
require_once('include/config.php');
require_once('include/permission-check.php');

// Solo usuarios con permiso view_patients pueden acceder
requirePermission('view_patients');

// Obtener lista de pacientes
$query = "SELECT * FROM patients ORDER BY created_at DESC";
$result = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Pacientes</title>
</head>
<body>
    <h1>Lista de Pacientes</h1>

    <!-- Botón de crear solo visible si tiene permiso -->
    <?php showIfHasPermission('create_patient', '
        <a href="add-patient.php" class="btn btn-primary">Nuevo Paciente</a>
    '); ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td>
                    <!-- Mostrar botones según permisos -->
                    <a href="view-patient.php?id=<?php echo $row['id']; ?>">Ver</a>

                    <?php if (hasPermission('edit_patient')): ?>
                        <a href="edit-patient.php?id=<?php echo $row['id']; ?>">Editar</a>
                    <?php endif; ?>

                    <?php if (hasPermission('delete_patient')): ?>
                        <a href="delete-patient.php?id=<?php echo $row['id']; ?>"
                           onclick="return confirm('¿Eliminar?')">Eliminar</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
```

### Ejemplo 2: Formulario de Edición de Paciente

```php
<?php
session_start();
require_once('include/config.php');
require_once('include/permission-check.php');

// Requiere permiso de edición
requirePermission('edit_patient');

$patient_id = $_GET['id'];

// Verificar acceso: Solo puede editar sus propios datos O tener permiso view_all_patients
requireOwnDataOrPermission($patient_id, 'view_all_patients');

// ... resto del código
?>
```

### Ejemplo 3: Dashboard con Widgets Condicionales

```php
<?php
session_start();
require_once('include/config.php');
require_once('include/permission-check.php');

requireLogin();
?>

<div class="dashboard">
    <!-- Widget visible solo para admins -->
    <?php if (hasRole('admin')): ?>
        <div class="widget">
            <h3>Panel de Administración</h3>
            <a href="manage-users.php">Gestionar Usuarios</a>
        </div>
    <?php endif; ?>

    <!-- Widget visible para doctores y admins -->
    <?php if (hasPermission('view_appointments')): ?>
        <div class="widget">
            <h3>Mis Citas</h3>
            <!-- Contenido -->
        </div>
    <?php endif; ?>

    <!-- Widget visible para todos -->
    <div class="widget">
        <h3>Mi Perfil</h3>
        <!-- Contenido -->
    </div>
</div>
```

### Ejemplo 4: Asignar Rol a Usuario (Admin)

```php
<?php
session_start();
require_once('include/config.php');
require_once('include/rbac-functions.php');
require_once('include/permission-check.php');

// Solo admins pueden asignar roles
requirePermission('manage_user_roles');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $role_id = $_POST['role_id'];
    $admin_id = $_SESSION['id'];

    $rbac = new RBAC($con);
    $result = $rbac->assignRoleToUser($user_id, $role_id, $admin_id);

    if ($result['success']) {
        echo "✓ " . $result['message'];
    } else {
        echo "✗ " . $result['message'];
    }
}
?>
```

---

## 🔧 Gestión de Roles y Permisos

### Ver Todos los Roles

```php
$rbac = new RBAC($con);
$roles = $rbac->getAllRoles();

foreach ($roles as $role) {
    echo $role['display_name'] . " (Prioridad: " . $role['priority'] . ")<br>";
}
```

### Ver Permisos de un Rol

```php
$rbac = new RBAC($con);
$role_id = 3; // ID del rol Doctor

$permissions = $rbac->getRolePermissions($role_id);

foreach ($permissions as $perm) {
    echo "- " . $perm['display_name'] . "<br>";
}
```

### Ver Permisos de un Usuario

```php
$rbac = new RBAC($con);
$user_id = $_SESSION['id'];

$permissions = $rbac->getUserPermissions($user_id);

echo "Tienes " . count($permissions) . " permisos:<br>";
foreach ($permissions as $perm) {
    echo "- " . $perm . "<br>";
}
```

---

## 📊 Auditoría y Logs

### Ver Intentos de Acceso No Autorizado

```sql
-- Últimos intentos de acceso denegado
SELECT
    u.email,
    sl.event_description,
    sl.ip_address,
    sl.created_at
FROM security_logs sl
INNER JOIN users u ON sl.user_id = u.id
WHERE sl.event_type = 'unauthorized_access'
ORDER BY sl.created_at DESC
LIMIT 50;
```

### Ver Cambios de Roles

```sql
-- Historial de cambios de roles
SELECT
    u.email AS usuario_afectado,
    r.display_name AS rol,
    arc.action AS accion,
    admin.email AS realizado_por,
    arc.performed_at AS fecha
FROM audit_role_changes arc
INNER JOIN users u ON arc.user_id = u.id
INNER JOIN roles r ON arc.role_id = r.id
LEFT JOIN users admin ON arc.performed_by = admin.id
ORDER BY arc.performed_at DESC;
```

---

## 🎨 Buenas Prácticas

### ✅ DO (Hacer)
- Verificar permisos en el backend (PHP), no solo en frontend
- Usar `requirePermission()` al inicio de cada página protegida
- Implementar logs de auditoría para acciones críticas
- Usar permisos granulares (ej: `edit_patient` en vez de solo `edit`)

### ❌ DON'T (No Hacer)
- No confiar solo en ocultar botones (seguridad por oscuridad)
- No hardcodear roles en el código (usar permisos)
- No permitir acceso directo a páginas sin verificación
- No reutilizar sesiones sin verificar permisos

---

## 📞 Soporte

Para dudas o problemas:
- Ver código fuente: `hms/include/rbac-functions.php`
- Revisar logs: Tabla `security_logs`
- Contactar: Equipo SIS 321

---

**Versión**: 2.2.0
**Fecha**: 2025-10-20
**Proyecto**: SIS 321 - Seguridad de Sistemas
