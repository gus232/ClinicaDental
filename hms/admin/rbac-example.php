<?php
/**
 * ============================================================================
 * EJEMPLO DE USO: SISTEMA RBAC
 * ============================================================================
 *
 * Este archivo demuestra cómo usar el sistema RBAC en diferentes escenarios.
 * Puedes usar este archivo como referencia para implementar RBAC en tus páginas.
 *
 * Proyecto: SIS 321 - Seguridad de Sistemas
 * Versión: 2.2.0
 * ============================================================================
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../include/config.php');
require_once('../include/rbac-functions.php');
require_once('../include/permission-check.php');

// ============================================================================
// PROTECCIÓN DE PÁGINA: Solo admins pueden acceder a este ejemplo
// ============================================================================
requireAnyRole(['super_admin', 'admin']);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejemplo RBAC - HMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            margin-bottom: 10px;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .section h2 {
            color: #667eea;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 5px 5px 5px 0;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px 5px 5px 0;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 10px 0;
            overflow-x: auto;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .card h3 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .permission-list {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 10px;
        }

        .permission-item {
            padding: 8px;
            margin: 5px 0;
            background: white;
            border-radius: 5px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>🔐 Sistema RBAC - Ejemplo de Uso</h1>
            <p>Demostración práctica del sistema de Roles y Permisos</p>
        </div>

        <?php
        // Obtener información del usuario actual
        $current_user_id = $_SESSION['id'];
        $rbac = new RBAC($con);

        // Obtener roles del usuario
        $user_roles = $rbac->getUserRoles($current_user_id);

        // Obtener permisos del usuario
        $user_permissions = $rbac->getUserPermissions($current_user_id);

        // Obtener todos los roles disponibles
        $all_roles = $rbac->getAllRoles();

        // Obtener información del usuario
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "i", $current_user_id);
        mysqli_stmt_execute($stmt);
        $user_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        ?>

        <!-- INFORMACIÓN DEL USUARIO ACTUAL -->
        <div class="section">
            <h2>👤 Tu Información</h2>
            <div class="grid">
                <div class="card">
                    <h3>Datos Básicos</h3>
                    <p><strong>Usuario:</strong> <?php echo htmlspecialchars($user_info['full_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_info['email']); ?></p>
                    <p><strong>Tipo:</strong> <?php echo htmlspecialchars($user_info['user_type']); ?></p>
                </div>

                <div class="card">
                    <h3>Tus Roles</h3>
                    <?php if (empty($user_roles)): ?>
                        <span class="badge badge-warning">Sin roles asignados</span>
                    <?php else: ?>
                        <?php foreach ($user_roles as $role): ?>
                            <span class="badge badge-info"><?php echo htmlspecialchars($role['display_name']); ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h3>Estadísticas</h3>
                    <p><strong>Total de Roles:</strong> <?php echo count($user_roles); ?></p>
                    <p><strong>Total de Permisos:</strong> <?php echo count($user_permissions); ?></p>
                    <p><strong>Es Admin:</strong> <?php echo isAdmin() ? '✅ Sí' : '❌ No'; ?></p>
                </div>
            </div>
        </div>

        <!-- TUS PERMISOS -->
        <div class="section">
            <h2>🎯 Tus Permisos Efectivos</h2>
            <p>Estos son todos los permisos que tienes actualmente:</p>

            <div class="permission-list">
                <?php if (empty($user_permissions)): ?>
                    <div class="alert alert-info">
                        No tienes permisos asignados. Contacta con el administrador.
                    </div>
                <?php else: ?>
                    <?php
                    // Agrupar permisos por módulo
                    $grouped_permissions = [];
                    foreach ($user_permissions as $perm) {
                        // Obtener módulo del permiso
                        $query = "SELECT module FROM permissions WHERE permission_name = ?";
                        $stmt = mysqli_prepare($con, $query);
                        mysqli_stmt_bind_param($stmt, "s", $perm);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                        $module = $result['module'] ?? 'general';
                        $grouped_permissions[$module][] = $perm;
                    }

                    foreach ($grouped_permissions as $module => $perms):
                    ?>
                        <div class="card" style="margin-bottom: 10px;">
                            <h3>📂 <?php echo ucfirst($module); ?> (<?php echo count($perms); ?>)</h3>
                            <?php foreach ($perms as $perm): ?>
                                <span class="badge badge-success"><?php echo htmlspecialchars($perm); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- EJEMPLOS DE VERIFICACIÓN -->
        <div class="section">
            <h2>🧪 Ejemplos de Verificación de Permisos</h2>

            <div class="code">
                <strong>Ejemplo 1: Verificar permiso específico</strong><br>
                <?php if (hasPermission('view_patients')): ?>
                    ✅ Tienes permiso para <strong>ver pacientes</strong>
                <?php else: ?>
                    ❌ NO tienes permiso para <strong>ver pacientes</strong>
                <?php endif; ?>
            </div>

            <div class="code">
                <strong>Ejemplo 2: Verificar rol</strong><br>
                <?php if (hasRole('doctor')): ?>
                    ✅ Tienes el rol de <strong>Doctor</strong>
                <?php else: ?>
                    ❌ NO tienes el rol de <strong>Doctor</strong>
                <?php endif; ?>
            </div>

            <div class="code">
                <strong>Ejemplo 3: Verificar si es Super Admin</strong><br>
                <?php if (isSuperAdmin()): ?>
                    ✅ Eres <strong>Super Administrador</strong> (acceso total)
                <?php else: ?>
                    ❌ NO eres Super Administrador
                <?php endif; ?>
            </div>

            <div class="code">
                <strong>Ejemplo 4: Botones condicionales</strong><br>
                <!-- Solo se muestra si tiene permiso -->
                <?php if (hasPermission('create_patient')): ?>
                    <button class="btn btn-success">✅ Crear Paciente (tienes permiso)</button>
                <?php else: ?>
                    <button class="btn btn-danger" disabled>❌ Crear Paciente (sin permiso)</button>
                <?php endif; ?>

                <?php if (hasPermission('delete_user')): ?>
                    <button class="btn btn-success">✅ Eliminar Usuario (tienes permiso)</button>
                <?php else: ?>
                    <button class="btn btn-danger" disabled>❌ Eliminar Usuario (sin permiso)</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- TABLA DE ROLES DEL SISTEMA -->
        <div class="section">
            <h2>📋 Roles del Sistema</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Rol</th>
                        <th>Prioridad</th>
                        <th>Total Permisos</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_roles as $role): ?>
                        <?php
                        // Contar permisos del rol
                        $role_perms = $rbac->getRolePermissions($role['id']);
                        $perm_count = count($role_perms);
                        ?>
                        <tr>
                            <td><?php echo $role['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($role['display_name']); ?></strong><br>
                                <small style="color: #6c757d;"><?php echo htmlspecialchars($role['description']); ?></small>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo $role['priority']; ?></span>
                            </td>
                            <td>
                                <span class="badge badge-success"><?php echo $perm_count; ?> permisos</span>
                            </td>
                            <td>
                                <?php if ($role['status'] === 'active'): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- CÓDIGO DE EJEMPLO -->
        <div class="section">
            <h2>💻 Código de Ejemplo</h2>

            <h3 style="margin-top: 20px; color: #495057;">Proteger una página PHP:</h3>
            <div class="code">
&lt;?php<br>
session_start();<br>
require_once('include/config.php');<br>
require_once('include/permission-check.php');<br>
<br>
// Requiere permiso específico<br>
<span style="color: #28a745;">requirePermission('view_patients');</span><br>
<br>
// O requiere un rol<br>
<span style="color: #28a745;">requireRole('doctor');</span><br>
<br>
// Tu código aquí...<br>
?&gt;
            </div>

            <h3 style="margin-top: 20px; color: #495057;">Mostrar contenido condicional:</h3>
            <div class="code">
&lt;?php if (hasPermission('edit_patient')): ?&gt;<br>
&nbsp;&nbsp;&lt;button&gt;Editar Paciente&lt;/button&gt;<br>
&lt;?php endif; ?&gt;
            </div>

            <h3 style="margin-top: 20px; color: #495057;">Asignar rol a usuario:</h3>
            <div class="code">
&lt;?php<br>
$rbac = new RBAC($con);<br>
$result = $rbac-&gt;assignRoleToUser(<br>
&nbsp;&nbsp;$user_id = 5,<br>
&nbsp;&nbsp;$role_id = 3, // Doctor<br>
&nbsp;&nbsp;$assigned_by = $_SESSION['id']<br>
);<br>
<br>
if ($result['success']) {<br>
&nbsp;&nbsp;echo "✓ Rol asignado";<br>
}<br>
?&gt;
            </div>
        </div>

        <!-- LINKS ÚTILES -->
        <div class="section">
            <h2>📚 Recursos</h2>
            <div class="alert alert-info">
                <strong>📖 Documentación completa:</strong> Ver archivo <code>docs/RBAC_USAGE_GUIDE.md</code>
            </div>
            <a href="dashboard.php" class="btn btn-primary">← Volver al Dashboard</a>
            <a href="../docs/RBAC_USAGE_GUIDE.md" class="btn btn-primary" target="_blank">Ver Documentación</a>
        </div>
    </div>
</body>
</html>
