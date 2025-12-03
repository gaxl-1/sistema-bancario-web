# 🏦 Sistema Bancario Web - Banco Seguro

Aplicación bancaria completa desarrollada con PHP, MySQL, HTML, CSS y JavaScript para XAMPP.

## 📋 Características

### Para Visitantes
- ✅ Página principal informativa
- ✅ Registro de nuevos clientes
- ✅ Sistema de autenticación seguro
- ✅ Información sobre servicios

### Para Clientes
- ✅ Dashboard personalizado
- ✅ Gestión de múltiples cuentas
- ✅ Transferencias entre cuentas propias
- ✅ Transferencias a terceros
- ✅ Historial de transacciones
- ✅ Pago de servicios
- ✅ Gestión de perfil
- ✅ Cambio de contraseña

### Para Administradores
- ✅ Panel de administración
- ✅ Gestión de usuarios (CRUD)
- ✅ Monitoreo de transacciones
- ✅ Registro de auditoría
- ✅ Reportes y estadísticas
- ✅ Bloqueo/desbloqueo de cuentas

### Seguridad
- 🔒 Encriptación de contraseñas con bcrypt
- 🔒 Protección contra SQL Injection (PDO)
- 🔒 Protección XSS
- 🔒 Tokens CSRF
- 🔒 Prevención de fuerza bruta
- 🔒 Registro de auditoría completo
- 🔒 Gestión segura de sesiones

## 🚀 Instalación

### Requisitos Previos
- XAMPP (Apache + MySQL + PHP 7.4 o superior)
- Navegador web moderno

### Pasos de Instalación

1. **Descargar e instalar XAMPP**
   - Descarga desde: https://www.apachefriends.org/
   - Instala XAMPP en tu sistema

2. **Copiar archivos del proyecto**
   ```bash
   # Copia la carpeta "Banco" a:
   C:\xampp\htdocs\Banco  (Windows)
   /opt/lampp/htdocs/Banco  (Linux)
   ```

3. **Iniciar servicios de XAMPP**
   - Abre el Panel de Control de XAMPP
   - Inicia Apache
   - Inicia MySQL

4. **Crear la base de datos**
   - Abre tu navegador y ve a: http://localhost/phpmyadmin
   - Crea una nueva base de datos llamada `banco_db`
   - Importa el archivo: `database/database.sql`
   
   O ejecuta desde la terminal:
   ```bash
   mysql -u root -p < database/database.sql
   ```

5. **Configurar la conexión (opcional)**
   - Si usas credenciales diferentes, edita: `config/database.php`
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Tu contraseña de MySQL
   define('DB_NAME', 'banco_db');
   ```

6. **Acceder a la aplicación**
   - Abre tu navegador
   - Ve a: http://localhost/Banco

## 👤 Credenciales de Prueba

### Administrador
- **Usuario:** admin
- **Contraseña:** Admin123!

### Cliente Demo
- **Usuario:** cliente_demo
- **Contraseña:** Cliente123!

## 📁 Estructura del Proyecto

```
Banco/
├── admin/                  # Panel de administración
│   ├── dashboard.php
│   ├── users.php
│   ├── transactions.php
│   ├── audit.php
│   └── reports.php
├── assets/                 # Recursos estáticos
│   ├── css/
│   │   ├── style.css      # Estilos principales
│   │   └── dashboard.css  # Estilos del dashboard
│   └── js/
│       ├── main.js        # JavaScript principal
│       └── validation.js  # Validaciones
├── client/                 # Área de clientes
│   ├── dashboard.php
│   ├── accounts.php
│   ├── transfer.php
│   ├── transactions.php
│   ├── payments.php
│   └── profile.php
├── config/                 # Configuración
│   ├── config.php         # Configuración general
│   └── database.php       # Conexión a BD
├── database/               # Base de datos
│   └── database.sql       # Script SQL
├── includes/               # Archivos incluidos
│   ├── functions.php      # Funciones auxiliares
│   └── security.php       # Funciones de seguridad
├── .htaccess              # Configuración Apache
├── index.php              # Página principal
├── login.php              # Inicio de sesión
├── register.php           # Registro
├── logout.php             # Cerrar sesión
├── about.php              # Sobre nosotros
├── contact.php            # Contacto
└── README.md              # Este archivo
```

## 🗄️ Base de Datos

### Tablas Principales

1. **usuarios** - Credenciales y roles
2. **clientes** - Información personal
3. **cuentas** - Cuentas bancarias
4. **transacciones** - Operaciones bancarias
5. **registro_auditoria** - Logs de seguridad
6. **intentos_login** - Intentos de acceso
7. **sesiones** - Sesiones activas

### Procedimientos Almacenados

- `realizar_transferencia()` - Procesa transferencias de forma segura

## 🔧 Configuración Avanzada

### Límites de Transacciones
Edita en `config/config.php`:
```php
define('LIMITE_TRANSFERENCIA_DIARIO', 5000.00);
define('LIMITE_TRANSFERENCIA_UNICA', 2000.00);
```

### Seguridad de Sesión
```php
define('SESSION_LIFETIME', 3600);  // 1 hora
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos
```

### Contraseñas
```php
define('PASSWORD_MIN_LENGTH', 8);
```

## 🛡️ Características de Seguridad

1. **Autenticación**
   - Contraseñas hasheadas con bcrypt
   - Bloqueo temporal tras intentos fallidos
   - Verificación de estado de cuenta

2. **Autorización**
   - Control de acceso basado en roles
   - Verificación de propiedad de cuentas
   - Tokens CSRF en formularios

3. **Transacciones**
   - Validación de saldos
   - Límites diarios y por operación
   - Procedimientos almacenados
   - Registro de auditoría

4. **Datos**
   - Sanitización de entradas
   - Consultas preparadas (PDO)
   - Validación en frontend y backend

## 📱 Responsive Design

La aplicación es completamente responsive y funciona en:
- 💻 Escritorio
- 📱 Tablets
- 📱 Móviles

## 🐛 Solución de Problemas

### Error de conexión a la base de datos
- Verifica que MySQL esté corriendo en XAMPP
- Comprueba las credenciales en `config/database.php`
- Asegúrate de que la base de datos `banco_db` existe

### Página en blanco
- Activa la visualización de errores en `config/config.php`:
  ```php
  define('ENVIRONMENT', 'development');
  ```
- Revisa los logs de PHP en `xampp/php/logs/`

### Sesión expira muy rápido
- Aumenta `SESSION_LIFETIME` en `config/config.php`

## 📝 Notas Importantes

⚠️ **IMPORTANTE PARA PRODUCCIÓN:**

1. Cambia las contraseñas por defecto
2. Habilita HTTPS
3. Configura `ENVIRONMENT` a `'production'`
4. Actualiza las credenciales de email SMTP
5. Revisa y ajusta los límites de transacciones
6. Implementa backups automáticos
7. Considera agregar autenticación de dos factores (2FA)

## 🤝 Contribuciones

Este es un proyecto educativo. Siéntete libre de:
- Reportar bugs
- Sugerir mejoras
- Hacer fork del proyecto
- Enviar pull requests

## 📄 Licencia

Este proyecto es de código abierto y está disponible bajo la licencia MIT.

## 👨‍💻 Autor

Desarrollado como proyecto educativo de sistema bancario web.
Jairo Gael Mota Lopez

## 📞 Soporte

Para preguntas o problemas:
- Email: lgael4885@gmail.com

---

**¡Gracias por usar Banco Seguro!** 🏦✨
