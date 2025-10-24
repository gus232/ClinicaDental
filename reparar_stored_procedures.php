<?php
/**
 * SCRIPT DE REPARACIÓN DE STORED PROCEDURES
 * 
 * Este script instala automáticamente los stored procedures necesarios
 * Ejecuta este archivo desde tu navegador: http://localhost/hospital/reparar_stored_procedures.php
 */

// Configuración de base de datos
$host = 'localhost';
$dbname = 'hms_v2';
$username = 'root';
$password = '';

// Conectar a la base de datos
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔧 Reparación de Stored Procedures</h1>";
    echo "<p>Base de datos: <strong>$dbname</strong></p>";
    echo "<hr>";
    
    $procedures = [
        'create_user_with_audit' => file_get_contents(__DIR__ . '/database/stored-procedures/06_create_user_with_audit.sql'),
        'update_user_with_history' => file_get_contents(__DIR__ . '/database/stored-procedures/07_update_user_with_history.sql'),
        'search_users' => file_get_contents(__DIR__ . '/database/stored-procedures/08_search_users.sql')
    ];
    
    $success = 0;
    $errors = 0;
    
    foreach ($procedures as $name => $sql) {
        try {
            // Limpiar el SQL (remover comentarios y dividir por DELIMITER)
            $sql = preg_replace('/^--.*$/m', '', $sql); // Quitar comentarios
            
            // Dividir por CREATE PROCEDURE
            if (preg_match('/CREATE PROCEDURE.*?END;/s', $sql, $matches)) {
                $procedureSQL = $matches[0];
                
                // Primero eliminar el procedure si existe
                $pdo->exec("DROP PROCEDURE IF EXISTS $name");
                
                // Crear el procedure
                $pdo->exec($procedureSQL);
                
                echo "<p style='color:green;'>✓ <strong>$name</strong> instalado correctamente</p>";
                $success++;
            } else {
                throw new Exception("No se pudo extraer el SQL del procedure");
            }
            
        } catch (Exception $e) {
            echo "<p style='color:red;'>✗ Error al instalar <strong>$name</strong>: " . htmlspecialchars($e->getMessage()) . "</p>";
            $errors++;
        }
    }
    
    echo "<hr>";
    echo "<h2>Resumen:</h2>";
    echo "<p>✓ Instalados correctamente: <strong>$success</strong></p>";
    echo "<p>✗ Errores: <strong>$errors</strong></p>";
    
    if ($errors === 0) {
        echo "<div style='background:#d4edda;border:1px solid #c3e6cb;padding:15px;border-radius:5px;margin-top:20px;'>";
        echo "<h3 style='color:#155724;margin:0;'>✅ ¡Reparación completada con éxito!</h3>";
        echo "<p style='color:#155724;margin:10px 0 0 0;'>Todos los stored procedures han sido instalados. Tu sistema debería funcionar correctamente ahora.</p>";
        echo "</div>";
        echo "<p><a href='hms/admin/manage-users.php' style='display:inline-block;margin-top:20px;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>Ir a Gestión de Usuarios</a></p>";
    } else {
        echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;padding:15px;border-radius:5px;margin-top:20px;'>";
        echo "<h3 style='color:#721c24;margin:0;'>⚠️ Hubo errores durante la instalación</h3>";
        echo "<p style='color:#721c24;margin:10px 0 0 0;'>Por favor, usa el método manual con phpMyAdmin.</p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;padding:15px;border-radius:5px;'>";
    echo "<h3 style='color:#721c24;'>❌ Error de conexión a la base de datos</h3>";
    echo "<p style='color:#721c24;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Verifica que:</p>";
    echo "<ul>";
    echo "<li>MySQL esté ejecutándose en XAMPP</li>";
    echo "<li>El nombre de la base de datos sea correcto: <strong>$dbname</strong></li>";
    echo "<li>Las credenciales sean correctas (usuario: <strong>$username</strong>)</li>";
    echo "</ul>";
    echo "</div>";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
    background: #f5f5f5;
}
h1 {
    color: #333;
}
hr {
    border: none;
    border-top: 2px solid #ddd;
    margin: 20px 0;
}
</style>
