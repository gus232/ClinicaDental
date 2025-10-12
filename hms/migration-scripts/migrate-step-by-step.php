<?php
/**
 * MIGRACIÓN PASO A PASO CON MANEJO DE ERRORES
 */

include("include/config.php");

echo "=== MIGRACIÓN PASO A PASO ===\n\n";

// PASO 1: Verificar si users_old ya existe
$result = mysqli_query($con, "SHOW TABLES LIKE 'users_old'");
if (mysqli_num_rows($result) > 0) {
    echo "⚠️  La tabla users_old ya existe. ¿Ya se ejecutó la migración antes?\n";
    echo "Saltando renombrado...\n\n";
} else {
    echo "📋 PASO 1: Renombrando tabla users a users_old...\n";
    if (mysqli_query($con, "RENAME TABLE users TO users_old")) {
        echo "  ✅ OK\n\n";
    } else {
        die("  ❌ Error: " . mysqli_error($con) . "\n");
    }
}

// PASO 2: Crear nueva tabla users
echo "📋 PASO 2: Creando nueva tabla users unificada...\n";
$sql = "CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('patient','doctor','admin') NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `status` enum('active','inactive','blocked') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_user_type` (`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($con, $sql)) {
    echo "  ✅ OK\n\n";
} else {
    echo "  ⏭️  Ya existe\n\n";
}

// PASO 3: Migrar pacientes
echo "📋 PASO 3: Migrando pacientes desde users_old...\n";
$sql = "INSERT IGNORE INTO `users` (`email`, `password`, `user_type`, `full_name`, `status`, `created_at`, `updated_at`)
SELECT
    email,
    password,
    'patient' as user_type,
    fullName,
    'active' as status,
    regDate,
    updationDate
FROM users_old
WHERE email IS NOT NULL AND email != ''";

if (mysqli_query($con, $sql)) {
    $count = mysqli_affected_rows($con);
    echo "  ✅ Migrados $count pacientes\n\n";
} else {
    echo "  ❌ Error: " . mysqli_error($con) . "\n\n";
}

// PASO 4: Migrar doctores
echo "📋 PASO 4: Migrando doctores...\n";
$sql = "INSERT IGNORE INTO `users` (`email`, `password`, `user_type`, `full_name`, `status`, `created_at`, `updated_at`)
SELECT
    docEmail,
    password,
    'doctor' as user_type,
    doctorName,
    'active' as status,
    creationDate,
    updationDate
FROM doctors
WHERE docEmail IS NOT NULL AND docEmail != ''";

if (mysqli_query($con, $sql)) {
    $count = mysqli_affected_rows($con);
    echo "  ✅ Migrados $count doctores\n\n";
} else {
    echo "  ❌ Error: " . mysqli_error($con) . "\n\n";
}

// PASO 5: Migrar administradores
echo "📋 PASO 5: Migrando administradores...\n";
$sql = "INSERT IGNORE INTO `users` (`email`, `password`, `user_type`, `full_name`, `status`, `created_at`)
SELECT
    CONCAT(username, '@hospital.com') as email,
    password,
    'admin' as user_type,
    username as full_name,
    'active' as status,
    NOW()
FROM admin";

if (mysqli_query($con, $sql)) {
    $count = mysqli_affected_rows($con);
    echo "  ✅ Migrados $count administradores\n\n";
} else {
    echo "  ❌ Error: " . mysqli_error($con) . "\n\n";
}

// PASO 6: Crear tabla patients
echo "📋 PASO 6: Creando tabla patients...\n";
$sql = "CREATE TABLE IF NOT EXISTS `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `address` longtext,
  `city` varchar(255) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($con, $sql)) {
    echo "  ✅ OK\n\n";
} else {
    echo "  ⏭️  Ya existe\n\n";
}

// PASO 7: Migrar datos de pacientes
echo "📋 PASO 7: Migrando info de pacientes...\n";
$sql = "INSERT IGNORE INTO `patients` (`user_id`, `address`, `city`, `gender`)
SELECT
    u.id,
    uo.address,
    uo.city,
    uo.gender
FROM users_old uo
INNER JOIN users u ON u.email = uo.email
WHERE u.user_type = 'patient'";

if (mysqli_query($con, $sql)) {
    $count = mysqli_affected_rows($con);
    echo "  ✅ Migrados $count registros de pacientes\n\n";
} else {
    echo "  ❌ Error: " . mysqli_error($con) . "\n\n";
}

// PASO 8: Agregar user_id a doctors
echo "📋 PASO 8: Actualizando tabla doctors...\n";
$result = mysqli_query($con, "SHOW COLUMNS FROM doctors LIKE 'user_id'");
if (mysqli_num_rows($result) == 0) {
    $sql = "ALTER TABLE `doctors` ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `id`";
    if (mysqli_query($con, $sql)) {
        echo "  ✅ Columna user_id agregada\n";
    } else {
        echo "  ❌ Error: " . mysqli_error($con) . "\n";
    }
} else {
    echo "  ⏭️  Columna user_id ya existe\n";
}

// Vincular doctors con users
echo "  Vinculando doctores con users...\n";
$sql = "UPDATE doctors d
INNER JOIN users u ON u.email = d.docEmail
SET d.user_id = u.id
WHERE u.user_type = 'doctor'";

if (mysqli_query($con, $sql)) {
    $count = mysqli_affected_rows($con);
    echo "  ✅ Vinculados $count doctores\n\n";
} else {
    echo "  ❌ Error: " . mysqli_error($con) . "\n\n";
}

// PASO 9: Crear tabla admins
echo "📋 PASO 9: Creando tabla admins...\n";
$sql = "CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `access_level` enum('super','standard') DEFAULT 'standard',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($con, $sql)) {
    echo "  ✅ OK\n\n";
} else {
    echo "  ⏭️  Ya existe\n\n";
}

// Migrar admins
echo "  Vinculando admins con users...\n";
$sql = "INSERT IGNORE INTO `admins` (`user_id`, `access_level`)
SELECT
    u.id,
    'super' as access_level
FROM admin a
INNER JOIN users u ON u.email = CONCAT(a.username, '@hospital.com')
WHERE u.user_type = 'admin'";

if (mysqli_query($con, $sql)) {
    $count = mysqli_affected_rows($con);
    echo "  ✅ Vinculados $count administradores\n\n";
} else {
    echo "  ❌ Error: " . mysqli_error($con) . "\n\n";
}

// VERIFICACIÓN FINAL
echo "==========================================\n";
echo "📊 VERIFICACIÓN FINAL:\n\n";

$sql = "SELECT user_type, COUNT(*) as cantidad FROM users GROUP BY user_type";
$result = mysqli_query($con, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    echo "  {$row['user_type']}: {$row['cantidad']}\n";
}

echo "\n";
$result = mysqli_query($con, "SELECT COUNT(*) as count FROM patients");
$row = mysqli_fetch_assoc($result);
echo "  Pacientes con info: {$row['count']}\n";

$result = mysqli_query($con, "SELECT COUNT(*) as count FROM doctors WHERE user_id IS NOT NULL");
$row = mysqli_fetch_assoc($result);
echo "  Doctores vinculados: {$row['count']}\n";

$result = mysqli_query($con, "SELECT COUNT(*) as count FROM admins");
$row = mysqli_fetch_assoc($result);
echo "  Admins vinculados: {$row['count']}\n";

echo "\n✅ MIGRACIÓN COMPLETADA!\n\n";

// Mostrar algunos usuarios de ejemplo
echo "📋 USUARIOS DE EJEMPLO:\n";
$result = mysqli_query($con, "SELECT id, email, user_type, full_name, status FROM users LIMIT 5");
while ($row = mysqli_fetch_assoc($result)) {
    echo "  ID:{$row['id']} | {$row['email']} | {$row['user_type']} | {$row['full_name']}\n";
}

mysqli_close($con);
?>
