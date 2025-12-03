<?php
require_once '../config/config.php';
requerirCliente();

$id_cliente = $_SESSION['cliente_id'];
$cuentas = obtenerCuentasCliente($id_cliente);

$mensaje = '';
$tipo_mensaje = '';

// Procesar depósito
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $mensaje = "Token de seguridad inválido";
        $tipo_mensaje = "danger";
    } else {
        $id_cuenta = (int)$_POST['cuenta'];
        $monto = (float)$_POST['monto'];
        $descripcion = sanitizeInput($_POST['descripcion'] ?? 'Depósito de fondos');
        
        if (!cuentaPerteneceACliente($id_cuenta, $id_cliente)) {
            $mensaje = "La cuenta seleccionada no te pertenece";
            $tipo_mensaje = "danger";
        } else {
            $validacion = validarMontoTransaccion($monto);
            if ($validacion !== true) {
                $mensaje = $validacion;
                $tipo_mensaje = "danger";
            } else {
                try {
                    $db = getDB();
                    $db->beginTransaction();
                    
                    // Obtener cuenta
                    $stmt = $db->prepare("SELECT saldo, estado FROM cuentas WHERE id_cuenta = ? FOR UPDATE");
                    $stmt->execute([$id_cuenta]);
                    $cuenta = $stmt->fetch();
                    
                    if ($cuenta['estado'] !== 'activa') {
                        throw new Exception("La cuenta no está activa");
                    }
                    
                    // Generar referencia
                    $referencia = generarReferencia('DEP');
                    
                    // Actualizar saldo
                    $stmt = $db->prepare("UPDATE cuentas SET saldo = saldo + ? WHERE id_cuenta = ?");
                    $stmt->execute([$monto, $id_cuenta]);
                    
                    // Registrar transacción
                    $stmt = $db->prepare("
                        INSERT INTO transacciones (
                            id_cuenta_destino, tipo_transaccion, monto, descripcion, 
                            referencia, estado, saldo_destino_anterior, saldo_destino_nuevo, ip_origen
                        ) VALUES (?, 'deposito', ?, ?, ?, 'completada', ?, ?, ?)
                    ");
                    $stmt->execute([
                        $id_cuenta,
                        $monto,
                        $descripcion,
                        $referencia,
                        $cuenta['saldo'],
                        $cuenta['saldo'] + $monto,
                        obtenerIP()
                    ]);
                    
                    registrarAuditoria(
                        $_SESSION['usuario_id'],
                        'Depósito realizado',
                        'transacciones',
                        null,
                        "Monto: $monto, Referencia: $referencia"
                    );
                    
                    $db->commit();
                    $mensaje = "Depósito realizado exitosamente. Referencia: $referencia";
                    $tipo_mensaje = "success";
                    
                    // Recargar cuentas
                    $cuentas = obtenerCuentasCliente($id_cliente);
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $mensaje = $e->getMessage();
                    $tipo_mensaje = "danger";
                } catch (PDOException $e) {
                    $db->rollBack();
                    $mensaje = "Error al procesar el depósito";
                    $tipo_mensaje = "danger";
                    error_log("Error en depósito: " . $e->getMessage());
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
    <title>Depositar Fondos - <?php echo APP_NAME; ?></title>
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
                    <i class="bi bi-bank2"></i> <?php echo APP_NAME; ?>
                </a>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> <span>Panel Principal</span></a></li>
                <li><a href="accounts.php"><i class="bi bi-wallet2"></i> <span>Mis Cuentas</span></a></li>
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
                <h1 class="page-title"><i class="bi bi-cash-stack"></i> Depositar Fondos</h1>
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

                <div class="row">
                    <div class="col-lg-6 mx-auto">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Agregar Fondos</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Cuenta Destino *</label>
                                        <select class="form-select" name="cuenta" required>
                                            <option value="">Selecciona una cuenta</option>
                                            <?php foreach ($cuentas as $cuenta): ?>
                                                <option value="<?php echo $cuenta['id_cuenta']; ?>">
                                                    <?php echo e($cuenta['numero_cuenta']); ?> - 
                                                    <?php echo formatearDinero($cuenta['saldo']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Monto a Depositar *</label>
                                        <div class="input-group input-group-lg">
                                            <input type="number" class="form-control" name="monto" 
                                                   step="0.01" min="0.01" placeholder="0.00" required>
                                            <span class="input-group-text">€</span>
                                        </div>
                                        <small class="text-muted">Monto mínimo: €0.01</small>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Descripción (Opcional)</label>
                                        <input type="text" class="form-control" name="descripcion" 
                                               placeholder="Ej: Depósito inicial, Ahorro mensual, etc.">
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Nota:</strong> Esta es una función de demostración. 
                                        En producción, los depósitos se realizarían mediante transferencias bancarias reales.
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="bi bi-plus-circle"></i> Depositar Fondos
                                        </button>
                                        <a href="dashboard.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left"></i> Volver al Dashboard
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Información adicional -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <h6><i class="bi bi-question-circle"></i> ¿Cómo funciona?</h6>
                                <ul class="small text-muted mb-0">
                                    <li>Selecciona la cuenta donde deseas agregar fondos</li>
                                    <li>Ingresa el monto que deseas depositar</li>
                                    <li>El saldo se actualizará inmediatamente</li>
                                    <li>Recibirás una referencia de la transacción</li>
                                </ul>
                            </div>
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
