<?php
require_once '../config/config.php';
requerirAdmin();

// Obtener registros de auditoría
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT r.*, u.nombre_usuario, u.email
        FROM registro_auditoria r
        LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
        ORDER BY r.fecha_accion DESC
        LIMIT 500
    ");
    $registros = $stmt->fetchAll();
} catch (PDOException $e) {
    $registros = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría - <?php echo APP_NAME; ?></title>
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
                    <i class="bi bi-shield-lock"></i> Admin Panel
                </a>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a></li>
                <li><a href="users.php"><i class="bi bi-people"></i> <span>Usuarios</span></a></li>
                <li><a href="transactions.php"><i class="bi bi-arrow-left-right"></i> <span>Transacciones</span></a></li>
                <li><a href="audit.php" class="active"><i class="bi bi-file-text"></i> <span>Auditoría</span></a></li>
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
                <h1 class="page-title"><i class="bi bi-file-text"></i> Registro de Auditoría</h1>
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
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Logs del Sistema (<?php echo count($registros); ?> registros)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Usuario</th>
                                        <th>Acción</th>
                                        <th>Tabla</th>
                                        <th>Detalles</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registros as $reg): ?>
                                        <tr>
                                            <td><?php echo $reg['id_registro']; ?></td>
                                            <td><?php echo formatearFecha($reg['fecha_accion']); ?></td>
                                            <td>
                                                <?php if ($reg['nombre_usuario']): ?>
                                                    <strong><?php echo e($reg['nombre_usuario']); ?></strong>
                                                    <br><small class="text-muted"><?php echo e($reg['email']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Sistema</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo e($reg['accion']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo e($reg['tabla_afectada'] ?? '-'); ?></td>
                                            <td>
                                                <small><?php echo e($reg['detalles'] ?? '-'); ?></small>
                                            </td>
                                            <td><code class="small"><?php echo e($reg['ip_address']); ?></code></td>
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
    <script src="../assets/js/chatbot.js"></script>
</body>
</html>
