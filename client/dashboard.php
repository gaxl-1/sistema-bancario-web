<?php
require_once '../config/config.php';
requerirCliente();

$usuario = obtenerUsuarioActual();
$id_cliente = $_SESSION['cliente_id'];
$cuentas = obtenerCuentasCliente($id_cliente);

// Obtener total de saldo
$saldo_total = 0;
foreach ($cuentas as $cuenta) {
    $saldo_total += $cuenta['saldo'];
}

// Obtener transacciones recientes
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT t.*, 
               co.numero_cuenta as cuenta_origen_num,
               cd.numero_cuenta as cuenta_destino_num
        FROM transacciones t
        LEFT JOIN cuentas co ON t.id_cuenta_origen = co.id_cuenta
        LEFT JOIN cuentas cd ON t.id_cuenta_destino = cd.id_cuenta
        WHERE (co.id_cliente = ? OR cd.id_cliente = ?)
        ORDER BY t.fecha_transaccion DESC
        LIMIT 10
    ");
    $stmt->execute([$id_cliente, $id_cliente]);
    $transacciones_recientes = $stmt->fetchAll();
} catch (PDOException $e) {
    $transacciones_recientes = [];
    error_log("Error al obtener transacciones: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cliente - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="../index.php" class="sidebar-brand">
                    <i class="bi bi-bank2"></i>
                    <?php echo APP_NAME; ?>
                </a>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="active">
                        <i class="bi bi-speedometer2"></i>
                        <span>Panel Principal</span>
                    </a>
                </li>
                <li>
                    <a href="accounts.php">
                        <i class="bi bi-wallet2"></i>
                        <span>Mis Cuentas</span>
                    </a>
                </li>
                <li>
                    <a href="transfer.php">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>Transferencias</span>
                    </a>
                </li>
                <li>
                    <a href="transactions.php">
                        <i class="bi bi-clock-history"></i>
                        <span>Historial</span>
                    </a>
                </li>
                <li>
                    <a href="payments.php">
                        <i class="bi bi-receipt"></i>
                        <span>Pagar Servicios</span>
                    </a>
                </li>
                <li>
                    <a href="profile.php">
                        <i class="bi bi-person-circle"></i>
                        <span>Mi Perfil</span>
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
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <h1 class="page-title">
                    <button class="mobile-menu-toggle">
                        <i class="bi bi-list"></i>
                    </button>
                    Panel Principal
                </h1>
                <div class="user-info">
                    <div>
                        <div class="fw-bold"><?php echo e($_SESSION['cliente_nombre_completo']); ?></div>
                        <small class="text-muted"><?php echo e($usuario['email']); ?></small>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['cliente_nombre_completo'], 0, 1)); ?>
                    </div>
                </div>
            </nav>

            <!-- Content Area -->
            <div class="content-area">
                <?php echo mostrarMensajeSesion(); ?>

                <!-- Bienvenida -->
                <div class="mb-4">
                    <h2>Bienvenido, <?php echo e(explode(' ', $_SESSION['cliente_nombre_completo'])[0]); ?>! 👋</h2>
                    <p class="text-muted">Aquí está el resumen de tus finanzas</p>
                </div>

                <!-- Estadísticas -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card primary">
                            <div class="stat-icon bg-primary text-white">
                                <i class="bi bi-wallet2"></i>
                            </div>
                            <div class="stat-label">Saldo Total</div>
                            <div class="stat-value"><?php echo formatearDinero($saldo_total); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <div class="stat-icon bg-success text-white">
                                <i class="bi bi-credit-card"></i>
                            </div>
                            <div class="stat-label">Cuentas Activas</div>
                            <div class="stat-value"><?php echo count($cuentas); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning">
                            <div class="stat-icon bg-warning text-white">
                                <i class="bi bi-arrow-repeat"></i>
                            </div>
                            <div class="stat-label">Transacciones Hoy</div>
                            <div class="stat-value">
                                <?php
                                $trans_hoy = 0;
                                foreach ($transacciones_recientes as $t) {
                                    if (date('Y-m-d', strtotime($t['fecha_transaccion'])) === date('Y-m-d')) {
                                        $trans_hoy++;
                                    }
                                }
                                echo $trans_hoy;
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card danger">
                            <div class="stat-icon bg-info text-white">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div class="stat-label">Último Acceso</div>
                            <div class="stat-value" style="font-size: 1rem;">
                                <?php echo formatearFechaRelativa($usuario['ultimo_acceso']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cuentas -->
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <h4 class="mb-3">Mis Cuentas</h4>
                    </div>
                    <?php foreach ($cuentas as $cuenta): ?>
                        <div class="col-md-6">
                            <div class="account-card">
                                <div class="account-type">
                                    <i class="bi bi-credit-card"></i>
                                    Cuenta <?php echo ucfirst($cuenta['tipo_cuenta']); ?>
                                </div>
                                <div class="account-number">
                                    <?php echo e($cuenta['numero_cuenta']); ?>
                                </div>
                                <div class="account-balance">
                                    <?php echo formatearDinero($cuenta['saldo']); ?>
                                </div>
                                <div class="account-actions">
                                    <a href="transfer.php?cuenta=<?php echo $cuenta['id_cuenta']; ?>" class="btn btn-light btn-sm">
                                        <i class="bi bi-send"></i> Transferir
                                    </a>
                                    <a href="transactions.php?cuenta=<?php echo $cuenta['id_cuenta']; ?>" class="btn btn-light btn-sm">
                                        <i class="bi bi-list"></i> Ver Movimientos
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($cuentas)): ?>
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="bi bi-wallet2"></i>
                                <p>No tienes cuentas activas</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Transacciones Recientes -->
                <div class="row">
                    <div class="col-12">
                        <h4 class="mb-3">Transacciones Recientes</h4>
                        <div class="transaction-table">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Descripción</th>
                                        <th>Fecha</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($transacciones_recientes)): ?>
                                        <?php foreach ($transacciones_recientes as $trans): ?>
                                            <?php
                                            // Determinar si es ingreso o egreso
                                            $es_ingreso = false;
                                            foreach ($cuentas as $c) {
                                                if ($c['numero_cuenta'] === $trans['cuenta_destino_num']) {
                                                    $es_ingreso = true;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <tr class="transaction-row">
                                                <td>
                                                    <div class="transaction-icon <?php echo $es_ingreso ? 'income' : 'expense'; ?>">
                                                        <i class="bi bi-<?php echo $es_ingreso ? 'arrow-down' : 'arrow-up'; ?>"></i>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo ucfirst($trans['tipo_transaccion']); ?></div>
                                                    <small class="text-muted"><?php echo e($trans['descripcion'] ?? 'Sin descripción'); ?></small>
                                                </td>
                                                <td><?php echo formatearFecha($trans['fecha_transaccion']); ?></td>
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
                                            <td colspan="5" class="text-center py-4">
                                                <div class="empty-state">
                                                    <i class="bi bi-inbox"></i>
                                                    <p>No hay transacciones recientes</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (!empty($transacciones_recientes)): ?>
                            <div class="text-center mt-3">
                                <a href="transactions.php" class="btn btn-outline-primary">
                                    Ver Todas las Transacciones <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h4 class="mb-3">Acciones Rápidas</h4>
                    </div>
                    <div class="col-md-3 col-6">
                        <a href="transfer.php" class="card text-center text-decoration-none hover-lift h-100">
                            <div class="card-body">
                                <i class="bi bi-arrow-left-right text-primary" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">Transferir</h5>
                                <p class="text-muted small">Enviar dinero</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-6">
                        <a href="deposit.php" class="card text-center text-decoration-none hover-lift h-100">
                            <div class="card-body">
                                <i class="bi bi-cash-stack text-success" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">Depositar</h5>
                                <p class="text-muted small">Agregar fondos</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-6">
                        <a href="payments.php" class="card text-center text-decoration-none hover-lift h-100">
                            <div class="card-body">
                                <i class="bi bi-receipt text-warning" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">Pagar</h5>
                                <p class="text-muted small">Servicios</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-6">
                        <a href="transactions.php" class="card text-center text-decoration-none hover-lift h-100">
                            <div class="card-body">
                                <i class="bi bi-clock-history text-info" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">Historial</h5>
                                <p class="text-muted small">Ver movimientos</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
