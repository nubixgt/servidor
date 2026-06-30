<template>
    <div class="login-root bg-grid-dots flex items-center justify-center min-h-screen px-4 py-8 select-none">
        <div class="w-full max-w-[440px] flex flex-col items-center">
            <!-- Centered Login Card -->
            <div class="bg-white rounded-[28px] border border-slate-100 p-8 md:p-10 w-full shadow-premium-lg animate-fade-in flex flex-col items-center">
                <!-- Animated Lottie Logo -->
                <div class="w-24 h-24 flex items-center justify-center mb-4">
                    <lottie-player
                        ref="lottiePlayer"
                        :src="lottieUrl"
                        background="transparent"
                        speed="1"
                        style="width: 96px; height: 96px;"
                        loop
                        autoplay
                    ></lottie-player>
                </div>

                <!-- Brand Header -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-extrabold tracking-wider text-slate-800 font-headline">SIGIE</h1>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest mt-1">Sistema de Gestión de Inspecciones</p>
                </div>

                <!-- Welcome Text -->
                <div class="mb-6 text-center w-full">
                    <h2 class="text-lg font-extrabold text-slate-900 tracking-tight leading-tight">Bienvenido a SIGIE</h2>
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">Ingresa tus credenciales para acceder al sistema institucional.</p>
                </div>

                <!-- Form -->
                <form class="flex flex-col gap-4 w-full mb-6" @submit.prevent="handleLogin">
                    <!-- Username Field -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider text-left" for="username">Usuario</label>
                        <div class="relative flex items-center bg-slate-50 border border-slate-200 rounded-xl focus-within:border-blue-600 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-100 transition-all duration-200">
                            <span class="material-symbols-outlined absolute left-3.5 text-slate-400 text-lg">person</span>
                            <input
                                id="username"
                                v-model="username"
                                type="text"
                                required
                                placeholder="nombre.usuario"
                                class="w-full bg-transparent border-none outline-none py-3 pl-11 pr-4 text-xs font-semibold text-slate-800 placeholder-slate-400"
                            />
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="flex flex-col gap-1.5">
                        <div class="flex justify-between items-center">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider text-left" for="password">Contraseña</label>
                            <a href="#" class="text-[10px] font-bold text-blue-600 hover:text-blue-700 transition-colors">¿Olvidó su clave?</a>
                        </div>
                        <div class="relative flex items-center bg-slate-50 border border-slate-200 rounded-xl focus-within:border-blue-600 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-100 transition-all duration-200">
                            <span class="material-symbols-outlined absolute left-3.5 text-slate-400 text-lg">lock</span>
                            <input
                                id="password"
                                v-model="password"
                                :type="showPwd ? 'text' : 'password'"
                                required
                                placeholder="••••••••••••"
                                class="w-full bg-transparent border-none outline-none py-3 pl-11 pr-12 text-xs font-semibold text-slate-800 placeholder-slate-400"
                            />
                            <button type="button" @click="showPwd = !showPwd" class="absolute right-3.5 text-slate-400 hover:text-slate-600 transition-colors flex">
                                <span class="material-symbols-outlined text-lg">{{ showPwd ? 'visibility_off' : 'visibility' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" :disabled="loading" class="flex items-center justify-center gap-2 w-full py-3.5 bg-[#0a192f] hover:bg-[#0f224b] text-white rounded-xl text-xs font-extrabold tracking-wide transition-all duration-250 shadow-md shadow-slate-900/10 hover:shadow-lg disabled:opacity-75 disabled:cursor-not-allowed mt-4">
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
                <div class="flex items-center justify-center gap-3 pt-5 border-t border-slate-100 text-[10px] font-bold text-slate-500 w-full">
                    <a href="#" class="hover:text-blue-600 transition-colors">Soporte Técnico</a>
                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                    <a href="#" class="hover:text-blue-600 transition-colors">Seguridad</a>
                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                    <a href="#" class="hover:text-blue-600 transition-colors">Privacidad</a>
                </div>
            </div>
            
            <!-- External Copyright -->
            <p class="text-[9px] font-bold text-slate-400 tracking-wider text-center mt-6 uppercase leading-relaxed max-w-[320px]">
                © 2026 Ministerio de Agricultura, Ganadería y Alimentación (MAGA).<br />Todos los derechos reservados.
            </p>
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
/* Las clases de tailwind se complementan con los estilos base globales */
</style>
