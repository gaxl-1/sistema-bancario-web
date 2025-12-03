<?php
/**
 * Funciones de Seguridad
 * Protección contra ataques comunes
 */

/**
 * Hash de contraseña seguro
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verificar contraseña
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Validar fortaleza de contraseña
 */
function validarPassword($password) {
    $errores = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errores[] = "La contraseña debe tener al menos " . PASSWORD_MIN_LENGTH . " caracteres";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errores[] = "La contraseña debe contener al menos una letra mayúscula";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errores[] = "La contraseña debe contener al menos una letra minúscula";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errores[] = "La contraseña debe contener al menos un número";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errores[] = "La contraseña debe contener al menos un carácter especial";
    }
    
    return $errores;
}

/**
 * Sanitizar entrada de datos
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generar token CSRF
 */
function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verificarTokenCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * Generar referencia única para transacciones
 */
function generarReferencia($prefijo = 'TRF') {
    return $prefijo . date('Ymd') . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Generar número de cuenta único
 */
function generarNumeroCuenta() {
    // Formato IBAN español simplificado: ES79 + 20 dígitos
    $codigo_banco = '2100';
    $codigo_sucursal = '0813';
    $dc = '61'; // Dígito de control
    $numero_cuenta = str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
    
    return 'ES79' . $codigo_banco . $codigo_sucursal . $dc . $numero_cuenta;
}

/**
 * Obtener IP del cliente
 */
function obtenerIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Obtener User Agent
 */
function obtenerUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
}

/**
 * Registrar en auditoría
 */
function registrarAuditoria($id_usuario, $accion, $tabla = null, $id_registro = null, $detalles = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO registro_auditoria 
            (id_usuario, accion, tabla_afectada, id_registro_afectado, detalles, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $id_usuario,
            $accion,
            $tabla,
            $id_registro,
            $detalles,
            obtenerIP(),
            obtenerUserAgent()
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error al registrar auditoría: " . $e->getMessage());
        return false;
    }
}

/**
 * Registrar intento de login
 */
function registrarIntentoLogin($nombre_usuario, $email, $exitoso) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO intentos_login (nombre_usuario, email, exitoso, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $nombre_usuario,
            $email,
            $exitoso ? 1 : 0,
            obtenerIP(),
            obtenerUserAgent()
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error al registrar intento de login: " . $e->getMessage());
        return false;
    }
}

/**
 * Prevenir ataques de fuerza bruta
 */
function verificarBloqueoCuenta($nombre_usuario) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT intentos_fallidos, bloqueado_hasta 
            FROM usuarios 
            WHERE nombre_usuario = ?
        ");
        $stmt->execute([$nombre_usuario]);
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            return ['bloqueado' => false];
        }
        
        // Verificar si está bloqueado
        if ($usuario['bloqueado_hasta'] && strtotime($usuario['bloqueado_hasta']) > time()) {
            $tiempo_restante = strtotime($usuario['bloqueado_hasta']) - time();
            return [
                'bloqueado' => true,
                'tiempo_restante' => ceil($tiempo_restante / 60) // en minutos
            ];
        }
        
        // Si el bloqueo expiró, resetear intentos
        if ($usuario['bloqueado_hasta'] && strtotime($usuario['bloqueado_hasta']) <= time()) {
            $stmt = $db->prepare("
                UPDATE usuarios 
                SET intentos_fallidos = 0, bloqueado_hasta = NULL 
                WHERE nombre_usuario = ?
            ");
            $stmt->execute([$nombre_usuario]);
        }
        
        return ['bloqueado' => false];
    } catch (PDOException $e) {
        error_log("Error al verificar bloqueo: " . $e->getMessage());
        return ['bloqueado' => false];
    }
}

/**
 * Incrementar intentos fallidos
 */
function incrementarIntentosFallidos($nombre_usuario) {
    try {
        $db = getDB();
        
        // Incrementar contador
        $stmt = $db->prepare("
            UPDATE usuarios 
            SET intentos_fallidos = intentos_fallidos + 1 
            WHERE nombre_usuario = ?
        ");
        $stmt->execute([$nombre_usuario]);
        
        // Verificar si debe bloquearse
        $stmt = $db->prepare("SELECT intentos_fallidos FROM usuarios WHERE nombre_usuario = ?");
        $stmt->execute([$nombre_usuario]);
        $usuario = $stmt->fetch();
        
        if ($usuario && $usuario['intentos_fallidos'] >= MAX_LOGIN_ATTEMPTS) {
            $bloqueado_hasta = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
            $stmt = $db->prepare("
                UPDATE usuarios 
                SET bloqueado_hasta = ? 
                WHERE nombre_usuario = ?
            ");
            $stmt->execute([$bloqueado_hasta, $nombre_usuario]);
            
            return true; // Cuenta bloqueada
        }
        
        return false; // No bloqueada aún
    } catch (PDOException $e) {
        error_log("Error al incrementar intentos fallidos: " . $e->getMessage());
        return false;
    }
}

/**
 * Resetear intentos fallidos
 */
function resetearIntentosFallidos($nombre_usuario) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE usuarios 
            SET intentos_fallidos = 0, bloqueado_hasta = NULL 
            WHERE nombre_usuario = ?
        ");
        $stmt->execute([$nombre_usuario]);
        return true;
    } catch (PDOException $e) {
        error_log("Error al resetear intentos fallidos: " . $e->getMessage());
        return false;
    }
}

/**
 * Escapar salida HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
