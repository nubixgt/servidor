<template>
    <div class="login-root">

        <!-- ═══ PANEL IZQUIERDO ═══ -->
        <div class="login-left">
            <div class="lp-orb lp-orb-1"></div>
            <div class="lp-orb lp-orb-2"></div>
            <div class="lp-orb lp-orb-3"></div>
            <div class="lp-dots"></div>

            <div class="lp-body">
                <!-- Logo -->
                <div class="lp-logo-wrap">
                    <img src="/logo.png" alt="SYS-DIPU Logo" class="lp-logo-img" />
                </div>

                <p class="lp-tagline" style="margin-top: 10px;">Portal de Gestión Institucional</p>

                <!-- Feature list -->
                <div class="lp-features">
                    <div class="lp-feat" v-for="f in features" :key="f.icon">
                        <div class="lp-feat-icon">
                            <span class="material-symbols-outlined">{{ f.icon }}</span>
                        </div>
                        <span>{{ f.label }}</span>
                    </div>
                </div>
            </div>

            <p class="lp-version">v2.0 · © 2025 Sistema de Gestión Pública</p>
        </div>

        <!-- ═══ PANEL DERECHO ═══ -->
        <div class="login-right">
            <div class="lr-card">

                <!-- Logo -->
                <div class="lr-mobile-brand">
                    <img src="/logo.png" alt="SYS-DIPU Logo" class="lr-logo-img" />
                    <div>
                        <p class="lr-mobile-sub" style="margin-top: 8px;">Portal de Gestión Institucional</p>
                    </div>
                </div>

                <!-- Header -->
                <div class="lr-header">
                    <h2 class="lr-title">Bienvenido</h2>
                    <p class="lr-sub">Ingresa tus credenciales para acceder al sistema</p>
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

                    <button type="submit" class="lr-submit">
                        <span>Ingresar al Sistema</span>
                        <span class="material-symbols-outlined">arrow_forward</span>
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

    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../../stores/authStore.js';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';

const auth = useAuthStore();
const router = useRouter();

const username = ref('');
const password = ref('');
const showPwd  = ref(false);

const features = [
    { icon: 'gavel',          label: 'Gestión de Iniciativas de Ley'  },
    { icon: 'policy',         label: 'Centro de Fiscalización'         },
    { icon: 'groups',         label: 'Comisiones y Citaciones'         },
    { icon: 'handshake',      label: 'Compromisos Distritales'         },
    { icon: 'event_available',label: 'Actividades y Agenda'            },
];


const handleLogin = async () => {
    try {
        const user = await auth.login(username.value, password.value);
        if (user.rol === 'administrador') {
            router.push('/dashboard-admin');
        } else {
            router.push('/dashboard-tecnico');
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Acceso Denegado',
            text: err.message || 'Error de conexión con el servidor.',
            confirmButtonColor: '#0891b2',
        });
    }
};
</script>

<style scoped>
/* ═══ ROOT ═══ */
.login-root {
    display: flex;
    min-height: 100vh;
    font-family: inherit;
}

/* ═══ PANEL IZQUIERDO ═══ */
.login-left {
    display: none;
}

/* Orbs animados */
.lp-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    animation: orbDrift 12s ease-in-out infinite;
    pointer-events: none;
}
.lp-orb-1 { width: 380px; height: 380px; background: #0e7490; opacity: 0.14; top: -120px; right: -80px; animation-delay: 0s; }
.lp-orb-2 { width: 260px; height: 260px; background: #1d4ed8; opacity: 0.12; bottom: -60px; left: -40px;  animation-delay: -4s; }
.lp-orb-3 { width: 180px; height: 180px; background: #06b6d4; opacity: 0.1;  top: 45%;    left: 10%;    animation-delay: -7s; }

@keyframes orbDrift {
    0%, 100% { transform: translateY(0) scale(1); }
    50%       { transform: translateY(-30px) scale(1.08); }
}

/* Dot grid overlay */
.lp-dots {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(34,211,238,0.12) 1px, transparent 1px);
    background-size: 32px 32px;
    pointer-events: none;
}

/* Body content */
.lp-body {
    position: relative; z-index: 1;
    display: flex; flex-direction: column; align-items: center;
    text-align: center;
}

/* Logo */
.lp-logo-wrap {
    position: relative;
    display: inline-flex; align-items: center; justify-content: center;
    margin-bottom: 28px;
}
.lp-logo-pulse {
    position: absolute;
    width: 130px; height: 130px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(34,211,238,0.25) 0%, transparent 70%);
    animation: logoPulse 3s ease-in-out infinite;
}
@keyframes logoPulse {
    0%, 100% { transform: scale(1);    opacity: 0.8; }
    50%       { transform: scale(1.18); opacity: 0.4; }
}
.lp-logo-img {
    position: relative; z-index: 1;
    width: 320px; height: auto;
    filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
    animation: logoFloat 6s ease-in-out infinite;
}
@keyframes logoFloat {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(-8px); }
}

/* Wordmark */
.lp-wordmark {
    font-size: 38px; font-weight: 900;
    color: white; letter-spacing: -0.5px;
    margin: 0 0 8px;
    font-family: 'Public Sans', sans-serif;
}
.lp-wordmark span {
    background: linear-gradient(135deg, #22d3ee, #0891b2);
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}
.lp-tagline {
    font-size: 11px; font-weight: 700; letter-spacing: 0.22em;
    text-transform: uppercase; color: rgba(255,255,255,0.38);
    margin: 0 0 36px;
}

/* Feature list */
.lp-features { display: flex; flex-direction: column; gap: 12px; width: 100%; max-width: 300px; }
.lp-feat {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 16px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(34,211,238,0.12);
    border-radius: 12px;
    transition: all 0.3s ease;
}
.lp-feat:hover {
    background: rgba(34,211,238,0.08);
    border-color: rgba(34,211,238,0.25);
    transform: translateX(4px);
}
.lp-feat-icon {
    width: 30px; height: 30px; border-radius: 8px;
    background: rgba(34,211,238,0.12);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.lp-feat-icon .material-symbols-outlined { font-size: 16px; color: #22d3ee; }
.lp-feat span:not(.material-symbols-outlined) { font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.65); }

/* Version footer */
.lp-version {
    position: absolute; bottom: 20px;
    font-size: 10px; font-weight: 600;
    color: rgba(255,255,255,0.2);
    letter-spacing: 0.08em;
    z-index: 1;
}

/* ═══ PANEL DERECHO ═══ */
.login-right {
    flex: 1;
    position: relative;
    background: linear-gradient(150deg, #060f22 0%, #0a1a35 40%, #071428 80%, #030a16 100%);
    display: flex; align-items: center; justify-content: center;
    padding: 40px 32px;
    overflow: hidden;
}
/* Orbs animados en el fondo del panel derecho */
.login-right::before,
.login-right::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    filter: blur(90px);
    pointer-events: none;
    animation: orbDrift 12s ease-in-out infinite;
}
.login-right::before {
    width: 400px; height: 400px;
    background: #0e7490; opacity: 0.12;
    top: -120px; right: -80px;
}
.login-right::after {
    width: 280px; height: 280px;
    background: #1d4ed8; opacity: 0.1;
    bottom: -80px; left: -60px;
    animation-delay: -5s;
}
/* Dot grid */
.login-right .lr-card::before {
    content: '';
    position: fixed; inset: 0;
    background-image: radial-gradient(circle, rgba(34,211,238,0.1) 1px, transparent 1px);
    background-size: 32px 32px;
    pointer-events: none;
    z-index: 0;
}

.lr-card {
    position: relative; z-index: 1;
    width: 100%; max-width: 440px;
    background: white;
    border-radius: 24px;
    padding: 44px 42px;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(34,211,238,0.08);
}

/* Brand siempre visible */
.lr-mobile-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 12px;
    margin-bottom: 32px;
    padding-bottom: 28px;
    border-bottom: 1px solid #f3f4f6;
}
.lr-logo-img {
    flex-shrink: 0;
    width: 220px; height: auto;
    filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.1));
}
@keyframes logoSpin {
    from { opacity: 0; transform: scale(0.6) rotate(-15deg); }
    to   { opacity: 1; transform: scale(1)   rotate(0deg); }
}
.lr-mobile-title {
    font-size: 20px; font-weight: 900; color: #1f2937;
    font-family: 'Public Sans', sans-serif;
    margin: 0;
}
.lr-mobile-title span {
    background: linear-gradient(135deg, #0891b2, #0369a1);
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}
.lr-mobile-sub { font-size: 10px; color: #9ca3af; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; }

/* Header */
.lr-header { margin-bottom: 30px; }
.lr-title { font-size: 26px; font-weight: 900; color: #111827; margin: 0 0 6px; font-family: 'Public Sans', sans-serif; }
.lr-sub   { font-size: 13px; color: #6b7280; margin: 0; }

/* Form */
.lr-form { display: flex; flex-direction: column; gap: 20px; margin-bottom: 28px; }

.lr-field { display: flex; flex-direction: column; gap: 8px; }
.lr-label { font-size: 10px; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: 0.15em; }
.lr-label-row { display: flex; justify-content: space-between; align-items: center; }
.lr-forgot { font-size: 11px; font-weight: 600; color: #6d28d9; text-decoration: none; transition: color 0.2s; }
.lr-forgot:hover { color: #4c1d95; }

.lr-input-wrap {
    position: relative; display: flex; align-items: center;
    background: #f9f7ff; border: 2px solid #ede9fe;
    border-radius: 14px; transition: all 0.3s ease;
}
.lr-input-wrap:focus-within {
    border-color: #a78bfa;
    box-shadow: 0 0 0 4px rgba(167,139,250,0.12);
    background: white;
}
.lr-input-icon {
    position: absolute; left: 14px;
    font-size: 18px !important; color: #a78bfa; pointer-events: none;
}
.lr-input {
    width: 100%; padding: 13px 44px 13px 44px;
    border: none; background: none; outline: none;
    font-size: 14px; color: #111827;
}
.lr-input::placeholder { color: #c4b5fd; }
.lr-eye-btn {
    position: absolute; right: 12px;
    border: none; background: none; cursor: pointer;
    color: #c4b5fd; display: flex;
    transition: color 0.2s;
}
.lr-eye-btn:hover { color: #7c3aed; }
.lr-eye-btn .material-symbols-outlined { font-size: 18px; }

/* Submit */
.lr-submit {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 14px;
    background: linear-gradient(135deg, #4f46e5, #0891b2);
    color: white; border: none; border-radius: 14px;
    font-size: 14px; font-weight: 800; cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
    letter-spacing: 0.02em;
}
.lr-submit .material-symbols-outlined { font-size: 18px; transition: transform 0.3s ease; }
.lr-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(79, 70, 229, 0.45); }
.lr-submit:hover .material-symbols-outlined { transform: translateX(4px); }
.lr-submit:active { transform: scale(0.98); }


/* Footer */
.lr-footer {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    flex-wrap: wrap;
}
.lr-footer a { font-size: 11px; color: #9ca3af; text-decoration: none; transition: color 0.2s; font-weight: 500; }
.lr-footer a:hover { color: #6d28d9; }
.lr-dot { width: 3px; height: 3px; border-radius: 50%; background: #d1d5db; }

/* ═══ RESPONSIVE ═══ */
@media (max-width: 480px) {
    .lr-card { padding: 28px 20px; border-radius: 18px; }
    .login-right { padding: 24px 16px; }
}
</style>
