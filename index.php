<?php require_once 'config/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Banca Digital Segura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    
    <!-- Navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-bank2"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">Nosotros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contacto</a>
                    </li>
                    <?php if (estaAutenticado()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo esAdministrador() ? 'admin/dashboard.php' : 'client/dashboard.php'; ?>">
                                <i class="bi bi-speedometer2"></i> Panel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Salir
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Acceder
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-light text-primary ms-2 px-3" href="register.php">
                                Registrarse
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-75">
                <div class="col-lg-6">
                    <h1 class="display-3 fw-bold mb-4 animate-fade-in">
                        Banca Digital del Futuro
                    </h1>
                    <p class="lead mb-4 text-muted">
                        Gestiona tus finanzas de forma segura, rápida y desde cualquier lugar. 
                        Tecnología de última generación para proteger tu dinero.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="register.php" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-person-plus"></i> Abrir Cuenta
                        </a>
                        <a href="login.php" class="btn btn-outline-primary btn-lg px-4">
                            <i class="bi bi-box-arrow-in-right"></i> Acceder
                        </a>
                    </div>
                    <div class="mt-4 d-flex gap-4">
                        <div class="stat-item">
                            <div class="h3 fw-bold text-primary mb-0">100%</div>
                            <small class="text-muted">Seguro</small>
                        </div>
                        <div class="stat-item">
                            <div class="h3 fw-bold text-primary mb-0">24/7</div>
                            <small class="text-muted">Disponible</small>
                        </div>
                        <div class="stat-item">
                            <div class="h3 fw-bold text-primary mb-0">0€</div>
                            <small class="text-muted">Comisiones</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image">
                        <div class="card shadow-lg border-0 floating">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="icon-circle bg-primary text-white me-3">
                                        <i class="bi bi-shield-check"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">Seguridad Garantizada</h5>
                                        <small class="text-muted">Encriptación de nivel bancario</small>
                                    </div>
                                </div>
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: 100%"></div>
                                </div>
                                <small class="text-success"><i class="bi bi-check-circle-fill"></i> Protección activa</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Servicios -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Nuestros Servicios</h2>
                <p class="lead text-muted">Todo lo que necesitas para gestionar tu dinero</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-primary text-white mx-auto mb-3">
                                <i class="bi bi-credit-card fs-3"></i>
                            </div>
                            <h4 class="card-title">Cuentas Bancarias</h4>
                            <p class="card-text text-muted">
                                Cuentas corrientes y de ahorro sin comisiones. 
                                Gestión 100% digital y segura.
                            </p>
                            <ul class="list-unstyled text-start mt-3">
                                <li><i class="bi bi-check-circle text-success"></i> Sin comisiones de mantenimiento</li>
                                <li><i class="bi bi-check-circle text-success"></i> Transferencias instantáneas</li>
                                <li><i class="bi bi-check-circle text-success"></i> Tarjeta de débito gratuita</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-success text-white mx-auto mb-3">
                                <i class="bi bi-arrow-left-right fs-3"></i>
                            </div>
                            <h4 class="card-title">Transferencias</h4>
                            <p class="card-text text-muted">
                                Envía y recibe dinero al instante. 
                                Nacional e internacional.
                            </p>
                            <ul class="list-unstyled text-start mt-3">
                                <li><i class="bi bi-check-circle text-success"></i> Transferencias inmediatas</li>
                                <li><i class="bi bi-check-circle text-success"></i> Sin límites entre tus cuentas</li>
                                <li><i class="bi bi-check-circle text-success"></i> Historial completo</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-warning text-white mx-auto mb-3">
                                <i class="bi bi-receipt fs-3"></i>
                            </div>
                            <h4 class="card-title">Pagos de Servicios</h4>
                            <p class="card-text text-muted">
                                Paga tus facturas de forma rápida y segura 
                                desde cualquier dispositivo.
                            </p>
                            <ul class="list-unstyled text-start mt-3">
                                <li><i class="bi bi-check-circle text-success"></i> Luz, agua, gas, teléfono</li>
                                <li><i class="bi bi-check-circle text-success"></i> Programación de pagos</li>
                                <li><i class="bi bi-check-circle text-success"></i> Comprobantes digitales</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Características -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold mb-4">¿Por qué elegirnos?</h2>
                    <div class="feature-list">
                        <div class="feature-item d-flex mb-4">
                            <div class="icon-circle bg-primary text-white me-3">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <div>
                                <h5>Máxima Seguridad</h5>
                                <p class="text-muted mb-0">
                                    Protección con encriptación de nivel bancario y autenticación multifactor.
                                </p>
                            </div>
                        </div>
                        <div class="feature-item d-flex mb-4">
                            <div class="icon-circle bg-success text-white me-3">
                                <i class="bi bi-lightning"></i>
                            </div>
                            <div>
                                <h5>Operaciones Instantáneas</h5>
                                <p class="text-muted mb-0">
                                    Transferencias y pagos procesados en tiempo real, 24/7.
                                </p>
                            </div>
                        </div>
                        <div class="feature-item d-flex mb-4">
                            <div class="icon-circle bg-info text-white me-3">
                                <i class="bi bi-phone"></i>
                            </div>
                            <div>
                                <h5>100% Digital</h5>
                                <p class="text-muted mb-0">
                                    Accede desde cualquier dispositivo, en cualquier momento y lugar.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="stats-card card border-0 shadow-lg">
                        <div class="card-body p-5">
                            <h3 class="mb-4">Confianza y Experiencia</h3>
                            <div class="row text-center g-4">
                                <div class="col-6">
                                    <div class="stat-box">
                                        <div class="h2 fw-bold text-primary mb-2">
                                            <i class="bi bi-people"></i> 50K+
                                        </div>
                                        <p class="text-muted mb-0">Clientes Satisfechos</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-box">
                                        <div class="h2 fw-bold text-success mb-2">
                                            <i class="bi bi-graph-up"></i> 99.9%
                                        </div>
                                        <p class="text-muted mb-0">Disponibilidad</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-box">
                                        <div class="h2 fw-bold text-warning mb-2">
                                            <i class="bi bi-award"></i> #1
                                        </div>
                                        <p class="text-muted mb-0">En Seguridad</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-box">
                                        <div class="h2 fw-bold text-info mb-2">
                                            <i class="bi bi-clock-history"></i> 24/7
                                        </div>
                                        <p class="text-muted mb-0">Soporte</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-3">¿Listo para comenzar?</h2>
            <p class="lead mb-4">Abre tu cuenta en menos de 5 minutos</p>
            <a href="register.php" class="btn btn-light btn-lg px-5">
                <i class="bi bi-person-plus"></i> Registrarse Ahora
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="bi bi-bank2"></i> <?php echo APP_NAME; ?></h5>
                    <p class="text-muted">Banca digital segura y confiable</p>
                </div>
                <div class="col-md-3">
                    <h6>Enlaces</h6>
                    <ul class="list-unstyled">
                        <li><a href="about.php" class="text-muted">Nosotros</a></li>
                        <li><a href="contact.php" class="text-muted">Contacto</a></li>
                        <li><a href="#" class="text-muted">Términos y Condiciones</a></li>
                        <li><a href="#" class="text-muted">Privacidad</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Contacto</h6>
                    <p class="text-muted mb-1">
                        <i class="bi bi-envelope"></i> info@bancoseguro.com
                    </p>
                    <p class="text-muted">
                        <i class="bi bi-telephone"></i> +34 900 123 456
                    </p>
                </div>
            </div>
            <hr class="my-3 bg-secondary">
            <div class="text-center text-muted">
                <small>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos los derechos reservados.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
