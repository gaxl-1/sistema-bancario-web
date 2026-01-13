<?php
require_once '../config/config.php';
requerirCliente();

$id_cliente = $_SESSION['cliente_id'];
$cuentas = obtenerCuentasCliente($id_cliente);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Cuentas - <?php echo APP_NAME; ?></title>
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
                <li><a href="accounts.php" class="active"><i class="bi bi-wallet2"></i> <span>Mis Cuentas</span></a></li>
                <li><a href="transfer.php"><i class="bi bi-arrow-left-right"></i> <span>Transferencias</span></a></li>
                <li><a href="transactions.php"><i class="bi bi-clock-history"></i> <span>Historial</span></a></li>
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
                <h1 class="page-title"><i class="bi bi-wallet2"></i> Mis Cuentas</h1>
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
                <div class="row g-4">
                    <?php foreach ($cuentas as $cuenta): ?>
                        <div class="col-md-6">
                            <div class="account-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="account-type">
                                            <i class="bi bi-credit-card"></i>
                                            Cuenta <?php echo ucfirst($cuenta['tipo_cuenta']); ?>
                                        </div>
                                        <div class="account-number">
                                            <?php echo e($cuenta['numero_cuenta']); ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-dark">
                                        <?php echo ucfirst($cuenta['estado']); ?>
                                    </span>
                                </div>
                                
                                <div class="account-balance">
                                    <?php echo formatearDinero($cuenta['saldo']); ?>
                                </div>
                                
                                <div class="row text-white-50 small mb-3">
                                    <div class="col-6">
                                        <div>Apertura</div>
                                        <div class="fw-bold"><?php echo formatearFecha($cuenta['fecha_apertura'], 'd/m/Y'); ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div>Límite Diario</div>
                                        <div class="fw-bold"><?php echo formatearDinero($cuenta['limite_diario']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="account-actions">
                                    <a href="transfer.php?cuenta=<?php echo $cuenta['id_cuenta']; ?>" class="btn btn-light btn-sm">
                                        <i class="bi bi-send"></i> Transferir
                                    </a>
                                    <a href="transactions.php?cuenta=<?php echo $cuenta['id_cuenta']; ?>" class="btn btn-light btn-sm">
                                        <i class="bi bi-list"></i> Movimientos
                                    </a>
                                    <button class="btn btn-light btn-sm" onclick="copyToClipboard('<?php echo $cuenta['numero_cuenta']; ?>')">
                                        <i class="bi bi-clipboard"></i> Copiar
                                    </button>
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
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/chatbot.js"></script>
</body>
</html>
