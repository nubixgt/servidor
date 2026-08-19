<template>
    <div class="min-h-screen w-full relative flex items-center justify-center p-4">
        <div class="relative z-10 w-full max-w-md bg-white/10 backdrop-blur-2xl backdrop-saturate-150 rounded-[32px] p-8 sm:p-10 border border-white/25 shadow-[inset_0_1px_0_rgba(255,255,255,0.4),0_8px_40px_rgba(0,0,0,0.3)] animate-fadeIn">
            <div class="text-center mb-8">
                <div class="w-16 h-16 rounded-2xl bg-white/15 border border-white/30 flex items-center justify-center mx-auto mb-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.4),0_0_20px_rgba(255,255,255,0.1)]">
                    <svg viewBox="0 0 48 48" width="30" height="30">
                        <defs><linearGradient id="gradAuth" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4ade80"/><stop offset="1" stop-color="#16a34a"/></linearGradient></defs>
                        <path d="M6 30 Q18 10 42 14 Q26 20 22 40 Q16 26 6 30Z" fill="url(#gradAuth)"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Keyline<span class="text-[#22c55e]">GT</span></h1>
                <p class="text-xs text-[#22c55e] font-semibold tracking-wide uppercase mt-0.5">Avance Nacional de Parcelas</p>
                <p class="text-xs text-white/60 mt-2">Suelo y agua para el futuro · Guatemala</p>
            </div>

            <form @submit.prevent="handleLogin" class="space-y-4">
                <div>
                    <label class="text-xs font-medium text-[#cbd5e1] block mb-1.5">Usuario</label>
                    <div class="relative">
                        <User class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-white/50" />
                        <input
                            v-model="usuario"
                            type="text"
                            required
                            autocomplete="username"
                            placeholder="tu.usuario"
                            class="w-full bg-white/10 border border-white/20 focus:border-white/50 focus:bg-white/15 rounded-xl py-2.5 pl-10 pr-4 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors"
                        />
                    </div>
                </div>

                <div>
                    <label class="text-xs font-medium text-[#cbd5e1] block mb-1.5">Contraseña</label>
                    <div class="relative">
                        <Lock class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-white/50" />
                        <input
                            v-model="password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full bg-white/10 border border-white/20 focus:border-white/50 focus:bg-white/15 rounded-xl py-2.5 pl-10 pr-10 text-xs text-white placeholder:text-white/40 focus:outline-none transition-colors"
                        />
                        <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white">
                            <EyeOff v-if="showPassword" class="w-4 h-4" />
                            <Eye v-else class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full py-3 bg-[#22c55e] hover:bg-[#16a34a] text-black font-bold text-xs rounded-xl shadow-lg shadow-[#22c55e]/20 transition-all flex items-center justify-center gap-2 mt-2 disabled:opacity-60"
                >
                    <div v-if="loading" class="w-4 h-4 border-2 border-black border-t-transparent rounded-full animate-spin"></div>
                    <template v-else>
                        <span>Ingresar al sistema</span>
                        <ArrowRight class="w-4 h-4" />
                    </template>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-white/15 text-center">
                <p class="text-[11px] text-white/40">Sistema de monitoreo de parcelas Keyline · Guatemala</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { toastSuccess, alertError } from '../../utils/alerts';
import { User, Lock, Eye, EyeOff, ArrowRight } from '@lucide/vue';

const usuario = ref('');
const password = ref('');
const loading = ref(false);
const showPassword = ref(false);

const auth = useAuthStore();
const router = useRouter();

const handleLogin = async () => {
    loading.value = true;
    try {
        const user = await auth.login(usuario.value.trim(), password.value);
        await toastSuccess(`Bienvenido, ${user.nombre}`);
        router.push(auth.homeRoute);
    } catch (err) {
        alertError(err.message || 'No se pudo iniciar sesión.', 'Credenciales inválidas');
    } finally {
        loading.value = false;
    }
};
</script>
