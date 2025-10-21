# 🔧 SOLUCIÓN DEFINITIVA: Stored Procedures

## 🚨 PROBLEMA
phpMyAdmin tiene conflictos con la sintaxis `DELIMITER` cuando se ejecuta desde la interfaz web.

---

## ✅ SOLUCIÓN RÁPIDA (2 minutos)

### **Opción 1: Ejecutar Script Completo (RECOMENDADO)**

1. **Abre phpMyAdmin** → Base de datos `hms_v2` → Pestaña "SQL"

2. **Abre este archivo en un editor de texto:**
   ```
   C:\xampp\htdocs\hospital\database\stored-procedures\EJECUTAR_TODOS_LOS_SP.sql
   ```

3. **Copia TODO el contenido** (Ctrl+A, Ctrl+C)

4. **Pega en phpMyAdmin** y haz clic en "Continuar"

5. **Espera 5-10 segundos** y deberías ver:
   ```
   ✓ Stored Procedures creados exitosamente

   Procedures Instalados:
   - assign_role_to_user
   - cleanup_old_security_data
   - get_user_permissions
   - revoke_role_from_user
   - user_has_permission
   ```

6. ✅ **¡LISTO!** Los 5 stored procedures están instalados.

---

## ✅ Opción 2: Usar Línea de Comandos (Alternativa)

Si phpMyAdmin sigue dando error, usa MySQL desde terminal:

```bash
# Abre CMD o PowerShell
cd C:\xampp\mysql\bin

# Ejecuta MySQL
mysql.exe -u root hms_v2

# Copia y pega TODO el contenido de EJECUTAR_TODOS_LOS_SP.sql
# O usa SOURCE:
source C:/xampp/htdocs/hospital/database/stored-procedures/EJECUTAR_TODOS_LOS_SP.sql

# Verifica
SHOW PROCEDURE STATUS WHERE Db = 'hms_v2';
```

---

## ✅ Opción 3: Ejecutar Uno por Uno (Si nada funciona)

Si las opciones anteriores fallan, ejecuta estos queries **UNO POR UNO**:

### **SP 1: assign_role_to_user**

```sql
DROP PROCEDURE IF EXISTS assign_role_to_user;

DELIMITER $$
CREATE PROCEDURE assign_role_to_user(
    IN p_user_id INT,
    IN p_role_id INT,
    IN p_assigned_by INT,
    IN p_expires_at DATETIME
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'Error al asignar rol' AS message, 0 AS success;
    END;

    START TRANSACTION;

    IF NOT EXISTS (SELECT 1 FROM users WHERE id = p_user_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario no encontrado';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM roles WHERE id = p_role_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Rol no encontrado';
    END IF;

    INSERT INTO user_roles (user_id, role_id, assigned_by, expires_at, is_active)
    VALUES (p_user_id, p_role_id, p_assigned_by, p_expires_at, 1)
    ON DUPLICATE KEY UPDATE
        assigned_by = p_assigned_by,
        expires_at = p_expires_at,
        is_active = 1,
        assigned_at = CURRENT_TIMESTAMP;

    INSERT INTO audit_role_changes (user_id, role_id, action, performed_by)
    VALUES (p_user_id, p_role_id, 'assigned', p_assigned_by);

    COMMIT;
    SELECT 'Rol asignado exitosamente' AS message, 1 AS success;
END$$
DELIMITER ;
```

**Después de pegar, haz clic en "Continuar"**

---

### **SP 2: revoke_role_from_user**

```sql
DROP PROCEDURE IF EXISTS revoke_role_from_user;

DELIMITER $$
CREATE PROCEDURE revoke_role_from_user(
    IN p_user_id INT,
    IN p_role_id INT,
    IN p_revoked_by INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'Error al revocar rol' AS message, 0 AS success;
    END;

    START TRANSACTION;

    IF NOT EXISTS (SELECT 1 FROM user_roles WHERE user_id = p_user_id AND role_id = p_role_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El usuario no tiene este rol asignado';
    END IF;

    DELETE FROM user_roles WHERE user_id = p_user_id AND role_id = p_role_id;

    INSERT INTO audit_role_changes (user_id, role_id, action, performed_by)
    VALUES (p_user_id, p_role_id, 'revoked', p_revoked_by);

    COMMIT;
    SELECT 'Rol revocado exitosamente' AS message, 1 AS success;
END$$
DELIMITER ;
```

---

### **SP 3: user_has_permission**

```sql
DROP PROCEDURE IF EXISTS user_has_permission;

DELIMITER $$
CREATE PROCEDURE user_has_permission(
    IN p_user_id INT,
    IN p_permission_name VARCHAR(100)
)
BEGIN
    SELECT EXISTS(
        SELECT 1 FROM user_effective_permissions
        WHERE user_id = p_user_id AND permission_name = p_permission_name
    ) AS has_permission;
END$$
DELIMITER ;
```

---

### **SP 4: get_user_permissions**

```sql
DROP PROCEDURE IF EXISTS get_user_permissions;

DELIMITER $$
CREATE PROCEDURE get_user_permissions(IN p_user_id INT)
BEGIN
    SELECT DISTINCT
        p.permission_name,
        p.display_name,
        p.module,
        r.role_name,
        r.display_name AS role_display_name
    FROM users u
    INNER JOIN user_roles ur ON u.id = ur.user_id
    INNER JOIN roles r ON ur.role_id = r.id
    INNER JOIN role_permissions rp ON r.id = rp.role_id
    INNER JOIN permissions p ON rp.permission_id = p.id
    WHERE u.id = p_user_id
      AND u.status = 'active'
      AND ur.is_active = 1
      AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
      AND r.status = 'active'
    ORDER BY p.module, p.permission_name;
END$$
DELIMITER ;
```

---

### **SP 5: cleanup_old_security_data**

```sql
DROP PROCEDURE IF EXISTS cleanup_old_security_data;

DELIMITER $$
CREATE PROCEDURE cleanup_old_security_data()
BEGIN
    DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    DELETE FROM password_reset_tokens WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
    SELECT 'Limpieza completada exitosamente' AS message;
END$$
DELIMITER ;
```

---

## 🔍 VERIFICAR INSTALACIÓN

Después de cualquier método, ejecuta:

```sql
SELECT COUNT(*) as total_procedures
FROM information_schema.routines
WHERE routine_schema = 'hms_v2'
AND routine_type = 'PROCEDURE';
```

**Debe mostrar:** `total_procedures = 5` ✅

---

## 🧪 PROBAR QUE FUNCIONAN

### Test 1: assign_role_to_user

```sql
-- Ver usuarios disponibles
SELECT id, email FROM users LIMIT 3;

-- Asignar Super Admin al usuario 1
CALL assign_role_to_user(1, 1, 1, NULL);
```

**Debe mostrar:**
```
message: Rol asignado exitosamente
success: 1
```

---

### Test 2: user_has_permission

```sql
CALL user_has_permission(1, 'view_patients');
```

**Debe mostrar:**
```
has_permission: 1
```

---

### Test 3: get_user_permissions

```sql
CALL get_user_permissions(1);
```

**Debe mostrar:** Lista de 58+ permisos

---

## 🆘 SOLUCIÓN DE PROBLEMAS

### Error: "DELIMITER command not found"
✅ **Solución:** Usa la **Opción 1** (archivo completo) o **Opción 2** (línea de comandos)

### Error: "Syntax error near DELIMITER"
✅ **Solución:** Asegúrate de copiar TODO el bloque incluyendo `DELIMITER ;` al final

### Error: "Procedure already exists"
✅ **Normal:** El `DROP PROCEDURE IF EXISTS` lo eliminará y creará de nuevo

### Error: "Table 'audit_role_changes' doesn't exist"
✅ **Ejecuta primero:**
```sql
CREATE TABLE IF NOT EXISTS audit_role_changes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    action ENUM('assigned', 'revoked', 'role_updated', 'permission_changed') NOT NULL,
    performed_by INT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    details JSON,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

---

## ✅ RESUMEN

1. **Mejor opción:** Ejecutar `EJECUTAR_TODOS_LOS_SP.sql` completo
2. **Si falla:** Usar línea de comandos MySQL
3. **Última opción:** Ejecutar cada SP uno por uno

**Resultado esperado:** 5 stored procedures instalados ✅

---

**Una vez instalados, continúa con la asignación de roles.**
