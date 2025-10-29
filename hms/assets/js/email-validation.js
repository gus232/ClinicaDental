/**
 * ============================================================================
 * VALIDACIÓN DE CORREOS CORPORATIVOS
 * ============================================================================
 *
 * Este archivo contiene funciones para validar correos corporativos
 * según la configuración del sistema.
 *
 * IMPORTANTE: Las siguientes variables deben ser definidas en el HTML
 * antes de cargar este script:
 * - CORPORATE_EMAIL_DOMAIN
 * - EMAIL_FORMAT_TEMPLATE
 * - ALLOW_CUSTOM_EMAILS
 */

/**
 * Validar que un email cumpla con el formato corporativo
 *
 * @param {string} email - Email a validar
 * @returns {boolean} - true si es válido
 */
function validateCorporateEmailFormat(email) {
    // Validar formato básico de email
    if (!isValidEmail(email)) {
        return false;
    }

    // Si se permiten emails personalizados, cualquier email válido es aceptable
    if (typeof ALLOW_CUSTOM_EMAILS !== 'undefined' && ALLOW_CUSTOM_EMAILS === true) {
        return true;
    }

    // Verificar que el dominio coincida
    if (typeof CORPORATE_EMAIL_DOMAIN === 'undefined') {
        console.error('CORPORATE_EMAIL_DOMAIN no está definido');
        return true; // Permitir si no hay configuración (fail-safe)
    }

    var domain = '@' + CORPORATE_EMAIL_DOMAIN;
    var emailLower = email.toLowerCase();
    var domainLower = domain.toLowerCase();

    // Validar que termine con el dominio corporativo
    if (!emailLower.endsWith(domainLower)) {
        return false;
    }

    return true;
}

/**
 * Validar formato básico de email
 *
 * @param {string} email - Email a validar
 * @returns {boolean} - true si tiene formato válido
 */
function isValidEmail(email) {
    // Regex básico para validar email
    var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Obtener mensaje de error para email inválido
 *
 * @param {string} email - Email que falló la validación
 * @returns {string} - Mensaje de error descriptivo
 */
function getEmailValidationError(email) {
    if (!email || email.trim() === '') {
        return 'El correo electrónico es requerido';
    }

    if (!isValidEmail(email)) {
        return 'Por favor ingrese un correo electrónico válido';
    }

    if (typeof ALLOW_CUSTOM_EMAILS !== 'undefined' && ALLOW_CUSTOM_EMAILS === true) {
        return 'Correo electrónico inválido';
    }

    if (typeof CORPORATE_EMAIL_DOMAIN !== 'undefined') {
        var domain = CORPORATE_EMAIL_DOMAIN;
        return 'El correo debe usar el dominio corporativo: @' + domain;
    }

    return 'Correo electrónico inválido';
}

/**
 * ============================================================================
 * VALIDACIÓN DE CONTRASEÑAS
 * ============================================================================
 *
 * IMPORTANTE: Las siguientes variables deben ser definidas en el HTML:
 * - PASSWORD_MIN_LENGTH
 * - PASSWORD_REQUIRE_UPPERCASE
 * - PASSWORD_REQUIRE_LOWERCASE
 * - PASSWORD_REQUIRE_NUMBERS
 * - PASSWORD_REQUIRE_SPECIAL
 */

/**
 * Validar contraseña según políticas del sistema
 *
 * @param {string} password - Contraseña a validar
 * @returns {object} - {valid: boolean, errors: array, strength: number}
 */
function validatePassword(password) {
    var errors = [];
    var strength = 0;
    var maxStrength = 0;

    // Valores por defecto si no están definidos
    var minLength = (typeof PASSWORD_MIN_LENGTH !== 'undefined') ? PASSWORD_MIN_LENGTH : 8;
    var requireUppercase = (typeof PASSWORD_REQUIRE_UPPERCASE !== 'undefined') ? PASSWORD_REQUIRE_UPPERCASE : true;
    var requireLowercase = (typeof PASSWORD_REQUIRE_LOWERCASE !== 'undefined') ? PASSWORD_REQUIRE_LOWERCASE : true;
    var requireNumbers = (typeof PASSWORD_REQUIRE_NUMBERS !== 'undefined') ? PASSWORD_REQUIRE_NUMBERS : true;
    var requireSpecial = (typeof PASSWORD_REQUIRE_SPECIAL !== 'undefined') ? PASSWORD_REQUIRE_SPECIAL : true;

    // Validar longitud
    maxStrength += 20;
    if (password.length >= minLength) {
        strength += 20;
    } else {
        errors.push('Mínimo ' + minLength + ' caracteres');
    }

    // Validar mayúsculas
    if (requireUppercase) {
        maxStrength += 20;
        if (/[A-Z]/.test(password)) {
            strength += 20;
        } else {
            errors.push('Al menos una mayúscula (A-Z)');
        }
    }

    // Validar minúsculas
    if (requireLowercase) {
        maxStrength += 20;
        if (/[a-z]/.test(password)) {
            strength += 20;
        } else {
            errors.push('Al menos una minúscula (a-z)');
        }
    }

    // Validar números
    if (requireNumbers) {
        maxStrength += 20;
        if (/[0-9]/.test(password)) {
            strength += 20;
        } else {
            errors.push('Al menos un número (0-9)');
        }
    }

    // Validar caracteres especiales
    if (requireSpecial) {
        maxStrength += 20;
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?`~]/.test(password)) {
            strength += 20;
        } else {
            errors.push('Al menos un símbolo (!@#$%...)');
        }
    }

    return {
        valid: errors.length === 0,
        errors: errors,
        strength: (maxStrength > 0) ? Math.round((strength / maxStrength) * 100) : 100
    };
}

/**
 * Obtener descripción de requisitos de contraseña
 *
 * @returns {string} - Descripción de requisitos
 */
function getPasswordRequirements() {
    var minLength = (typeof PASSWORD_MIN_LENGTH !== 'undefined') ? PASSWORD_MIN_LENGTH : 8;
    var requirements = [];

    requirements.push('Mínimo ' + minLength + ' caracteres');

    if (typeof PASSWORD_REQUIRE_UPPERCASE !== 'undefined' && PASSWORD_REQUIRE_UPPERCASE) {
        requirements.push('al menos una mayúscula');
    }

    if (typeof PASSWORD_REQUIRE_LOWERCASE !== 'undefined' && PASSWORD_REQUIRE_LOWERCASE) {
        requirements.push('al menos una minúscula');
    }

    if (typeof PASSWORD_REQUIRE_NUMBERS !== 'undefined' && PASSWORD_REQUIRE_NUMBERS) {
        requirements.push('al menos un número');
    }

    if (typeof PASSWORD_REQUIRE_SPECIAL !== 'undefined' && PASSWORD_REQUIRE_SPECIAL) {
        requirements.push('al menos un símbolo');
    }

    return requirements.join(', ');
}

/**
 * Obtener clase CSS según fortaleza de contraseña
 *
 * @param {number} strength - Fortaleza (0-100)
 * @returns {string} - Clase CSS
 */
function getPasswordStrengthClass(strength) {
    if (strength >= 100) return 'text-success';
    if (strength >= 60) return 'text-warning';
    return 'text-danger';
}

/**
 * Obtener texto según fortaleza de contraseña
 *
 * @param {number} strength - Fortaleza (0-100)
 * @returns {string} - Texto descriptivo
 */
function getPasswordStrengthText(strength) {
    if (strength >= 100) return 'Contraseña fuerte';
    if (strength >= 60) return 'Contraseña media';
    return 'Contraseña débil';
}
