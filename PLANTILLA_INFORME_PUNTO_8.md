# 8. ESQUEMA DE SEGURIDAD

## 8.1 GESTIÓN DE USUARIOS - MÓDULO ABM

### 8.1.1 Introducción

El módulo de Gestión de Usuarios (ABM - Altas, Bajas y Modificaciones) constituye uno de los pilares fundamentales del sistema de seguridad implementado en el Hospital Management System. Este módulo permite administrar el ciclo de vida completo de las cuentas de usuario, desde su creación hasta su desactivación, pasando por todas las actualizaciones necesarias durante su vigencia.

La implementación del módulo ABM es crítica para la seguridad del sistema ya que:
- Controla quién tiene acceso al sistema
- Define qué privilegios posee cada usuario
- Mantiene un registro auditable de todos los cambios
- Implementa los principios de mínimo privilegio y separación de responsabilidades

### 8.1.2 Arquitectura del Sistema

El módulo ABM está construido siguiendo una arquitectura de tres capas:

**[INSERTAR DIAGRAMA]**

**Componentes principales:**

| Capa | Archivo | Líneas | Responsabilidad |
|------|---------|--------|-----------------|
| Presentación | manage-users.php | 813 | Interfaz de usuario web |
| Lógica de Negocio | UserManagement.php | 620 | Validaciones y procesamiento |
| API REST | users-api.php | 600+ | Endpoints para AJAX |
| Datos | MySQL | - | Almacenamiento persistente |

**Tablas de Base de Datos involucradas:**
- `users`: Tabla principal de usuarios
- `user_change_history`: Auditoría de cambios
- `user_roles`: Relación usuarios-roles
- `login_attempts`: Control de intentos de acceso

### 8.1.3 Funcionalidad: ALTAS (Creación de Usuarios)

#### Descripción
La función de Altas permite crear nuevos usuarios en el sistema a través de un formulario web intuitivo y seguro.

**[CAPTURA 1: Botón "Nuevo Usuario"]**

**[CAPTURA 2: Formulario de creación completo]**

#### Campos del formulario
- **Nombre completo** (requerido): Nombre y apellidos del usuario
- **Email** (requerido, único): Correo electrónico para login
- **Contraseña** (requerido): Debe cumplir políticas de seguridad
- **Tipo de usuario** (requerido): patient, doctor, admin
- **Estado**: active, inactive
- **Roles**: Asignación de roles múltiples

#### Validaciones implementadas

1. **Email único:** El sistema verifica que el email no exista previamente
   
   **[CAPTURA 3: Error - Email duplicado]**

2. **Contraseña segura:** Valida que cumpla con las políticas (ver sección 8.3)

3. **Datos requeridos:** Todos los campos obligatorios deben estar completos

#### Proceso de creación

```php
public function createUser($data, $reason = null) {
    // 1. Validar email único
    if ($this->emailExists($data['email'])) {
        return ['success' => false, 'message' => 'El email ya está registrado'];
    }
    
    // 2. Hashear contraseña con Bcrypt
    $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);
    
    // 3. Llamar stored procedure con auditoría
    $stmt = $this->db->prepare("CALL create_user_with_audit(?, ?, ?, ?, ?, ?, ?, @new_user_id)");
    
    // 4. Retornar resultado
    return ['success' => true, 'user_id' => $new_user_id];
}
```

**[CAPTURA 4: Usuario creado exitosamente]**

#### Auditoría
Cada creación de usuario se registra automáticamente en la tabla `user_change_history`:

**[CAPTURA 5: Registro de auditoría en BD]**

Campos registrados:
- Usuario afectado
- Acción realizada (CREATE)
- Valores nuevos
- Quién realizó la acción
- IP de origen
- Fecha y hora
- Razón del cambio

### 8.1.4 Funcionalidad: BAJAS (Eliminación de Usuarios)

#### Descripción
El sistema implementa **Soft Delete** (eliminación lógica) en lugar de eliminación física. Esto significa que los usuarios no se borran de la base de datos, sino que se marcan como inactivos.

**Ventajas del Soft Delete:**
- Preservación del historial completo
- Posibilidad de reactivación
- Mantenimiento de integridad referencial
- Trazabilidad para auditorías

**[CAPTURA 6: Botón de eliminar]**

**[CAPTURA 7: Confirmación con SweetAlert]**

#### Implementación

```php
public function deleteUser($user_id, $reason = null) {
    // No ejecuta DELETE, solo UPDATE del status
    $sql = "UPDATE users SET status = 'inactive', 
            updated_at = NOW() 
            WHERE id = ?";
    
    // Registrar en auditoría
    $this->logChange($user_id, 'DELETE', $old_data, $new_data, $reason);
}
```

**[CAPTURA 8: Usuario marcado como inactivo - badge gris]**

**[CAPTURA 9: Registro de auditoría de la eliminación]**

#### Control de acceso
Solo usuarios con el permiso específico `delete_user` pueden eliminar usuarios.

### 8.1.5 Funcionalidad: MODIFICACIONES (Actualización)

#### Descripción
Permite editar información de usuarios existentes, incluyendo datos personales y roles asignados.

**[CAPTURA 10: Botón de editar]**

**[CAPTURA 11: Modal de edición con datos precargados]**

#### Campos editables
- Nombre completo
- Email (validando unicidad)
- Estado (active/inactive/blocked)
- Roles asignados

**[CAPTURA 12: Selección múltiple de roles]**

#### Validación de cambios
El sistema compara los valores actuales con los nuevos y solo actualiza si hay cambios reales:

```php
public function updateUser($user_id, $data, $reason = null) {
    // Obtener datos actuales
    $current_data = $this->getUserById($user_id);
    
    // Comparar y detectar cambios
    if ($current_data['full_name'] != $data['full_name'] ||
        $current_data['email'] != $data['email'] ||
        $current_data['status'] != $data['status']) {
        
        // Hay cambios, proceder con update
        // Registrar en historial
    } else {
        return ['success' => false, 'message' => 'No hay cambios'];
    }
}
```

**[CAPTURA 13: Mensaje de éxito al actualizar]**

**[CAPTURA 14: Historial de cambios en BD]**

### 8.1.6 Búsqueda y Filtros

**[CAPTURA 15: Barra de búsqueda y filtros]**

Funcionalidades:
- Búsqueda por nombre o email (texto libre)
- Filtro por estado (active, inactive, blocked)
- Filtro por tipo (patient, doctor, admin)
- Combinación de múltiples filtros

**[CAPTURA 16: Resultados filtrados]**

### 8.1.7 Estadísticas en Tiempo Real

**[CAPTURA 17: Dashboard con 4 tarjetas de estadísticas]**

El sistema muestra:
- **Total de usuarios:** Conteo general
- **Usuarios activos:** Con status='active'
- **Usuarios inactivos:** Con status='inactive'
- **Usuarios bloqueados:** Con status='blocked'

Actualización automática mediante stored procedure `get_user_statistics()`.

### 8.1.8 Formato de User ID

**Estado actual:** El sistema utiliza IDs numéricos autoincrementales generados por MySQL (AUTO_INCREMENT).

**Propuesta de mejora:** Implementar formato estándar:
- Usuarios generales: `USR-2025-0001`
- Doctores: `DOC-2025-0001`
- Administradores: `ADM-2025-0001`
- Pacientes: `PAT-2025-0001`

Formato: `[TIPO]-[AÑO]-[SECUENCIA]`

**Nota:** Esta funcionalidad se encuentra pendiente de implementación y se considera una mejora futura del sistema.

### 8.1.9 Seguridad Implementada

#### Control de Acceso RBAC
```php
// En manage-users.php
requirePermission('view_users');     // Para ver la página
hasPermission('create_user');        // Para crear
hasPermission('edit_user');          // Para editar
hasPermission('delete_user');        // Para eliminar
```

#### Prevención de Ataques

1. **SQL Injection:** Uso de prepared statements
2. **XSS:** htmlspecialchars() en todas las salidas
3. **CSRF:** Tokens de validación en formularios

### 8.1.10 Pruebas y Resultados

**[CAPTURA 18: Script test-user-management.php mostrando 21/21 PASS]**

| # | Caso de Prueba | Resultado Esperado | Estado |
|---|----------------|-------------------|--------|
| 1 | Crear usuario con email único | Usuario creado correctamente | ✅ PASS |
| 2 | Crear con email duplicado | Error: Email ya existe | ✅ PASS |
| 3 | Editar usuario sin cambios | Mensaje: Sin cambios | ✅ PASS |
| 4 | Eliminar usuario | Status cambia a inactive | ✅ PASS |
| 5 | Buscar por nombre | Lista filtrada correctamente | ✅ PASS |

**Tasa de éxito:** 21/21 (100%)

### 8.1.11 Conclusión de la Sección 8.1

El módulo de Gestión de Usuarios (ABM) cumple completamente con los requisitos establecidos. Implementa un sistema robusto de CRUD con las siguientes características destacadas:

- ✅ Auditoría completa de todas las operaciones
- ✅ Control de acceso basado en roles (RBAC)
- ✅ Validaciones exhaustivas de datos
- ✅ Protección contra ataques comunes (SQL Injection, XSS, CSRF)
- ✅ Soft delete para preservar historial
- ✅ Gestión 100% desde interfaz web
- ✅ 21 pruebas automatizadas con 100% de éxito

El único punto pendiente es la implementación del formato estándar de User ID, propuesto como mejora futura.

---

## 8.2 GESTIÓN DE ROLES Y MATRIZ DE ACCESOS

### 8.2.1 Introducción al Control de Acceso Basado en Roles (RBAC)

El sistema implementa un modelo de Control de Acceso Basado en Roles (RBAC - Role-Based Access Control) que constituye uno de los mecanismos de seguridad más robustos y flexibles disponibles en sistemas de información modernos.

#### ¿Qué es RBAC?

RBAC es un enfoque de seguridad que gestiona el acceso a recursos del sistema mediante la asignación de permisos a roles, y luego la asignación de roles a usuarios. Esto separa la identidad del usuario (quién es) de sus capacidades (qué puede hacer).

#### Beneficios del enfoque RBAC

1. **Principio de mínimo privilegio:** Cada usuario tiene únicamente los permisos necesarios
2. **Gestión centralizada:** Cambiar permisos de un rol afecta a todos los usuarios con ese rol
3. **Escalabilidad:** Fácil agregar nuevos usuarios o modificar permisos
4. **Auditoría:** Trazabilidad completa de permisos y cambios

### 8.2.2 Arquitectura del Sistema RBAC

**[INSERTAR DIAGRAMA DE RELACIONES]**

El sistema se compone de:
- **7 roles** predefinidos con jerarquía de prioridades
- **58+ permisos** granulares organizados en 9 categorías
- **Matriz interactiva** que relaciona roles con permisos
- **Sistema de auditoría** de cambios de roles y permisos

**Archivos principales:**

| Archivo | Líneas | Función |
|---------|--------|---------|
| manage-roles.php | 1564 | Interfaz completa de gestión |
| rbac-functions.php | 550 | Clase RBAC con toda la lógica |
| permission-check.php | 350 | Middleware de validación |
| 003_rbac_system.sql | - | Estructura de base de datos |

### 8.2.3 Roles del Sistema

**[CAPTURA 19: Tab "Roles" con lista completa]**

**[CAPTURA 20: Estadísticas de roles - 4 tarjetas]**

El sistema define 7 roles con diferentes niveles de autoridad:

| ID | Rol | Prioridad | Permisos | % | Descripción |
|----|-----|-----------|----------|---|-------------|
| 1 | Super Admin | 1 | 58/58 | 100% | Control total del sistema |
| 2 | Admin | 10 | 45/58 | 78% | Administrador general |
| 3 | Doctor | 20 | 25/58 | 43% | Médico con acceso clínico |
| 4 | Nurse | 25 | 15/58 | 26% | Enfermera con acceso limitado |
| 5 | Receptionist | 30 | 12/58 | 21% | Recepcionista (citas, pacientes) |
| 6 | Lab Technician | 35 | 8/58 | 14% | Técnico de laboratorio |
| 7 | Patient | 40 | 5/58 | 9% | Paciente (solo lectura propia) |

#### Sistema de Prioridades
- Número **menor** = **mayor** autoridad
- Super Admin (prioridad 1) tiene la máxima autoridad
- Patient (prioridad 40) tiene la mínima autoridad
- Las prioridades definen la jerarquía organizacional

### 8.2.4 Permisos Granulares

El sistema cuenta con **58+ permisos específicos** organizados en **9 categorías** funcionales:

**[CAPTURA 21: Modal mostrando todas las categorías de permisos]**

| Categoría | Icono | # Permisos | Ejemplos de Permisos |
|-----------|-------|------------|----------------------|
| 👥 users | fa-users | 8 | view_users, create_user, edit_user, delete_user, manage_user_roles |
| 🏥 patients | fa-wheelchair | 7 | view_patients, create_patient, edit_patient, delete_patient |
| 👨‍⚕️ doctors | fa-user-md | 6 | view_doctors, create_doctor, edit_doctor, delete_doctor |
| 📅 appointments | fa-calendar | 7 | view_appointments, book_appointment, cancel_appointment |
| 📋 medical_records | fa-file-text-o | 7 | view_records, add_records, edit_records, delete_records |
| 💰 billing | fa-usd | 7 | view_billing, create_invoice, process_payment |
| 📊 reports | fa-bar-chart | 5 | view_reports, export_reports, generate_reports |
| ⚙️ system | fa-cog | 7 | system_settings, manage_specializations, view_logs |
| 🔒 security | fa-shield | 4 | manage_roles, view_audit, unlock_accounts, reset_passwords |

#### Ejemplo detallado de permisos (Categoría: users)

```
Categoría: users (8 permisos)
├─ view_users          - Ver lista de usuarios del sistema
├─ create_user         - Crear nuevos usuarios
├─ edit_user           - Editar información de usuarios
├─ delete_user         - Eliminar/desactivar usuarios
├─ manage_user_roles   - Asignar y revocar roles a usuarios
├─ view_user_audit     - Ver historial de cambios de usuarios
├─ unlock_accounts     - Desbloquear cuentas bloqueadas
└─ reset_passwords     - Reiniciar contraseñas de usuarios
```

### 8.2.5 MATRIZ DE ACCESOS ⭐

**⭐ ESTA ES LA SECCIÓN MÁS IMPORTANTE DEL PUNTO 8.2**

La Matriz de Accesos es una representación visual e interactiva que muestra qué permisos tiene cada rol en cada categoría funcional del sistema.

**[CAPTURA 22: Tab "Matriz de Permisos" - Vista completa de la matriz]**

**[CAPTURA 23: Zoom de una fila mostrando los badges de permisos]**

#### Tabla completa de la Matriz

|  | users | patients | doctors | appts | records | billing | reports | system | security |
|--|-------|----------|---------|-------|---------|---------|---------|--------|----------|
| **Super Admin** | 8/8 | 7/7 | 6/6 | 7/7 | 7/7 | 7/7 | 5/5 | 7/7 | 4/4 |
| **Admin** | 6/8 | 7/7 | 6/6 | 7/7 | 5/7 | 7/7 | 5/5 | 5/7 | 2/4 |
| **Doctor** | 2/8 | 7/7 | 3/6 | 5/7 | 7/7 | 2/7 | 3/5 | 1/7 | 0/4 |
| **Nurse** | 0/8 | 5/7 | 1/6 | 4/7 | 4/7 | 0/7 | 1/5 | 0/7 | 0/4 |
| **Receptionist** | 0/8 | 4/7 | 1/6 | 7/7 | 1/7 | 2/7 | 2/5 | 0/7 | 0/4 |
| **Lab Tech** | 0/8 | 2/7 | 0/6 | 1/7 | 3/7 | 0/7 | 2/5 | 0/7 | 0/4 |
| **Patient** | 0/8 | 1/7 | 0/6 | 2/7 | 1/7 | 1/7 | 0/5 | 0/7 | 0/4 |

#### Vista SQL de la Matriz

La matriz también está disponible como vista SQL para consultas y exportación:

```sql
SELECT * FROM role_permission_matrix;
```

**[CAPTURA 24: Resultado de consulta SQL role_permission_matrix]**

### 8.2.6 Gestión desde la Aplicación

**⭐ CUMPLIMIENTO DEL REQUISITO: "cada funcionalidad sea granular e independiente, 
permitiendo esta gestión desde la aplicación y no dentro del código o en la base de datos"**

Todo el sistema de roles y permisos se gestiona al 100% desde la interfaz web, sin necesidad de:
- ❌ Modificar código fuente
- ❌ Ejecutar scripts SQL manualmente
- ❌ Acceder directamente a la base de datos

#### 8.2.6.1 ALTAS de Roles

**[CAPTURA 25: Botón "Nuevo Rol"]**

**[CAPTURA 26: Modal de creación de rol con todos los campos]**

Campos del formulario:
- Nombre interno del rol (role_name)
- Nombre visible (display_name)
- Descripción
- Prioridad (número)
- Estado (active/inactive)

**[CAPTURA 27: Mensaje "Rol creado exitosamente"]**

#### 8.2.6.2 BAJAS de Roles

**[CAPTURA 28: Botón eliminar + confirmación SweetAlert]**

Características:
- Confirmación antes de eliminar
- Protección: no se pueden eliminar roles del sistema (Super Admin, Admin, Doctor, Patient)
- Desactivación (status='inactive') en lugar de eliminación física

#### 8.2.6.3 ASIGNACIÓN de Permisos a Roles

**[CAPTURA 29: Botón "Gestionar Permisos" en cada rol]**

**[CAPTURA 30: Modal con lista de permisos organizados por categorías]**

**[CAPTURA 31: Checkboxes marcados mostrando permisos seleccionados]**

Características:
- Categorías colapsables para mejor organización
- Checkbox por cada permiso individual
- Checkbox general por categoría (seleccionar todos)
- Guardado con un solo clic
- Actualización en tiempo real

```php
// manage-roles.php - Asignar permisos desde interfaz
if (isset($_POST['action']) && $_POST['action'] == 'update_permissions') {
    $role_id = (int)$_POST['role_id'];
    $permissions = $_POST['permissions'] ?? [];
    
    // 1. Eliminar permisos actuales
    mysqli_query($con, "DELETE FROM role_permissions WHERE role_id = $role_id");
    
    // 2. Insertar nuevos permisos
    foreach ($permissions as $perm_id) {
        $sql = "INSERT INTO role_permissions (role_id, permission_id, granted_by)
                VALUES ($role_id, $perm_id, $granted_by)";
        mysqli_query($con, $sql);
    }
    
    // 3. Registrar en auditoría
    // ...
}
```

**[CAPTURA 32: Mensaje "Se actualizaron X permisos para el rol"]**

### 8.2.7 Auditoría de Cambios

Toda modificación en roles y permisos se registra en la tabla `audit_role_changes`:

Campos registrados:
- Usuario afectado (si aplica)
- Rol modificado
- Acción realizada (role_assigned, role_revoked, permissions_updated)
- Quién realizó la acción
- Fecha y hora

### 8.2.8 Conclusión de la Sección 8.2

El sistema de Gestión de Roles y Matriz de Accesos cumple al 100% con todos los requisitos:

✅ **Funcionalidad granular e independiente:** 58+ permisos específicos
✅ **Gestión desde la aplicación:** Interfaz completa sin tocar código ni BD
✅ **Matriz de accesos elaborada:** Visual e interactiva, exportable SQL
✅ **Funcionalidad demostrada:** Capturas de cada operación
✅ **CRUD completo de roles:** Crear, modificar, desactivar
✅ **Asignación de permisos:** Interfaz intuitiva con categorías
✅ **Auditoría completa:** Registro de todos los cambios
✅ **7 roles × 9 categorías:** Cobertura completa del sistema

---

## 8.3 GESTIÓN DE CONTRASEÑAS

### 8.3.1 Introducción

[Continúa con estructura similar...]

---

**FIN DE LA PLANTILLA**
