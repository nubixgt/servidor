<template>
    <div class="login-root">
        <!-- Background Glowing Orbs -->
        <div class="login-orb login-orb-1"></div>
        <div class="login-orb login-orb-2"></div>
        <div class="login-orb login-orb-3"></div>
        <div class="login-dots"></div>

        <!-- Centered Login Card -->
        <div class="lr-card">
            <!-- Brand Logo Header -->
            <div class="lr-brand">
                <img :src="logoUrl" alt="SIGIE Logo" class="lr-logo-img" />
                <p class="lr-mobile-sub">Sistema de Gestión de Inspecciones</p>
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
                    <span class="material-symbols-outlined" v-if="!loading">arrow_forward</span>
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
            confirmButtonColor: '#0284c7',
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
    background: linear-gradient(150deg, #090f1c 0%, #0f172a 40%, #0b132b 100%);
    position: relative;
    overflow: hidden;
    padding: 24px;
    font-family: inherit;
}

/* Background animated Orbs */
.login-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(90px);
    animation: orbDrift 12s ease-in-out infinite;
    pointer-events: none;
}
.login-orb-1 { width: 450px; height: 450px; background: #0284c7; opacity: 0.12; top: -120px; right: -80px; }
.login-orb-2 { width: 350px; height: 350px; background: #4f46e5; opacity: 0.08; bottom: -80px; left: -60px; animation-delay: -5s; }
.login-orb-3 { width: 220px; height: 220px; background: #0d9488; opacity: 0.05; top: 40%; left: 15%; animation-delay: -3s; }

@keyframes orbDrift {
    0%, 100% { transform: translateY(0) scale(1); }
    50%       { transform: translateY(-30px) scale(1.06); }
}

/* Dot grid overlay */
.login-dots {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(2,132,199,0.08) 1px, transparent 1px);
    background-size: 32px 32px;
    pointer-events: none;
    z-index: 0;
}

/* Login Card Container */
.lr-card {
    position: relative; z-index: 1;
    width: 100%; max-width: 440px;
    background: white;
    border-radius: 24px;
    padding: 44px 42px;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(2,132,199,0.08);
}

/* Logo Brand Header */
.lr-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 8px;
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f1f5f9;
}
.lr-logo-img {
    width: 120px;
    height: auto;
    filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.1));
}
.lr-mobile-sub { 
    font-size: 10px; 
    color: #64748b; 
    font-weight: 700; 
    letter-spacing: 0.12em; 
    text-transform: uppercase; 
}

/* Header text */
.lr-header { margin-bottom: 30px; text-align: center; }
.lr-title { font-size: 22px; font-weight: 800; color: #0f172a; margin: 0 0 6px; font-family: 'Public Sans', sans-serif; }
.lr-sub   { font-size: 13px; color: #475569; margin: 0; }

/* Form inputs */
.lr-form { display: flex; flex-direction: column; gap: 20px; margin-bottom: 28px; }

.lr-field { display: flex; flex-direction: column; gap: 8px; }
.lr-label { font-size: 10px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.15em; }
.lr-label-row { display: flex; justify-content: space-between; align-items: center; }
.lr-forgot { font-size: 11px; font-weight: 600; color: #4f46e5; text-decoration: none; transition: color 0.2s; }
.lr-forgot:hover { color: #4338ca; }

.lr-input-wrap {
    position: relative; display: flex; align-items: center;
    background: #f8fafc; border: 2px solid #e2e8f0;
    border-radius: 14px; transition: all 0.3s ease;
}
.lr-input-wrap:focus-within {
    border-color: #38bdf8;
    box-shadow: 0 0 0 4px rgba(56,189,248,0.12);
    background: white;
}
.lr-input-icon {
    position: absolute; left: 14px;
    font-size: 18px !important; color: #64748b; pointer-events: none;
}
.lr-input {
    width: 100%; padding: 13px 44px 13px 44px;
    border: none; background: none; outline: none;
    font-size: 14px; color: #0f172a;
}
.lr-input::placeholder { color: #94a3b8; }
.lr-eye-btn {
    position: absolute; right: 12px;
    border: none; background: none; cursor: pointer;
    color: #94a3b8; display: flex;
    transition: color 0.2s;
}
.lr-eye-btn:hover { color: #4f46e5; }
.lr-eye-btn .material-symbols-outlined { font-size: 18px; }

/* Submit button */
.lr-submit {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 14px;
    background: linear-gradient(135deg, #0284c7, #4f46e5);
    color: white; border: none; border-radius: 14px;
    font-size: 14px; font-weight: 800; cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 6px 20px rgba(2, 132, 199, 0.3);
    letter-spacing: 0.02em;
}
.lr-submit .material-symbols-outlined { font-size: 18px; transition: transform 0.3s ease; }
.lr-submit:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(2, 132, 199, 0.4); }
.lr-submit:hover:not(:disabled) .material-symbols-outlined { transform: translateX(4px); }
.lr-submit:active { transform: scale(0.98); }
.lr-submit:disabled { opacity: 0.7; cursor: not-allowed; }

/* Footer links */
.lr-footer {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    flex-wrap: wrap;
}
.lr-footer a { font-size: 11px; color: #64748b; text-decoration: none; transition: color 0.2s; font-weight: 500; }
.lr-footer a:hover { color: #4f46e5; }
.lr-dot { width: 3px; height: 3px; border-radius: 50%; background: #cbd5e1; }

/* Responsive tweaks */
@media (max-width: 480px) {
    .lr-card { padding: 28px 20px; border-radius: 18px; }
}
</style>
