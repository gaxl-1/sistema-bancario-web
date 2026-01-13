<?php
require_once '../config/config.php';
requerirCliente();

$id_cliente = $_SESSION['cliente_id'];
$cuentas = obtenerCuentasCliente($id_cliente);

// Filtros
$cuenta_filtro = isset($_GET['cuenta']) ? (int)$_GET['cuenta'] : null;
$tipo_filtro = isset($_GET['tipo']) ? sanitizeInput($_GET['tipo']) : null;
$fecha_desde = isset($_GET['fecha_desde']) ? sanitizeInput($_GET['fecha_desde']) : null;
$fecha_hasta = isset($_GET['fecha_hasta']) ? sanitizeInput($_GET['fecha_hasta']) : null;

// Construir consulta
try {
    $db = getDB();
    
    // Primero obtener los IDs de las cuentas del cliente
    $ids_cuentas = array_column($cuentas, 'id_cuenta');
    $placeholders = str_repeat('?,', count($ids_cuentas) - 1) . '?';
    
    $sql = "SELECT t.*, 
            co.numero_cuenta as cuenta_origen_num,
            cd.numero_cuenta as cuenta_destino_num
            FROM transacciones t
            LEFT JOIN cuentas co ON t.id_cuenta_origen = co.id_cuenta
            LEFT JOIN cuentas cd ON t.id_cuenta_destino = cd.id_cuenta
            WHERE (t.id_cuenta_origen IN ($placeholders) OR t.id_cuenta_destino IN ($placeholders))";
    
    $params = array_merge($ids_cuentas, $ids_cuentas);
    
    if ($cuenta_filtro) {
        $sql .= " AND (t.id_cuenta_origen = ? OR t.id_cuenta_destino = ?)";
        $params[] = $cuenta_filtro;
        $params[] = $cuenta_filtro;
    }
    
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
    
    $sql .= " ORDER BY t.fecha_transaccion DESC LIMIT 100";
    
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
    <title>Historial de Transacciones - <?php echo APP_NAME; ?></title>
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
                <li><a href="transactions.php" class="active"><i class="bi bi-clock-history"></i> <span>Historial</span></a></li>
                <li><a href="payments.php"><i class="bi bi-receipt"></i> <span>Pagar Servicios</span></a></li>
                <li><a href="profile.php"><i class="bi bi-person-circle"></i> <span>Mi Perfil</span></a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="../logout.php" class="text-white text-decoration-none">
                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                </a>
            </div>
        </aside>

        <main class="main-content">
            <nav class="top-navbar">
                <h1 class="page-title"><i class="bi bi-clock-history"></i> Historial de Transacciones</h1>
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
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="bi bi-funnel"></i> Filtros</h5>
                        <form method="GET" action="">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Cuenta</label>
                                    <select class="form-select" name="cuenta">
                                        <option value="">Todas las cuentas</option>
                                        <?php foreach ($cuentas as $cuenta): ?>
                                            <option value="<?php echo $cuenta['id_cuenta']; ?>" 
                                                    <?php echo $cuenta_filtro == $cuenta['id_cuenta'] ? 'selected' : ''; ?>>
                                                <?php echo e($cuenta['numero_cuenta']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tipo</label>
                                    <select class="form-select" name="tipo">
                                        <option value="">Todos los tipos</option>
                                        <option value="transferencia" <?php echo $tipo_filtro == 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                                        <option value="deposito" <?php echo $tipo_filtro == 'deposito' ? 'selected' : ''; ?>>Depósito</option>
                                        <option value="retiro" <?php echo $tipo_filtro == 'retiro' ? 'selected' : ''; ?>>Retiro</option>
                                        <option value="pago_servicio" <?php echo $tipo_filtro == 'pago_servicio' ? 'selected' : ''; ?>>Pago de Servicio</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Desde</label>
                                    <input type="date" class="form-control" name="fecha_desde" value="<?php echo e($fecha_desde); ?>">
                                </div>
                                <div class="col-md-2">
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

                <!-- Tabla de Transacciones -->
                <div class="transaction-table">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Referencia</th>
                                <th>Descripción</th>
                                <th>Monto</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($transacciones)): ?>
                                <?php foreach ($transacciones as $trans): ?>
                                    <?php
                                    $es_ingreso = false;
                                    foreach ($cuentas as $c) {
                                        if ($c['numero_cuenta'] === $trans['cuenta_destino_num']) {
                                            $es_ingreso = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo formatearFecha($trans['fecha_transaccion']); ?></td>
                                        <td>
                                            <div class="transaction-icon <?php echo $es_ingreso ? 'income' : 'expense'; ?> d-inline-flex">
                                                <i class="bi bi-<?php echo $es_ingreso ? 'arrow-down' : 'arrow-up'; ?>"></i>
                                            </div>
                                            <?php echo ucfirst($trans['tipo_transaccion']); ?>
                                        </td>
                                        <td><code><?php echo e($trans['referencia']); ?></code></td>
                                        <td><?php echo e($trans['descripcion'] ?? '-'); ?></td>
                                        <td class="fw-bold <?php echo $es_ingreso ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo ($es_ingreso ? '+' : '-') . formatearDinero($trans['monto']); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = [
                                                'completada' => 'bg-success',
                                                'pendiente' => 'bg-warning',
                                                'rechazada' => 'bg-danger',
                                                'cancelada' => 'bg-secondary'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $badge_class[$trans['estado']] ?? 'bg-secondary'; ?>">
                                                <?php echo ucfirst($trans['estado']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <p>No se encontraron transacciones</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($transacciones)): ?>
                    <div class="text-center mt-3">
                        <small class="text-muted">Mostrando <?php echo count($transacciones); ?> transacciones</small>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/chatbot.js"></script>
</body>
</html>
