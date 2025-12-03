<?php
/**
 * Configuración General de la Aplicación
 * Sistema Bancario
 */

// Configuración de zona horaria
date_default_timezone_set('Europe/Madrid');

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Configuración de errores (cambiar en producción)
define('ENVIRONMENT', 'development'); // development o production

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// Configuración de la aplicación
define('APP_NAME', 'Banco Seguro');
define('APP_URL', 'http://localhost/Banco');
define('APP_VERSION', '1.0.0');

// Configuración de seguridad
define('SESSION_LIFETIME', 3600); // 1 hora en segundos
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos en segundos
define('PASSWORD_MIN_LENGTH', 8);

// Límites de transacciones
define('LIMITE_TRANSFERENCIA_DIARIO', 5000.00);
define('LIMITE_TRANSFERENCIA_UNICA', 2000.00);
define('MONEDA_DEFECTO', 'EUR');

// Configuración de email (para futuras implementaciones)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'tu-email@gmail.com');
define('SMTP_PASS', 'tu-contraseña');
define('SMTP_FROM', 'noreply@bancoseguro.com');
define('SMTP_FROM_NAME', 'Banco Seguro');

// Rutas de la aplicación
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('UPLOADS_PATH', BASE_PATH . '/uploads');

// Crear directorios necesarios si no existen
// Nota: Si necesitas estos directorios, créalos manualmente con:
// mkdir -p /opt/lampp/htdocs/Banco/logs /opt/lampp/htdocs/Banco/uploads/documentos
/*
$directories = [
    BASE_PATH . '/logs',
    UPLOADS_PATH,
    UPLOADS_PATH . '/documentos'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}
*/

// Autoload de archivos necesarios
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/security.php';
require_once INCLUDES_PATH . '/functions.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    
    // Regenerar ID de sesión periódicamente
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // Cada 5 minutos
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    // Verificar expiración de sesión
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        header('Location: ' . APP_URL . '/login.php?timeout=1');
        exit();
    }
    $_SESSION['last_activity'] = time();
}
?>
