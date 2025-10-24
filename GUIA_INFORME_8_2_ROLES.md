# 📝 GUÍA INFORME - 8.2 GESTIÓN DE ROLES Y MATRIZ

## Tabla de Roles
| Rol | Prioridad | Permisos | %  |
|-----|-----------|----------|-----|
| Super Admin | 1 | 58/58 | 100% |
| Admin | 10 | 45/58 | 78% |
| Doctor | 20 | 25/58 | 43% |
| Nurse | 25 | 15/58 | 26% |
| Receptionist | 30 | 12/58 | 21% |
| Lab Technician | 35 | 8/58 | 14% |
| Patient | 40 | 5/58 | 9% |

## Categorías de Permisos
- 👥 users: 8 permisos
- 🏥 patients: 7 permisos
- 👨‍⚕️ doctors: 6 permisos
- 📅 appointments: 7 permisos
- 📋 medical_records: 7 permisos
- 💰 billing: 7 permisos
- 📊 reports: 5 permisos
- ⚙️ system: 7 permisos
- 🔒 security: 4 permisos

## MATRIZ DE ACCESOS (tabla completa)
```
         users patients doctors appts records billing reports system security
Super     8/8    7/7     6/6    7/7    7/7     7/7     5/5    7/7     4/4
Admin     6/8    7/7     6/6    7/7    5/7     7/7     5/5    5/7     2/4
Doctor    2/8    7/7     3/6    5/7    7/7     2/7     3/5    1/7     0/4
Nurse     0/8    5/7     1/6    4/7    4/7     0/7     1/5    0/7     0/4
Recep     0/8    4/7     1/6    7/7    1/7     2/7     2/5    0/7     0/4
Lab       0/8    2/7     0/6    1/7    3/7     0/7     2/5    0/7     0/4
Patient   0/8    1/7     0/6    2/7    1/7     1/7     0/5    0/7     0/4
```

## Capturas Necesarias (14 total)
19. Lista de roles
20. Estadísticas de roles
21. Modal de permisos por categorías
22. Matriz completa en interfaz
23. Zoom de badges en matriz
24. Vista SQL role_permission_matrix
25. Botón "Nuevo Rol"
26. Modal crear rol
27. Rol creado exitosamente
28. Confirmación eliminar rol
29. Botón "Gestionar Permisos"
30. Modal con checkboxes de permisos
31. Permisos seleccionados
32. Mensaje "X permisos actualizados"

## Archivos del Sistema
- manage-roles.php (1564 líneas)
- rbac-functions.php (550 líneas)
- permission-check.php (350 líneas)

## SQL Importante
```sql
SELECT * FROM role_permission_matrix;
```

## Páginas estimadas: 10-12
