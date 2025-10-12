<?php
require_once 'vendor1/autoload.php';

// Configuración de la conexión a la base de datos
include("include/config.php");

// Carga las credenciales desde el archivo JSON
$credentialsFile = __DIR__ . '/credentials.json';
$credentials = json_decode(file_get_contents($credentialsFile), true);

$client = new Google_Client();
$client->setAuthConfig($credentials);
$client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
$client->addScope(Google_Service_Oauth2::USERINFO_PROFILE);

// Verifica si el usuario ha sido redirigido desde Google después de la autenticación
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // Obtener información del usuario
    $oauthService = new Google_Service_Oauth2($client);
    $userInfo = $oauthService->userinfo->get();
    $email = $userInfo->email;
    $name = $userInfo->name;

    // Verificar si el usuario existe en la base de datos
    $checkUser = mysqli_query($con, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($checkUser) > 0) {
        // El usuario ya existe, inicia su sesión
        $row = mysqli_fetch_assoc($checkUser);
        $_SESSION['login'] = $row['email'];
        $_SESSION['id'] = $row['id'];
        // Otras variables de sesión que necesites
    } else {
        // El usuario no existe, crea un nuevo registro
        $password = ""; // Genera una contraseña segura o déjala en blanco
        $query = "INSERT INTO users (email, name, password) VALUES ('$email', '$name', '$password')";
        mysqli_query($con, $query);
        $_SESSION['login'] = $email;
        // Otras variables de sesión que necesites
    }

    // Redirige al usuario a la página de inicio o panel de control después de la autenticación exitosa
    header("Location: dashboard.php");
    exit;
} else {
    // Generar la URL de autenticación de Google y redirigir al usuario
    $authUrl = $client->createAuthUrl();
    header("Location: $authUrl");
    exit;
}
?>