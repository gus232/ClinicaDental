<?php
define('DB_SERVER','localhost');
define('DB_USER','root');
define('DB_PASS' ,'');
define('DB_NAME', 'hms_v2');
$con = mysqli_connect(DB_SERVER,DB_USER,DB_PASS,DB_NAME);

// Check connection
if (mysqli_connect_errno())
{
 echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

// Configurar zona horaria (Bolivia GMT-4)
date_default_timezone_set('America/La_Paz');
mysqli_query($con, "SET time_zone = '-04:00'");
?>