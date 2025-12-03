// =====================================================
// FUNCIONES PRINCIPALES - SISTEMA BANCARIO
// =====================================================

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar tooltips de Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Menú móvil
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', function () {
            sidebar.classList.toggle('active');
        });
    }

    // Cerrar sidebar al hacer click fuera en móvil
    document.addEventListener('click', function (e) {
        if (window.innerWidth <= 992) {
            if (sidebar && !sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });

    // Auto-cerrar alertas después de 5 segundos
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Formatear números de cuenta mientras se escriben
    const cuentaInputs = document.querySelectorAll('input[name="cuenta_destino"]');
    cuentaInputs.forEach(input => {
        input.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\s/g, '');
            if (value.startsWith('ES')) {
                value = value.substring(0, 24);
                let formatted = 'ES';
                for (let i = 2; i < value.length; i += 4) {
                    formatted += value.substring(i, i + 4) + ' ';
                }
                e.target.value = formatted.trim();
            }
        });
    });

    // Confirmación para acciones peligrosas
    const dangerButtons = document.querySelectorAll('.btn-danger[data-confirm]');
    dangerButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || '¿Estás seguro?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Actualizar reloj en tiempo real (si existe)
    const clockElement = document.getElementById('currentTime');
    if (clockElement) {
        function updateClock() {
            const now = new Date();
            const options = {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };
            clockElement.textContent = now.toLocaleTimeString('es-ES', options);
        }
        updateClock();
        setInterval(updateClock, 1000);
    }
});

// =====================================================
// FUNCIONES DE UTILIDAD
// =====================================================

// Formatear moneda
function formatCurrency(amount, currency = 'EUR') {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

// Formatear fecha
function formatDate(date) {
    return new Intl.DateTimeFormat('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
}

// Copiar al portapapeles
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copiado al portapapeles', 'success');
    }).catch(err => {
        console.error('Error al copiar:', err);
    });
}

// Mostrar toast notification
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// =====================================================
// VALIDACIONES DE FORMULARIOS
// =====================================================

// Validar IBAN español
function validateIBAN(iban) {
    const regex = /^ES\d{22}$/;
    return regex.test(iban.replace(/\s/g, ''));
}

// Validar email
function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// Validar monto
function validateAmount(amount, max = null) {
    const num = parseFloat(amount);
    if (isNaN(num) || num <= 0) {
        return false;
    }
    if (max && num > max) {
        return false;
    }
    return true;
}

// =====================================================
// AJAX HELPERS
// =====================================================

// Realizar petición AJAX
async function ajaxRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error en petición AJAX:', error);
        throw error;
    }
}

// =====================================================
// ANIMACIONES Y EFECTOS
// =====================================================

// Animar contador
function animateCounter(element, target, duration = 1000) {
    const start = 0;
    const increment = target / (duration / 16);
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target.toFixed(2);
            clearInterval(timer);
        } else {
            element.textContent = current.toFixed(2);
        }
    }, 16);
}

// Scroll suave
function smoothScroll(target) {
    document.querySelector(target).scrollIntoView({
        behavior: 'smooth'
    });
}

// =====================================================
// BÚSQUEDA Y FILTRADO
// =====================================================

// Filtrar tabla
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);

    if (!input || !table) return;

    input.addEventListener('keyup', function () {
        const filter = this.value.toUpperCase();
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            let found = false;
            const cells = rows[i].getElementsByTagName('td');

            for (let j = 0; j < cells.length; j++) {
                const cell = cells[j];
                if (cell) {
                    const textValue = cell.textContent || cell.innerText;
                    if (textValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }

            rows[i].style.display = found ? '' : 'none';
        }
    });
}

// =====================================================
// IMPRESIÓN Y EXPORTACIÓN
// =====================================================

// Imprimir comprobante
function printReceipt(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Comprobante</title>');
    printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
    printWindow.document.write('</head><body>');
    printWindow.document.write(element.innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

// Exportar tabla a CSV
function exportTableToCSV(tableId, filename = 'datos.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    for (let row of rows) {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        for (let col of cols) {
            csvRow.push(col.innerText);
        }
        csv.push(csvRow.join(','));
    }

    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
}

// =====================================================
// GESTIÓN DE SESIÓN
// =====================================================

// Verificar actividad del usuario
let inactivityTime = 0;
const maxInactivity = 30 * 60; // 30 minutos en segundos

function resetInactivityTimer() {
    inactivityTime = 0;
}

// Eventos que resetean el timer
['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
    document.addEventListener(event, resetInactivityTimer, true);
});

// Verificar inactividad cada minuto
setInterval(() => {
    inactivityTime += 60;
    if (inactivityTime >= maxInactivity) {
        alert('Tu sesión ha expirado por inactividad');
        window.location.href = '../logout.php';
    }
}, 60000);

console.log('Sistema bancario cargado correctamente');
