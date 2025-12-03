<?php
require_once 'config/config.php';

// Si ya está autenticado, redirigir
if (estaAutenticado()) {
    header('Location: ' . (esAdministrador() ? 'admin/dashboard.php' : 'client/dashboard.php'));
    exit();
}

$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $errores[] = "Token de seguridad inválido";
    } else {
        // Sanitizar datos
        $nombre_usuario = sanitizeInput($_POST['nombre_usuario'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $nombres = sanitizeInput($_POST['nombres'] ?? '');
        $apellidos = sanitizeInput($_POST['apellidos'] ?? '');
        $documento = sanitizeInput($_POST['documento_identidad'] ?? '');
        $tipo_documento = sanitizeInput($_POST['tipo_documento'] ?? 'DNI');
        $fecha_nacimiento = sanitizeInput($_POST['fecha_nacimiento'] ?? '');
        $telefono = sanitizeInput($_POST['telefono'] ?? '');
        $direccion = sanitizeInput($_POST['direccion'] ?? '');
        $ciudad = sanitizeInput($_POST['ciudad'] ?? '');
        $codigo_postal = sanitizeInput($_POST['codigo_postal'] ?? '');
        $terminos = isset($_POST['terminos']);
        
        // Validaciones
        if (empty($nombre_usuario) || strlen($nombre_usuario) < 4) {
            $errores[] = "El nombre de usuario debe tener al menos 4 caracteres";
        }
        
        if (!validarEmail($email)) {
            $errores[] = "El email no es válido";
        }
        
        $errores_password = validarPassword($password);
        if (!empty($errores_password)) {
            $errores = array_merge($errores, $errores_password);
        }
        
        if ($password !== $password_confirm) {
            $errores[] = "Las contraseñas no coinciden";
        }
        
        if (empty($nombres) || empty($apellidos)) {
            $errores[] = "Nombres y apellidos son obligatorios";
        }
        
        if (empty($documento)) {
            $errores[] = "El documento de identidad es obligatorio";
        }
        
        if (empty($fecha_nacimiento)) {
            $errores[] = "La fecha de nacimiento es obligatoria";
        } else {
            $edad = date_diff(date_create($fecha_nacimiento), date_create('today'))->y;
            if ($edad < 18) {
                $errores[] = "Debes ser mayor de 18 años para abrir una cuenta";
            }
        }
        
        if (!$terminos) {
            $errores[] = "Debes aceptar los términos y condiciones";
        }
        
        // Si no hay errores, procesar registro
        if (empty($errores)) {
            try {
                $db = getDB();
                
                // Verificar si el usuario o email ya existen
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE nombre_usuario = ? OR email = ?");
                $stmt->execute([$nombre_usuario, $email]);
                if ($stmt->fetch()['total'] > 0) {
                    $errores[] = "El nombre de usuario o email ya están registrados";
                } else {
                    // Verificar si el documento ya existe
                    $stmt = $db->prepare("SELECT COUNT(*) as total FROM clientes WHERE documento_identidad = ?");
                    $stmt->execute([$documento]);
                    if ($stmt->fetch()['total'] > 0) {
                        $errores[] = "El documento de identidad ya está registrado";
                    } else {
                        // Iniciar transacción
                        $db->beginTransaction();
                        
                        try {
                            // Crear usuario
                            $password_hash = hashPassword($password);
                            $stmt = $db->prepare("
                                INSERT INTO usuarios (nombre_usuario, email, contrasena, rol, estado)
                                VALUES (?, ?, ?, 'cliente', 'activo')
                            ");
                            $stmt->execute([$nombre_usuario, $email, $password_hash]);
                            $id_usuario = $db->lastInsertId();
                            
                            // Crear cliente
                            $stmt = $db->prepare("
                                INSERT INTO clientes (
                                    id_usuario, nombres, apellidos, documento_identidad, 
                                    tipo_documento, fecha_nacimiento, telefono, direccion, 
                                    ciudad, codigo_postal, verificado
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)
                            ");
                            $stmt->execute([
                                $id_usuario, $nombres, $apellidos, $documento,
                                $tipo_documento, $fecha_nacimiento, $telefono, $direccion,
                                $ciudad, $codigo_postal
                            ]);
                            $id_cliente = $db->lastInsertId();
                            
                            // Crear cuenta corriente inicial
                            $numero_cuenta = generarNumeroCuenta();
                            $stmt = $db->prepare("
                                INSERT INTO cuentas (id_cliente, numero_cuenta, tipo_cuenta, saldo, estado)
                                VALUES (?, ?, 'corriente', 0.00, 'activa')
                            ");
                            $stmt->execute([$id_cliente, $numero_cuenta]);
                            
                            // Registrar en auditoría
                            registrarAuditoria($id_usuario, 'Registro de nuevo cliente', 'usuarios', $id_usuario, 
                                "Cliente: $nombres $apellidos - Documento: $documento");
                            
                            $db->commit();
                            $exito = true;
                            
                        } catch (Exception $e) {
                            $db->rollBack();
                            $errores[] = "Error al crear la cuenta: " . $e->getMessage();
                            error_log("Error en registro: " . $e->getMessage());
                        }
                    }
                }
            } catch (PDOException $e) {
                $errores[] = "Error de base de datos";
                error_log("Error en registro: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <!-- Navegación simple -->
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-bank2 text-primary"></i> <?php echo APP_NAME; ?>
            </a>
            <a href="login.php" class="btn btn-outline-primary">
                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($exito): ?>
                    <div class="card border-0 shadow-lg">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                            </div>
                            <h2 class="mb-3">¡Registro Exitoso!</h2>
                            <p class="lead text-muted mb-4">
                                Tu cuenta ha sido creada correctamente. Ya puedes acceder a tu banca digital.
                            </p>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                Se ha creado una cuenta corriente automáticamente para ti.
                            </div>
                            <a href="login.php" class="btn btn-primary btn-lg px-5 mt-3">
                                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card border-0 shadow-lg">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <h2 class="fw-bold">Crear Cuenta</h2>
                                <p class="text-muted">Completa el formulario para abrir tu cuenta bancaria</p>
                            </div>

                            <?php if (!empty($errores)): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle"></i> <strong>Errores:</strong>
                                    <ul class="mb-0 mt-2">
                                        <?php foreach ($errores as $error): ?>
                                            <li><?php echo e($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" id="registerForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                                
                                <!-- Datos de Acceso -->
                                <h5 class="mb-3"><i class="bi bi-person-lock"></i> Datos de Acceso</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre de Usuario *</label>
                                        <input type="text" class="form-control" name="nombre_usuario" 
                                               value="<?php echo e($_POST['nombre_usuario'] ?? ''); ?>" required>
                                        <small class="text-muted">Mínimo 4 caracteres</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contraseña *</label>
                                        <input type="password" class="form-control" name="password" required>
                                        <small class="text-muted">Mínimo 8 caracteres, mayúsculas, minúsculas, números y símbolos</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirmar Contraseña *</label>
                                        <input type="password" class="form-control" name="password_confirm" required>
                                    </div>
                                </div>

                                <!-- Datos Personales -->
                                <h5 class="mb-3"><i class="bi bi-person"></i> Datos Personales</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Nombres *</label>
                                        <input type="text" class="form-control" name="nombres" 
                                               value="<?php echo e($_POST['nombres'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Apellidos *</label>
                                        <input type="text" class="form-control" name="apellidos" 
                                               value="<?php echo e($_POST['apellidos'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tipo de Documento *</label>
                                        <select class="form-select" name="tipo_documento" required>
                                            <option value="DNI" selected>DNI</option>
                                            <option value="Pasaporte">Pasaporte</option>
                                            <option value="Cédula">Cédula</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Número de Documento *</label>
                                        <input type="text" class="form-control" name="documento_identidad" 
                                               value="<?php echo e($_POST['documento_identidad'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Fecha de Nacimiento *</label>
                                        <input type="date" class="form-control" name="fecha_nacimiento" 
                                               value="<?php echo e($_POST['fecha_nacimiento'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <!-- Datos de Contacto -->
                                <h5 class="mb-3"><i class="bi bi-geo-alt"></i> Datos de Contacto</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" name="telefono" 
                                               value="<?php echo e($_POST['telefono'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Ciudad</label>
                                        <input type="text" class="form-control" name="ciudad" 
                                               value="<?php echo e($_POST['ciudad'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Dirección</label>
                                        <input type="text" class="form-control" name="direccion" 
                                               value="<?php echo e($_POST['direccion'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Código Postal</label>
                                        <input type="text" class="form-control" name="codigo_postal" 
                                               value="<?php echo e($_POST['codigo_postal'] ?? ''); ?>">
                                    </div>
                                </div>

                                <!-- Términos y Condiciones -->
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" name="terminos" id="terminos" required>
                                    <label class="form-check-label" for="terminos">
                                        Acepto los <a href="#" class="text-primary">términos y condiciones</a> 
                                        y la <a href="#" class="text-primary">política de privacidad</a> *
                                    </label>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-person-plus"></i> Crear Cuenta
                                    </button>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left"></i> Volver al Inicio
                                    </a>
                                </div>
                            </form>

                            <div class="text-center mt-4">
                                <p class="text-muted">
                                    ¿Ya tienes cuenta? <a href="login.php" class="text-primary fw-bold">Inicia Sesión</a>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/validation.js"></script>
</body>
</html>
