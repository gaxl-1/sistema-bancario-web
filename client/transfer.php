<?php
require_once '../config/config.php';
requerirCliente();

$id_cliente = $_SESSION['cliente_id'];
$cuentas = obtenerCuentasCliente($id_cliente);

$error = '';
$exito = false;
$referencia = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Token de seguridad inválido";
    } else {
        $id_cuenta_origen = (int)$_POST['cuenta_origen'];
        $numero_cuenta_destino = sanitizeInput($_POST['cuenta_destino']);
        $monto = (float)$_POST['monto'];
        $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
        
        // Validaciones
        if (!cuentaPerteneceACliente($id_cuenta_origen, $id_cliente)) {
            $error = "La cuenta de origen no te pertenece";
        } elseif (!validarNumeroCuenta($numero_cuenta_destino)) {
            $error = "Número de cuenta destino inválido";
        } else {
            $validacion_monto = validarMontoTransaccion($monto);
            if ($validacion_monto !== true) {
                $error = $validacion_monto;
            } else {
                // Obtener cuenta destino
                $cuenta_destino = obtenerCuentaPorNumero($numero_cuenta_destino);
                
                if (!$cuenta_destino) {
                    $error = "La cuenta destino no existe";
                } elseif ($cuenta_destino['estado'] !== 'activa') {
                    $error = "La cuenta destino no está activa";
                } else {
                    try {
                        $db = getDB();
                        $db->beginTransaction();
                        
                        try {
                            // Obtener cuenta origen con bloqueo
                            $stmt = $db->prepare("SELECT saldo, estado, limite_diario FROM cuentas WHERE id_cuenta = ? FOR UPDATE");
                            $stmt->execute([$id_cuenta_origen]);
                            $cuenta_origen = $stmt->fetch();
                            
                            if (!$cuenta_origen) {
                                throw new Exception("Cuenta origen no encontrada");
                            }
                            
                            if ($cuenta_origen['estado'] !== 'activa') {
                                throw new Exception("Cuenta origen no está activa");
                            }
                            
                            if ($cuenta_origen['saldo'] < $monto) {
                                throw new Exception("Saldo insuficiente");
                            }
                            
                            // Verificar límite diario
                            $stmt = $db->prepare("
                                SELECT COALESCE(SUM(monto), 0) as total_hoy
                                FROM transacciones
                                WHERE id_cuenta_origen = ?
                                AND DATE(fecha_transaccion) = CURDATE()
                                AND estado = 'completada'
                            ");
                            $stmt->execute([$id_cuenta_origen]);
                            $total_hoy = $stmt->fetch()['total_hoy'];
                            
                            if (($total_hoy + $monto) > $cuenta_origen['limite_diario']) {
                                throw new Exception("Límite diario excedido");
                            }
                            
                            // Obtener cuenta destino con bloqueo
                            $stmt = $db->prepare("SELECT saldo, estado FROM cuentas WHERE id_cuenta = ? FOR UPDATE");
                            $stmt->execute([$cuenta_destino['id_cuenta']]);
                            $cuenta_dest = $stmt->fetch();
                            
                            if ($cuenta_dest['estado'] !== 'activa') {
                                throw new Exception("Cuenta destino no está activa");
                            }
                            
                            // Generar referencia única
                            $referencia = generarReferencia('TRF');
                            
                            // Actualizar saldos
                            $stmt = $db->prepare("UPDATE cuentas SET saldo = saldo - ? WHERE id_cuenta = ?");
                            $stmt->execute([$monto, $id_cuenta_origen]);
                            
                            $stmt = $db->prepare("UPDATE cuentas SET saldo = saldo + ? WHERE id_cuenta = ?");
                            $stmt->execute([$monto, $cuenta_destino['id_cuenta']]);
                            
                            // Registrar transacción
                            $stmt = $db->prepare("
                                INSERT INTO transacciones (
                                    id_cuenta_origen, id_cuenta_destino, tipo_transaccion, monto,
                                    descripcion, referencia, estado, saldo_origen_anterior,
                                    saldo_origen_nuevo, saldo_destino_anterior, saldo_destino_nuevo, ip_origen
                                ) VALUES (?, ?, 'transferencia', ?, ?, ?, 'completada', ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $id_cuenta_origen,
                                $cuenta_destino['id_cuenta'],
                                $monto,
                                $descripcion,
                                $referencia,
                                $cuenta_origen['saldo'],
                                $cuenta_origen['saldo'] - $monto,
                                $cuenta_dest['saldo'],
                                $cuenta_dest['saldo'] + $monto,
                                obtenerIP()
                            ]);
                            
                            // Registrar en auditoría
                            registrarAuditoria(
                                $_SESSION['usuario_id'],
                                'Transferencia realizada',
                                'transacciones',
                                null,
                                "Monto: $monto, Referencia: $referencia"
                            );
                            
                            $db->commit();
                            $exito = true;
                            
                        } catch (Exception $e) {
                            $db->rollBack();
                            $error = $e->getMessage();
                            error_log("Error en transferencia: " . $e->getMessage());
                        }
                    } catch (PDOException $e) {
                        $error = "Error al procesar la transferencia";
                        error_log("Error en transferencia: " . $e->getMessage());
                    }
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
    <title>Transferencias - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar (mismo que dashboard) -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="../index.php" class="sidebar-brand">
                    <i class="bi bi-bank2"></i>
                    <?php echo APP_NAME; ?>
                </a>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php">
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
                    <a href="transfer.php" class="active">
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
            <nav class="top-navbar">
                <h1 class="page-title">
                    <i class="bi bi-arrow-left-right"></i> Transferencias
                </h1>
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
                <?php if ($exito): ?>
                    <div class="card border-0 shadow-lg">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                            </div>
                            <h2 class="mb-3">¡Transferencia Exitosa!</h2>
                            <p class="lead text-muted mb-4">
                                Tu transferencia se ha procesado correctamente
                            </p>
                            
                            <div class="transfer-summary mx-auto" style="max-width: 500px;">
                                <div class="transfer-summary-item">
                                    <span>Referencia:</span>
                                    <strong><?php echo e($referencia); ?></strong>
                                </div>
                                <div class="transfer-summary-item">
                                    <span>Monto:</span>
                                    <strong><?php echo formatearDinero($_POST['monto']); ?></strong>
                                </div>
                                <div class="transfer-summary-item">
                                    <span>Fecha:</span>
                                    <strong><?php echo formatearFecha(time()); ?></strong>
                                </div>
                            </div>
                            
                            <div class="mt-4 d-flex gap-3 justify-content-center">
                                <a href="transfer.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-repeat"></i> Nueva Transferencia
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-house"></i> Volver al Inicio
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-lg-8 mx-auto">
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle"></i> <?php echo e($error); ?>
                                </div>
                            <?php endif; ?>

                            <div class="transfer-form">
                                <h3 class="mb-4">
                                    <i class="bi bi-send"></i> Nueva Transferencia
                                </h3>
                                
                                <form method="POST" action="" id="transferForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Cuenta de Origen *</label>
                                        <select class="form-select" name="cuenta_origen" required id="cuentaOrigen">
                                            <option value="">Selecciona una cuenta</option>
                                            <?php foreach ($cuentas as $cuenta): ?>
                                                <option value="<?php echo $cuenta['id_cuenta']; ?>" 
                                                        data-saldo="<?php echo $cuenta['saldo']; ?>"
                                                        <?php echo (isset($_GET['cuenta']) && $_GET['cuenta'] == $cuenta['id_cuenta']) ? 'selected' : ''; ?>>
                                                    <?php echo e($cuenta['numero_cuenta']); ?> - 
                                                    <?php echo formatearDinero($cuenta['saldo']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Saldo disponible: <span id="saldoDisponible">-</span></small>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Cuenta de Destino *</label>
                                        <input type="text" class="form-control" name="cuenta_destino" 
                                               placeholder="ES79 2100 0813 6101 2345 6789" 
                                               pattern="ES\d{22}" required maxlength="24">
                                        <small class="text-muted">Formato: ES seguido de 22 dígitos</small>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Monto *</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="monto" 
                                                   step="0.01" min="0.01" 
                                                   max="<?php echo LIMITE_TRANSFERENCIA_UNICA; ?>" 
                                                   placeholder="0.00" required id="montoInput">
                                            <span class="input-group-text">€</span>
                                        </div>
                                        <small class="text-muted">
                                            Límite por transferencia: <?php echo formatearDinero(LIMITE_TRANSFERENCIA_UNICA); ?>
                                        </small>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Descripción</label>
                                        <textarea class="form-control" name="descripcion" rows="3" 
                                                  placeholder="Concepto de la transferencia (opcional)"></textarea>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Importante:</strong> Verifica que los datos sean correctos antes de confirmar. 
                                        Las transferencias son inmediatas y no pueden revertirse.
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-send"></i> Realizar Transferencia
                                        </button>
                                        <a href="dashboard.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-x"></i> Cancelar
                                        </a>
                                    </div>
                                </form>
                            </div>

                            <!-- Información adicional -->
                            <div class="row mt-4 g-3">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6><i class="bi bi-shield-check text-success"></i> Seguridad</h6>
                                            <p class="small text-muted mb-0">
                                                Todas las transferencias están protegidas con encriptación de nivel bancario.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6><i class="bi bi-lightning text-warning"></i> Inmediato</h6>
                                            <p class="small text-muted mb-0">
                                                Las transferencias se procesan instantáneamente, 24/7.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Actualizar saldo disponible
        document.getElementById('cuentaOrigen').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const saldo = selected.getAttribute('data-saldo');
            if (saldo) {
                document.getElementById('saldoDisponible').textContent = 
                    new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(saldo);
            }
        });
        
        // Trigger inicial
        document.getElementById('cuentaOrigen').dispatchEvent(new Event('change'));
        
        // Validación de monto
        document.getElementById('transferForm').addEventListener('submit', function(e) {
            const monto = parseFloat(document.getElementById('montoInput').value);
            const cuentaSelect = document.getElementById('cuentaOrigen');
            const saldo = parseFloat(cuentaSelect.options[cuentaSelect.selectedIndex].getAttribute('data-saldo'));
            
            if (monto > saldo) {
                e.preventDefault();
                alert('El monto excede el saldo disponible');
            }
        });
    </script>
</body>
</html>
