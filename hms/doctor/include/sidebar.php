<?php
/**
 * ============================================================================
 * SIDEBAR DE DOCTORES - 3 SECCIONES
 * ============================================================================
 * Estructura simplificada para doctores:
 * - Tablero
 * - Citas (Historial)
 * - Pacientes (Agregar + Gestionar + Buscar)
 * ============================================================================
 */

// Verificar permisos para cada opción del menú
$canViewAppointments = hasPermission('view_appointments');
$canCreatePatient = hasPermission('create_patient');
$canViewPatients = hasPermission('view_patients');
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

/* Estilos para el botón de toggle del sidebar */
.sidebar-header {
    position: absolute;
    top: 15px;
    left: 15px;
    right: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 1001;
}

/* Asegurar que el sidebar tenga z-index alto */
.sidebar.app-aside {
    z-index: 999 !important;
}

.sidebar-container {
    position: relative;
    z-index: 999;
}

.sidebar-toggle-btn {
    background: rgba(255,255,255,0.1);
    border: none;
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 5px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.sidebar-toggle-btn:hover {
    background: rgba(255,255,255,0.3);
}

.sidebar-toggle-btn i {
    font-size: 18px;
}

.sidebar-user-type {
    color: white;
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

/* Ocultar el texto cuando el sidebar está cerrado */
.app-sidebar-closed .sidebar-user-type {
    opacity: 0;
    visibility: hidden;
}

.navbar-title {
    padding-top: 60px !important;
}

/* Evitar que los items del menú se superpongan al botón */
.main-navigation-menu {
    margin-top: 80px !important;
}

.main-navigation-menu li .item-content {
    position: relative;
    z-index: 1;
}

.main-navigation-menu li .item-media {
    position: relative;
    z-index: 1;
}

/* Cuando el sidebar está cerrado */
.app-sidebar-closed .sidebar .navbar-title {
    display: none !important;
}

.app-sidebar-closed .sidebar .main-navigation-menu {
    margin-top: 80px !important;
    padding-top: 0 !important;
}
</style>

<div class="sidebar app-aside" id="sidebar">
    <div class="sidebar-container perfect-scrollbar">
        <!-- Header del sidebar con botón y texto -->
        <div class="sidebar-header">
            <button class="sidebar-toggle-btn" onclick="toggleSidebar()">
                <i class="ti-align-justify"></i>
            </button>
            <span class="sidebar-user-type">DOCTOR</span>
        </div>
        
        <nav>
            <!-- start: MENÚ DE NAVEGACIÓN PRINCIPAL -->
            <div class="navbar-title">
                <span>Panel de Doctor</span>
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

                <!-- ========================================================= -->
                <!-- SECCIÓN 2: CITAS -->
                <!-- ========================================================= -->
                <li class="<?php echo !$canViewAppointments ? 'menu-item-disabled' : ''; ?>">
                    <a href="<?php echo $canViewAppointments ? 'appointment-history.php' : 'javascript:void(0)'; ?>">
                        <div class="item-content">
                            <div class="item-media">
                                <i class="fa fa-calendar"></i>
                            </div>
                            <div class="item-inner">
                                <span class="title">
                                    Citas
                                    <?php if (!$canViewAppointments): ?>
                                    <i class="fa fa-lock" style="font-size: 12px; opacity: 0.6;"></i>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </a>
                </li>

                <!-- ========================================================= -->
                <!-- SECCIÓN 3: PACIENTES -->
                <!-- ========================================================= -->
                <li>
                    <a href="javascript:void(0)">
                        <div class="item-content">
                            <div class="item-media">
                                <i class="fa fa-users"></i>
                            </div>
                            <div class="item-inner">
                                <span class="title">Pacientes</span>
                                <i class="icon-arrow"></i>
                            </div>
                        </div>
                    </a>
                    <ul class="sub-menu">
                        <!-- Agregar Paciente -->
                        <li class="<?php echo !$canCreatePatient ? 'menu-item-disabled' : ''; ?>">
                            <a href="<?php echo $canCreatePatient ? 'add-patient.php' : 'javascript:void(0)'; ?>">
                                <i class="fa fa-user-plus"></i>
                                <span class="title">
                                    Agregar Paciente
                                    <?php if (!$canCreatePatient): ?>
                                    <i class="fa fa-lock" style="font-size: 10px; opacity: 0.6;"></i>
                                    <?php endif; ?>
                                </span>
                            </a>
                        </li>

                        <!-- Gestionar Pacientes -->
                        <li class="<?php echo !$canViewPatients ? 'menu-item-disabled' : ''; ?>">
                            <a href="<?php echo $canViewPatients ? 'manage-patient.php' : 'javascript:void(0)'; ?>">
                                <i class="fa fa-list-alt"></i>
                                <span class="title">
                                    Gestionar Pacientes
                                    <?php if (!$canViewPatients): ?>
                                    <i class="fa fa-lock" style="font-size: 10px; opacity: 0.6;"></i>
                                    <?php endif; ?>
                                </span>
                            </a>
                        </li>

                        <!-- Buscar Paciente -->
                        <li class="<?php echo !$canViewPatients ? 'menu-item-disabled' : ''; ?>">
                            <a href="<?php echo $canViewPatients ? 'search.php' : 'javascript:void(0)'; ?>">
                                <i class="fa fa-search"></i>
                                <span class="title">
                                    Buscar Paciente
                                    <?php if (!$canViewPatients): ?>
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

<script>
// Script para el botón de toggle del sidebar
function toggleSidebar() {
    const app = document.getElementById('app');
    if (app) {
        app.classList.toggle('app-sidebar-closed');
    }
}
</script>
