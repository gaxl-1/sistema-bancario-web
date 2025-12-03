<?php
/**
 * Configuración de Base de Datos - EJEMPLO
 * Renombra este archivo a database.php y configura tus credenciales
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Configuración de la base de datos
    private $host = 'localhost';
    private $database = 'banco_db';
    private $username = 'root';
    private $password = '';  // Cambia esto por tu contraseña de MySQL
    private $charset = 'utf8mb4';
    
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    private function __clone() {}
    public function __wakeup() {}
}

function getDB() {
    return Database::getInstance()->getConnection();
}
?>
