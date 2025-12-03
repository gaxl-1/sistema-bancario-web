<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-bank2"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">Nosotros</a></li>
                    <li class="nav-item"><a class="nav-link active" href="contact.php">Contacto</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Acceder</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="mb-4">Contacto</h1>
                
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <i class="bi bi-telephone text-primary" style="font-size: 2.5rem;"></i>
                                <h5 class="mt-3">Teléfono</h5>
                                <p class="text-muted">+34 900 123 456</p>
                                <small class="text-muted">Lun - Vie: 9:00 - 18:00</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <i class="bi bi-envelope text-success" style="font-size: 2.5rem;"></i>
                                <h5 class="mt-3">Email</h5>
                                <p class="text-muted">info@bancoseguro.com</p>
                                <small class="text-muted">Respuesta en 24h</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <i class="bi bi-geo-alt text-warning" style="font-size: 2.5rem;"></i>
                                <h5 class="mt-3">Oficina</h5>
                                <p class="text-muted">Madrid, España</p>
                                <small class="text-muted">Calle Mayor 123</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
