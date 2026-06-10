<template>
    <div class="flex items-center justify-center min-h-screen bg-background relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-primary/10 rounded-full blur-[100px] pointer-events-none -z-10"></div>
        <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-tertiary/10 rounded-full blur-[100px] pointer-events-none -z-10"></div>

        <div class="p-10 bg-surface-container-high/40 backdrop-blur-xl rounded-3xl shadow-[0_8px_32px_rgba(0,0,0,0.5),0_0_40px_rgba(233,193,118,0.1)] w-[400px] border border-white/10 relative z-10 group hover:shadow-[0_8px_32px_rgba(0,0,0,0.5),0_0_60px_rgba(233,193,118,0.2)] transition-all duration-700">
            <!-- Inner Glow -->
            <div class="absolute inset-0 bg-gradient-to-b from-primary/10 to-transparent opacity-50 rounded-3xl pointer-events-none"></div>

            <!-- Header & Logo -->
            <div class="flex flex-col items-center justify-center mb-10 relative">
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-32 h-32 bg-primary/20 rounded-full blur-[50px] pointer-events-none"></div>
                <img alt="Horus Logo Gold" class="w-56 h-56 object-contain drop-shadow-[0_0_25px_rgba(233,193,118,0.8)] relative z-10 hover:scale-105 transition-transform duration-500" src="/src/assets/images/LogoHorus-2.png"/>
            </div>
            
            <form @submit.prevent="handleLogin" class="space-y-5">
                <div class="relative z-10">
                    <label class="block mb-2 font-label-caps text-xs text-on-surface-variant uppercase tracking-wider drop-shadow-[0_0_2px_rgba(255,255,255,0.2)]">Usuario</label>
                    <div class="relative focus-within:text-primary transition-colors text-on-surface-variant group/input">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[20px] group-focus-within/input:drop-shadow-[0_0_8px_rgba(233,193,118,0.8)]">person</span>
                        <input v-model="username" type="text" class="w-full px-3 py-3 pl-10 border border-white/10 bg-surface-container-high/30 backdrop-blur-xl rounded-xl shadow-inner appearance-none text-on-surface font-body-lg focus:border-primary focus:ring-1 focus:ring-primary focus:shadow-[0_0_15px_rgba(233,193,118,0.3)] outline-none transition-all placeholder-on-surface-variant/50" placeholder="Nombre de usuario" required />
                    </div>
                </div>
                <div class="relative z-10">
                    <label class="block mb-2 font-label-caps text-xs text-on-surface-variant uppercase tracking-wider drop-shadow-[0_0_2px_rgba(255,255,255,0.2)]">Contraseña</label>
                    <div class="relative focus-within:text-primary transition-colors text-on-surface-variant group/input">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[20px] group-focus-within/input:drop-shadow-[0_0_8px_rgba(233,193,118,0.8)]">lock</span>
                        <input v-model="password" type="password" class="w-full px-3 py-3 pl-10 border border-white/10 bg-surface-container-high/30 backdrop-blur-xl rounded-xl shadow-inner appearance-none text-on-surface font-body-lg focus:border-primary focus:ring-1 focus:ring-primary focus:shadow-[0_0_15px_rgba(233,193,118,0.3)] outline-none transition-all placeholder-on-surface-variant/50" placeholder="••••••••" required />
                    </div>
                </div>
                
                <div class="pt-6 relative z-10">
                    <button type="submit" class="w-full py-3 bg-primary/10 border border-primary text-primary font-title-md rounded-xl hover:bg-primary/20 transition-all shadow-[0_0_20px_rgba(233,193,118,0.3)] hover:shadow-[0_0_30px_rgba(233,193,118,0.6)] backdrop-blur-sm flex items-center justify-center gap-2 font-medium active:scale-95 duration-300 group">
                        <span class="drop-shadow-[0_0_8px_rgba(233,193,118,0.8)]">Iniciar Sesión</span>
                        <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform drop-shadow-[0_0_8px_rgba(233,193,118,0.8)]">arrow_forward</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { authService } from '../../services/authService';
import Swal from 'sweetalert2';

const router = useRouter();
const username = ref('');
const password = ref('');
const isLoading = ref(false);

const handleLogin = async () => {
    isLoading.value = true;
    try {
        const response = await authService.login({
            username: username.value,
            password: password.value
        });
        
        localStorage.setItem('token', response.data.token);
        
        await Swal.fire({
            title: '¡Bienvenido!',
            text: 'Has iniciado sesión correctamente',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false,
            background: '#131313',
            color: '#ffffff',
            customClass: {
                popup: 'border border-white/10 rounded-2xl',
                title: 'text-primary font-headline-lg',
            }
        });

        router.push('/dashboard');
    } catch (error) {
        console.error(error);
        Swal.fire({
            title: 'Error de Autenticación',
            text: error.response?.data?.error || 'Usuario o contraseña incorrectos',
            icon: 'error',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176',
            customClass: {
                popup: 'border border-white/10 rounded-2xl',
                title: 'text-error font-headline-lg',
            }
        });
    } finally {
        isLoading.value = false;
    }
};
</script>
