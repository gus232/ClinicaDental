<?php
include("include/config.php");

echo "=== ANÁLISIS COMPLETO DE TABLAS ===\n\n";

// 1. Tabla USERS
echo "📊 TABLA USERS:\n";
$sql = "DESCRIBE users";
$result = mysqli_query($con, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}
echo "\nDatos:\n";
$sql = "SELECT id, fullName, email, role FROM users LIMIT 3";
$result = mysqli_query($con, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    echo "  ID:{$row['id']} | {$row['fullName']} | {$row['email']} | Role:{$row['role']}\n";
}

// 2. Tabla DOCTORS
echo "\n📊 TABLA DOCTORS:\n";
$sql = "DESCRIBE doctors";
$result = mysqli_query($con, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}
echo "\nDatos:\n";
$sql = "SELECT id, doctorName, docEmail FROM doctors LIMIT 3";
$result = mysqli_query($con, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    echo "  ID:{$row['id']} | {$row['doctorName']} | {$row['docEmail']}\n";
}

// 3. Tabla ADMIN
echo "\n📊 TABLA ADMIN:\n";
$sql = "DESCRIBE admin";
$result = mysqli_query($con, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}
echo "\nDatos:\n";
$sql = "SELECT id, username FROM admin";
$result = mysqli_query($con, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    echo "  ID:{$row['id']} | {$row['username']}\n";
}

mysqli_close($con);
?>
