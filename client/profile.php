<?php
require_once '../config/config.php';
requerirCliente();

$id_cliente = $_SESSION['cliente_id'];
$usuario = obtenerUsuarioActual();

$mensaje = '';
$tipo_mensaje = '';

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $mensaje = "Token de seguridad inválido";
        $tipo_mensaje = "danger";
    } else {
        $password_actual = $_POST['password_actual'];
        $password_nueva = $_POST['password_nueva'];
        $password_confirmar = $_POST['password_confirmar'];
        
        // Verificar contraseña actual
        if (!verifyPassword($password_actual, $usuario['contrasena'])) {
            $mensaje = "La contraseña actual es incorrecta";
            $tipo_mensaje = "danger";
        } elseif ($password_nueva !== $password_confirmar) {
            $mensaje = "Las contraseñas nuevas no coinciden";
            $tipo_mensaje = "danger";
        } else {
            $validacion = validarPassword($password_nueva);
            if ($validacion !== true) {
                $mensaje = $validacion;
                $tipo_mensaje = "danger";
            } else {
                try {
                    $db = getDB();
                    $nueva_hash = hashPassword($password_nueva);
                    $stmt = $db->prepare("UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?");
                    $stmt->execute([$nueva_hash, $_SESSION['usuario_id']]);
                    
                    registrarAuditoria($_SESSION['usuario_id'], 'Cambio de contraseña', 'usuarios', $_SESSION['usuario_id']);
                    
                    $mensaje = "Contraseña actualizada correctamente";
                    $tipo_mensaje = "success";
                } catch (PDOException $e) {
                    $mensaje = "Error al actualizar la contraseña";
                    $tipo_mensaje = "danger";
                }
            }
        }
    }
}

// Obtener datos del cliente
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM clientes WHERE id_usuario = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $cliente = $stmt->fetch();
} catch (PDOException $e) {
    $cliente = null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/chatbot.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="../index.php" class="sidebar-brand">
                    <i class="bi bi-bank2"></i> <?php echo APP_NAME; ?>
                </a>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> <span>Panel Principal</span></a></li>
                <li><a href="accounts.php"><i class="bi bi-wallet2"></i> <span>Mis Cuentas</span></a></li>
                <li><a href="transfer.php"><i class="bi bi-arrow-left-right"></i> <span>Transferencias</span></a></li>
                <li><a href="transactions.php"><i class="bi bi-clock-history"></i> <span>Historial</span></a></li>
                <li><a href="payments.php"><i class="bi bi-receipt"></i> <span>Pagar Servicios</span></a></li>
                <li><a href="profile.php" class="active"><i class="bi bi-person-circle"></i> <span>Mi Perfil</span></a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="../logout.php" class="text-white text-decoration-none">
                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                </a>
            </div>
        </aside>

        <main class="main-content">
            <nav class="top-navbar">
                <h1 class="page-title"><i class="bi bi-person-circle"></i> Mi Perfil</h1>
                <div class="user-info">
                    <div>
                        <div class="fw-bold"><?php echo e($_SESSION['cliente_nombre_completo']); ?></div>
                        <small class="text-muted">Cliente</small>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['cliente_nombre_completo'], 0, 1)); ?>
                    </div>
                </div>
            </nav>

            <div class="content-area">
                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                        <?php echo e($mensaje); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Información Personal -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-person"></i> Información Personal</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($cliente): ?>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="text-muted small">Nombres</label>
                                            <p class="fw-bold"><?php echo e($cliente['nombres']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small">Apellidos</label>
                                            <p class="fw-bold"><?php echo e($cliente['apellidos']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small">Documento</label>
                                            <p class="fw-bold"><?php echo e($cliente['tipo_documento'] . ': ' . $cliente['documento_identidad']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small">Fecha de Nacimiento</label>
                                            <p class="fw-bold"><?php echo formatearFecha($cliente['fecha_nacimiento'], 'd/m/Y'); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small">Teléfono</label>
                                            <p class="fw-bold"><?php echo e($cliente['telefono'] ?? 'No registrado'); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small">Email</label>
                                            <p class="fw-bold"><?php echo e($usuario['email']); ?></p>
                                        </div>
                                        <div class="col-12">
                                            <label class="text-muted small">Dirección</label>
                                            <p class="fw-bold"><?php echo e($cliente['direccion'] ?? 'No registrada'); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="text-muted small">Ciudad</label>
                                            <p class="fw-bold"><?php echo e($cliente['ciudad'] ?? '-'); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="text-muted small">Código Postal</label>
                                            <p class="fw-bold"><?php echo e($cliente['codigo_postal'] ?? '-'); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="text-muted small">País</label>
                                            <p class="fw-bold"><?php echo e($cliente['pais']); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Seguridad -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header bg-warning text-white">
                                <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Seguridad</h5>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted">Usuario: <strong><?php echo e($usuario['nombre_usuario']); ?></strong></p>
                                <p class="small text-muted">Último acceso: <strong><?php echo $usuario['ultimo_acceso'] ? formatearFecha($usuario['ultimo_acceso']) : 'Primer acceso'; ?></strong></p>
                                <p class="small text-muted">Estado: 
                                    <span class="badge bg-success">
                                        <?php echo ucfirst($usuario['estado']); ?>
                                    </span>
                                </p>
                                <hr>
                                <button class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#cambiarPasswordModal">
                                    <i class="bi bi-key"></i> Cambiar Contraseña
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Cambiar Contraseña -->
    <div class="modal fade" id="cambiarPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-key"></i> Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Contraseña Actual *</label>
                            <input type="password" class="form-control" name="password_actual" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nueva Contraseña *</label>
                            <input type="password" class="form-control" name="password_nueva" required minlength="8">
                            <small class="text-muted">Mínimo 8 caracteres, mayúsculas, minúsculas, números y símbolos</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirmar Nueva Contraseña *</label>
                            <input type="password" class="form-control" name="password_confirmar" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="cambiar_password" class="btn btn-primary">Cambiar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/chatbot.js"></script>
</body>
</html>
