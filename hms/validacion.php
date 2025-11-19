<?php
session_start();
require 'conexion.php';

$correo = $_GET['correo'] ?? $_SESSION['correo_recuperacion'] ?? '';
$mensaje = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $otpIngresado = $_POST['otp'] ?? '';

    if(!$correo || !$otpIngresado){
        $mensaje = "⚠️ Todos los campos son obligatorios.";
    } else {
        // Obtener OTP más reciente y el id_user asociado
        $stmt = $conn->prepare("SELECT id_user, passcode FROM otp WHERE correo=? ORDER BY creado_en DESC LIMIT 1");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows == 0){
            $mensaje = "❌ No se encontró OTP.";
        } else {
            $row = $result->fetch_assoc();
            if($otpIngresado == $row['passcode']){
                // Guardar datos en sesión
                $_SESSION['correo_recuperacion'] = $correo;
                $_SESSION['id_user_recuperacion'] = $row['id_user'];
                header("Location: nuevo.php");
                exit;
            } else {
                $mensaje = "❌ OTP incorrecto.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Validación OTP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card p-4" style="width:400px;">
    <h4 class="text-primary text-center mb-3">Validación OTP</h4>
    <?php if($mensaje): ?>
      <div class="alert <?php echo strpos($mensaje,'✅')!==false?'alert-success':'alert-danger'; ?> text-center">
        <?php echo $mensaje; ?>
      </div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <label>OTP</label>
        <input type="text" name="otp" class="form-control" placeholder="123456" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Validar OTP</button>
    </form>
  </div>
</div>
</body>
</html>
