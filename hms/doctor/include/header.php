<?php error_reporting(0);?>
<style>
/* Mejoras visuales para el header de doctores */
.navbar-default {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    min-height: 70px;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 1000 !important;
    width: 100% !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Asegurar que el header siempre sea visible incluso con sidebar cerrado */
.app-sidebar-closed .navbar-default,
.app-sidebar-closed .navbar-header,
.app-sidebar-closed .navbar-brand,
.app-sidebar-closed .navbar-brand h2,
.app-sidebar-closed .navbar-collapse {
    visibility: visible !important;
    opacity: 1 !important;
    display: flex !important;
}

.navbar-brand {
    display: flex !important;
    align-items: center;
    height: 70px;
    visibility: visible !important;
    opacity: 1 !important;
}

.navbar-brand h2 {
    color: white !important;
    font-weight: 700;
    font-size: 20px;
    margin: 0;
    padding: 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
    letter-spacing: 0.5px;
    display: flex !important;
    align-items: center;
    white-space: nowrap;
    visibility: visible !important;
    opacity: 1 !important;
}

.navbar-brand h2:before {
    content: "\f0f8";
    font-family: FontAwesome;
    margin-right: 10px;
    font-size: 26px;
}

.navbar-header {
    display: flex !important;
    align-items: center;
    min-height: 70px;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Ocultar el botón de toggle del header */
.sidebar-toggler {
    display: none !important;
}

.navbar-collapse {
    min-height: 70px;
    display: flex !important;
    align-items: center;
    visibility: visible !important;
    opacity: 1 !important;
}

.navbar-collapse .nav {
    margin: 0;
    margin-left: auto !important;
}

.navbar-collapse .navbar-right {
    float: right !important;
    margin-left: auto !important;
}

.navbar-collapse h2 {
    color: white;
    font-weight: 600;
    font-size: 24px;
    margin: 0;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
}

.navbar-collapse h2:before {
    content: "\f0f8";
    font-family: FontAwesome;
    margin-right: 10px;
    font-size: 26px;
}

.current-user {
    display: flex !important;
    align-items: center !important;
    height: 70px !important;
    margin: 0 !important;
    margin-left: auto !important;
}

.current-user > a {
    display: flex !important;
    align-items: center !important;
    padding: 10px 15px !important;
    height: 100%;
}

.current-user .username {
    font-weight: 600;
    font-size: 15px;
    color: white !important;
    padding: 8px 16px;
    background: rgba(255,255,255,0.2);
    border-radius: 25px;
    display: inline-flex !important;
    align-items: center;
    margin-left: 8px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    white-space: nowrap;
    visibility: visible !important;
    opacity: 1 !important;
}

.current-user .username:hover {
    background: rgba(255,255,255,0.3);
}

.current-user img {
    border-radius: 50%;
    width: 45px;
    height: 45px;
    border: 3px solid white;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    vertical-align: middle;
}

.current-user img:hover {
    transform: scale(1.1);
}

.current-user .ti-angle-down {
    color: white;
    margin-left: 5px;
}

.current-user .username i {
    margin-left: 5px;
}

.sidebar-toggler i,
.sidebar-mobile-toggler i,
.menu-toggler i {
    color: white;
    font-size: 20px;
}

.dropdown-menu {
    border-radius: 10px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border: none;
    margin-top: 10px;
}

.dropdown-menu li a {
    padding: 12px 20px;
    transition: all 0.3s ease;
}

.dropdown-menu li a:hover {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

.dropdown-menu li a i {
    margin-right: 8px;
    width: 20px;
}
</style>
<header class="navbar navbar-default navbar-static-top">
					<!-- start: NAVBAR HEADER -->
					<div class="navbar-header">
						<a href="#" class="sidebar-mobile-toggler pull-left hidden-md hidden-lg" class="btn btn-navbar sidebar-toggle" data-toggle-class="app-slide-off" data-toggle-target="#app" data-toggle-click-outside="#sidebar">
							<i class="ti-align-justify"></i>
						</a>
						<a class="navbar-brand" href="#">
							<h2>Clínica Dental Muelitas</h2>
						</a>
						<a href="#" class="sidebar-toggler pull-right visible-md visible-lg" data-toggle-class="app-sidebar-closed" data-toggle-target="#app">
							<i class="ti-align-justify"></i>
						</a>
						<a class="pull-right menu-toggler visible-xs-block" id="menu-toggler" data-toggle="collapse" href=".navbar-collapse">
							<span class="sr-only">Toggle navigation</span>
							<i class="ti-view-grid"></i>
						</a>
					</div>
					<!-- end: NAVBAR HEADER -->
					<!-- start: NAVBAR COLLAPSE -->
					<div class="navbar-collapse collapse">
						<ul class="nav navbar-right">
							<li class="dropdown current-user">
								<a href class="dropdown-toggle" data-toggle="dropdown">
									<img src="assets/images/images.jpg"> <span class="username">


									<?php $query=mysqli_query($con,"select doctorName from doctors where id='".$_SESSION['id']."'");
while($row=mysqli_fetch_array($query))
{
	echo $row['doctorName'];
}
									?> <i class="ti-angle-down"></i></i></span>
								</a>
								<ul class="dropdown-menu dropdown-dark">
									<li>
										<a href="edit-profile.php">
											<i class="fa fa-user"></i> Mi Perfil
										</a>
									</li>

									<li>
										<a href="change-password.php">
											<i class="fa fa-lock"></i> Cambiar Contraseña
										</a>
									</li>
									<li>
										<a href="logout.php">
											<i class="fa fa-sign-out"></i> Cerrar Sesión
										</a>
									</li>
								</ul>
							</li>
							<!-- end: USER OPTIONS DROPDOWN -->
						</ul>
						<!-- start: MENU TOGGLER FOR MOBILE DEVICES -->
						<div class="close-handle visible-xs-block menu-toggler" data-toggle="collapse" href=".navbar-collapse">
							<div class="arrow-left"></div>
							<div class="arrow-right"></div>
						</div>
						<!-- end: MENU TOGGLER FOR MOBILE DEVICES -->
					</div>
				
					
					<!-- end: NAVBAR COLLAPSE -->
				</header>
