# 🧪 Scripts de Prueba - FASE 1

Este directorio contiene scripts y documentación para probar las funcionalidades implementadas en la FASE 1.

---

## 📁 Archivos Disponibles

| Archivo | Descripción | URL |
|---------|-------------|-----|
| `PLAN_DE_PRUEBAS_FASE1.md` | Plan completo de pruebas paso a paso | - |
| `generate-hash.php` | Generador de hashes Bcrypt | [Abrir](http://localhost/hospital/tests/generate-hash.php) |
| `create-test-users.php` | Crear usuarios de prueba automáticamente | [Abrir](http://localhost/hospital/tests/create-test-users.php) |

---

## 🚀 Inicio Rápido

### PASO 1: Crear Usuarios de Prueba

```
1. Abrir: http://localhost/hospital/tests/create-test-users.php
2. Click en: "Crear Usuarios de Prueba"
3. Listo! Ahora tienes:
   - test@hospital.com / Test123@!       (Paciente)
   - admin@hospital.com / Admin123@!     (Administrador)
   - doctor@hospital.com / Doctor123@!   (Doctor)
```

### PASO 2: Probar Bloqueo al 3er Intento

```
1. Ir a: http://localhost/hospital/hms/login.php
2. Intentar login 3 veces con contraseña incorrecta
3. Ver cómo se bloquea la cuenta
```

### PASO 3: Desbloquear desde Admin

```
1. Login como: admin@hospital.com / Admin123@!
2. Ir a: admin/unlock-accounts.php
3. Desbloquear la cuenta de prueba
```

### PASO 4: Cambiar Contraseña

```
1. Login como: test@hospital.com / Test123@!
2. Ir a: Cambiar Contraseña
3. Probar cambiar a contraseña débil (ver errores)
4. Cambiar a contraseña válida: NewPass123@!
```

---

## 📋 Lista de Verificación Rápida

- [ ] ✅ Migración ejecutada (`verify-migration.php`)
- [ ] ✅ Usuarios de prueba creados
- [ ] ✅ Bloqueo al 3er intento funciona
- [ ] ✅ Desbloqueo admin funciona
- [ ] ✅ Validación de contraseñas funciona
- [ ] ✅ Historial de contraseñas funciona
- [ ] ✅ Login registra intentos en BD

---

## 🔗 Enlaces Útiles

- [Login](http://localhost/hospital/hms/login.php)
- [Registro](http://localhost/hospital/hms/registration.php)
- [Admin Panel](http://localhost/hospital/hms/admin/)
- [phpMyAdmin](http://localhost/phpmyadmin)
- [Verificar Migración](http://localhost/hospital/database/migrations/verify-migration.php)

---

## 📞 Ayuda

Si tienes problemas:
1. Verifica que XAMPP esté corriendo
2. Verifica que la migración se ejecutó (`verify-migration.php`)
3. Revisa el plan de pruebas completo en `PLAN_DE_PRUEBAS_FASE1.md`
4. Revisa los errores en: `C:\xampp\apache\logs\error.log`

---

**Proyecto:** SIS 321 - Seguridad de Sistemas
**Fecha:** Octubre 2025
