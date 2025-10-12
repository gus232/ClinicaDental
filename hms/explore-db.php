<?php
include("include/config.php");

echo "=== EXPLORACIÓN DE BASE DE DATOS HMS ===\n\n";

// 1. Estructura de users primero
echo "📊 1. ESTRUCTURA TABLA USERS:\n";
$sql = "DESCRIBE users";
$result = mysqli_query($con, $sql);
if ($result) {
    $columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
        echo sprintf("  %s (%s) %s %s\n",
            $row['Field'],
            $row['Type'],
            $row['Null'] == 'YES' ? 'NULL' : 'NOT NULL',
            $row['Extra']
        );
    }
}

// Ahora mostrar datos
echo "\n📊 2. DATOS TABLA USERS:\n";
$sql = "SELECT * FROM users LIMIT 10";
$result = mysqli_query($con, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "  ID: " . $row['id'] . " | ";
        echo "Name: " . $row['fullName'] . " | ";
        echo "Email: " . $row['email'] . " | ";
        echo "Pass: " . substr($row['password'], 0, 30) . "...\n";
    }
}

// 3. Doctores
echo "\n📊 3. TABLA DOCTORS:\n";
$sql = "SELECT * FROM doctors LIMIT 5";
$result = mysqli_query($con, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "  ID: " . $row['id'] . " | ";
        echo "Name: " . $row['doctorName'] . " | ";
        echo "Email: " . $row['docEmail'] . " | ";
        echo "Pass: " . substr($row['password'], 0, 30) . "...\n";
    }
}

// 4. Admin
echo "\n📊 4. TABLA ADMIN:\n";
$sql = "SELECT * FROM admin LIMIT 5";
$result = mysqli_query($con, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "  ID: " . $row['id'] . " | ";
        echo "Username: " . $row['username'] . " | ";
        echo "Pass: " . substr($row['password'], 0, 30) . "...\n";
    }
}

// 5. Tablas disponibles
echo "\n📊 5. TODAS LAS TABLAS:\n";
$sql = "SHOW TABLES";
$result = mysqli_query($con, $sql);
$tables = [];
if ($result) {
    while ($row = mysqli_fetch_array($result)) {
        $tables[] = $row[0];
    }
}
echo "  " . implode(", ", $tables) . "\n";

mysqli_close($con);
?>
