<template>
    <div class="bg-surface text-on-surface min-h-screen flex flex-col items-center justify-center p-6 relative overflow-hidden">
        <!-- Decorative blobs -->
        <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full pointer-events-none" style="background: radial-gradient(circle, hsl(195,70%,40%,0.12) 0%, transparent 70%)"></div>
        <div class="absolute -bottom-32 -left-32 w-96 h-96 rounded-full pointer-events-none" style="background: radial-gradient(circle, hsl(195,70%,40%,0.08) 0%, transparent 70%)"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full pointer-events-none opacity-30" style="background: radial-gradient(circle, hsl(195,60%,50%,0.04) 0%, transparent 60%)"></div>

        <main class="w-full max-w-md relative z-10">
            <!-- Logo Section -->
            <div class="text-center mb-10">
                <!-- SVG Emblem -->
                <div class="inline-flex items-center justify-center mb-6 logo-container">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="logo-svg">
                        <defs>
                            <radialGradient id="dipu-bg" cx="40" cy="28" r="44" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#1b3a6b"/>
                                <stop offset="100%" stop-color="#060e1c"/>
                            </radialGradient>
                            <linearGradient id="dipu-teal" x1="0" y1="0" x2="0" y2="80" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#7dd8f3"/>
                                <stop offset="100%" stop-color="#0891b2"/>
                            </linearGradient>
                            <linearGradient id="dipu-gold" x1="0" y1="0" x2="0" y2="80" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#fef08a"/>
                                <stop offset="100%" stop-color="#d97706"/>
                            </linearGradient>
                            <linearGradient id="dipu-border" x1="8" y1="4" x2="72" y2="76" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#67e8f9"/>
                                <stop offset="55%" stop-color="#0891b2"/>
                                <stop offset="100%" stop-color="#164e63"/>
                            </linearGradient>
                            <filter id="dipu-glow-sm">
                                <feGaussianBlur stdDeviation="1.5" result="b"/>
                                <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                            </filter>
                            <filter id="dipu-glow-md">
                                <feGaussianBlur stdDeviation="2.5" result="b"/>
                                <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                            </filter>
                            <filter id="dipu-drop">
                                <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="#000a1f" flood-opacity="0.6"/>
                            </filter>
                        </defs>
                        <!-- Ambient glow -->
                        <circle cx="40" cy="42" r="30" fill="#0ea5e9" opacity="0.05" filter="url(#dipu-glow-md)"/>
                        <!-- Main hexagon -->
                        <polygon points="40,4 73,22 73,58 40,76 7,58 7,22" fill="url(#dipu-bg)" filter="url(#dipu-drop)"/>
                        <!-- Hex border -->
                        <polygon points="40,4 73,22 73,58 40,76 7,58 7,22" fill="none" stroke="url(#dipu-border)" stroke-width="1.5"/>
                        <!-- Inner ring -->
                        <polygon points="40,11 67,27 67,53 40,69 13,53 13,27" fill="none" stroke="#38bdf8" stroke-width="0.5" opacity="0.2"/>
                        <!-- Left column -->
                        <rect x="24" y="35" width="5.5" height="20" rx="2.75" fill="url(#dipu-teal)" filter="url(#dipu-glow-sm)"/>
                        <!-- Right column -->
                        <rect x="50.5" y="35" width="5.5" height="20" rx="2.75" fill="url(#dipu-teal)" filter="url(#dipu-glow-sm)"/>
                        <!-- Center column (gold, taller) -->
                        <rect x="37.25" y="29" width="5.5" height="26" rx="2.75" fill="url(#dipu-gold)" filter="url(#dipu-glow-md)"/>
                        <!-- Pediment -->
                        <polygon points="40,19 58,32 22,32" fill="url(#dipu-gold)" opacity="0.9" filter="url(#dipu-glow-sm)"/>
                        <!-- Pediment top accent -->
                        <line x1="33" y1="19.5" x2="47" y2="19.5" stroke="#7dd8f3" stroke-width="1" opacity="0.5"/>
                        <!-- Base platform -->
                        <rect x="20" y="56" width="40" height="3.5" rx="1.75" fill="url(#dipu-teal)" opacity="0.8" filter="url(#dipu-glow-sm)"/>
                        <rect x="16" y="60.5" width="48" height="2" rx="1" fill="#38bdf8" opacity="0.25"/>
                        <!-- Apex star -->
                        <path d="M40,1.5 L41.5,3.5 L44,5 L41.5,6.5 L40,8.5 L38.5,6.5 L36,5 L38.5,3.5 Z" fill="url(#dipu-gold)" filter="url(#dipu-glow-md)"/>
                        <!-- Vertex accents -->
                        <circle cx="73" cy="22" r="1.8" fill="#38bdf8" opacity="0.45"/>
                        <circle cx="73" cy="58" r="1.8" fill="#38bdf8" opacity="0.45"/>
                        <circle cx="7" cy="22" r="1.8" fill="#38bdf8" opacity="0.45"/>
                        <circle cx="7" cy="58" r="1.8" fill="#38bdf8" opacity="0.45"/>
                    </svg>
                </div>

                <!-- Wordmark -->
                <div class="space-y-1">
                    <h1 class="text-3xl font-extrabold tracking-tight font-headline leading-none">
                        <span style="background: linear-gradient(135deg, hsl(195,65%,38%), hsl(210,60%,28%)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">SYS</span><span class="text-on-surface">·DIPU</span>
                    </h1>
                    <div class="flex items-center justify-center gap-2">
                        <div class="h-px flex-1 max-w-[40px]" style="background: linear-gradient(90deg, transparent, hsl(195,50%,50%))"></div>
                        <p class="text-on-surface-variant font-semibold tracking-[0.25em] text-[10px] uppercase">Portal de Gestión Institucional</p>
                        <div class="h-px flex-1 max-w-[40px]" style="background: linear-gradient(90deg, hsl(195,50%,50%), transparent)"></div>
                    </div>
                </div>
            </div>

            <!-- Login Card -->
            <div class="bg-surface-container-lowest p-10 rounded-2xl shadow-[0_12px_40px_rgba(43,52,55,0.06)] border border-outline-variant/10">
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-on-surface mb-1 font-headline">Acceso Administrativo</h2>
                    <p class="text-on-surface-variant text-sm">Ingrese sus credenciales para continuar.</p>
                </div>

                <form class="space-y-6" @submit.prevent="handleLogin">
                    <!-- Username Field -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-widest" for="username">
                            Usuario
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline">
                                <span class="material-symbols-outlined text-sm">person</span>
                            </div>
                            <input
                                id="username"
                                v-model="username"
                                name="username"
                                type="text"
                                required
                                placeholder="nombre.usuario"
                                class="block w-full pl-11 pr-4 py-3.5 bg-surface-container-low border-0 border-b-2 border-transparent focus:border-primary focus:ring-0 rounded-lg text-on-surface transition-all duration-300 placeholder:text-outline-variant/60 outline-none"
                            />
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-end">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-widest" for="password">
                                Contraseña
                            </label>
                            <a href="#" class="text-xs font-medium text-primary hover:text-primary-dim transition-colors">¿Olvidó su clave?</a>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline">
                                <span class="material-symbols-outlined text-sm">lock</span>
                            </div>
                            <input
                                id="password"
                                v-model="password"
                                name="password"
                                type="password"
                                required
                                placeholder="••••••••••••"
                                class="block w-full pl-11 pr-4 py-3.5 bg-surface-container-low border-0 border-b-2 border-transparent focus:border-primary focus:ring-0 rounded-lg text-on-surface transition-all duration-300 placeholder:text-outline-variant/60 outline-none"
                            />
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full py-4 px-6 bg-gradient-to-r from-primary to-primary-dim text-white font-bold rounded-xl shadow-md hover:shadow-xl transition-all duration-300 active:scale-[0.98] flex items-center justify-center space-x-2">
                        <span>Entrar</span>
                        <span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </button>
                </form>
            </div>



            <!-- Footer -->
            <div class="mt-10 text-center space-y-4">
                <p class="text-on-surface-variant text-xs font-medium">
                    © 2024 Civic Ethos Suite. Sistema de Gestión Pública.
                </p>
                <div class="flex items-center justify-center space-x-6">
                    <a href="#" class="text-xs text-outline hover:text-on-surface transition-colors">Soporte Técnico</a>
                    <span class="w-1 h-1 bg-outline-variant rounded-full"></span>
                    <a href="#" class="text-xs text-outline hover:text-on-surface transition-colors">Seguridad</a>
                    <span class="w-1 h-1 bg-outline-variant rounded-full"></span>
                    <a href="#" class="text-xs text-outline hover:text-on-surface transition-colors">Privacidad</a>
                </div>
            </div>
        </main>
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
            confirmButtonColor: '#005D6B',
        });
    }
};
</script>
