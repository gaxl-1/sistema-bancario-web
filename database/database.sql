-- =====================================================
-- BASE DE DATOS PARA APLICACIÓN BANCARIA
-- Sistema completo de gestión bancaria
-- =====================================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS banco_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE banco_db;

-- =====================================================
-- TABLA: usuarios
-- Almacena credenciales y roles de usuarios
-- =====================================================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('cliente', 'administrador', 'sistema') DEFAULT 'cliente',
    estado ENUM('activo', 'bloqueado', 'pendiente') DEFAULT 'pendiente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_nombre_usuario (nombre_usuario),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: clientes
-- Información personal y KYC de clientes
-- =====================================================
CREATE TABLE clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNIQUE NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    documento_identidad VARCHAR(20) UNIQUE NOT NULL,
    tipo_documento ENUM('DNI', 'Pasaporte', 'Cédula') DEFAULT 'DNI',
    fecha_nacimiento DATE NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    ciudad VARCHAR(100),
    codigo_postal VARCHAR(10),
    pais VARCHAR(50) DEFAULT 'España',
    verificado BOOLEAN DEFAULT FALSE,
    fecha_verificacion TIMESTAMP NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_documento (documento_identidad),
    INDEX idx_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: cuentas
-- Cuentas bancarias de los clientes
-- =====================================================
CREATE TABLE cuentas (
    id_cuenta INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    numero_cuenta VARCHAR(24) UNIQUE NOT NULL,
    tipo_cuenta ENUM('ahorro', 'corriente', 'nomina', 'servicio') DEFAULT 'ahorro',
    saldo DECIMAL(15, 2) DEFAULT 0.00,
    moneda VARCHAR(3) DEFAULT 'EUR',
    estado ENUM('activa', 'bloqueada', 'cerrada') DEFAULT 'activa',
    fecha_apertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre TIMESTAMP NULL,
    limite_diario DECIMAL(15, 2) DEFAULT 5000.00,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE,
    INDEX idx_numero_cuenta (numero_cuenta),
    INDEX idx_cliente (id_cliente),
    INDEX idx_estado (estado),
    CHECK (saldo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: transacciones
-- Registro de todas las operaciones bancarias
-- =====================================================
CREATE TABLE transacciones (
    id_transaccion INT AUTO_INCREMENT PRIMARY KEY,
    id_cuenta_origen INT,
    id_cuenta_destino INT,
    tipo_transaccion ENUM('transferencia', 'deposito', 'retiro', 'pago_servicio') NOT NULL,
    monto DECIMAL(15, 2) NOT NULL,
    moneda VARCHAR(3) DEFAULT 'EUR',
    descripcion TEXT,
    referencia VARCHAR(50) UNIQUE NOT NULL,
    estado ENUM('completada', 'pendiente', 'rechazada', 'cancelada') DEFAULT 'completada',
    fecha_transaccion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    saldo_origen_anterior DECIMAL(15, 2),
    saldo_origen_nuevo DECIMAL(15, 2),
    saldo_destino_anterior DECIMAL(15, 2),
    saldo_destino_nuevo DECIMAL(15, 2),
    ip_origen VARCHAR(45),
    FOREIGN KEY (id_cuenta_origen) REFERENCES cuentas(id_cuenta),
    FOREIGN KEY (id_cuenta_destino) REFERENCES cuentas(id_cuenta),
    INDEX idx_cuenta_origen (id_cuenta_origen),
    INDEX idx_cuenta_destino (id_cuenta_destino),
    INDEX idx_fecha (fecha_transaccion),
    INDEX idx_referencia (referencia),
    INDEX idx_tipo (tipo_transaccion),
    CHECK (monto > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: registro_auditoria
-- Logs de seguridad y acciones administrativas
-- =====================================================
CREATE TABLE registro_auditoria (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(50),
    id_registro_afectado INT,
    detalles TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_usuario (id_usuario),
    INDEX idx_fecha (fecha_accion),
    INDEX idx_accion (accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: intentos_login
-- Seguimiento de intentos de inicio de sesión
-- =====================================================
CREATE TABLE intentos_login (
    id_intento INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50),
    email VARCHAR(100),
    exitoso BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre_usuario (nombre_usuario),
    INDEX idx_fecha (fecha_intento),
    INDEX idx_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: sesiones
-- Gestión de sesiones activas
-- =====================================================
CREATE TABLE sesiones (
    id_sesion VARCHAR(128) PRIMARY KEY,
    id_usuario INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_actividad TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fecha_expiracion TIMESTAMP NOT NULL,
    activa BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_usuario (id_usuario),
    INDEX idx_expiracion (fecha_expiracion),
    INDEX idx_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Insertar usuario administrador por defecto
-- Contraseña: Admin123! (debe cambiarse en producción)
INSERT INTO usuarios (nombre_usuario, email, contrasena, rol, estado) VALUES
('admin', 'admin@banco.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador', 'activo');

-- Insertar cliente de prueba
-- Contraseña: Cliente123!
INSERT INTO usuarios (nombre_usuario, email, contrasena, rol, estado) VALUES
('cliente_demo', 'cliente@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 'activo');

-- Insertar usuario sistema para cuentas de servicios
INSERT INTO usuarios (nombre_usuario, email, contrasena, rol, estado) VALUES
('sistema', 'sistema@bancoseguro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sistema', 'activo');

-- Datos del cliente demo
INSERT INTO clientes (id_usuario, nombres, apellidos, documento_identidad, tipo_documento, fecha_nacimiento, telefono, direccion, ciudad, codigo_postal, verificado) VALUES
(2, 'Juan Carlos', 'García López', '12345678A', 'DNI', '1990-05-15', '+34 600 123 456', 'Calle Mayor 123', 'Madrid', '28001', TRUE);

-- Datos del cliente sistema (proveedores de servicios)
INSERT INTO clientes (id_usuario, nombres, apellidos, documento_identidad, tipo_documento, fecha_nacimiento, telefono, verificado) VALUES
(3, 'Sistema', 'Proveedores de Servicios', 'SISTEMA001', 'DNI', '2000-01-01', '+34 000 000 000', TRUE);

-- Cuentas del cliente demo
INSERT INTO cuentas (id_cliente, numero_cuenta, tipo_cuenta, saldo, estado) VALUES
(1, 'ES7921000813610123456789', 'corriente', 5000.00, 'activa'),
(1, 'ES7921000813610987654321', 'ahorro', 15000.00, 'activa');

-- Cuentas de servicios (proveedores)
INSERT INTO cuentas (id_cliente, numero_cuenta, tipo_cuenta, saldo, estado, limite_diario) VALUES
(2, 'ES7921000813610001111111', 'servicio', 0.00, 'activa', 999999.00),  -- Electricidad
(2, 'ES7921000813610002222222', 'servicio', 0.00, 'activa', 999999.00),  -- Agua
(2, 'ES7921000813610003333333', 'servicio', 0.00, 'activa', 999999.00),  -- Gas
(2, 'ES7921000813610004444444', 'servicio', 0.00, 'activa', 999999.00),  -- Teléfono
(2, 'ES7921000813610005555555', 'servicio', 0.00, 'activa', 999999.00),  -- Internet
(2, 'ES7921000813610006666666', 'servicio', 0.00, 'activa', 999999.00),  -- TV Cable
(2, 'ES7921000813610007777777', 'servicio', 0.00, 'activa', 999999.00),  -- Seguro
(2, 'ES7921000813610008888888', 'servicio', 0.00, 'activa', 999999.00);  -- Otro

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista de cuentas con información del cliente
CREATE VIEW vista_cuentas_clientes AS
SELECT 
    c.id_cuenta,
    c.numero_cuenta,
    c.tipo_cuenta,
    c.saldo,
    c.moneda,
    c.estado AS estado_cuenta,
    cl.nombres,
    cl.apellidos,
    cl.documento_identidad,
    u.nombre_usuario,
    u.email,
    u.estado AS estado_usuario
FROM cuentas c
INNER JOIN clientes cl ON c.id_cliente = cl.id_cliente
INNER JOIN usuarios u ON cl.id_usuario = u.id_usuario;

-- Vista de saldos de servicios
CREATE VIEW vista_saldos_servicios AS
SELECT 
    c.id_cuenta,
    c.numero_cuenta,
    c.saldo,
    c.moneda,
    CASE 
        WHEN c.numero_cuenta = 'ES7921000813610001111111' THEN 'Electricidad'
        WHEN c.numero_cuenta = 'ES7921000813610002222222' THEN 'Agua'
        WHEN c.numero_cuenta = 'ES7921000813610003333333' THEN 'Gas'
        WHEN c.numero_cuenta = 'ES7921000813610004444444' THEN 'Teléfono'
        WHEN c.numero_cuenta = 'ES7921000813610005555555' THEN 'Internet'
        WHEN c.numero_cuenta = 'ES7921000813610006666666' THEN 'TV Cable'
        WHEN c.numero_cuenta = 'ES7921000813610007777777' THEN 'Seguro'
        WHEN c.numero_cuenta = 'ES7921000813610008888888' THEN 'Otro'
        ELSE 'Desconocido'
    END AS nombre_servicio,
    CASE 
        WHEN c.numero_cuenta = 'ES7921000813610001111111' THEN '⚡'
        WHEN c.numero_cuenta = 'ES7921000813610002222222' THEN '💧'
        WHEN c.numero_cuenta = 'ES7921000813610003333333' THEN '🔥'
        WHEN c.numero_cuenta = 'ES7921000813610004444444' THEN '📞'
        WHEN c.numero_cuenta = 'ES7921000813610005555555' THEN '🌐'
        WHEN c.numero_cuenta = 'ES7921000813610006666666' THEN '📺'
        WHEN c.numero_cuenta = 'ES7921000813610007777777' THEN '🛡️'
        WHEN c.numero_cuenta = 'ES7921000813610008888888' THEN '📋'
        ELSE '❓'
    END AS icono
FROM cuentas c
WHERE c.tipo_cuenta = 'servicio'
ORDER BY c.numero_cuenta;

-- Vista de transacciones con detalles
CREATE VIEW vista_transacciones_detalladas AS
SELECT 
    t.id_transaccion,
    t.referencia,
    t.tipo_transaccion,
    t.monto,
    t.moneda,
    t.descripcion,
    t.estado,
    t.fecha_transaccion,
    co.numero_cuenta AS cuenta_origen,
    cd.numero_cuenta AS cuenta_destino,
    CONCAT(clo.nombres, ' ', clo.apellidos) AS cliente_origen,
    CONCAT(cld.nombres, ' ', cld.apellidos) AS cliente_destino
FROM transacciones t
LEFT JOIN cuentas co ON t.id_cuenta_origen = co.id_cuenta
LEFT JOIN cuentas cd ON t.id_cuenta_destino = cd.id_cuenta
LEFT JOIN clientes clo ON co.id_cliente = clo.id_cliente
LEFT JOIN clientes cld ON cd.id_cliente = cld.id_cliente;

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================

DROP PROCEDURE IF EXISTS realizar_transferencia;

DELIMITER $$

CREATE PROCEDURE realizar_transferencia(
    IN p_id_cuenta_origen INT,
    IN p_id_cuenta_destino INT,
    IN p_monto DECIMAL(15, 2),
    IN p_descripcion TEXT,
    IN p_ip_origen VARCHAR(45),
    OUT p_resultado VARCHAR(100),
    OUT p_referencia VARCHAR(50)
)
BEGIN
    DECLARE v_saldo_origen DECIMAL(15, 2);
    DECLARE v_saldo_destino DECIMAL(15, 2);
    DECLARE v_limite_diario DECIMAL(15, 2);
    DECLARE v_total_hoy DECIMAL(15, 2);
    DECLARE v_estado_origen VARCHAR(20);
    DECLARE v_estado_destino VARCHAR(20);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 'ERROR: Transacción fallida';
        SET p_referencia = NULL;
    END;
    
    START TRANSACTION;
    
    -- Generar referencia única
    SET p_referencia = CONCAT('TRF', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(FLOOR(RAND() * 999999), 6, '0'));
    
    -- Verificar estado de cuentas
    SELECT saldo, estado, limite_diario INTO v_saldo_origen, v_estado_origen, v_limite_diario
    FROM cuentas WHERE id_cuenta = p_id_cuenta_origen FOR UPDATE;
    
    SELECT saldo, estado INTO v_saldo_destino, v_estado_destino
    FROM cuentas WHERE id_cuenta = p_id_cuenta_destino FOR UPDATE;
    
    -- Validaciones
    IF v_estado_origen != 'activa' THEN
        SET p_resultado = 'ERROR: Cuenta origen no está activa';
        ROLLBACK;
    ELSEIF v_estado_destino != 'activa' THEN
        SET p_resultado = 'ERROR: Cuenta destino no está activa';
        ROLLBACK;
    ELSEIF v_saldo_origen < p_monto THEN
        SET p_resultado = 'ERROR: Saldo insuficiente';
        ROLLBACK;
    ELSE
        -- Verificar límite diario
        SELECT COALESCE(SUM(monto), 0) INTO v_total_hoy
        FROM transacciones
        WHERE id_cuenta_origen = p_id_cuenta_origen
        AND DATE(fecha_transaccion) = CURDATE()
        AND estado = 'completada';
        
        IF (v_total_hoy + p_monto) > v_limite_diario THEN
            SET p_resultado = 'ERROR: Límite diario excedido';
            ROLLBACK;
        ELSE
            -- Realizar transferencia
            UPDATE cuentas SET saldo = saldo - p_monto WHERE id_cuenta = p_id_cuenta_origen;
            UPDATE cuentas SET saldo = saldo + p_monto WHERE id_cuenta = p_id_cuenta_destino;
            
            -- Registrar transacción
            INSERT INTO transacciones (
                id_cuenta_origen, id_cuenta_destino, tipo_transaccion, monto,
                descripcion, referencia, estado, saldo_origen_anterior,
                saldo_origen_nuevo, saldo_destino_anterior, saldo_destino_nuevo, ip_origen
            ) VALUES (
                p_id_cuenta_origen, p_id_cuenta_destino, 'transferencia', p_monto,
                p_descripcion, p_referencia, 'completada', v_saldo_origen,
                v_saldo_origen - p_monto, v_saldo_destino, v_saldo_destino + p_monto, p_ip_origen
            );
            
            SET p_resultado = 'ÉXITO: Transferencia completada';
            COMMIT;
        END IF;
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- EVENTOS PROGRAMADOS
-- =====================================================

-- Habilitar el programador de eventos
SET GLOBAL event_scheduler = ON;

DROP EVENT IF EXISTS limpiar_sesiones_expiradas;

DELIMITER $$

CREATE EVENT limpiar_sesiones_expiradas
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    UPDATE sesiones SET activa = FALSE WHERE fecha_expiracion < NOW() AND activa = TRUE;
END$$

DELIMITER ;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
