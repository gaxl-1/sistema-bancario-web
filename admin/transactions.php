<?php
require_once '../config/config.php';
requerirAdmin();

// Filtros
$tipo_filtro = isset($_GET['tipo']) ? sanitizeInput($_GET['tipo']) : null;
$fecha_desde = isset($_GET['fecha_desde']) ? sanitizeInput($_GET['fecha_desde']) : null;
$fecha_hasta = isset($_GET['fecha_hasta']) ? sanitizeInput($_GET['fecha_hasta']) : null;

// Obtener transacciones
try {
    $db = getDB();
    
    $sql = "SELECT t.*, 
            co.numero_cuenta as cuenta_origen_num,
            cd.numero_cuenta as cuenta_destino_num,
            clo.nombres as cliente_origen_nombre,
            clo.apellidos as cliente_origen_apellido,
            cld.nombres as cliente_destino_nombre,
            cld.apellidos as cliente_destino_apellido
            FROM transacciones t
            LEFT JOIN cuentas co ON t.id_cuenta_origen = co.id_cuenta
            LEFT JOIN cuentas cd ON t.id_cuenta_destino = cd.id_cuenta
            LEFT JOIN clientes clo ON co.id_cliente = clo.id_cliente
            LEFT JOIN clientes cld ON cd.id_cliente = cld.id_cliente
            WHERE 1=1";
    
    $params = [];
    
    if ($tipo_filtro) {
        $sql .= " AND t.tipo_transaccion = ?";
        $params[] = $tipo_filtro;
    }
    
    if ($fecha_desde) {
        $sql .= " AND DATE(t.fecha_transaccion) >= ?";
        $params[] = $fecha_desde;
    }
    
    if ($fecha_hasta) {
        $sql .= " AND DATE(t.fecha_transaccion) <= ?";
        $params[] = $fecha_hasta;
    }
    
    $sql .= " ORDER BY t.fecha_transaccion DESC LIMIT 200";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $transacciones = $stmt->fetchAll();
} catch (PDOException $e) {
    $transacciones = [];
    error_log("Error al obtener transacciones: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transacciones - <?php echo APP_NAME; ?></title>
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
                <li><a href="transactions.php" class="active"><i class="bi bi-arrow-left-right"></i> <span>Transacciones</span></a></li>
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
                <h1 class="page-title"><i class="bi bi-arrow-left-right"></i> Monitoreo de Transacciones</h1>
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
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="bi bi-funnel"></i> Filtros</h5>
                        <form method="GET" action="">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Tipo</label>
                                    <select class="form-select" name="tipo">
                                        <option value="">Todos los tipos</option>
                                        <option value="transferencia" <?php echo $tipo_filtro == 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                                        <option value="deposito" <?php echo $tipo_filtro == 'deposito' ? 'selected' : ''; ?>>Depósito</option>
                                        <option value="retiro" <?php echo $tipo_filtro == 'retiro' ? 'selected' : ''; ?>>Retiro</option>
                                        <option value="pago_servicio" <?php echo $tipo_filtro == 'pago_servicio' ? 'selected' : ''; ?>>Pago de Servicio</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Desde</label>
                                    <input type="date" class="form-control" name="fecha_desde" value="<?php echo e($fecha_desde); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Hasta</label>
                                    <input type="date" class="form-control" name="fecha_hasta" value="<?php echo e($fecha_hasta); ?>">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search"></i> Filtrar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Todas las Transacciones (<?php echo count($transacciones); ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Origen</th>
                                        <th>Destino</th>
                                        <th>Monto</th>
                                        <th>Referencia</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transacciones as $trans): ?>
                                        <tr>
                                            <td><?php echo $trans['id_transaccion']; ?></td>
                                            <td><?php echo formatearFecha($trans['fecha_transaccion']); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst($trans['tipo_transaccion']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($trans['cuenta_origen_num']): ?>
                                                    <small><?php echo e($trans['cuenta_origen_num']); ?></small>
                                                    <?php if ($trans['cliente_origen_nombre']): ?>
                                                        <br><small class="text-muted">
                                                            <?php echo e($trans['cliente_origen_nombre'] . ' ' . $trans['cliente_origen_apellido']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($trans['cuenta_destino_num']): ?>
                                                    <small><?php echo e($trans['cuenta_destino_num']); ?></small>
                                                    <?php if ($trans['cliente_destino_nombre']): ?>
                                                        <br><small class="text-muted">
                                                            <?php echo e($trans['cliente_destino_nombre'] . ' ' . $trans['cliente_destino_apellido']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-bold"><?php echo formatearDinero($trans['monto']); ?></td>
                                            <td><code class="small"><?php echo e($trans['referencia']); ?></code></td>
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
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/chatbot.js"></script>
</body>
</html>
