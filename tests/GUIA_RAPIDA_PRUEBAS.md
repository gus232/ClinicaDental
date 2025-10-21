# ⚡ GUÍA RÁPIDA DE PRUEBAS - 10 MINUTOS

## 🎯 Objetivo
Probar todas las funcionalidades de seguridad implementadas en 10 minutos.

---

## ✅ ANTES DE EMPEZAR

### 1. Verificar XAMPP
```
✓ Apache: CORRIENDO (verde)
✓ MySQL: CORRIENDO (verde)
```

### 2. Verificar Migración
Abre en tu navegador:
```
http://localhost/hospital/database/migrations/verify-migration.php
```

**¿Todo con ✅?** → Continúa
**¿Algo con ❌?** → Ejecuta de nuevo la migración en phpMyAdmin

---

## 🚀 PRUEBAS (10 minutos)

### ⏱️ MINUTO 1-2: Crear Usuarios

**Abre:**
```
http://localhost/hospital/tests/create-test-users.php
```

**Haz click en:**
```
[Crear Usuarios de Prueba]
```

**Verás:**
```
✅ Usuario test@hospital.com creado exitosamente
✅ Usuario admin@hospital.com creado exitosamente
✅ Usuario doctor@hospital.com creado exitosamente
```

**Credenciales creadas:**
- Paciente: `test@hospital.com` / `Test123@!`
- Admin: `admin@hospital.com` / `Admin123@!`
- Doctor: `doctor@hospital.com` / `Doctor123@!`

---

### ⏱️ MINUTO 3-4: Probar Bloqueo de Cuenta

**Abre:**
```
http://localhost/hospital/hms/login.php
```

**Paso 1:** Intento fallido #1
```
Email: test@hospital.com
Password: ContraseñaIncorrecta1
Click: [Iniciar Sesión]

Resultado esperado:
❌ "Email o contraseña incorrectos. Le quedan 2 intentos."
```

**Paso 2:** Intento fallido #2
```
Email: test@hospital.com
Password: ContraseñaIncorrecta2
Click: [Iniciar Sesión]

Resultado esperado:
❌ "Email o contraseña incorrectos. Le queda 1 intento."
```

**Paso 3:** Intento fallido #3
```
Email: test@hospital.com
Password: ContraseñaIncorrecta3
Click: [Iniciar Sesión]

Resultado esperado:
🚫 "Cuenta bloqueada por múltiples intentos fallidos. Inténtelo nuevamente en 30 minutos."
```

**Paso 4:** Probar con contraseña correcta
```
Email: test@hospital.com
Password: Test123@!  (la correcta)
Click: [Iniciar Sesión]

Resultado esperado:
🚫 "Cuenta bloqueada... Inténtelo en X minutos."
```

✅ **PRUEBA PASADA:** La cuenta se bloqueó correctamente

---

### ⏱️ MINUTO 5-6: Desbloquear como Admin

**Paso 1:** Login como admin
```
Ve a: http://localhost/hospital/hms/login.php
Email: admin@hospital.com
Password: Admin123@!
Click: [Iniciar Sesión]

Deberías ver el dashboard de admin
```

**Paso 2:** Acceder al módulo de desbloqueo
```
En el menú lateral, busca:
"Seguridad" → "Desbloqueo de Cuentas"

O ve directo a:
http://localhost/hospital/hms/admin/unlock-accounts.php
```

**Paso 3:** Verificar cuenta bloqueada
```
Deberías ver una tabla con:
┌─────────────────────┬──────────┬────────────┬──────────────┐
│ Email               │ Intentos │ Estado     │ Acción       │
├─────────────────────┼──────────┼────────────┼──────────────┤
│ test@hospital.com   │ 3        │ BLOQUEADA  │ [Desbloquear]│
└─────────────────────┴──────────┴────────────┴──────────────┘
```

**Paso 4:** Desbloquear
```
Click en: [Desbloquear]
Click en: OK (confirmación)

Resultado esperado:
✅ "Cuenta desbloqueada exitosamente"
```

**Paso 5:** Logout del admin
```
Click en tu nombre (arriba derecha) → Logout
```

✅ **PRUEBA PASADA:** Admin puede desbloquear cuentas

---

### ⏱️ MINUTO 7-8: Probar Validación de Contraseñas

**Paso 1:** Login como paciente
```
Email: test@hospital.com
Password: Test123@!
Click: [Iniciar Sesión]

Ahora debería funcionar (ya está desbloqueada)
```

**Paso 2:** Ir a cambiar contraseña
```
En el menú lateral:
"Cambiar Contraseña"

O: http://localhost/hospital/hms/change-password.php
```

**Paso 3:** Probar contraseña DÉBIL
```
Contraseña Actual: Test123@!
Nueva Contraseña: abc123
Confirmar: abc123
Click: [Cambiar Contraseña]

Resultado esperado:
❌ "La contraseña debe tener al menos 8 caracteres"
❌ "Debe contener al menos una letra mayúscula"
❌ "Debe contener al menos un carácter especial"
```

**Paso 4:** Probar contraseña VÁLIDA
```
Nueva Contraseña: NewPassword123@!
Confirmar: NewPassword123@!
Click: [Cambiar Contraseña]

Resultado esperado:
✅ "Contraseña cambiada exitosamente. La contraseña expirará en 90 días"
(Redirige al dashboard en 2 segundos)
```

✅ **PRUEBA PASADA:** Validación de políticas funciona

---

### ⏱️ MINUTO 9: Probar Historial de Contraseñas

**Paso 1:** Cambiar contraseña de nuevo
```
Ir a: Cambiar Contraseña
Contraseña Actual: NewPassword123@!
Nueva: SecondPassword456@!
Click: [Cambiar Contraseña]

Resultado: ✅ Cambiada
```

**Paso 2:** Intentar volver a la anterior
```
Contraseña Actual: SecondPassword456@!
Nueva: NewPassword123@!  (la que usamos hace un momento)
Click: [Cambiar Contraseña]

Resultado esperado:
❌ "Esta contraseña ya fue utilizada recientemente. No puede reutilizar las últimas 5 contraseñas"
```

✅ **PRUEBA PASADA:** Historial de contraseñas funciona

---

### ⏱️ MINUTO 10: Verificar Registro de Intentos

**Paso 1:** Abrir phpMyAdmin
```
http://localhost/phpmyadmin
```

**Paso 2:** Seleccionar base de datos
```
Click en: hms_v2 (panel izquierdo)
```

**Paso 3:** Ver tabla login_attempts
```
Click en: login_attempts
Click en: Examinar (arriba)
```

**Deberías ver:**
```
┌────┬───────────────────────┬─────────────────┬────────────────────┐
│ id │ email                 │ attempt_result  │ attempted_at       │
├────┼───────────────────────┼─────────────────┼────────────────────┤
│ 1  │ test@hospital.com     │ failed_password │ 2025-10-20 14:30:01│
│ 2  │ test@hospital.com     │ failed_password │ 2025-10-20 14:30:15│
│ 3  │ test@hospital.com     │ failed_password │ 2025-10-20 14:30:30│
│ 4  │ test@hospital.com     │ account_locked  │ 2025-10-20 14:30:45│
│ 5  │ admin@hospital.com    │ success         │ 2025-10-20 14:35:00│
│ 6  │ test@hospital.com     │ success         │ 2025-10-20 14:40:00│
└────┴───────────────────────┴─────────────────┴────────────────────┘
```

✅ **PRUEBA PASADA:** Todos los intentos se registran correctamente

---

## 🎉 ¡PRUEBAS COMPLETADAS!

### ✅ Resumen de lo probado:

1. ✅ Migración de base de datos ejecutada
2. ✅ Creación de usuarios de prueba
3. ✅ Bloqueo al 3er intento fallido (30 min)
4. ✅ Desbloqueo desde panel admin
5. ✅ Validación de políticas de contraseñas
6. ✅ Historial de contraseñas (no reutilización)
7. ✅ Registro de todos los intentos de login

---

## 📸 Captura de Pantalla para el Informe

Toma screenshots de:

1. **Tabla de cuentas bloqueadas** (admin/unlock-accounts.php)
2. **Validación de contraseña** (change-password.php con errores)
3. **Indicador de fortaleza** (change-password.php)
4. **Tabla login_attempts** (phpMyAdmin)
5. **Tabla password_policy_config** (phpMyAdmin)

---

## ❓ ¿Alguna prueba falló?

### Si el bloqueo no funciona:
```
Verifica en phpMyAdmin:
- Tabla users → campo failed_login_attempts debe incrementar
- Tabla users → campo account_locked_until debe tener fecha
```

### Si el desbloqueo no funciona:
```
Verifica que:
- Estés logueado como admin
- El archivo admin/unlock-accounts.php existe
- El usuario tiene permisos de admin
```

### Si la validación no funciona:
```
Verifica que:
- El archivo include/password-policy.php existe
- change-password.php incluye password-policy.php
- La tabla password_policy_config tiene 13 registros
```

---

## 🚀 SIGUIENTE PASO

Una vez que todas las pruebas pasen:

**OPCIÓN 1:** Continuar con FASE 2 (Roles y Permisos)
**OPCIÓN 2:** Documentar resultados para el informe
**OPCIÓN 3:** Hacer una demo en vivo

---

**Tiempo estimado:** 10-15 minutos
**Dificultad:** ⭐⭐ (Fácil)
**Estado:** ✅ Listo para probar
