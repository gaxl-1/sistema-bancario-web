<?php
require_once '../config/config.php';
requerirAdmin();

$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $mensaje = "Token de seguridad inválido";
        $tipo_mensaje = "danger";
    } else {
        $accion = $_POST['accion'] ?? '';
        $id_usuario = (int)($_POST['id_usuario'] ?? 0);
        
        try {
            $db = getDB();
            
            if ($accion === 'bloquear') {
                $stmt = $db->prepare("UPDATE usuarios SET estado = 'bloqueado' WHERE id_usuario = ?");
                $stmt->execute([$id_usuario]);
                registrarAuditoria($_SESSION['usuario_id'], 'Usuario bloqueado', 'usuarios', $id_usuario);
                $mensaje = "Usuario bloqueado exitosamente";
                $tipo_mensaje = "success";
            } elseif ($accion === 'activar') {
                $stmt = $db->prepare("UPDATE usuarios SET estado = 'activo', intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = ?");
                $stmt->execute([$id_usuario]);
                registrarAuditoria($_SESSION['usuario_id'], 'Usuario activado', 'usuarios', $id_usuario);
                $mensaje = "Usuario activado exitosamente";
                $tipo_mensaje = "success";
            } elseif ($accion === 'eliminar') {
                $stmt = $db->prepare("DELETE FROM usuarios WHERE id_usuario = ? AND rol != 'administrador'");
                $stmt->execute([$id_usuario]);
                registrarAuditoria($_SESSION['usuario_id'], 'Usuario eliminado', 'usuarios', $id_usuario);
                $mensaje = "Usuario eliminado exitosamente";
                $tipo_mensaje = "success";
            }
        } catch (PDOException $e) {
            $mensaje = "Error al procesar la acción";
            $tipo_mensaje = "danger";
            error_log("Error en gestión de usuarios: " . $e->getMessage());
        }
    }
}

// Obtener usuarios
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT u.*, c.nombres, c.apellidos, c.documento_identidad,
               (SELECT COUNT(*) FROM cuentas WHERE id_cliente = c.id_cliente) as num_cuentas
        FROM usuarios u
        LEFT JOIN clientes c ON u.id_usuario = c.id_usuario
        ORDER BY u.fecha_registro DESC
    ");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $usuarios = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="../index.php" class="sidebar-brand">
                    <i class="bi bi-shield-lock"></i> Admin Panel
                </a>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a></li>
                <li><a href="users.php" class="active"><i class="bi bi-people"></i> <span>Usuarios</span></a></li>
                <li><a href="transactions.php"><i class="bi bi-arrow-left-right"></i> <span>Transacciones</span></a></li>
                <li><a href="audit.php"><i class="bi bi-file-text"></i> <span>Auditoría</span></a></li>
                <li><a href="reports.php"><i class="bi bi-graph-up"></i> <span>Reportes</span></a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="../logout.php" class="text-white text-decoration-none">
                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                </a>
            </div>
        </aside>

        <main class="main-content">
            <nav class="top-navbar">
                <h1 class="page-title"><i class="bi bi-people"></i> Gestión de Usuarios</h1>
                <div class="user-info">
                    <div>
                        <div class="fw-bold">Administrador</div>
                        <small class="text-muted"><?php echo e($_SESSION['usuario_email']); ?></small>
                    </div>
                    <div class="user-avatar bg-danger">
                        <i class="bi bi-shield-lock"></i>
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

                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-people"></i> Todos los Usuarios</h5>
                        <span class="badge bg-light text-primary"><?php echo count($usuarios); ?> usuarios</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Nombre Completo</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Cuentas</th>
                                        <th>Estado</th>
                                        <th>Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id_usuario']; ?></td>
                                            <td><strong><?php echo e($user['nombre_usuario']); ?></strong></td>
                                            <td>
                                                <?php if ($user['nombres']): ?>
                                                    <?php echo e($user['nombres'] . ' ' . $user['apellidos']); ?>
                                                    <br><small class="text-muted"><?php echo e($user['documento_identidad']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo e($user['email']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $user['rol'] === 'administrador' ? 'bg-danger' : 'bg-info'; ?>">
                                                    <?php echo ucfirst($user['rol']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php echo $user['num_cuentas'] ?? 0; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = [
                                                    'activo' => 'bg-success',
                                                    'bloqueado' => 'bg-danger',
                                                    'pendiente' => 'bg-warning'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $badge_class[$user['estado']]; ?>">
                                                    <?php echo ucfirst($user['estado']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatearFecha($user['fecha_registro'], 'd/m/Y'); ?></td>
                                            <td>
                                                <?php if ($user['rol'] !== 'administrador'): ?>
                                                    <div class="btn-group btn-group-sm">
                                                        <?php if ($user['estado'] === 'activo'): ?>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                                                                <input type="hidden" name="accion" value="bloquear">
                                                                <input type="hidden" name="id_usuario" value="<?php echo $user['id_usuario']; ?>">
                                                                <button type="submit" class="btn btn-warning" title="Bloquear">
                                                                    <i class="bi bi-lock"></i>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                                                                <input type="hidden" name="accion" value="activar">
                                                                <input type="hidden" name="id_usuario" value="<?php echo $user['id_usuario']; ?>">
                                                                <button type="submit" class="btn btn-success" title="Activar">
                                                                    <i class="bi bi-unlock"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este usuario?');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                                                            <input type="hidden" name="accion" value="eliminar">
                                                            <input type="hidden" name="id_usuario" value="<?php echo $user['id_usuario']; ?>">
                                                            <button type="submit" class="btn btn-danger" title="Eliminar">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
