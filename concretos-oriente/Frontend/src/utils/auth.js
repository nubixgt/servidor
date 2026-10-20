/**
 * Utilidades de Autenticación y Manejo de JWT para ConstructPro / Concretos de Oriente
 */

/**
 * Decodifica el payload de un token JWT sin requerir librerías externas.
 * @param {string|null} token 
 * @returns {object|null}
 */
export function decodeJwt(token) {
    if (!token || typeof token !== 'string') return null;
    try {
        const parts = token.split('.');
        if (parts.length !== 3) return null;
        
        const base64Url = parts[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(
            atob(base64)
                .split('')
                .map(c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
                .join('')
        );
        return JSON.parse(jsonPayload);
    } catch (e) {
        return null;
    }
}

/**
 * Verifica si un token JWT ha expirado o está próximo a expirar.
 * @param {string|null} token 
 * @param {number} bufferSeconds Margen de seguridad en segundos (por defecto 10s)
 * @returns {boolean} true si está expirado o es inválido, false si aún es válido
 */
export function isTokenExpired(token, bufferSeconds = 10) {
    if (!token) return true;
    const payload = decodeJwt(token);
    if (!payload || !payload.exp) return true;
    
    // payload.exp está en segundos, Date.now() en milisegundos
    const expirationTimeMs = payload.exp * 1000;
    const currentTimeWithBufferMs = Date.now() + (bufferSeconds * 1000);
    
    return expirationTimeMs <= currentTimeWithBufferMs;
}

// Bandera para evitar múltiples redirecciones simultáneas (debounce)
let isRedirecting = false;

/**
 * Maneja el vencimiento de la sesión de forma global.
 * Limpia el almacenamiento, guarda aviso para la pantalla de login y redirige.
 * @param {string} [message]
 * @param {object} [routerInstance]
 */
export function handleSessionExpired(message = 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.', routerInstance = null) {
    if (isRedirecting) return;
    isRedirecting = true;

    // Limpiar almacenamiento de sesión y permisos
    localStorage.removeItem('token');
    localStorage.removeItem('userRole');
    localStorage.removeItem('userName');
    localStorage.removeItem('userPermisos');
    localStorage.removeItem('userProyectos');

    // Registrar aviso para mostrar en Login
    sessionStorage.setItem('session_expired_notice', 'true');
    sessionStorage.setItem('session_expired_message', message);

    const currentPath = window.location.pathname;
    
    // Evitar redirección si ya estamos en /login
    if (!currentPath.includes('/login')) {
        const redirectQuery = encodeURIComponent(currentPath + window.location.search);
        const targetUrl = `/login?expired=1&redirect=${redirectQuery}`;
        
        if (routerInstance && typeof routerInstance.push === 'function') {
            routerInstance.push({
                path: '/login',
                query: {
                    expired: '1',
                    redirect: currentPath + window.location.search
                }
            }).catch(() => {
                window.location.href = targetUrl;
            });
        } else {
            window.location.href = targetUrl;
        }
    }

    // Restablecer la bandera tras 2 segundos para permitir inicios de sesión posteriores
    setTimeout(() => {
        isRedirecting = false;
    }, 2000);
}
