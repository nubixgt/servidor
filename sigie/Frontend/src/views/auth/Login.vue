<template>
    <div class="bg-login-fondo flex flex-col min-h-screen select-none">
        <!-- Main Content - Centered Card -->
        <div class="flex-1 flex items-center justify-center px-4 py-8">
            <div class="w-full max-w-[420px] animate-fade-in">
                <!-- Login Card -->
                <div class="bg-white rounded-2xl shadow-premium-lg overflow-hidden">
                    <!-- Navy Header with Logo -->
                    <div class="bg-[#0a192f] px-8 py-6 flex flex-col items-center">
                        <div class="w-16 h-16 flex items-center justify-center mb-3">
                            <lottie-player
                                ref="lottiePlayer"
                                :src="lottieUrl"
                                background="transparent"
                                speed="1"
                                style="width: 64px; height: 64px;"
                                loop
                                autoplay
                            ></lottie-player>
                        </div>
                        <h1 class="text-xl font-extrabold tracking-wider text-white font-headline">SIGIE</h1>
                        <p class="text-[9px] font-semibold text-white/50 uppercase tracking-[0.2em] mt-1">Sistema de Gestión de Inspecciones</p>
                    </div>

                    <!-- Form Section -->
                    <div class="px-8 py-8">
                        <div class="mb-6">
                            <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Bienvenido a SIGIE</h2>
                            <p class="text-[11px] text-slate-500 mt-1">Ingresa tus credenciales para acceder al sistema institucional.</p>
                        </div>

                        <form class="flex flex-col gap-5" @submit.prevent="handleLogin">
                            <!-- Username Field -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider" for="username">Usuario</label>
                                <div class="relative flex items-center bg-slate-50 border border-slate-200 rounded-xl focus-within:border-blue-600 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-50 transition-all duration-200">
                                    <span class="material-symbols-outlined absolute left-3.5 text-slate-400 text-lg">person</span>
                                    <input
                                        id="username"
                                        v-model="username"
                                        type="text"
                                        required
                                        placeholder="Ej. juan.perez"
                                        class="w-full bg-transparent border-none outline-none py-3 pl-11 pr-4 text-xs font-semibold text-slate-800 placeholder-slate-400"
                                    />
                                </div>
                            </div>

                            <!-- Password Field -->
                            <div class="flex flex-col gap-1.5">
                                <div class="flex justify-between items-center">
                                    <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider" for="password">Contraseña</label>
                                    <a href="#" class="text-[10px] font-bold text-blue-600 hover:text-blue-700 transition-colors">¿Olvidó su contraseña?</a>
                                </div>
                                <div class="relative flex items-center bg-slate-50 border border-slate-200 rounded-xl focus-within:border-blue-600 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-50 transition-all duration-200">
                                    <span class="material-symbols-outlined absolute left-3.5 text-slate-400 text-lg">lock</span>
                                    <input
                                        id="password"
                                        v-model="password"
                                        :type="showPwd ? 'text' : 'password'"
                                        required
                                        placeholder="••••••••"
                                        class="w-full bg-transparent border-none outline-none py-3 pl-11 pr-12 text-xs font-semibold text-slate-800 placeholder-slate-400"
                                    />
                                    <button type="button" @click="showPwd = !showPwd" class="absolute right-3.5 text-slate-400 hover:text-slate-600 transition-colors flex">
                                        <span class="material-symbols-outlined text-lg">{{ showPwd ? 'visibility_off' : 'visibility' }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" :disabled="loading" class="flex items-center justify-center gap-2 w-full py-3.5 bg-[#0a192f] hover:bg-[#122347] text-white rounded-xl text-xs font-bold tracking-wide transition-all duration-250 shadow-md hover:shadow-lg disabled:opacity-75 disabled:cursor-not-allowed mt-2">
                                <span v-if="!loading" class="flex items-center justify-center gap-1.5">
                                    Ingresar al Sistema
                                    <span class="material-symbols-outlined text-base">arrow_right_alt</span>
                                </span>
                                <span v-else class="flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined animate-spin text-base">sync</span>
                                    Autenticando...
                                </span>
                            </button>
                        </form>

                        <!-- Footer Quick Links -->
                        <div class="flex items-center justify-center gap-3 pt-5 mt-5 border-t border-slate-100 text-[10px] font-semibold text-slate-400">
                            <a href="#" class="hover:text-blue-600 transition-colors">¿Problemas para acceder?</a>
                            <span class="text-blue-600 font-bold hover:underline cursor-pointer">Contactar con soporte técnico</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="flex items-center justify-between px-6 py-4 text-[9px] font-semibold text-white/50">
            <div class="flex items-center gap-4">
                <a href="#" class="hover:text-white/80 transition-colors">Soporte</a>
                <a href="#" class="hover:text-white/80 transition-colors">Seguridad</a>
                <a href="#" class="hover:text-white/80 transition-colors">Privacidad</a>
            </div>
            <div class="text-right">
                <span class="tracking-wider uppercase">© 2024 Ministerio de Agricultura, Ganadería y Alimentación</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../../stores/authStore.js';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import '@lottiefiles/lottie-player';

const getLottieUrl = () => {
    const path = window.location.pathname.toLowerCase();
    const isViteDev = window.location.port !== '' && window.location.port !== '80' && window.location.port !== '8080';
    if (isViteDev) {
        return '/login-animation.json';
    }
    const distIndex = path.indexOf('/frontend/dist');
    if (distIndex !== -1) {
        return window.location.pathname.substring(0, distIndex) + '/Frontend/dist/login-animation.json';
    }
    const sigieIndex = path.indexOf('/sigie');
    if (sigieIndex !== -1) {
        return window.location.pathname.substring(0, sigieIndex) + '/sigie/login-animation.json';
    }
    return '/login-animation.json';
};
const lottieUrl = getLottieUrl();

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
            confirmButtonColor: '#0a192f',
        });
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
/* Override background styles for login inputs since they sit on white card */
</style>
