<template>
    <div class="bg-surface text-on-surface min-h-screen flex flex-col items-center justify-center p-6 relative overflow-hidden">
        <!-- Decorative Elements -->
        <div class="absolute top-0 right-0 p-12 pointer-events-none opacity-20 hidden lg:block">
            <div class="w-64 h-64 border-[40px] border-surface-container-highest rounded-full"></div>
        </div>
        <div class="absolute bottom-0 left-0 p-12 pointer-events-none opacity-20 hidden lg:block">
            <div class="w-32 h-32 bg-surface-container-highest rounded-full"></div>
        </div>

        <main class="w-full max-w-md relative z-10">
            <!-- Logo Section -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center mb-6">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-primary-dim flex items-center justify-center shadow-lg">
                        <span class="material-symbols-outlined text-white text-2xl">account_balance</span>
                    </div>
                </div>
                <h1 class="text-3xl font-extrabold text-on-surface tracking-tight mb-2 font-headline">
                    Civic Ethos Suite
                </h1>
                <p class="text-on-surface-variant font-medium tracking-wide text-sm uppercase">
                    Portal de Gestión Institucional
                </p>
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

                    <!-- Remember Me -->
                    <div class="flex items-center space-x-2 px-1">
                        <input type="checkbox" id="remember" class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary/20 bg-surface-container" />
                        <label for="remember" class="text-sm text-on-surface-variant cursor-pointer select-none">Recordar mi sesión</label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full py-4 px-6 bg-gradient-to-r from-primary to-primary-dim text-white font-bold rounded-xl shadow-md hover:shadow-xl transition-all duration-300 active:scale-[0.98] flex items-center justify-center space-x-2">
                        <span>Entrar</span>
                        <span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </button>
                </form>
            </div>

            <!-- Test Buttons -->
            <div class="mt-8 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/10 shadow-sm">
                <h3 class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-4 text-center">Ingreso Rápido (Pruebas)</h3>
                <div class="grid grid-cols-1 gap-3">
                    <button
                        @click="quickLogin('administrador')"
                        class="py-3 px-4 bg-primary-container text-on-primary-container text-xs font-bold rounded-xl hover:bg-primary hover:text-white transition-colors flex items-center justify-center gap-2"
                    >
                        <span class="material-symbols-outlined text-sm">admin_panel_settings</span>
                        Ingresar como Administrador (Diputado)
                    </button>
                    <div class="flex gap-2">
                        <button
                            @click="quickLogin('tecnico', tecnicoCategory)"
                            class="flex-1 py-3 px-4 bg-secondary-container text-on-secondary-container text-xs font-bold rounded-xl hover:bg-secondary hover:text-white transition-colors flex items-center justify-center gap-2"
                        >
                            <span class="material-symbols-outlined text-sm">manage_accounts</span>
                            Ingresar como Técnico
                        </button>
                        <select
                            v-model="tecnicoCategory"
                            class="bg-surface-container-low border-none rounded-xl text-xs font-bold text-on-surface px-3 focus:ring-2 focus:ring-primary/20 outline-none"
                        >
                            <option value="iniciativas">Iniciativas</option>
                            <option value="citaciones">Citaciones</option>
                            <option value="comisiones">Comisiones</option>
                            <option value="fiscalizacion">Fiscalización</option>
                            <option value="compromisos">Compromisos</option>
                            <option value="actividades">Actividades</option>
                            <option value="redes">Redes</option>
                            <option value="afiliaciones">Afiliaciones</option>
                        </select>
                    </div>
                </div>
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

const auth = useAuthStore();
const router = useRouter();

const username = ref('');
const password = ref('');
const tecnicoCategory = ref('iniciativas');

const handleLogin = () => {
    auth.login('administrador');
    router.push('/dashboard-admin');
};

const quickLogin = (role, category = null) => {
    auth.login(role, category);
    if (role === 'administrador') {
        router.push('/dashboard-admin');
    } else {
        router.push('/dashboard-tecnico');
    }
};
</script>
