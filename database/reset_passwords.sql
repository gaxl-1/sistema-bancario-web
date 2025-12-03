-- Script para actualizar las contraseñas de los usuarios de prueba
-- Ejecuta este script en phpMyAdmin o desde la línea de comandos

USE banco_db;

-- Actualizar contraseña del admin (Admin123!)
UPDATE usuarios 
SET contrasena = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE nombre_usuario = 'admin';

-- Actualizar contraseña del cliente demo (Cliente123!)
UPDATE usuarios 
SET contrasena = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE nombre_usuario = 'cliente_demo';

-- Resetear intentos fallidos y desbloquear cuentas
UPDATE usuarios 
SET intentos_fallidos = 0, 
    bloqueado_hasta = NULL,
    estado = 'activo'
WHERE nombre_usuario IN ('admin', 'cliente_demo');

-- Verificar las contraseñas actualizadas
SELECT nombre_usuario, email, rol, estado, intentos_fallidos 
FROM usuarios 
WHERE nombre_usuario IN ('admin', 'cliente_demo');
