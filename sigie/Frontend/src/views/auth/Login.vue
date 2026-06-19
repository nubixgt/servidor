<template>
    <div class="login-root">
        <!-- Centered Login Card -->
        <div class="lr-card">
            <!-- Brand Logo Header -->
            <div class="lr-brand">
                <div class="w-24 h-24 flex items-center justify-center bg-white rounded border border-slate-200 p-2 shadow-sm">
                    <img :src="logoUrl" alt="SIGIE Logo" class="max-w-full max-h-full object-contain" />
                </div>
            </div>

            <!-- Header -->
            <div class="lr-header">
                <h2 class="lr-title">Ingresar al Sistema</h2>
                <p class="lr-sub">Introduce tus credenciales para acceder</p>
            </div>

            <!-- Form -->
            <form class="lr-form" @submit.prevent="handleLogin">
                <div class="lr-field">
                    <label class="lr-label" for="username">Usuario</label>
                    <div class="lr-input-wrap">
                        <span class="material-symbols-outlined lr-input-icon">person</span>
                        <input
                            id="username"
                            v-model="username"
                            type="text"
                            required
                            placeholder="nombre.usuario"
                            class="lr-input"
                        />
                    </div>
                </div>

                <div class="lr-field">
                    <div class="lr-label-row">
                        <label class="lr-label" for="password">Contraseña</label>
                        <a href="#" class="lr-forgot">¿Olvidó su clave?</a>
                    </div>
                    <div class="lr-input-wrap">
                        <span class="material-symbols-outlined lr-input-icon">lock</span>
                        <input
                            id="password"
                            v-model="password"
                            :type="showPwd ? 'text' : 'password'"
                            required
                            placeholder="••••••••••••"
                            class="lr-input"
                        />
                        <button type="button" @click="showPwd = !showPwd" class="lr-eye-btn">
                            <span class="material-symbols-outlined">{{ showPwd ? 'visibility_off' : 'visibility' }}</span>
                        </button>
                    </div>
                </div>

                <button type="submit" :disabled="loading" class="lr-submit">
                    <span v-if="!loading">Ingresar al Sistema</span>
                    <span v-else>Autenticando...</span>
                </button>
            </form>

            <!-- Footer -->
            <div class="lr-footer">
                <a href="#">Soporte Técnico</a>
                <span class="lr-dot"></span>
                <a href="#">Seguridad</a>
                <span class="lr-dot"></span>
                <a href="#">Privacidad</a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../../stores/authStore.js';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';

const getLogoUrl = () => {
    const path = window.location.pathname.toLowerCase();
    const isViteDev = window.location.port !== '' && window.location.port !== '80' && window.location.port !== '8080';
    if (isViteDev) {
        return '/sigie/logo.png';
    }
    const distIndex = path.indexOf('/frontend/dist');
    if (distIndex !== -1) {
        return window.location.pathname.substring(0, distIndex) + '/Frontend/dist/logo.png';
    }
    const sigieIndex = path.indexOf('/sigie');
    if (sigieIndex !== -1) {
        return window.location.pathname.substring(0, sigieIndex) + '/sigie/logo.png';
    }
    return '/logo.png';
};
const logoUrl = getLogoUrl();

const auth = useAuthStore();
const router = useRouter();

const username = ref('');
const password = ref('');
const showPwd  = ref(false);
const loading  = ref(false);

const handleLogin = async () => {
    loading.value = true;
    try {
        await auth.login(username.value, password.value);
        router.push('/dashboard');
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Acceso Denegado',
            text: err.message || 'Error de conexión con el servidor.',
            confirmButtonColor: '#005a9c',
        });
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
/* ═══ ROOT ═══ */
.login-root {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-color: #0b192c; /* Azul marino institucional oscuro */
    padding: 24px;
}

/* Login Card Container */
.lr-card {
    width: 100%;
    max-width: 420px;
    background: white;
    border-radius: 4px; /* Esquinas cuadradas SAP/Microsoft */
    border: 1px solid #d1d5db;
    padding: 40px 36px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

/* Logo Brand Header */
.lr-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}
.lr-mobile-sub { 
    font-size: 10px; 
    color: #4b5563; 
    font-weight: 700; 
    letter-spacing: 0.1em; 
    text-transform: uppercase; 
    margin-top: 4px;
}

/* Header text */
.lr-header { margin-bottom: 24px; text-align: center; }
.lr-title { font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 6px; }
.lr-sub   { font-size: 12px; color: #4b5563; margin: 0; }

/* Form inputs */
.lr-form { display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px; }

.lr-field { display: flex; flex-direction: column; gap: 6px; }
.lr-label { font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.05em; }
.lr-label-row { display: flex; justify-content: space-between; align-items: center; }
.lr-forgot { font-size: 11px; font-weight: 600; color: #005a9c; text-decoration: none; }
.lr-forgot:hover { text-decoration: underline; }

.lr-input-wrap {
    position: relative; display: flex; align-items: center;
    background: #ffffff; border: 1px solid #9ca3af;
    border-radius: 4px; /* Esquinas cuadradas Fluent/Microsoft */
}
.lr-input-wrap:focus-within {
    border-color: #005a9c;
    box-shadow: 0 0 0 2px rgba(0, 90, 156, 0.2);
}
.lr-input-icon {
    position: absolute; left: 12px;
    font-size: 18px !important; color: #4b5563; pointer-events: none;
}
.lr-input {
    width: 100%; padding: 10px 12px 10px 38px;
    border: none; background: none; outline: none;
    font-size: 13px; color: #111827;
}
.lr-input::placeholder { color: #9ca3af; }
.lr-eye-btn {
    position: absolute; right: 12px;
    border: none; background: none; cursor: pointer;
    color: #4b5563; display: flex;
}
.lr-eye-btn:hover { color: #005a9c; }
.lr-eye-btn .material-symbols-outlined { font-size: 18px; }

/* Submit button */
.lr-submit {
    display: flex; align-items: center; justify-content: center;
    width: 100%; padding: 12px;
    background-color: #005a9c; /* Azul primario sólido corporativo */
    color: white; border: 1px solid #004b87;
    border-radius: 4px;
    font-size: 13px; font-weight: 700; cursor: pointer;
    transition: background-color 0.2s;
    margin-top: 8px;
}
.lr-submit:hover:not(:disabled) { background-color: #004b87; }
.lr-submit:active { background-color: #003a6c; }
.lr-submit:disabled { opacity: 0.6; cursor: not-allowed; }

/* Footer links */
.lr-footer {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    flex-wrap: wrap; border-top: 1px solid #e5e7eb; padding-top: 16px;
}
.lr-footer a { font-size: 11px; color: #4b5563; text-decoration: none; }
.lr-footer a:hover { text-decoration: underline; color: #005a9c; }
.lr-dot { width: 3px; height: 3px; border-radius: 50%; background: #9ca3af; }

/* Responsive tweaks */
@media (max-width: 480px) {
    .lr-card { padding: 24px 20px; border-radius: 4px; }
}
</style>
