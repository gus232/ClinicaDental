<?php
/**
 * Configuración de Google reCAPTCHA v2
 *
 * IMPORTANTE: Para reCAPTCHA v2 necesita obtener NUEVAS claves:
 * 1. Vaya a: https://www.google.com/recaptcha/admin/create
 * 2. Seleccione "reCAPTCHA v2"
 * 3. Seleccione "Casilla de verificación 'No soy un robot'"
 * 4. Agregue su dominio (localhost para desarrollo)
 * 5. Copie las claves Site Key y Secret Key aquí
 */

// Versión de reCAPTCHA
define('RECAPTCHA_VERSION', 'v2'); // v2 = visual, v3 = invisible

// Clave del sitio (Site Key) - Se usa en el frontend (HTML/JavaScript)
define('RECAPTCHA_SITE_KEY', '6LdwHvorAAAAAORkpk1do93ydCb34HEGuYREyD73');

// Clave secreta (Secret Key) - Se usa en el backend (PHP)
define('RECAPTCHA_SECRET_KEY', '6LdwHvorAAAAAL7F2YKFT3KDyUTaJhjxeu-yhRHS');

// URL de la API de verificación de Google reCAPTCHA
define('RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify');

// Configuración adicional
define('RECAPTCHA_ENABLED', true); // Cambiar a false para desactivar temporalmente
?>
