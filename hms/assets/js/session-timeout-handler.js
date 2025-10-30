/**
 * ============================================================================
 * SESSION TIMEOUT HANDLER
 * ============================================================================
 *
 * Maneja el timeout de sesiones con advertencia modal
 *
 * Funcionalidades:
 * - Detecta inactividad del usuario
 * - Muestra modal de advertencia 2 minutos antes de expirar
 * - Permite extender sesión
 * - Cierra sesión automáticamente si no hay respuesta
 *
 * @version 2.4.0
 * @since 2025-10-30
 */

(function() {
    'use strict';

    // Variables globales
    var sessionTimeoutSeconds = 1800; // 30 minutos por defecto
    var warningTimeSeconds = 120;     // 2 minutos por defecto
    var inactivityTimer = null;
    var warningTimer = null;
    var countdownInterval = null;
    var modalShown = false;

    /**
     * Inicializar sistema de timeout
     * @param {number} timeout Timeout en segundos
     * @param {number} warning Tiempo de advertencia en segundos
     */
    window.initSessionTimeout = function(timeout, warning) {
        sessionTimeoutSeconds = timeout || 1800;
        warningTimeSeconds = warning || 120;

        console.log('[SessionTimeout] Iniciado con timeout:', sessionTimeoutSeconds, 'segundos');
        console.log('[SessionTimeout] Advertencia:', warningTimeSeconds, 'segundos antes');

        // Crear modal si no existe
        createTimeoutModal();

        // Escuchar eventos de actividad
        listenForActivity();

        // Iniciar timer
        resetInactivityTimer();
    };

    /**
     * Crear modal de advertencia de timeout
     */
    function createTimeoutModal() {
        // Verificar si ya existe
        if (document.getElementById('sessionTimeoutModal')) {
            return;
        }

        var modalHTML = `
            <div class="modal fade" id="sessionTimeoutModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header" style="background: #ff9800; color: white;">
                            <h4 class="modal-title">
                                <i class="fa fa-clock-o"></i> Sesión por Expirar
                            </h4>
                        </div>
                        <div class="modal-body text-center" style="padding: 30px;">
                            <div style="margin-bottom: 20px;">
                                <i class="fa fa-exclamation-triangle" style="font-size: 60px; color: #ff9800;"></i>
                            </div>
                            <p class="lead">Tu sesión está por expirar por inactividad</p>
                            <p>Tiempo restante:</p>
                            <h1 id="timeoutCountdown" style="color: #f44336; font-size: 48px; margin: 20px 0;">2:00</h1>
                            <p class="text-muted">¿Deseas continuar tu sesión?</p>
                        </div>
                        <div class="modal-footer" style="text-align: center; border-top: none;">
                            <button class="btn btn-success btn-lg" onclick="extendSession()" style="min-width: 200px; margin: 5px;">
                                <i class="fa fa-refresh"></i> Continuar Sesión
                            </button>
                            <button class="btn btn-default" onclick="logoutNow()" style="margin: 5px;">
                                <i class="fa fa-sign-out"></i> Cerrar Sesión
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insertar en el DOM
        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = modalHTML;
        document.body.appendChild(tempDiv.firstElementChild);
    }

    /**
     * Escuchar eventos de actividad del usuario
     */
    function listenForActivity() {
        var events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];

        events.forEach(function(event) {
            document.addEventListener(event, function() {
                if (!modalShown) {
                    resetInactivityTimer();
                }
            }, true);
        });

        console.log('[SessionTimeout] Escuchando eventos de actividad');
    }

    /**
     * Resetear timer de inactividad
     */
    function resetInactivityTimer() {
        // Limpiar timers existentes
        if (inactivityTimer) {
            clearTimeout(inactivityTimer);
        }
        if (warningTimer) {
            clearTimeout(warningTimer);
        }

        // Calcular cuando mostrar advertencia
        var timeUntilWarning = (sessionTimeoutSeconds - warningTimeSeconds) * 1000;

        // Timer para mostrar advertencia
        warningTimer = setTimeout(function() {
            showTimeoutWarning();
        }, timeUntilWarning);

        // Timer para logout automático
        inactivityTimer = setTimeout(function() {
            autoLogout();
        }, sessionTimeoutSeconds * 1000);
    }

    /**
     * Mostrar modal de advertencia
     */
    function showTimeoutWarning() {
        console.log('[SessionTimeout] Mostrando advertencia');
        modalShown = true;

        // Mostrar modal
        if (typeof jQuery !== 'undefined') {
            jQuery('#sessionTimeoutModal').modal('show');
        } else {
            document.getElementById('sessionTimeoutModal').classList.add('in');
            document.getElementById('sessionTimeoutModal').style.display = 'block';
        }

        // Iniciar countdown
        startCountdown();
    }

    /**
     * Iniciar cuenta regresiva en el modal
     */
    function startCountdown() {
        var secondsLeft = warningTimeSeconds;
        var countdownElement = document.getElementById('timeoutCountdown');

        if (countdownInterval) {
            clearInterval(countdownInterval);
        }

        // Actualizar cada segundo
        countdownInterval = setInterval(function() {
            secondsLeft--;

            var minutes = Math.floor(secondsLeft / 60);
            var seconds = secondsLeft % 60;

            if (countdownElement) {
                countdownElement.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            }

            if (secondsLeft <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    }

    /**
     * Extender sesión (llamado desde botón del modal)
     */
    window.extendSession = function() {
        console.log('[SessionTimeout] Extendiendo sesión...');

        // Hacer petición AJAX para extender sesión en el servidor
        var xhr = new XMLHttpRequest();
        xhr.open('POST', getExtendSessionUrl(), true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        console.log('[SessionTimeout] Sesión extendida exitosamente');

                        // Cerrar modal
                        hideModal();

                        // Resetear timers
                        modalShown = false;
                        resetInactivityTimer();

                        // Mostrar mensaje opcional
                        showNotification('Sesión extendida exitosamente', 'success');
                    } else {
                        console.error('[SessionTimeout] Error al extender:', response.message);
                        showNotification(response.message || 'Error al extender sesión', 'error');

                        // Si no se puede extender, cerrar sesión
                        setTimeout(function() {
                            autoLogout();
                        }, 2000);
                    }
                } catch (e) {
                    console.error('[SessionTimeout] Error parsing response:', e);
                }
            }
        };

        xhr.onerror = function() {
            console.error('[SessionTimeout] Error de red al extender sesión');
            showNotification('Error de conexión', 'error');
        };

        xhr.send('action=extend');
    };

    /**
     * Cerrar sesión ahora (desde botón del modal)
     */
    window.logoutNow = function() {
        console.log('[SessionTimeout] Logout manual desde modal');
        window.location.href = getLogoutUrl();
    };

    /**
     * Logout automático por timeout
     */
    function autoLogout() {
        console.log('[SessionTimeout] Logout automático por inactividad');

        // Limpiar timers
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }

        // Redirigir a logout con parámetro de timeout
        window.location.href = getLogoutUrl() + '?reason=timeout';
    }

    /**
     * Ocultar modal
     */
    function hideModal() {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }

        if (typeof jQuery !== 'undefined') {
            jQuery('#sessionTimeoutModal').modal('hide');
        } else {
            var modal = document.getElementById('sessionTimeoutModal');
            if (modal) {
                modal.classList.remove('in');
                modal.style.display = 'none';
            }
        }
    }

    /**
     * Mostrar notificación
     */
    function showNotification(message, type) {
        // Implementación simple con alert
        // Puedes mejorar esto con toastr u otra librería
        if (type === 'success') {
            console.log('[Notificación]', message);
        } else {
            console.warn('[Notificación]', message);
        }

        // Si existe toastr, usarlo
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        }
    }

    /**
     * Obtener URL de extend-session según tipo de usuario
     */
    function getExtendSessionUrl() {
        var path = window.location.pathname;

        if (path.indexOf('/admin/') !== -1) {
            return 'extend-session.php';
        } else if (path.indexOf('/doctor/') !== -1) {
            return 'extend-session.php';
        } else {
            return 'extend-session.php';
        }
    }

    /**
     * Obtener URL de logout según tipo de usuario
     */
    function getLogoutUrl() {
        var path = window.location.pathname;

        if (path.indexOf('/admin/') !== -1) {
            return 'logout.php';
        } else if (path.indexOf('/doctor/') !== -1) {
            return 'logout.php';
        } else {
            return 'logout.php';
        }
    }

    console.log('[SessionTimeout] Script cargado');
})();
