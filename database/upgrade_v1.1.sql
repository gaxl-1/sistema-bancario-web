-- =====================================================
-- SCRIPT DE MIGRACIÓN v1.0 -> v1.1
-- Sistema de Cuentas de Servicios
-- =====================================================

USE banco_db;

-- =====================================================
-- MODIFICAR TABLAS EXISTENTES
-- =====================================================

-- Agregar nuevo tipo de cuenta: 'servicio'
ALTER TABLE cuentas MODIFY COLUMN tipo_cuenta 
    ENUM('ahorro', 'corriente', 'nomina', 'servicio') DEFAULT 'ahorro';

-- Agregar nuevo rol de usuario: 'sistema'
ALTER TABLE usuarios MODIFY COLUMN rol 
    ENUM('cliente', 'administrador', 'sistema') DEFAULT 'cliente';

-- =====================================================
-- CREAR USUARIO Y CLIENTE SISTEMA
-- =====================================================

-- Verificar si el usuario sistema ya existe
INSERT INTO usuarios (nombre_usuario, email, contrasena, rol, estado)
SELECT 'sistema', 'sistema@bancoseguro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sistema', 'activo'
WHERE NOT EXISTS (
    SELECT 1 FROM usuarios WHERE nombre_usuario = 'sistema'
);

-- Obtener el ID del usuario sistema
SET @id_usuario_sistema = (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'sistema');

-- Verificar si el cliente sistema ya existe
INSERT INTO clientes (id_usuario, nombres, apellidos, documento_identidad, tipo_documento, fecha_nacimiento, telefono, verificado)
SELECT @id_usuario_sistema, 'Sistema', 'Proveedores de Servicios', 'SISTEMA001', 'DNI', '2000-01-01', '+34 000 000 000', TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM clientes WHERE documento_identidad = 'SISTEMA001'
);

-- Obtener el ID del cliente sistema
SET @id_cliente_sistema = (SELECT id_cliente FROM clientes WHERE documento_identidad = 'SISTEMA001');

-- =====================================================
-- CREAR CUENTAS DE SERVICIOS
-- =====================================================

-- Insertar cuentas solo si no existen previamente

-- 1. Electricidad
INSERT INTO cuentas (id_cliente, numero_cuenta, tipo_cuenta, saldo, moneda, estado, limite_diario)
SELECT @id_cliente_sistema, 'ES7921000813610001111111', 'servicio', 0.00, 'EUR', 'activa', 999999.00
WHERE NOT EXISTS (
    SELECT 1 FROM cuentas WHERE numero_cuenta = 'ES7921000813610001111111'
);

-- 2. Agua
INSERT INTO cuentas (id_cliente, numero_cuenta, tipo_cuenta, saldo, moneda, estado, limite_diario)
SELECT @id_cliente_sistema, 'ES7921000813610002222222', 'servicio', 0.00, 'EUR', 'activa', 999999.00
WHERE NOT EXISTS (
    SELECT 1 FROM cuentas WHERE numero_cuenta = 'ES7921000813610002222222'
);

-- 3. Gas
INSERT INTO cuentas (id_cliente, numero_cuenta, tipo_cuenta, saldo, moneda, estado, limite_diario)
SELECT @id_cliente_sistema, 'ES7921000813610003333333', 'servicio', 0.00, 'EUR', 'activa', 999999.00
WHERE NOT EXISTS (
    SELECT 1 FROM cuentas WHERE numero_cuenta = 'ES7921000813610003333333'
);

-- 4. Teléfono
INSERT INTO cuentas (id_cliente, numero_cuenta, tipo_cuenta, saldo, moneda, estado, limite_diario)
SELECT @id_cliente_sistema, 'ES7921000813610004444444', 'servicio', 0.00, 'EUR', 'activa', 999999.00
WHERE NOT EXISTS (
    SELECT 1 FROM cuentas WHERE numero_cuenta = 'ES7921000813610004444444'
);

-- 5. Internet
INSERT INTO cuentas (id_cliente, numero_cuenta, tipo_cuenta, saldo, moneda, estado, limite_diario)
SELECT @id_cliente_sistema, 'ES7921000813610005555555', 'servicio', 0.00, 'EUR', 'activa', 999999.00
WHERE NOT EXISTS (
    SELECT 1 FROM cuentas WHERE numero_cuenta = 'ES7921000813610005555555'
);

-- 6. TV Cable
INSERT INTO cuentas (id_cliente, numero_cuenta, tipo_cuenta, saldo, moneda, estado, limite_diario)
SELECT @id_cliente_sistema, 'ES7921000813610006666666', 'servicio', 0.00, 'EUR', 'activa', 999999.00
WHERE NOT EXISTS (
    SELECT 1 FROM cuentas WHERE numero_cuenta = 'ES7921000813610006666666'
);

-- 7. Seguro
INSERT INTO cuentas (id_cliente, numero_cuenta, tipo_cuenta, saldo, moneda, estado, limite_diario)
SELECT @id_cliente_sistema, 'ES7921000813610007777777', 'servicio', 0.00, 'EUR', 'activa', 999999.00
WHERE NOT EXISTS (
    SELECT 1 FROM cuentas WHERE numero_cuenta = 'ES7921000813610007777777'
);

-- 8. Otro
INSERT INTO cuentas (id_cliente, numero_cuenta, tipo_cuenta, saldo, moneda, estado, limite_diario)
SELECT @id_cliente_sistema, 'ES7921000813610008888888', 'servicio', 0.00, 'EUR', 'activa', 999999.00
WHERE NOT EXISTS (
    SELECT 1 FROM cuentas WHERE numero_cuenta = 'ES7921000813610008888888'
);

-- =====================================================
-- CREAR VISTA DE SALDOS DE SERVICIOS
-- =====================================================

DROP VIEW IF EXISTS vista_saldos_servicios;

CREATE VIEW vista_saldos_servicios AS
SELECT 
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

-- =====================================================
-- VERIFICACIÓN
-- =====================================================

-- Mostrar las cuentas de servicios creadas
SELECT 'Cuentas de servicios creadas:' AS mensaje;
SELECT nombre_servicio, icono, numero_cuenta, saldo 
FROM vista_saldos_servicios;

-- =====================================================
-- FIN DEL SCRIPT DE MIGRACIÓN
-- =====================================================
