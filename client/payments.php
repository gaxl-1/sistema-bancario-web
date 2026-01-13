<?php
require_once '../config/config.php';
requerirCliente();

$id_cliente = $_SESSION['cliente_id'];
$cuentas = obtenerCuentasCliente($id_cliente);

$mensaje = '';
$tipo_mensaje = '';
$referencia = '';

// Procesar pago de servicio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $mensaje = "Token de seguridad inválido";
        $tipo_mensaje = "danger";
    } else {
        $id_cuenta = (int)$_POST['cuenta'];
        $tipo_servicio = sanitizeInput($_POST['tipo_servicio']);
        $referencia_servicio = sanitizeInput($_POST['referencia_servicio']);
        $monto = (float)$_POST['monto'];
        
        if (!cuentaPerteneceACliente($id_cuenta, $id_cliente)) {
            $mensaje = "La cuenta seleccionada no te pertenece";
            $tipo_mensaje = "danger";
        } else {
            $validacion = validarMontoTransaccion($monto);
            if ($validacion !== true) {
                $mensaje = $validacion;
                $tipo_mensaje = "danger";
            } else {
                // Obtener cuenta de destino del servicio
                $id_cuenta_destino = obtenerIdCuentaServicio($tipo_servicio);
                
                if (!$id_cuenta_destino) {
                    $mensaje = "Servicio no disponible temporalmente";
                    $tipo_mensaje = "danger";
                } else {
                    try {
                        $db = getDB();
                        
                        // Llamar al procedimiento almacenado
                        $stmt = $db->prepare("CALL realizar_transferencia(?, ?, ?, ?, ?, @resultado, @referencia)");
                        $descripcion = "Pago de $tipo_servicio - Ref: $referencia_servicio";
                        $ip = obtenerIP();
                        
                        $stmt->bindParam(1, $id_cuenta, PDO::PARAM_INT);
                        $stmt->bindParam(2, $id_cuenta_destino, PDO::PARAM_INT);
                        $stmt->bindParam(3, $monto, PDO::PARAM_STR);
                        $stmt->bindParam(4, $descripcion, PDO::PARAM_STR);
                        $stmt->bindParam(5, $ip, PDO::PARAM_STR);
                        
                        $stmt->execute();
                        $stmt->closeCursor();
                        
                        // Obtener resultados
                        $result = $db->query("SELECT @resultado AS resultado, @referencia AS referencia")->fetch();
                        
                        if (strpos($result['resultado'], 'ÉXITO') !== false) {
                            $mensaje = "Pago realizado exitosamente";
                            $tipo_mensaje = "success";
                            $referencia = $result['referencia'];
                            
                            // Auditoría adicional específica de pagos
                            registrarAuditoria(
                                $_SESSION['usuario_id'],
                                'Pago de servicio',
                                'transacciones',
                                null,
                                "Servicio: $tipo_servicio, Monto: $monto, Ref: $referencia"
                            );
                        } else {
                            // Extraer mensaje de error
                            $error_msg = str_replace('ERROR: ', '', $result['resultado']);
                            throw new Exception($error_msg);
                        }
                        
                    } catch (Exception $e) {
                        $mensaje = $e->getMessage();
                        $tipo_mensaje = "danger";
                    } catch (PDOException $e) {
                        $mensaje = "Error al procesar el pago";
                        $tipo_mensaje = "danger";
                        error_log("Error en pago: " . $e->getMessage());
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
    <title>Pagar Servicios - <?php echo APP_NAME; ?></title>
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
                <li><a href="transactions.php"><i class="bi bi-clock-history"></i> <span>Historial</span></a></li>
                <li><a href="payments.php" class="active"><i class="bi bi-receipt"></i> <span>Pagar Servicios</span></a></li>
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
                <h1 class="page-title"><i class="bi bi-receipt"></i> Pagar Servicios</h1>
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
                        <?php if ($tipo_mensaje === 'success' && $referencia): ?>
                            <br><strong>Referencia:</strong> <?php echo e($referencia); ?>
                        <?php endif; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-receipt"></i> Pago de Servicios</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Cuenta de Pago *</label>
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
                                        <label class="form-label fw-bold">Tipo de Servicio *</label>
                                        <select class="form-select" name="tipo_servicio" required>
                                            <option value="">Selecciona un servicio</option>
                                            <option value="Electricidad">⚡ Electricidad</option>
                                            <option value="Agua">💧 Agua</option>
                                            <option value="Gas">🔥 Gas</option>
                                            <option value="Teléfono">📞 Teléfono</option>
                                            <option value="Internet">🌐 Internet</option>
                                            <option value="TV Cable">📺 TV Cable</option>
                                            <option value="Seguro">🛡️ Seguro</option>
                                            <option value="Otro">📋 Otro</option>
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Número de Referencia del Servicio *</label>
                                        <input type="text" class="form-control" name="referencia_servicio" 
                                               placeholder="Ej: 123456789" required>
                                        <small class="text-muted">Número de contrato o cliente del servicio</small>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Monto a Pagar *</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="monto" 
                                                   step="0.01" min="0.01" placeholder="0.00" required>
                                            <span class="input-group-text">€</span>
                                        </div>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        El pago se procesará inmediatamente y se descontará de tu cuenta.
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-check-circle"></i> Realizar Pago
                                        </button>
                                        <a href="dashboard.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-x"></i> Cancelar
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Servicios Populares -->
                        <div class="row mt-4 g-3">
                            <div class="col-12">
                                <h5>Servicios Populares</h5>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="bi bi-lightning text-warning" style="font-size: 2rem;"></i>
                                        <h6 class="mt-2">Electricidad</h6>
                                        <small class="text-muted">Pago rápido y seguro</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="bi bi-droplet text-primary" style="font-size: 2rem;"></i>
                                        <h6 class="mt-2">Agua</h6>
                                        <small class="text-muted">Sin comisiones</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="bi bi-wifi text-success" style="font-size: 2rem;"></i>
                                        <h6 class="mt-2">Internet</h6>
                                        <small class="text-muted">Pago instantáneo</small>
                                    </div>
                                </div>
                            </div>
                        </div>
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
