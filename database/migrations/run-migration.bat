@echo off
REM ============================================================================
REM Script para ejecutar la migración 002_password_security.sql
REM ============================================================================

echo.
echo ========================================
echo  EJECUTANDO MIGRACION 002
echo  Seguridad de Contraseñas
echo ========================================
echo.

REM Cambiar al directorio de MySQL en XAMPP
cd C:\xampp\mysql\bin

REM Ejecutar la migración
echo Ejecutando SQL...
mysql -u root hms_v2 < "C:\xampp\htdocs\hospital\database\migrations\002_password_security.sql"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo  MIGRACION EXITOSA!
    echo ========================================
    echo.
    echo La base de datos ha sido actualizada correctamente.
    echo.
    echo Nuevas tablas creadas:
    echo   - password_history
    echo   - password_reset_tokens
    echo   - login_attempts
    echo   - password_policy_config
    echo.
    echo Campos agregados a 'users':
    echo   - failed_login_attempts
    echo   - account_locked_until
    echo   - password_expires_at
    echo   - password_changed_at
    echo   - last_login_ip
    echo   - force_password_change
    echo.
) else (
    echo.
    echo ========================================
    echo  ERROR EN LA MIGRACION
    echo ========================================
    echo.
    echo Revisa los errores arriba.
    echo.
    echo Posibles soluciones:
    echo 1. Verifica que MySQL este corriendo en XAMPP
    echo 2. Verifica que la base de datos 'hms_v2' exista
    echo 3. Ejecuta desde phpMyAdmin si persiste el error
    echo.
)

echo.
pause
