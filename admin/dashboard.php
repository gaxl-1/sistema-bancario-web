<?php
require_once '../config/config.php';
requerirAdmin();

$stats = obtenerEstadisticasAdmin();

// Obtener usuarios recientes
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT u.*, c.nombres, c.apellidos 
        FROM usuarios u
        LEFT JOIN clientes c ON u.id_usuario = c.id_usuario
        WHERE u.rol = 'cliente'
        ORDER BY u.fecha_registro DESC
        LIMIT 10
    ");
    $usuarios_recientes = $stmt->fetchAll();
} catch (PDOException $e) {
    $usuarios_recientes = [];
}

// Obtener transacciones recientes
try {
    $stmt = $db->query("
        SELECT t.*, co.numero_cuenta as origen, cd.numero_cuenta as destino
        FROM transacciones t
        LEFT JOIN cuentas co ON t.id_cuenta_origen = co.id_cuenta
        LEFT JOIN cuentas cd ON t.id_cuenta_destino = cd.id_cuenta
        ORDER BY t.fecha_transaccion DESC
        LIMIT 10
    ");
    $transacciones_recientes = $stmt->fetchAll();
} catch (PDOException $e) {
    $transacciones_recientes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/chatbot.css">
    <link rel="stylesheet" href="../assets/css/chatbot.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="../index.php" class="sidebar-brand">
                    <i class="bi bi-shield-lock"></i>
                    Admin Panel
                </a>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="active">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="users.php">
                        <i class="bi bi-people"></i>
                        <span>Usuarios</span>
                    </a>
                </li>
                <li>
                    <a href="transactions.php">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>Transacciones</span>
                    </a>
                </li>
                <li>
                    <a href="audit.php">
                        <i class="bi bi-file-text"></i>
                        <span>Auditoría</span>
                    </a>
                </li>
                <li>
                    <a href="reports.php">
                        <i class="bi bi-graph-up"></i>
                        <span>Reportes</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <a href="../logout.php" class="text-white text-decoration-none">
                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <nav class="top-navbar">
                <h1 class="page-title">
                    <i class="bi bi-shield-lock"></i> Panel de Administración
                </h1>
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
                <?php echo mostrarMensajeSesion(); ?>

                <!-- Estadísticas Principales -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card primary">
                            <div class="stat-icon bg-primary text-white">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="stat-label">Total Clientes</div>
                            <div class="stat-value"><?php echo number_format($stats['total_usuarios'] ?? 0); ?></div>
                            <small class="text-muted">Usuarios registrados</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <div class="stat-icon bg-success text-white">
                                <i class="bi bi-wallet2"></i>
                            </div>
                            <div class="stat-label">Cuentas Activas</div>
                            <div class="stat-value"><?php echo number_format($stats['total_cuentas'] ?? 0); ?></div>
                            <small class="text-muted">En el sistema</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning">
                            <div class="stat-icon bg-warning text-white">
                                <i class="bi bi-arrow-repeat"></i>
                            </div>
                            <div class="stat-label">Transacciones Hoy</div>
                            <div class="stat-value"><?php echo number_format($stats['transacciones_hoy'] ?? 0); ?></div>
                            <small class="text-muted">Operaciones realizadas</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card danger">
                            <div class="stat-icon bg-info text-white">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <div class="stat-label">Saldo Total</div>
                            <div class="stat-value" style="font-size: 1.3rem;">
                                <?php echo formatearDinero($stats['saldo_total'] ?? 0); ?>
                            </div>
                            <small class="text-muted">En el sistema</small>
                        </div>
                    </div>
                </div>

                <!-- Saldos de Servicios -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-primary">
                                        <i class="bi bi-bank"></i> Saldos de Proveedores de Servicios
                                    </h5>
                                    <span class="badge bg-primary rounded-pill">
                                        Total: <?php echo formatearDinero(obtenerTotalServicios()); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <?php 
                                    $saldos_servicios = obtenerSaldosServicios(); 
                                    foreach ($saldos_servicios as $servicio):
                                    ?>
                                    <div class="col-md-3">
                                        <div class="p-3 border rounded bg-light hover-shadow text-center">
                                            <div class="fs-1 mb-2"><?php echo $servicio['icono']; ?></div>
                                            <div class="fw-bold text-dark"><?php echo $servicio['nombre_servicio']; ?></div>
                                            <div class="h5 text-primary mb-0 mt-2">
                                                <?php echo formatearDinero($servicio['saldo'], $servicio['moneda']); ?>
                                            </div>
                                            <small class="text-muted" style="font-size: 0.7rem;">
                                                <?php echo $servicio['numero_cuenta']; ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alertas y Pendientes -->
                <?php if (($stats['usuarios_pendientes'] ?? 0) > 0): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Atención:</strong> Hay <?php echo $stats['usuarios_pendientes']; ?> 
                        usuario(s) pendiente(s) de aprobación.
                        <a href="users.php?filter=pendiente" class="alert-link">Ver ahora</a>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Usuarios Recientes -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-person-plus"></i> Usuarios Recientes
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Usuario</th>
                                                <th>Email</th>
                                                <th>Fecha</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($usuarios_recientes as $user): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo e($user['nombre_usuario']); ?></strong>
                                                        <?php if ($user['nombres']): ?>
                                                            <br><small class="text-muted">
                                                                <?php echo e($user['nombres'] . ' ' . $user['apellidos']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo e($user['email']); ?></td>
                                                    <td><?php echo formatearFecha($user['fecha_registro'], 'd/m/Y'); ?></td>
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
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer text-center">
                                <a href="users.php" class="btn btn-sm btn-outline-primary">
                                    Ver Todos los Usuarios <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Transacciones Recientes -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-arrow-left-right"></i> Transacciones Recientes
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Monto</th>
                                                <th>Fecha</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transacciones_recientes as $trans): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo ucfirst($trans['tipo_transaccion']); ?></strong>
                                                        <br><small class="text-muted"><?php echo e($trans['referencia']); ?></small>
                                                    </td>
                                                    <td class="fw-bold"><?php echo formatearDinero($trans['monto']); ?></td>
                                                    <td><?php echo formatearFecha($trans['fecha_transaccion']); ?></td>
                                                    <td>
                                                        <?php
                                                        $badge_class = [
                                                            'completada' => 'bg-success',
                                                            'pendiente' => 'bg-warning',
                                                            'rechazada' => 'bg-danger'
                                                        ];
                                                        ?>
                                                        <span class="badge <?php echo $badge_class[$trans['estado']] ?? 'bg-secondary'; ?>">
                                                            <?php echo ucfirst($trans['estado']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer text-center">
                                <a href="transactions.php" class="btn btn-sm btn-outline-success">
                                    Ver Todas las Transacciones <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h4 class="mb-3">Acciones Rápidas</h4>
                    </div>
                    <div class="col-md-3">
                        <a href="users.php" class="card text-center text-decoration-none hover-lift h-100">
                            <div class="card-body">
                                <i class="bi bi-people text-primary" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">Gestionar Usuarios</h5>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="transactions.php" class="card text-center text-decoration-none hover-lift h-100">
                            <div class="card-body">
                                <i class="bi bi-arrow-left-right text-success" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">Ver Transacciones</h5>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="audit.php" class="card text-center text-decoration-none hover-lift h-100">
                            <div class="card-body">
                                <i class="bi bi-file-text text-warning" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">Auditoría</h5>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="reports.php" class="card text-center text-decoration-none hover-lift h-100">
                            <div class="card-body">
                                <i class="bi bi-graph-up text-info" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">Reportes</h5>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/chatbot.js"></script>
</body>
</html>
