<?php
/**
 * ============================================================================
 * SIDEBAR ADMINISTRATIVO - 4 SECCIONES
 * ============================================================================
 * Estructura simplificada para roles administrativos:
 * - Admin Técnico
 * - Admin Operativo
 * - OSI (Oficial de Seguridad de Información)
 * ============================================================================
 */

// Verificar permisos para cada opción del menú
$canViewUsers = hasPermission('view_users');
$canManageRoles = hasPermission('manage_roles');
$canManagePasswordPolicies = hasPermission('manage_password_policies');
$canManageSystemSettings = hasPermission('manage_system_settings');
$canBackupDatabase = hasPermission('backup_database');
$canViewSystemLogs = hasPermission('view_system_logs');
$canViewSecurityLogs = hasPermission('view_security_logs');
$canManageSecuritySettings = hasPermission('manage_security_settings');
?>

<style>
/* Estilos para items bloqueados */
.menu-item-disabled {
    opacity: 0.5;
    cursor: not-allowed !important;
    position: relative;
}

.menu-item-disabled > a {
    color: #999 !important;
    pointer-events: none;
    cursor: not-allowed !important;
}

.menu-item-disabled:hover::after {
    content: "🔒 Sin permiso";
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: #f44336;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    z-index: 1000;
    white-space: nowrap;
}

.menu-section-title {
    padding: 15px 20px 10px 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #9e9e9e;
    margin-top: 10px;
}

.menu-section-title:first-child {
    margin-top: 0;
}

/* Badge de permisos */
.permission-badge {
    display: inline-block;
    background: #00a8b3;
    color: white;
    font-size: 9px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 5px;
    font-weight: 600;
}
</style>

<div class="sidebar app-aside" id="sidebar">
    <div class="sidebar-container perfect-scrollbar">
        <nav>
            <!-- start: MENÚ DE NAVEGACIÓN PRINCIPAL -->
            <div class="navbar-title">
                <span>Panel Administrativo</span>
            </div>

            <ul class="main-navigation-menu">

                <!-- ========================================================= -->
                <!-- SECCIÓN 1: TABLERO -->
                <!-- ========================================================= -->
                <li>
                    <a href="dashboard.php">
                        <div class="item-content">
                            <div class="item-media">
                                <i class="fa fa-dashboard"></i>
                            </div>
                            <div class="item-inner">
                                <span class="title">Tablero</span>
                            </div>
                        </div>
                    </a>
                </li>

                <li><div class="menu-section-title">Gestión</div></li>

                <!-- ========================================================= -->
                <!-- SECCIÓN 2: USUARIOS -->
                <!-- ========================================================= -->
                <li class="<?php echo !$canViewUsers ? 'menu-item-disabled' : ''; ?>">
                    <a href="<?php echo $canViewUsers ? 'manage-users.php' : 'javascript:void(0)'; ?>">
                        <div class="item-content">
                            <div class="item-media">
                                <i class="fa fa-users"></i>
                            </div>
                            <div class="item-inner">
                                <span class="title">
                                    Usuarios
                                    <?php if (!$canViewUsers): ?>
                                    <i class="fa fa-lock" style="font-size: 12px; opacity: 0.6;"></i>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </a>
                </li>

                <li><div class="menu-section-title">Configuración</div></li>

                <!-- ========================================================= -->
                <!-- SECCIÓN 3: SISTEMA -->
                <!-- ========================================================= -->
                <li>
                    <a href="javascript:void(0)">
                        <div class="item-content">
                            <div class="item-media">
                                <i class="fa fa-cogs"></i>
                            </div>
                            <div class="item-inner">
                                <span class="title">Sistema</span>
                                <i class="icon-arrow"></i>
                            </div>
                        </div>
                    </a>
                    <ul class="sub-menu">
                        <!-- Gestionar Roles y Permisos -->
                        <li class="<?php echo !$canManageRoles ? 'menu-item-disabled' : ''; ?>">
                            <a href="<?php echo $canManageRoles ? 'manage-roles.php' : 'javascript:void(0)'; ?>">
                                <i class="fa fa-shield"></i>
                                <span class="title">
                                    Roles y Permisos
                                    <?php if (!$canManageRoles): ?>
                                    <i class="fa fa-lock" style="font-size: 10px; opacity: 0.6;"></i>
                                    <?php endif; ?>
                                </span>
                            </a>
                        </li>

                        <!-- Políticas de Contraseña -->
                        <li class="<?php echo !$canManagePasswordPolicies ? 'menu-item-disabled' : ''; ?>">
                            <a href="<?php echo $canManagePasswordPolicies ? 'manage-password-policies.php' : 'javascript:void(0)'; ?>">
                                <i class="fa fa-key"></i>
                                <span class="title">
                                    Políticas de Contraseña
                                    <?php if (!$canManagePasswordPolicies): ?>
                                    <i class="fa fa-lock" style="font-size: 10px; opacity: 0.6;"></i>
                                    <?php endif; ?>
                                </span>
                            </a>
                        </li>

                        <!-- Configuración General -->
                        <li class="<?php echo !$canManageSystemSettings ? 'menu-item-disabled' : ''; ?>">
                            <a href="<?php echo $canManageSystemSettings ? 'system-settings.php' : 'javascript:void(0)'; ?>">
                                <i class="fa fa-sliders"></i>
                                <span class="title">
                                    Configuración General
                                    <?php if (!$canManageSystemSettings): ?>
                                    <i class="fa fa-lock" style="font-size: 10px; opacity: 0.6;"></i>
                                    <?php endif; ?>
                                </span>
                            </a>
                        </li>

                        <!-- Respaldos y Restauración -->
                        <li class="<?php echo !$canBackupDatabase ? 'menu-item-disabled' : ''; ?>">
                            <a href="<?php echo $canBackupDatabase ? 'backup-restore.php' : 'javascript:void(0)'; ?>">
                                <i class="fa fa-database"></i>
                                <span class="title">
                                    Respaldos
                                    <?php if (!$canBackupDatabase): ?>
                                    <i class="fa fa-lock" style="font-size: 10px; opacity: 0.6;"></i>
                                    <?php endif; ?>
                                </span>
                            </a>
                        </li>

                        <!-- Logs del Sistema -->
                        <li class="<?php echo !$canViewSystemLogs ? 'menu-item-disabled' : ''; ?>">
                            <a href="<?php echo $canViewSystemLogs ? 'system-logs.php' : 'javascript:void(0)'; ?>">
                                <i class="fa fa-file-text-o"></i>
                                <span class="title">
                                    Logs del Sistema
                                    <?php if (!$canViewSystemLogs): ?>
                                    <i class="fa fa-lock" style="font-size: 10px; opacity: 0.6;"></i>
                                    <?php endif; ?>
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- ========================================================= -->
                <!-- SECCIÓN 4: SEGURIDAD -->
                <!-- ========================================================= -->
                <li>
                    <a href="javascript:void(0)">
                        <div class="item-content">
                            <div class="item-media">
                                <i class="fa fa-shield"></i>
                            </div>
                            <div class="item-inner">
                                <span class="title">Seguridad</span>
                                <i class="icon-arrow"></i>
                            </div>
                        </div>
                    </a>
                    <ul class="sub-menu">
                        <!-- Logs de Seguridad -->
                        <li class="<?php echo !$canViewSecurityLogs ? 'menu-item-disabled' : ''; ?>">
                            <a href="<?php echo $canViewSecurityLogs ? 'security-logs.php' : 'javascript:void(0)'; ?>">
                                <i class="fa fa-list-alt"></i>
                                <span class="title">
                                    Logs de Seguridad
                                    <?php if (!$canViewSecurityLogs): ?>
                                    <i class="fa fa-lock" style="font-size: 10px; opacity: 0.6;"></i>
                                    <?php endif; ?>
                                </span>
                            </a>
                        </li>

                        <!-- Configuración de Seguridad -->
                        <li class="<?php echo !$canManageSecuritySettings ? 'menu-item-disabled' : ''; ?>">
                            <a href="<?php echo $canManageSecuritySettings ? 'security-settings.php' : 'javascript:void(0)'; ?>">
                                <i class="fa fa-lock"></i>
                                <span class="title">
                                    Configuración de Seguridad
                                    <?php if (!$canManageSecuritySettings): ?>
                                    <i class="fa fa-lock" style="font-size: 10px; opacity: 0.6;"></i>
                                    <?php endif; ?>
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>
            <!-- end: MENÚ DE NAVEGACIÓN PRINCIPAL -->
        </nav>
    </div>
</div>
