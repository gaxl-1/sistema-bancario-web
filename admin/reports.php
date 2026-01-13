<?php
require_once '../config/config.php';
requerirAdmin();

$stats = obtenerEstadisticasAdmin();

// Estadísticas adicionales
try {
    $db = getDB();
    
    // Transacciones por tipo
    $stmt = $db->query("
        SELECT tipo_transaccion, COUNT(*) as total, SUM(monto) as monto_total
        FROM transacciones
        WHERE estado = 'completada'
        GROUP BY tipo_transaccion
    ");
    $trans_por_tipo = $stmt->fetchAll();
    
    // Actividad por día (últimos 7 días)
    $stmt = $db->query("
        SELECT DATE(fecha_transaccion) as fecha, COUNT(*) as total
        FROM transacciones
        WHERE fecha_transaccion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(fecha_transaccion)
        ORDER BY fecha DESC
    ");
    $actividad_diaria = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $trans_por_tipo = [];
    $actividad_diaria = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - <?php echo APP_NAME; ?></title>
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
                <li><a href="audit.php"><i class="bi bi-file-text"></i> <span>Auditoría</span></a></li>
                <li><a href="reports.php" class="active"><i class="bi bi-graph-up"></i> <span>Reportes</span></a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="../logout.php" class="text-white text-decoration-none">
                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                </a>
            </div>
        </aside>

        <main class="main-content">
            <nav class="top-navbar">
                <h1 class="page-title"><i class="bi bi-graph-up"></i> Reportes y Estadísticas</h1>
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
                <!-- Estadísticas Generales -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card primary">
                            <div class="stat-icon bg-primary text-white">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="stat-label">Total Usuarios</div>
                            <div class="stat-value"><?php echo number_format($stats['total_usuarios'] ?? 0); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <div class="stat-icon bg-success text-white">
                                <i class="bi bi-wallet2"></i>
                            </div>
                            <div class="stat-label">Total Cuentas</div>
                            <div class="stat-value"><?php echo number_format($stats['total_cuentas'] ?? 0); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning">
                            <div class="stat-icon bg-warning text-white">
                                <i class="bi bi-arrow-repeat"></i>
                            </div>
                            <div class="stat-label">Transacciones</div>
                            <div class="stat-value"><?php echo number_format($stats['transacciones_hoy'] ?? 0); ?></div>
                            <small>Hoy</small>
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
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Transacciones por Tipo -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Transacciones por Tipo</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Cantidad</th>
                                            <th>Monto Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($trans_por_tipo as $tipo): ?>
                                            <tr>
                                                <td><strong><?php echo ucfirst($tipo['tipo_transaccion']); ?></strong></td>
                                                <td><?php echo number_format($tipo['total']); ?></td>
                                                <td><?php echo formatearDinero($tipo['monto_total']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Actividad Diaria -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-calendar-week"></i> Actividad Últimos 7 Días</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Transacciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($actividad_diaria as $dia): ?>
                                            <tr>
                                                <td><?php echo formatearFecha($dia['fecha'], 'd/m/Y'); ?></td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-success" style="width: <?php echo min(100, ($dia['total'] / 10) * 100); ?>%">
                                                            <?php echo $dia['total']; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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
