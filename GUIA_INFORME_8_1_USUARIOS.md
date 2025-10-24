# 📝 GUÍA INFORME - 8.1 GESTIÓN DE USUARIOS (ABM)

## 📌 QUÉ INCLUIR

### A. Introducción (1 página)

**Redacta:**
- Qué es ABM (Altas, Bajas, Modificaciones)
- Importancia para seguridad del sistema
- Objetivo del módulo

**Ejemplo:**
> "El módulo de Gestión de Usuarios (ABM) permite administrar el ciclo de vida completo 
> de las cuentas de usuario. Implementa controles de acceso, auditoría y validaciones 
> que garantizan la integridad y seguridad de la información."

### B. Arquitectura (1-2 páginas)

**INCLUIR:**

1. **Diagrama de Componentes:**
```
manage-users.php (interfaz) 
   ↓
UserManagement.php (lógica)
   ↓
Base de Datos (users, user_change_history)
```

2. **Tabla de Archivos:**
| Archivo | Líneas | Función |
|---------|--------|---------|
| manage-users.php | 813 | Interfaz CRUD |
| UserManagement.php | 620 | Lógica de negocio |
| users-api.php | 600+ | API REST |

### C. ALTAS - Crear Usuarios (2-3 páginas)

**Explica:**
- Formulario de creación
- Campos: full_name, email, password, user_type, status, roles
- Validaciones:
  * Email único
  * Password seguro (políticas)
  * Datos requeridos
- Auditoría automática

**CAPTURAS NECESARIAS (5):**
1. ✅ Botón "Nuevo Usuario"
2. ✅ Formulario de creación completo
3. ✅ Error: Email duplicado
4. ✅ Éxito: Usuario creado
5. ✅ Auditoría en BD (user_change_history)

**CÓDIGO A MOSTRAR:**
```php
public function createUser($data, $reason = null) {
    // Validar email único
    if ($this->emailExists($data['email'])) {
        return ['success' => false, 'message' => 'Email existe'];
    }
    
    // Hashear password
    $hashed = password_hash($data['password'], PASSWORD_BCRYPT);
    
    // Stored procedure con auditoría
    $stmt = $this->db->prepare("CALL create_user_with_audit(...)");
    // ...
}
```

### D. BAJAS - Eliminar Usuarios (1-2 páginas)

**Explica:**
- Soft Delete (no elimina físicamente)
- Cambia status a 'inactive'
- Preserva historial
- Confirmación antes de eliminar
- Solo con permiso 'delete_user'

**CAPTURAS NECESARIAS (4):**
6. ✅ Botón eliminar
7. ✅ Confirmación (SweetAlert)
8. ✅ Usuario inactivo (badge gris)
9. ✅ Auditoría de eliminación

**CÓDIGO:**
```php
public function deleteUser($user_id, $reason = null) {
    $sql = "UPDATE users SET status = 'inactive' WHERE id = ?";
    // NO hace DELETE, solo UPDATE
}
```

### E. MODIFICACIONES - Actualizar (2 páginas)

**Explica:**
- Editar nombre, email, status
- Actualizar roles
- Validación de cambios
- Historial completo
- Requiere permiso 'edit_user'

**CAPTURAS NECESARIAS (5):**
10. ✅ Botón editar
11. ✅ Modal con datos precargados
12. ✅ Selección de roles
13. ✅ Éxito al actualizar
14. ✅ Historial de cambios

### F. BÚSQUEDA Y FILTROS (1 página)

**Explica:**
- Búsqueda por nombre/email
- Filtros: status, user_type
- Combinación de filtros

**CAPTURAS NECESARIAS (2):**
15. ✅ Barra de búsqueda y filtros
16. ✅ Resultados filtrados

### G. ESTADÍSTICAS (1 página)

**Explica:**
- 4 tarjetas: Total, Activos, Inactivos, Bloqueados
- Actualización en tiempo real
- Stored procedure

**CAPTURA NECESARIA (1):**
17. ✅ Dashboard con 4 tarjetas

### H. ⚠️ FORMATO USER ID (0.5 páginas)

**IMPORTANTE MENCIONAR:**
- Actualmente usa IDs numéricos (AUTO_INCREMENT)
- Propuesta futura: USR-2025-0001, DOC-2025-0001
- Formato: [TIPO]-[AÑO]-[NÚMERO]

**Redacción:**
> "NOTA: El sistema usa IDs numéricos. Como mejora futura se propone 
> formato estándar [TIPO]-[AÑO]-[SECUENCIA] para mejor trazabilidad."

### I. SEGURIDAD (1-2 páginas)

**Explica:**

1. **Control de Acceso:**
```php
requirePermission('view_users');   // Ver
hasPermission('create_user');      // Crear
hasPermission('edit_user');        // Editar
hasPermission('delete_user');      // Eliminar
```

2. **Validaciones:**
- Email formato válido
- Password políticas
- Datos requeridos

3. **Auditoría:**
- Tabla: user_change_history
- Registra: qué, quién, cuándo, IP

4. **Protección:**
- SQL Injection: Prepared statements
- XSS: htmlspecialchars()
- CSRF: Tokens

### J. PRUEBAS (1 página)

**Tabla de Casos de Prueba:**
| # | Prueba | Esperado | ✅ Resultado |
|---|--------|----------|-------------|
| 1 | Crear con email único | Usuario creado | PASS |
| 2 | Crear con email duplicado | Error | PASS |
| 3 | Editar sin cambios | Sin cambios | PASS |
| 4 | Eliminar usuario | Inactive | PASS |
| 5 | Buscar por nombre | Filtrado | PASS |

**CAPTURA:**
18. ✅ Test automatizado (21/21 PASS)

### K. Conclusión (0.5 páginas)

> "El módulo ABM cumple todos los requisitos. Implementa CRUD completo 
> con auditoría, control RBAC, validaciones y protección contra ataques. 
> Gestión 100% desde interfaz web. Probado con 21 tests (100% éxito)."

---

## 📸 RESUMEN DE CAPTURAS (18 TOTAL)

1-5: ALTAS (Crear)
6-9: BAJAS (Eliminar)
10-14: MODIFICACIONES (Editar)
15-16: Búsqueda
17: Estadísticas
18: Pruebas

**TOTAL PÁGINAS ESTIMADAS: 12-15**
