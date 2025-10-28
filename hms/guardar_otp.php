<?php

include("include/config.php"); 
header("Content-Type: application/json");

// 🔹 Conectar a la base de datos
$conn = new mysqli($host, $user, $pass, $db);

// Verificar conexión
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Error de conexión a la base de datos"]);
    exit;
}

// 🔹 Leer los datos JSON enviados desde JavaScript
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["correo"]) || !isset($data["passcode"])) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    exit;
}

$correo = trim($data["correo"]);
$passcode = trim($data["passcode"]);

// 🔹 Verificar si existe el correo en la columna correo_re
$stmt = $conn->prepare("SELECT id FROM users WHERE correo_re = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "El correo no existe en el sistema"]);
    $stmt->close();
    $conn->close();
    exit;
}

// Obtener el id del usuario
$row = $result->fetch_assoc();
$id_user = $row["id"];
$stmt->close();

// 🔹 Insertar el OTP en la tabla otp
$stmt = $conn->prepare("INSERT INTO otp (passcode, correo, id_user) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $passcode, $correo, $id_user);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "OTP guardado correctamente", "user_id" => $id_user]);
} else {
    echo json_encode(["status" => "error", "message" => "Error al guardar el OTP"]);
}

$stmt->close();
$conn->close();
?>
