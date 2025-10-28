<?php
session_start();
include("include/config.php"); 

// Verificar sesión
if(!isset($_SESSION['id_user_recuperacion'])) {
    header("Location: validacion.php");
    exit;
}

$mensaje = "";
$idUser = $_SESSION['id_user_recuperacion'];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validaciones del servidor
    if(!$password || !$password_confirm){
        $mensaje = "⚠️ Todos los campos son obligatorios.";
    } elseif(strlen($password) < 12 || strlen($password) > 64){
        $mensaje = "❌ La contraseña debe tener entre 12 y 64 caracteres.";
    } elseif(!preg_match('/[A-Z]/', $password)){
        $mensaje = "❌ La contraseña debe incluir al menos una letra mayúscula.";
    } elseif(!preg_match('/[a-z]/', $password)){
        $mensaje = "❌ La contraseña debe incluir al menos una letra minúscula.";
    } elseif(!preg_match('/[0-9]/', $password)){
        $mensaje = "❌ La contraseña debe incluir al menos un número.";
    } elseif(!preg_match('/[\W_]/', $password)){
        $mensaje = "❌ La contraseña debe incluir al menos un carácter especial.";
    } elseif($password !== $password_confirm){
        $mensaje = "❌ Las contraseñas no coinciden.";
    } else {
        // Actualizar en la base de datos
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=?, password_changed_at=NOW() WHERE id=?");
        $stmt->bind_param("si", $hash, $idUser);

        if($stmt->execute()){
            // Limpiar OTP y sesión
            $conn->query("DELETE FROM otp WHERE id_user=$idUser");
            unset($_SESSION['id_user_recuperacion']);
            unset($_SESSION['correo_recuperacion']);

            // Redirigir al login
            header("Location: login.php?recuperado=1");
            exit;
        } else {
            $mensaje = "❌ Error al actualizar la contraseña.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nueva Contraseña</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script>
function validarFormulario(e) {
  const pass1 = document.querySelector('input[name="password"]').value;
  const pass2 = document.querySelector('input[name="password_confirm"]').value;

  const regexMayus = /[A-Z]/;
  const regexMinus = /[a-z]/;
  const regexNum = /[0-9]/;
  const regexEsp = /[\W_]/;

  if (pass1.length < 12 || pass1.length > 64) {
    alert("La contraseña debe tener entre 12 y 64 caracteres.");
    e.preventDefault();
    return false;
  }
  if (!regexMayus.test(pass1)) {
    alert("Debe incluir al menos una letra mayúscula.");
    e.preventDefault();
    return false;
  }
  if (!regexMinus.test(pass1)) {
    alert("Debe incluir al menos una letra minúscula.");
    e.preventDefault();
    return false;
  }
  if (!regexNum.test(pass1)) {
    alert("Debe incluir al menos un número.");
    e.preventDefault();
    return false;
  }
  if (!regexEsp.test(pass1)) {
    alert("Debe incluir al menos un carácter especial (por ejemplo: @, #, $, %, &, *).");
    e.preventDefault();
    return false;
  }
  if (pass1 !== pass2) {
    alert("Las contraseñas no coinciden.");
    e.preventDefault();
    return false;
  }
  return true;
}
</script>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card p-4" style="width:400px;">
    <h4 class="text-primary text-center mb-3">Nueva Contraseña</h4>
    <?php if($mensaje): ?>
      <div class="alert <?php echo strpos($mensaje,'✅')!==false?'alert-success':'alert-danger'; ?> text-center">
        <?php echo $mensaje; ?>
      </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validarFormulario(event)">
      <div class="mb-3">
        <label>Nueva Contraseña</label>
        <input type="password" name="password" class="form-control" required minlength="12" maxlength="64">
        <div class="form-text">
          Debe tener entre 12 y 64 caracteres, incluir mayúsculas, minúsculas, números y un carácter especial.
        </div>
      </div>
      <div class="mb-3">
        <label>Confirmar Contraseña</label>
        <input type="password" name="password_confirm" class="form-control" required minlength="12" maxlength="64">
      </div>
      <button type="submit" class="btn btn-primary w-100">Actualizar Contraseña</button>
    </form>
  </div>
</div>
</body>
</html>
