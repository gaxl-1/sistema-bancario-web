// =====================================================
// VALIDACIONES DE FORMULARIOS
// =====================================================

document.addEventListener('DOMContentLoaded', function () {
    // Validación de formulario de registro
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            if (!validateRegisterForm()) {
                e.preventDefault();
            }
        });

        // Validación en tiempo real de contraseña
        const passwordInput = registerForm.querySelector('input[name="password"]');
        const passwordConfirm = registerForm.querySelector('input[name="password_confirm"]');

        if (passwordInput) {
            passwordInput.addEventListener('input', function () {
                validatePasswordStrength(this.value);
            });
        }

        if (passwordConfirm) {
            passwordConfirm.addEventListener('input', function () {
                validatePasswordMatch(passwordInput.value, this.value);
            });
        }
    }

    // Validación de formulario de transferencia
    const transferForm = document.getElementById('transferForm');
    if (transferForm) {
        transferForm.addEventListener('submit', function (e) {
            if (!validateTransferForm()) {
                e.preventDefault();
            }
        });
    }
});

// Validar formulario de registro
function validateRegisterForm() {
    let isValid = true;
    const errors = [];

    // Validar nombre de usuario
    const username = document.querySelector('input[name="nombre_usuario"]').value;
    if (username.length < 4) {
        errors.push('El nombre de usuario debe tener al menos 4 caracteres');
        isValid = false;
    }

    // Validar email
    const email = document.querySelector('input[name="email"]').value;
    if (!validateEmail(email)) {
        errors.push('El email no es válido');
        isValid = false;
    }

    // Validar contraseña
    const password = document.querySelector('input[name="password"]').value;
    const passwordErrors = validatePassword(password);
    if (passwordErrors.length > 0) {
        errors.push(...passwordErrors);
        isValid = false;
    }

    // Validar confirmación de contraseña
    const passwordConfirm = document.querySelector('input[name="password_confirm"]').value;
    if (password !== passwordConfirm) {
        errors.push('Las contraseñas no coinciden');
        isValid = false;
    }

    // Validar edad
    const fechaNacimiento = document.querySelector('input[name="fecha_nacimiento"]').value;
    if (fechaNacimiento) {
        const edad = calculateAge(fechaNacimiento);
        if (edad < 18) {
            errors.push('Debes ser mayor de 18 años');
            isValid = false;
        }
    }

    // Validar términos
    const terminos = document.querySelector('input[name="terminos"]').checked;
    if (!terminos) {
        errors.push('Debes aceptar los términos y condiciones');
        isValid = false;
    }

    if (!isValid) {
        showErrors(errors);
    }

    return isValid;
}

// Validar contraseña
function validatePassword(password) {
    const errors = [];

    if (password.length < 8) {
        errors.push('La contraseña debe tener al menos 8 caracteres');
    }
    if (!/[A-Z]/.test(password)) {
        errors.push('Debe contener al menos una letra mayúscula');
    }
    if (!/[a-z]/.test(password)) {
        errors.push('Debe contener al menos una letra minúscula');
    }
    if (!/[0-9]/.test(password)) {
        errors.push('Debe contener al menos un número');
    }
    if (!/[^A-Za-z0-9]/.test(password)) {
        errors.push('Debe contener al menos un carácter especial');
    }

    return errors;
}

// Validar fortaleza de contraseña en tiempo real
function validatePasswordStrength(password) {
    const strengthIndicator = document.getElementById('passwordStrength');
    if (!strengthIndicator) return;

    let strength = 0;
    const checks = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[^A-Za-z0-9]/.test(password)
    };

    strength = Object.values(checks).filter(Boolean).length;

    const strengthLevels = ['Muy débil', 'Débil', 'Media', 'Fuerte', 'Muy fuerte'];
    const strengthColors = ['danger', 'warning', 'info', 'success', 'success'];

    strengthIndicator.textContent = strengthLevels[strength - 1] || 'Muy débil';
    strengthIndicator.className = `text-${strengthColors[strength - 1] || 'danger'}`;
}

// Validar coincidencia de contraseñas
function validatePasswordMatch(password, confirm) {
    const matchIndicator = document.getElementById('passwordMatch');
    if (!matchIndicator) return;

    if (confirm.length === 0) {
        matchIndicator.textContent = '';
        return;
    }

    if (password === confirm) {
        matchIndicator.textContent = '✓ Las contraseñas coinciden';
        matchIndicator.className = 'text-success';
    } else {
        matchIndicator.textContent = '✗ Las contraseñas no coinciden';
        matchIndicator.className = 'text-danger';
    }
}

// Calcular edad
function calculateAge(birthDate) {
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }

    return age;
}

// Validar email
function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// Validar formulario de transferencia
function validateTransferForm() {
    let isValid = true;
    const errors = [];

    // Validar cuenta origen
    const cuentaOrigen = document.querySelector('select[name="cuenta_origen"]').value;
    if (!cuentaOrigen) {
        errors.push('Debes seleccionar una cuenta de origen');
        isValid = false;
    }

    // Validar cuenta destino
    const cuentaDestino = document.querySelector('input[name="cuenta_destino"]').value.replace(/\s/g, '');
    if (!validateIBAN(cuentaDestino)) {
        errors.push('El número de cuenta destino no es válido');
        isValid = false;
    }

    // Validar monto
    const monto = parseFloat(document.querySelector('input[name="monto"]').value);
    if (isNaN(monto) || monto <= 0) {
        errors.push('El monto debe ser mayor a 0');
        isValid = false;
    }

    // Validar saldo disponible
    const cuentaSelect = document.querySelector('select[name="cuenta_origen"]');
    const selectedOption = cuentaSelect.options[cuentaSelect.selectedIndex];
    const saldoDisponible = parseFloat(selectedOption.getAttribute('data-saldo'));

    if (monto > saldoDisponible) {
        errors.push('El monto excede el saldo disponible');
        isValid = false;
    }

    if (!isValid) {
        showErrors(errors);
    }

    return isValid;
}

// Validar IBAN
function validateIBAN(iban) {
    const regex = /^ES\d{22}$/;
    return regex.test(iban);
}

// Mostrar errores
function showErrors(errors) {
    let errorHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    errorHtml += '<strong>Por favor, corrige los siguientes errores:</strong><ul class="mb-0 mt-2">';
    errors.forEach(error => {
        errorHtml += `<li>${error}</li>`;
    });
    errorHtml += '</ul>';
    errorHtml += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    errorHtml += '</div>';

    // Insertar al inicio del formulario
    const form = document.querySelector('form');
    const existingAlert = form.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    form.insertAdjacentHTML('afterbegin', errorHtml);

    // Scroll al error
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Validación de solo números
function onlyNumbers(input) {
    input.value = input.value.replace(/[^0-9]/g, '');
}

// Validación de solo letras
function onlyLetters(input) {
    input.value = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
}

// Formatear número de teléfono
function formatPhone(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 9) {
        value = value.substring(0, 9);
    }
    input.value = value;
}

console.log('Validaciones cargadas correctamente');
