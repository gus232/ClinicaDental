# 🧪 PLAN DE PRUEBAS - FASE 1: Políticas de Contraseñas

## 📋 Checklist de Pruebas

- [ ] **Prueba 1:** Verificar migración de base de datos
- [ ] **Prueba 2:** Crear usuario de prueba con contraseña segura
- [ ] **Prueba 3:** Probar validación de políticas (contraseñas débiles)
- [ ] **Prueba 4:** Probar bloqueo al 3er intento
- [ ] **Prueba 5:** Desbloquear cuenta desde panel admin
- [ ] **Prueba 6:** Cambiar contraseña con validaciones
- [ ] **Prueba 7:** Verificar historial de contraseñas (no reutilización)
- [ ] **Prueba 8:** Verificar registro de intentos de login
- [ ] **Prueba 9:** Probar expiración de contraseñas
- [ ] **Prueba 10:** Verificar limpieza automática de datos

---

## 🚀 PRUEBA 1: Verificar Migración de Base de Datos

### Objetivo:
Confirmar que todas las tablas, campos y configuraciones se crearon correctamente.

### Pasos:

#### Opción A: Script PHP (Recomendado)
```bash
1. Abre tu navegador
2. Ve a: http://localhost/hospital/database/migrations/verify-migration.php
3. Verifica que todos los elementos tengan ✅
```

#### Opción B: phpMyAdmin
```
1. Abre http://localhost/phpmyadmin
2. Selecciona base de datos: hms_v2
3. Verifica que existan estas tablas:
   ✅ password_history
   ✅ password_reset_tokens
   ✅ login_attempts
   ✅ password_policy_config

4. Click en tabla "users" → Estructura
   Verifica estos campos nuevos:
   ✅ failed_login_attempts
   ✅ account_locked_until
   ✅ password_expires_at
   ✅ password_changed_at
   ✅ last_login_ip
   ✅ force_password_change

5. Click en tabla "password_policy_config" → Examinar
   Debe haber 13 registros
```

### Resultado Esperado:
```
✅ 4 tablas nuevas creadas
✅ 6 campos agregados a users
✅ 13 políticas configuradas
✅ 2 vistas creadas
✅ 1 stored procedure
```

**Estado:** [ ] PASÓ  [ ] FALLÓ

---

## 🧑‍💻 PRUEBA 2: Crear Usuario de Prueba

### Objetivo:
Crear un usuario que cumpla con las nuevas políticas de contraseñas.

### Pasos:

#### Método 1: Registro Manual desde el Sistema

```
1. Abre: http://localhost/hospital/hms/registration.php
2. Completa el formulario:
   - Full Name: Usuario Prueba
   - Email: test@hospital.com
   - Password: Test123@!
   - Confirm Password: Test123@!
   - Address: Calle Falsa 123
   - City: La Paz
   - Gender: Male

3. Click "Submit"
```

**NOTA:** Si registration.php aún no tiene validaciones (lo actualizaremos después), usa el Método 2.

#### Método 2: SQL Directo (phpMyAdmin)

```sql
-- Ejecuta esto en phpMyAdmin → SQL

-- 1. Insertar usuario con contraseña "Test123@!" (ya hasheada)
INSERT INTO users (
    email,
    password,
    user_type,
    full_name,
    status,
    created_at,
    password_changed_at,
    password_expires_at
) VALUES (
    'test@hospital.com',
    '$2y$12$YourBcryptHashHere',  -- Ver nota abajo
    'patient',
    'Usuario Prueba',
    'active',
    NOW(),
    NOW(),
    DATE_ADD(NOW(), INTERVAL 90 DAY)
);

-- 2. Obtener el ID del usuario
SET @user_id = LAST_INSERT_ID();

-- 3. Insertar datos de paciente
INSERT INTO patients (user_id, address, city, gender)
VALUES (@user_id, 'Calle Falsa 123', 'La Paz', 'Male');
```

**NOTA:** Para generar el hash de "Test123@!", ejecuta este script PHP:

```php
<?php
// Guardar como: C:\xampp\htdocs\hospital\tests\generate-hash.php
echo password_hash('Test123@!', PASSWORD_BCRYPT, ['cost' => 12]);
?>
```

Luego visita: `http://localhost/hospital/tests/generate-hash.php`

#### Método 3: Script de Pruebas Automatizado (Ver archivo adjunto)

Ejecuta el script `create-test-users.php` que crearé a continuación.

### Resultado Esperado:
```
✅ Usuario creado exitosamente
✅ Puede hacer login con: test@hospital.com / Test123@!
✅ password_expires_at = created_at + 90 días
✅ failed_login_attempts = 0
```

**Estado:** [ ] PASÓ  [ ] FALLÓ

---

## 🔒 PRUEBA 3: Validación de Políticas de Contraseñas

### Objetivo:
Verificar que el sistema rechaza contraseñas débiles.

### Pasos:

```
1. Abre: http://localhost/hospital/hms/login.php
2. Haz login con: test@hospital.com / Test123@!
3. Ve a: Cambiar Contraseña (menú lateral)
4. Intenta cambiar a contraseñas DÉBILES:
```

#### Prueba 3.1: Contraseña muy corta
```
Contraseña actual: Test123@!
Nueva contraseña: Abc1@
Confirmar: Abc1@
Resultado esperado: ❌ "La contraseña debe tener al menos 8 caracteres"
```

#### Prueba 3.2: Sin mayúscula
```
Nueva contraseña: test123@!
Resultado esperado: ❌ "Debe contener al menos una letra mayúscula"
```

#### Prueba 3.3: Sin minúscula
```
Nueva contraseña: TEST123@!
Resultado esperado: ❌ "Debe contener al menos una letra minúscula"
```

#### Prueba 3.4: Sin número
```
Nueva contraseña: TestPass@!
Resultado esperado: ❌ "Debe contener al menos un número"
```

#### Prueba 3.5: Sin carácter especial
```
Nueva contraseña: Test12345
Resultado esperado: ❌ "Debe contener al menos un carácter especial"
```

#### Prueba 3.6: Contraseña válida
```
Nueva contraseña: NewPass123@!
Resultado esperado: ✅ "Contraseña cambiada exitosamente"
```

### Resultado Esperado:
```
✅ Rechaza contraseñas que no cumplen requisitos
✅ Acepta contraseñas que cumplen todos los requisitos
✅ Muestra mensajes de error claros
✅ Indicador de fortaleza funciona en tiempo real
```

**Estado:** [ ] PASÓ  [ ] FALLÓ

---

## 🚫 PRUEBA 4: Bloqueo al 3er Intento Fallido

### Objetivo:
Verificar que la cuenta se bloquea después de 3 intentos fallidos.

### Pasos:

```
1. CERRAR SESIÓN (logout)
2. Ir a: http://localhost/hospital/hms/login.php
3. Usar email correcto pero contraseña INCORRECTA
```

#### Intento 1:
```
Email: test@hospital.com
Password: WrongPass1
Resultado esperado: ❌ "Email o contraseña incorrectos. Le quedan 2 intentos."
```

#### Intento 2:
```
Email: test@hospital.com
Password: WrongPass2
Resultado esperado: ❌ "Email o contraseña incorrectos. Le queda 1 intento."
```

#### Intento 3:
```
Email: test@hospital.com
Password: WrongPass3
Resultado esperado: ❌ "Cuenta bloqueada por múltiples intentos fallidos. Inténtelo nuevamente en 30 minutos."
```

#### Intento 4 (con contraseña correcta):
```
Email: test@hospital.com
Password: NewPass123@!  (la correcta)
Resultado esperado: ❌ "Cuenta bloqueada... Inténtelo en X minutos."
```

### Verificar en Base de Datos:

```sql
-- En phpMyAdmin → SQL
SELECT
    email,
    failed_login_attempts,
    account_locked_until,
    TIMESTAMPDIFF(MINUTE, NOW(), account_locked_until) as minutes_remaining
FROM users
WHERE email = 'test@hospital.com';
```

**Resultado esperado:**
```
failed_login_attempts: 3
account_locked_until: (fecha/hora actual + 30 minutos)
minutes_remaining: ~30
```

### Verificar Registro de Intentos:

```sql
SELECT * FROM login_attempts
WHERE email = 'test@hospital.com'
ORDER BY attempted_at DESC
LIMIT 10;
```

**Resultado esperado:**
```
4 registros:
- 3 con attempt_result = 'failed_password'
- 1 con attempt_result = 'account_locked'
```

### Resultado Esperado:
```
✅ Cuenta se bloquea al 3er intento
✅ Muestra minutos restantes correctamente
✅ No permite login ni con contraseña correcta
✅ Registra todos los intentos en login_attempts
```

**Estado:** [ ] PASÓ  [ ] FALLÓ

---

## 🔓 PRUEBA 5: Desbloqueo desde Panel Admin

### Objetivo:
Verificar que el administrador puede desbloquear cuentas.

### Pasos:

#### 5.1: Crear usuario admin (si no existe)

```sql
-- En phpMyAdmin → SQL
INSERT INTO users (email, password, user_type, full_name, status, created_at, password_changed_at, password_expires_at)
VALUES (
    'admin@hospital.com',
    -- Hash de "Admin123@!" (generar con generate-hash.php)
    '$2y$12$...',
    'admin',
    'Administrador',
    'active',
    NOW(),
    NOW(),
    DATE_ADD(NOW(), INTERVAL 90 DAY)
);

SET @admin_id = LAST_INSERT_ID();

INSERT INTO admins (user_id, username)
VALUES (@admin_id, 'admin');
```

#### 5.2: Login como admin

```
1. Logout
2. Login con: admin@hospital.com / Admin123@!
3. Serás redirigido a: http://localhost/hospital/hms/admin/dashboard.php
```

#### 5.3: Acceder al módulo de desbloqueo

```
4. En el menú lateral, busca:
   "Seguridad" → "Desbloqueo de Cuentas"

   O ve directamente a:
   http://localhost/hospital/hms/admin/unlock-accounts.php
```

#### 5.4: Verificar cuentas bloqueadas

```
5. Deberías ver una tabla con:
   - Email: test@hospital.com
   - Estado: BLOQUEADA (badge rojo)
   - Intentos: 3
   - Tiempo restante: ~30 minutos (o menos si pasó tiempo)
   - Botón: "Desbloquear"
```

#### 5.5: Desbloquear cuenta

```
6. Click en botón "Desbloquear"
7. Confirmar en el alert
8. Resultado esperado:
   ✅ Mensaje verde: "Cuenta desbloqueada exitosamente"
   ✅ El usuario desaparece de la tabla de bloqueados
   ✅ (O aparece con badge verde "DESBLOQUEADA")
```

#### 5.6: Verificar en BD

```sql
SELECT
    email,
    failed_login_attempts,
    account_locked_until
FROM users
WHERE email = 'test@hospital.com';
```

**Resultado esperado:**
```
failed_login_attempts: 0
account_locked_until: NULL
```

#### 5.7: Probar login nuevamente

```
9. Logout del admin
10. Login con: test@hospital.com / NewPass123@!
11. Resultado esperado: ✅ Login exitoso
```

### Resultado Esperado:
```
✅ Admin puede ver cuentas bloqueadas
✅ Admin puede desbloquear con un click
✅ Contador de intentos se resetea a 0
✅ Usuario puede hacer login inmediatamente
```

**Estado:** [ ] PASÓ  [ ] FALLÓ

---

## 🔄 PRUEBA 6: Historial de Contraseñas (No Reutilización)

### Objetivo:
Verificar que no se pueden reutilizar las últimas 5 contraseñas.

### Pasos:

```
1. Login como: test@hospital.com / NewPass123@!
2. Ir a: Cambiar Contraseña
```

#### 6.1: Cambiar contraseña (1ra vez)
```
Actual: NewPass123@!
Nueva: SecondPass456@!
Confirmar: SecondPass456@!
Resultado: ✅ Cambio exitoso
```

#### 6.2: Intentar volver a la anterior
```
Actual: SecondPass456@!
Nueva: NewPass123@!  (la anterior)
Resultado esperado: ❌ "Esta contraseña ya fue utilizada recientemente. No puede reutilizar las últimas 5 contraseñas"
```

#### 6.3: Cambiar 4 veces más
```
Cambio 2: ThirdPass789@!
Cambio 3: FourthPass012@!
Cambio 4: FifthPass345@!
Cambio 5: SixthPass678@!
```

#### 6.4: Verificar historial en BD
```sql
SELECT
    u.email,
    COUNT(ph.id) as passwords_in_history
FROM users u
LEFT JOIN password_history ph ON u.id = ph.user_id
WHERE u.email = 'test@hospital.com'
GROUP BY u.id;
```

**Resultado esperado:** `passwords_in_history: 5` (máximo)

#### 6.5: Intentar reutilizar una que YA salió del historial
```
Actual: SixthPass678@!
Nueva: NewPass123@!  (la primera de todas, debería estar fuera del historial)
Resultado esperado: ✅ PERMITIDO (ya no está en las últimas 5)
```

### Resultado Esperado:
```
✅ Rechaza contraseñas en el historial
✅ Mantiene máximo 5 contraseñas en historial
✅ Permite reutilizar después de 5 cambios
✅ Muestra mensaje claro de error
```

**Estado:** [ ] PASÓ  [ ] FALLÓ

---

## 📊 PRUEBA 7: Verificar Tabla de Intentos de Login

### Objetivo:
Confirmar que todos los intentos se registran correctamente.

### Pasos:

#### 7.1: Hacer varios intentos de login
```
1. Logout
2. Login EXITOSO: test@hospital.com / NewPass123@!
3. Logout
4. Login FALLIDO: test@hospital.com / WrongPassword
5. Login FALLIDO: wrong@email.com / AnyPassword
6. Login EXITOSO: test@hospital.com / NewPass123@!
```

#### 7.2: Verificar en base de datos
```sql
SELECT
    id,
    email,
    user_id,
    ip_address,
    attempt_result,
    attempted_at
FROM login_attempts
ORDER BY attempted_at DESC
LIMIT 10;
```

**Resultado esperado:**
```
6 registros (o más):
- email: test@hospital.com | result: success
- email: wrong@email.com | result: failed_user_not_found | user_id: NULL
- email: test@hospital.com | result: failed_password
- email: test@hospital.com | result: success
- email: test@hospital.com | result: account_locked (de prueba anterior)
- ...
```

#### 7.3: Verificar IPs registradas
```
✅ Todos tienen ip_address (no NULL)
✅ Probablemente sea: 127.0.0.1 o ::1
```

### Resultado Esperado:
```
✅ TODOS los intentos se registran
✅ Diferencia entre success, failed_password, failed_user_not_found
✅ Guarda IP y timestamp correcto
✅ user_id es NULL si usuario no existe
```

**Estado:** [ ] PASÓ  [ ] FALLÓ

---

## ⏰ PRUEBA 8: Expiración de Contraseñas

### Objetivo:
Verificar que las contraseñas expiran después de 90 días.

### Pasos:

#### 8.1: Verificar fecha de expiración
```sql
SELECT
    email,
    password_changed_at,
    password_expires_at,
    DATEDIFF(password_expires_at, NOW()) as days_until_expiry
FROM users
WHERE email = 'test@hospital.com';
```

**Resultado esperado:**
```
days_until_expiry: ~90 (o menos si cambiaste contraseña hace días)
```

#### 8.2: Simular contraseña expirada (modificar manualmente)
```sql
-- Forzar expiración para prueba
UPDATE users
SET password_expires_at = DATE_SUB(NOW(), INTERVAL 1 DAY)
WHERE email = 'test@hospital.com';
```

#### 8.3: Intentar login
```
1. Logout
2. Login con: test@hospital.com / NewPass123@!
3. Resultado esperado:
   ✅ Redirige a: change-password.php?expired=1
   ✅ Mensaje: "Su contraseña ha expirado. Debe cambiarla para continuar."
   ✅ No permite cancelar (botón cancelar oculto)
```

#### 8.4: Cambiar contraseña
```
4. Cambiar a: ExpiredTest123@!
5. Resultado esperado:
   ✅ Permite cambiar
   ✅ Redirige al dashboard
   ✅ Nueva expiración: NOW() + 90 días
```

#### 8.5: Probar advertencia de expiración próxima
```sql
-- Simular que expira en 5 días
UPDATE users
SET password_expires_at = DATE_ADD(NOW(), INTERVAL 5 DAY)
WHERE email = 'test@hospital.com';
```

```
6. Login nuevamente
7. Resultado esperado:
   ✅ Login exitoso (no fuerza cambio)
   ✅ Mensaje de advertencia en dashboard:
      "Su contraseña expirará en 5 día(s). Por favor, considere cambiarla."
```

### Resultado Esperado:
```
✅ Contraseña expira a los 90 días
✅ Fuerza cambio si está expirada
✅ Advierte 7 días antes
✅ Nueva contraseña tiene nueva fecha de expiración
```

**Estado:** [ ] PASÓ  [ ] FALLÓ

---

## 🧹 PRUEBA 9: Limpieza Automática de Datos

### Objetivo:
Verificar que el stored procedure limpia datos antiguos.

### Pasos:

#### 9.1: Crear datos de prueba antiguos
```sql
-- Insertar intento de login antiguo (100 días)
INSERT INTO login_attempts (email, user_id, ip_address, user_agent, attempt_result, attempted_at)
VALUES (
    'old@test.com',
    NULL,
    '127.0.0.1',
    'Test Browser',
    'failed_password',
    DATE_SUB(NOW(), INTERVAL 100 DAY)
);

-- Insertar token antiguo (10 días)
INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at)
VALUES (
    1,
    'old_token_12345',
    DATE_SUB(NOW(), INTERVAL 10 DAY),
    DATE_SUB(NOW(), INTERVAL 10 DAY)
);
```

#### 9.2: Ejecutar limpieza
```sql
CALL cleanup_old_security_data();
```

#### 9.3: Verificar que se eliminaron
```sql
-- Intentos antiguos deberían haberse eliminado
SELECT COUNT(*) as old_attempts
FROM login_attempts
WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
-- Resultado esperado: 0

-- Tokens antiguos deberían haberse eliminado
SELECT COUNT(*) as old_tokens
FROM password_reset_tokens
WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
-- Resultado esperado: 0
```

### Resultado Esperado:
```
✅ Elimina login_attempts > 90 días
✅ Elimina password_reset_tokens > 7 días
✅ Mantiene solo últimas 5 contraseñas en historial
✅ Muestra mensaje: "Limpieza completada exitosamente"
```

**Estado:** [ ] PASÓ  [ ] FALLÓ

---

## 📱 PRUEBA 10: Interfaz de Usuario

### Objetivo:
Verificar que la UI funciona correctamente.

### Checklist Visual:

#### En `change-password.php`:
```
[ ] ✅ Indicador de fortaleza funciona en tiempo real
[ ] ✅ Checks visuales cambian de rojo a verde
[ ] ✅ Botón de mostrar/ocultar contraseña funciona
[ ] ✅ Mensajes de error son claros y específicos
[ ] ✅ Lista de requisitos está visible
[ ] ✅ Diseño responsivo (se ve bien en móvil)
```

#### En `login.php`:
```
[ ] ✅ Mensajes de error son específicos pero seguros
     (no revela si email existe)
[ ] ✅ Contador de intentos es visible
[ ] ✅ Link "¿Olvidó su contraseña?" presente
[ ] ✅ Link "Regístrese aquí" presente
[ ] ✅ Badges de tipo de usuario visibles
```

#### En `admin/unlock-accounts.php`:
```
[ ] ✅ Tablas son responsivas
[ ] ✅ Badges de colores correctos (rojo=bloqueado, verde=desbloqueado)
[ ] ✅ Tiempo restante se calcula correctamente
[ ] ✅ Botones funcionan con confirmación
[ ] ✅ Estadísticas en tiempo real
```

**Estado:** [ ] PASÓ  [ ] FALLÓ

---

## 📝 RESUMEN DE RESULTADOS

| # | Prueba | Estado | Notas |
|---|--------|--------|-------|
| 1 | Migración de BD | [ ] PASÓ / [ ] FALLÓ | |
| 2 | Crear usuario de prueba | [ ] PASÓ / [ ] FALLÓ | |
| 3 | Validación de políticas | [ ] PASÓ / [ ] FALLÓ | |
| 4 | Bloqueo al 3er intento | [ ] PASÓ / [ ] FALLÓ | |
| 5 | Desbloqueo admin | [ ] PASÓ / [ ] FALLÓ | |
| 6 | Historial de contraseñas | [ ] PASÓ / [ ] FALLÓ | |
| 7 | Registro de intentos | [ ] PASÓ / [ ] FALLÓ | |
| 8 | Expiración de contraseñas | [ ] PASÓ / [ ] FALLÓ | |
| 9 | Limpieza automática | [ ] PASÓ / [ ] FALLÓ | |
| 10 | Interfaz de usuario | [ ] PASÓ / [ ] FALLÓ | |

---

## 🐛 REPORTE DE BUGS

Si alguna prueba falla, documenta aquí:

```
Prueba #: ___
Descripción del error:


Pasos para reproducir:
1.
2.
3.

Resultado esperado:


Resultado actual:


Capturas de pantalla: (si aplica)
```

---

## ✅ CRITERIOS DE ACEPTACIÓN

Para considerar la FASE 1 como APROBADA:

- [ ] Todas las pruebas (1-10) PASARON
- [ ] No hay bugs críticos
- [ ] La documentación está completa
- [ ] El código está comentado
- [ ] Se pueden hacer demos en vivo

---

**Proyecto:** SIS 321 - Seguridad de Sistemas
**Fecha de pruebas:** _______________
**Probado por:** _______________
**Resultado final:** [ ] APROBADO  [ ] RECHAZADO
