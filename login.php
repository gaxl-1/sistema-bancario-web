<?php
require_once 'config/config.php';

// Si ya está autenticado, redirigir
if (estaAutenticado()) {
    header('Location: ' . (esAdministrador() ? 'admin/dashboard.php' : 'client/dashboard.php'));
    exit();
}

$error = '';
$timeout = isset($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Token de seguridad inválido";
    } else {
        $nombre_usuario = sanitizeInput($_POST['nombre_usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        $recordar = isset($_POST['recordar']);
        
        if (empty($nombre_usuario) || empty($password)) {
            $error = "Por favor, completa todos los campos";
        } else {
            // Verificar si la cuenta está bloqueada
            $bloqueo = verificarBloqueoCuenta($nombre_usuario);
            
            if ($bloqueo['bloqueado']) {
                $error = "Cuenta bloqueada temporalmente. Intenta de nuevo en " . $bloqueo['tiempo_restante'] . " minutos.";
            } else {
                try {
                    $db = getDB();
                    $stmt = $db->prepare("
                        SELECT u.*, c.id_cliente, c.nombres, c.apellidos
                        FROM usuarios u
                        LEFT JOIN clientes c ON u.id_usuario = c.id_usuario
                        WHERE u.nombre_usuario = ? OR u.email = ?
                    ");
                    $stmt->execute([$nombre_usuario, $nombre_usuario]);
                    $usuario = $stmt->fetch();
                    
                    if ($usuario && verifyPassword($password, $usuario['contrasena'])) {
                        // Verificar estado de la cuenta
                        if ($usuario['estado'] === 'bloqueado') {
                            $error = "Tu cuenta ha sido bloqueada. Contacta con soporte.";
                            registrarIntentoLogin($nombre_usuario, $usuario['email'], false);
                        } elseif ($usuario['estado'] === 'pendiente') {
                            $error = "Tu cuenta está pendiente de aprobación.";
                            registrarIntentoLogin($nombre_usuario, $usuario['email'], false);
                        } else {
                            // Login exitoso
                            resetearIntentosFallidos($nombre_usuario);
                            registrarIntentoLogin($nombre_usuario, $usuario['email'], true);
                            
                            // Actualizar último acceso
                            $stmt = $db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?");
                            $stmt->execute([$usuario['id_usuario']]);
                            
                            // Crear sesión
                            session_regenerate_id(true);
                            $_SESSION['usuario_id'] = $usuario['id_usuario'];
                            $_SESSION['usuario_nombre'] = $usuario['nombre_usuario'];
                            $_SESSION['usuario_email'] = $usuario['email'];
                            $_SESSION['usuario_rol'] = $usuario['rol'];
                            
                            if ($usuario['rol'] === 'cliente' && $usuario['id_cliente']) {
                                $_SESSION['cliente_id'] = $usuario['id_cliente'];
                                $_SESSION['cliente_nombre_completo'] = $usuario['nombres'] . ' ' . $usuario['apellidos'];
                            }
                            
                            // Guardar sesión en base de datos
                            $session_id = session_id();
                            $expiracion = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
                            $stmt = $db->prepare("
                                INSERT INTO sesiones (id_sesion, id_usuario, ip_address, user_agent, fecha_expiracion)
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $session_id,
                                $usuario['id_usuario'],
                                obtenerIP(),
                                obtenerUserAgent(),
                                $expiracion
                            ]);
                            
                            // Registrar en auditoría
                            registrarAuditoria($usuario['id_usuario'], 'Inicio de sesión exitoso', 'usuarios', $usuario['id_usuario']);
                            
                            // Redirigir según rol
                            if ($usuario['rol'] === 'administrador') {
                                header('Location: admin/dashboard.php');
                            } else {
                                header('Location: client/dashboard.php');
                            }
                            exit();
                        }
                    } else {
                        // Login fallido
                        $error = "Usuario o contraseña incorrectos";
                        if ($usuario) {
                            incrementarIntentosFallidos($nombre_usuario);
                            registrarIntentoLogin($nombre_usuario, $usuario['email'], false);
                        } else {
                            registrarIntentoLogin($nombre_usuario, '', false);
                        }
                    }
                } catch (PDOException $e) {
                    $error = "Error de conexión. Intenta de nuevo.";
                    error_log("Error en login: " . $e->getMessage());
                }
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
    <title>Iniciar Sesión - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/chatbot.css">
</head>
<body class="bg-light">
    <!-- Navegación simple -->
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-bank2 text-primary"></i> <?php echo APP_NAME; ?>
            </a>
            <a href="register.php" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Registrarse
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-lg-5 col-md-7">
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="icon-circle bg-primary text-white mx-auto mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-shield-lock" style="font-size: 2.5rem;"></i>
                            </div>
                            <h2 class="fw-bold">Bienvenido</h2>
                            <p class="text-muted">Accede a tu banca digital</p>
                        </div>

                        <?php if ($timeout): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-clock"></i> Tu sesión ha expirado. Por favor, inicia sesión nuevamente.
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo e($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php echo mostrarMensajeSesion(); ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Usuario o Email</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" name="nombre_usuario" 
                                           value="<?php echo e($_POST['nombre_usuario'] ?? ''); ?>" 
                                           placeholder="Ingresa tu usuario o email" required autofocus>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" name="password" 
                                           placeholder="Ingresa tu contraseña" required id="password">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="recordar" id="recordar">
                                <label class="form-check-label" for="recordar">
                                    Recordar sesión
                                </label>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                                </button>
                            </div>

                            <div class="text-center">
                                <a href="#" class="text-muted small">¿Olvidaste tu contraseña?</a>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="text-muted mb-2">¿No tienes cuenta?</p>
                            <a href="register.php" class="btn btn-outline-primary">
                                <i class="bi bi-person-plus"></i> Crear Cuenta Nueva
                            </a>
                        </div>

                        <!-- Credenciales de prueba -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <small class="text-muted d-block mb-2"><strong>Cuentas de prueba:</strong></small>
                            <small class="text-muted d-block">
                    </div>
                </div>

                <!-- Información de seguridad -->
                <div class="text-center mt-4">
                    <div class="d-flex justify-content-center gap-4 text-muted small">
                        <div>
                            <i class="bi bi-shield-check text-success"></i> Conexión Segura
                        </div>
                        <div>
                            <i class="bi bi-lock text-success"></i> Datos Encriptados
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        });
    </script>
    <script src="assets/js/chatbot.js"></script>
</body>
</html>
