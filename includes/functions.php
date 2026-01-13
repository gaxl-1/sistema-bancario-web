<?php
/**
 * Funciones Auxiliares Generales
 * Sistema Bancario
 */

/**
 * Verificar si el usuario está autenticado
 */
function estaAutenticado() {
    return isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_nombre']);
}

/**
 * Verificar si el usuario es administrador
 */
function esAdministrador() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'administrador';
}

/**
 * Verificar si el usuario es cliente
 */
function esCliente() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'cliente';
}

/**
 * Requerir autenticación
 */
function requerirAutenticacion() {
    if (!estaAutenticado()) {
        header('Location: ' . APP_URL . '/login.php');
        exit();
    }
}

/**
 * Requerir rol de administrador
 */
function requerirAdmin() {
    requerirAutenticacion();
    if (!esAdministrador()) {
        header('Location: ' . APP_URL . '/index.php');
        exit();
    }
}

/**
 * Requerir rol de cliente
 */
function requerirCliente() {
    requerirAutenticacion();
    if (!esCliente()) {
        header('Location: ' . APP_URL . '/index.php');
        exit();
    }
}

/**
 * Cerrar sesión
 */
function cerrarSesion() {
    if (isset($_SESSION['usuario_id'])) {
        // Marcar sesión como inactiva en la base de datos
        try {
            $db = getDB();
            $stmt = $db->prepare("UPDATE sesiones SET activa = FALSE WHERE id_usuario = ? AND activa = TRUE");
            $stmt->execute([$_SESSION['usuario_id']]);
        } catch (PDOException $e) {
            error_log("Error al cerrar sesión en BD: " . $e->getMessage());
        }
    }
    
    session_unset();
    session_destroy();
}

/**
 * Formatear cantidad de dinero
 */
function formatearDinero($cantidad, $moneda = MONEDA_DEFECTO) {
    $simbolos = [
        'EUR' => '€',
        'USD' => '$',
        'GBP' => '£'
    ];
    
    $simbolo = $simbolos[$moneda] ?? $moneda;
    return number_format($cantidad, 2, ',', '.') . ' ' . $simbolo;
}

/**
 * Formatear fecha
 */
function formatearFecha($fecha, $formato = 'd/m/Y H:i') {
    if (empty($fecha)) return '-';
    $timestamp = is_numeric($fecha) ? $fecha : strtotime($fecha);
    return date($formato, $timestamp);
}

/**
 * Formatear fecha relativa (hace X tiempo)
 */
function formatearFechaRelativa($fecha) {
    $timestamp = is_numeric($fecha) ? $fecha : strtotime($fecha);
    $diferencia = time() - $timestamp;
    
    if ($diferencia < 60) {
        return 'Hace ' . $diferencia . ' segundos';
    } elseif ($diferencia < 3600) {
        $minutos = floor($diferencia / 60);
        return 'Hace ' . $minutos . ' minuto' . ($minutos > 1 ? 's' : '');
    } elseif ($diferencia < 86400) {
        $horas = floor($diferencia / 3600);
        return 'Hace ' . $horas . ' hora' . ($horas > 1 ? 's' : '');
    } elseif ($diferencia < 604800) {
        $dias = floor($diferencia / 86400);
        return 'Hace ' . $dias . ' día' . ($dias > 1 ? 's' : '');
    } else {
        return formatearFecha($fecha, 'd/m/Y');
    }
}

/**
 * Obtener información del usuario actual
 */
function obtenerUsuarioActual() {
    if (!estaAutenticado()) {
        return null;
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT u.*, c.nombres, c.apellidos, c.documento_identidad
            FROM usuarios u
            LEFT JOIN clientes c ON u.id_usuario = c.id_usuario
            WHERE u.id_usuario = ?
        ");
        $stmt->execute([$_SESSION['usuario_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error al obtener usuario actual: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtener cuentas del cliente actual
 */
function obtenerCuentasCliente($id_cliente) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM cuentas 
            WHERE id_cliente = ? AND estado = 'activa'
            ORDER BY tipo_cuenta, fecha_apertura
        ");
        $stmt->execute([$id_cliente]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener cuentas: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener ID de cliente del usuario actual
 */
function obtenerIdCliente($id_usuario) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id_cliente FROM clientes WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        $resultado = $stmt->fetch();
        return $resultado ? $resultado['id_cliente'] : null;
    } catch (PDOException $e) {
        error_log("Error al obtener ID de cliente: " . $e->getMessage());
        return null;
    }
}

/**
 * Validar número de cuenta
 */
function validarNumeroCuenta($numero_cuenta) {
    // Validación básica de formato IBAN español
    return preg_match('/^ES\d{22}$/', $numero_cuenta);
}

/**
 * Obtener cuenta por número
 */
function obtenerCuentaPorNumero($numero_cuenta) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM cuentas WHERE numero_cuenta = ?");
        $stmt->execute([$numero_cuenta]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error al obtener cuenta: " . $e->getMessage());
        return null;
    }
}

/**
 * Verificar si la cuenta pertenece al cliente
 */
function cuentaPerteneceACliente($id_cuenta, $id_cliente) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM cuentas WHERE id_cuenta = ? AND id_cliente = ?");
        $stmt->execute([$id_cuenta, $id_cliente]);
        $resultado = $stmt->fetch();
        return $resultado['total'] > 0;
    } catch (PDOException $e) {
        error_log("Error al verificar propiedad de cuenta: " . $e->getMessage());
        return false;
    }
}

/**
 * Generar mensaje de alerta HTML
 */
function generarAlerta($mensaje, $tipo = 'info') {
    $clases = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $clase = $clases[$tipo] ?? 'alert-info';
    
    return '<div class="alert ' . $clase . ' alert-dismissible fade show" role="alert">
                ' . e($mensaje) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

/**
 * Redireccionar con mensaje
 */
function redirigirConMensaje($url, $mensaje, $tipo = 'info') {
    $_SESSION['mensaje'] = $mensaje;
    $_SESSION['mensaje_tipo'] = $tipo;
    header('Location: ' . $url);
    exit();
}

/**
 * Mostrar mensaje de sesión
 */
function mostrarMensajeSesion() {
    if (isset($_SESSION['mensaje'])) {
        $mensaje = $_SESSION['mensaje'];
        $tipo = $_SESSION['mensaje_tipo'] ?? 'info';
        unset($_SESSION['mensaje']);
        unset($_SESSION['mensaje_tipo']);
        return generarAlerta($mensaje, $tipo);
    }
    return '';
}

/**
 * Validar monto de transacción
 */
function validarMontoTransaccion($monto) {
    if (!is_numeric($monto) || $monto <= 0) {
        return "El monto debe ser un número positivo";
    }
    
    if ($monto > LIMITE_TRANSFERENCIA_UNICA) {
        return "El monto excede el límite por transacción de " . formatearDinero(LIMITE_TRANSFERENCIA_UNICA);
    }
    
    return true;
}

/**
 * Obtener estadísticas del dashboard (admin)
 */
function obtenerEstadisticasAdmin() {
    try {
        $db = getDB();
        
        $stats = [];
        
        // Total de usuarios
        $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'cliente'");
        $stats['total_usuarios'] = $stmt->fetch()['total'];
        
        // Total de cuentas activas
        $stmt = $db->query("SELECT COUNT(*) as total FROM cuentas WHERE estado = 'activa'");
        $stats['total_cuentas'] = $stmt->fetch()['total'];
        
        // Total de transacciones hoy
        $stmt = $db->query("SELECT COUNT(*) as total FROM transacciones WHERE DATE(fecha_transaccion) = CURDATE()");
        $stats['transacciones_hoy'] = $stmt->fetch()['total'];
        
        // Monto total en el sistema
        $stmt = $db->query("SELECT SUM(saldo) as total FROM cuentas WHERE estado = 'activa'");
        $stats['saldo_total'] = $stmt->fetch()['total'] ?? 0;
        
        // Usuarios pendientes de aprobación
        $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'pendiente'");
        $stats['usuarios_pendientes'] = $stmt->fetch()['total'];
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error al obtener estadísticas: " . $e->getMessage());
        return [];
    }
}

/**
 * Paginación
 */
function paginar($total_registros, $registros_por_pagina = 10, $pagina_actual = 1) {
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    $pagina_actual = max(1, min($pagina_actual, $total_paginas));
    $offset = ($pagina_actual - 1) * $registros_por_pagina;
    
    return [
        'total_registros' => $total_registros,
        'total_paginas' => $total_paginas,
        'pagina_actual' => $pagina_actual,
        'registros_por_pagina' => $registros_por_pagina,
        'offset' => $offset
    ];
}

/**
 * Obtener número de cuenta de servicio por tipo
 */
function obtenerCuentaServicio($tipo_servicio) {
    $cuentas_servicios = [
        'Electricidad' => 'ES7921000813610001111111',
        'Agua' => 'ES7921000813610002222222',
        'Gas' => 'ES7921000813610003333333',
        'Teléfono' => 'ES7921000813610004444444',
        'Internet' => 'ES7921000813610005555555',
        'TV Cable' => 'ES7921000813610006666666',
        'Seguro' => 'ES7921000813610007777777',
        'Otro' => 'ES7921000813610008888888'
    ];
    
    return $cuentas_servicios[$tipo_servicio] ?? null;
}

/**
 * Obtener ID de cuenta de servicio por tipo
 */
function obtenerIdCuentaServicio($tipo_servicio) {
    $numero_cuenta = obtenerCuentaServicio($tipo_servicio);
    
    if (!$numero_cuenta) {
        return null;
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id_cuenta FROM cuentas WHERE numero_cuenta = ?");
        $stmt->execute([$numero_cuenta]);
        $resultado = $stmt->fetch();
        return $resultado ? $resultado['id_cuenta'] : null;
    } catch (PDOException $e) {
        error_log("Error al obtener ID de cuenta de servicio: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtener saldos de todas las cuentas de servicios
 */
function obtenerSaldosServicios() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM vista_saldos_servicios ORDER BY numero_cuenta");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener saldos de servicios: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener monto total en cuentas de servicios
 */
function obtenerTotalServicios() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT SUM(saldo) as total FROM cuentas WHERE tipo_cuenta = 'servicio'");
        $resultado = $stmt->fetch();
        return $resultado ? $resultado['total'] : 0;
    } catch (PDOException $e) {
        error_log("Error al obtener total de servicios: " . $e->getMessage());
        return 0;
    }
}
?>
