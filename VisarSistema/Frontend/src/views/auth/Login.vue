<template>
    <div class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900 relative overflow-hidden">
        
        <!-- Dynamic Animated Background -->
        <div class="absolute inset-0 bg-slate-50 dark:bg-slate-900 z-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
            <div class="absolute top-40 -left-40 w-96 h-96 bg-emerald-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
            <div class="absolute -bottom-40 left-20 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0IiBoZWlnaHQ9IjQiPjxyZWN0IHdpZHRoPSI0IiBoZWlnaHQ9IjQiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSIvPjwvc3ZnPg==')] opacity-20"></div>
        </div>

        <div class="w-full h-full flex flex-col items-center justify-center p-4 relative z-10 animate-fade-in-up">
            
            <!-- Login Card (Glassmorphism Avanzado) -->
            <div class="bg-white/70 dark:bg-[#0f172a]/70 backdrop-blur-2xl rounded-[2.5rem] shadow-2xl p-8 sm:p-12 w-full max-w-md border border-white/60 dark:border-white/10 relative overflow-hidden group hover:shadow-emerald-500/10 transition-shadow duration-500">
                
                <!-- Glowing Orb Behind Card Content -->
                <div class="absolute -top-20 -right-20 w-40 h-40 bg-emerald-400/20 rounded-full blur-3xl group-hover:bg-emerald-400/40 transition-colors duration-700 pointer-events-none"></div>

                <!-- Logo Section -->
                <div class="flex flex-col items-center mb-10 relative z-10">
                    <div class="p-3 bg-white/50 dark:bg-slate-800/80 rounded-3xl shadow-sm border border-white/40 dark:border-white/10 mb-4 animate-float flex items-center justify-center bg-white dark:bg-transparent">
                        <img 
                            src="https://www.maga.gob.gt/wp-content/uploads/2024/01/1Maga-Logo.png" 
                            alt="MAGA Logo" 
                            class="h-20 w-auto object-contain drop-shadow-md dark:brightness-0 dark:invert transition-all duration-300"
                        />
                    </div>
                    <h2 class="text-3xl font-brand font-black text-slate-800 dark:text-white tracking-tight mb-1">
                        VISAR
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-xs tracking-wide text-center uppercase">Viceministerio de Sanidad Agropecuaria y Regulaciones</p>
                </div>

                <!-- Glass Card Form -->
                <div class="glass-panel p-8 rounded-3xl relative overflow-hidden">
                    
                    <!-- Decorative shine -->
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-white/50 to-transparent"></div>

                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        
                        <!-- Floating Label Input: Username -->
                        <div class="relative group">
                            <input 
                                type="text" 
                                id="username"
                                v-model="username"
                                @keydown.enter.prevent="focusPassword"
                                class="peer block w-full pl-12 pr-4 py-4 bg-white/60 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all dark:text-white backdrop-blur-md placeholder-transparent shadow-sm hover:bg-white/80 dark:hover:bg-slate-800/80"
                                placeholder="Usuario"
                             />
                            <label for="username" class="absolute left-12 -top-2 text-xs text-gray-500 dark:text-gray-400 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:top-4 peer-focus:-top-2 peer-focus:text-xs peer-focus:text-blue-500 bg-transparent peer-focus:bg-white dark:peer-focus:bg-slate-800 px-1 rounded-sm -ml-1">Usuario</label>
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <UserIcon class="w-5 h-5 text-gray-400 peer-focus:text-blue-500 transition-colors" />
                            </div>
                        </div>

                        <!-- Floating Label Input: Password -->
                        <div class="relative group">
                            <input 
                                type="password" 
                                id="password"
                                v-model="password"
                                ref="passwordInput"
                                class="peer block w-full pl-12 pr-4 py-4 bg-white/60 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all dark:text-white backdrop-blur-md placeholder-transparent shadow-sm hover:bg-white/80 dark:hover:bg-slate-800/80"
                                placeholder="Contraseña"
                            />
                            <label for="password" class="absolute left-12 -top-2 text-xs text-gray-500 dark:text-gray-400 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:top-4 peer-focus:-top-2 peer-focus:text-xs peer-focus:text-blue-500 bg-transparent peer-focus:bg-white dark:peer-focus:bg-slate-800 px-1 rounded-sm -ml-1">Contraseña</label>
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <LockClosedIcon class="w-5 h-5 text-gray-400 peer-focus:text-blue-500 transition-colors" />
                            </div>
                        </div>

                        <!-- Banner: Sesión expirada -->
                        <div v-if="sessionExpiredMsg" class="p-3 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-600 dark:text-amber-400 text-sm font-medium text-center animate-fade-in flex items-center gap-2 justify-center">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                            {{ sessionExpiredMsg }}
                        </div>

                        <div v-if="error" class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 text-sm font-medium text-center animate-fade-in">
                            {{ error }}
                        </div>


                        <button 
                            type="submit" 
                            :disabled="isLoading"
                            class="w-full relative group/btn overflow-hidden rounded-2xl p-4 bg-emerald-600 hover:bg-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-400 text-white font-bold text-sm tracking-wide transition-all duration-300 disabled:opacity-70 flex justify-center items-center shadow-lg hover:shadow-emerald-500/50 transform hover:-translate-y-1 mt-4"
                        >
                            <!-- Glow effect on hover -->
                            <div class="absolute inset-0 bg-white/20 translate-y-full group-hover/btn:translate-y-0 transition-transform duration-300 ease-out"></div>
                            
                            <span v-if="isLoading" class="flex items-center gap-2 relative z-10">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Autenticando...
                            </span>
                            <span v-else class="flex items-center gap-2 relative z-10 group-hover/btn:gap-3 transition-all duration-300">
                                Iniciar Sesión 
                                <ArrowRightIcon class="w-4 h-4 opacity-70 group-hover/btn:opacity-100 transition-opacity" />
                            </span>
                        </button>
                    </form>

                    <div class="mt-8 text-center">
                        <a href="#" class="text-sm text-gray-500 hover:text-primary transition-colors">¿Olvidaste tu contraseña?</a>
                    </div>
                </div>
                
                <!-- Footer text -->
                <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-8">
                    &copy; 2026 VISAR System v3.0
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { LockClosedIcon, UserIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import api from '../../services/api';

const router = useRouter();
const route = useRoute();
const username = ref('');
const password = ref('');
const error = ref('');
const sessionExpiredMsg = ref('');
const isLoading = ref(false);

const passwordInput = ref(null);

// Mostrar aviso si fue redirigido por sesión expirada
onMounted(() => {
    if (route.query.expired === '1') {
        sessionExpiredMsg.value = 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.';
    }
});

const focusPassword = () => {
    if (passwordInput.value) {
        passwordInput.value.focus();
    }
};

const handleSubmit = async () => {
    error.value = '';
    sessionExpiredMsg.value = '';
    isLoading.value = true;

    try {
        const response = await api.post('/auth/login', {
            username: username.value,
            password: password.value
        });

        if (response.data.success) {
            const { token, user } = response.data;
            localStorage.setItem('token', token);
            localStorage.setItem('user', JSON.stringify(user));
            localStorage.setItem('isAuthenticated', 'true');
            
            router.push('/admin/visar/dashboard');
        } else {
             error.value = response.data.error || response.data.message || 'Fallo desconocido';
        }
    } catch (err) {
        console.error(err);
        error.value = err.response?.data?.error || err.response?.data?.message || 'Error al iniciar sesión';
    } finally {
        isLoading.value = false;
    }
};
</script>


<style scoped>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}

.animate-blob {
  animation: blob 7s infinite;
}

.animation-delay-2000 {
  animation-delay: 2s;
}

.animation-delay-4000 {
  animation-delay: 4s;
}
</style>
