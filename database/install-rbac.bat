@echo off
echo ============================================================================
echo INSTALADOR DE SISTEMA RBAC - HMS
echo ============================================================================
echo.

REM Configuracion
set MYSQL_PATH=C:\xampp\mysql\bin\mysql.exe
set DB_NAME=hms_v2
set DB_USER=root
set DB_PASS=

echo [1/4] Verificando MySQL...
if not exist "%MYSQL_PATH%" (
    echo ERROR: MySQL no encontrado en %MYSQL_PATH%
    echo Por favor, ajusta la ruta en install-rbac.bat
    pause
    exit /b 1
)
echo OK - MySQL encontrado

echo.
echo [2/4] Ejecutando migracion 003_rbac_system.sql...
"%MYSQL_PATH%" -u %DB_USER% %DB_NAME% < "C:\xampp\htdocs\hospital\database\migrations\003_rbac_system.sql"
if errorlevel 1 (
    echo ERROR: Fallo al ejecutar 003_rbac_system.sql
    pause
    exit /b 1
)
echo OK - Tablas RBAC creadas

echo.
echo [3/4] Ejecutando migracion 004_security_logs.sql...
"%MYSQL_PATH%" -u %DB_USER% %DB_NAME% < "C:\xampp\htdocs\hospital\database\migrations\004_security_logs.sql"
if errorlevel 1 (
    echo ERROR: Fallo al ejecutar 004_security_logs.sql
    pause
    exit /b 1
)
echo OK - Tabla security_logs creada

echo.
echo [4/4] Ejecutando seed 003_default_roles_permissions.sql...
"%MYSQL_PATH%" -u %DB_USER% %DB_NAME% < "C:\xampp\htdocs\hospital\database\seeds\003_default_roles_permissions.sql"
if errorlevel 1 (
    echo ERROR: Fallo al ejecutar 003_default_roles_permissions.sql
    pause
    exit /b 1
)
echo OK - Roles y permisos insertados

echo.
echo ============================================================================
echo INSTALACION COMPLETADA EXITOSAMENTE
echo ============================================================================
echo.
echo Verificando instalacion...
echo.

REM Verificar tablas creadas
"%MYSQL_PATH%" -u %DB_USER% %DB_NAME% -e "SELECT COUNT(*) as tablas_creadas FROM information_schema.tables WHERE table_schema = 'hms_v2' AND table_name IN ('roles', 'permissions', 'role_permissions', 'user_roles', 'permission_categories', 'role_hierarchy', 'audit_role_changes', 'security_logs');"

echo.
"%MYSQL_PATH%" -u %DB_USER% %DB_NAME% -e "SELECT COUNT(*) as total_roles FROM roles;"
"%MYSQL_PATH%" -u %DB_USER% %DB_NAME% -e "SELECT COUNT(*) as total_permisos FROM permissions;"
"%MYSQL_PATH%" -u %DB_USER% %DB_NAME% -e "SELECT COUNT(*) as asignaciones FROM role_permissions;"

echo.
echo ============================================================================
echo SIGUIENTE PASO: Ejecuta test-rbac.bat para probar el sistema
echo ============================================================================
echo.
pause
