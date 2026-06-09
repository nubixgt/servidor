<template>
    <div class="flex items-center justify-center min-h-screen bg-background relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-primary/10 rounded-full blur-[100px] pointer-events-none -z-10"></div>
        <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-tertiary/10 rounded-full blur-[100px] pointer-events-none -z-10"></div>

        <div class="p-10 bg-surface-container rounded-2xl shadow-2xl w-[400px] border border-outline-variant relative z-10">
            <!-- Header & Logo -->
            <div class="flex flex-col items-center justify-center mb-8">
                <img alt="Horus Logo Gold" class="w-24 h-24 rounded-full object-cover border-2 border-primary shadow-[0_0_20px_rgba(233,193,118,0.3)] mb-4" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDfcPwaOtB0nrnifCx3L2LwjdLAsUiW41ZeG4ORojWoloZN4Vi8DdLk84ziY_i5_TYw7XxmPjfTMXSVIg6mu7pnTPKvHnL8-VdlWM7giQJQWN1XSmKIUvo60yPCEqDw0zOU_rGuLy0VgcGS9RILTDOfLgIMXBqV4z9B29pZh9ICQKTMAsVvJRUlUmbKfRUPNfv1SJ5H0kt76PGx6Gx2-UIXF4mPDb1e7BZeM2AgAWlgRQqsJ2gPjjnuGZgiHQ7p-wkFQrQ"/>
                <h1 class="font-headline-lg text-3xl font-bold text-center text-primary leading-tight tracking-tight">Horus</h1>
                <p class="font-label-caps text-label-caps text-on-surface-variant uppercase tracking-widest mt-1">Elite Finance</p>
            </div>
            
            <form @submit.prevent="handleLogin" class="space-y-5">
                <div>
                    <label class="block mb-2 font-label-caps text-xs text-on-surface-variant uppercase tracking-wider">Usuario</label>
                    <div class="relative focus-within:text-primary transition-colors text-on-surface-variant">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[20px]">person</span>
                        <input type="text" class="w-full px-3 py-3 pl-10 border border-outline-variant bg-surface rounded-lg shadow-inner appearance-none text-on-surface font-body-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" placeholder="Nombre de usuario" />
                    </div>
                </div>
                <div>
                    <label class="block mb-2 font-label-caps text-xs text-on-surface-variant uppercase tracking-wider">Contraseña</label>
                    <div class="relative focus-within:text-primary transition-colors text-on-surface-variant">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[20px]">lock</span>
                        <input type="password" class="w-full px-3 py-3 pl-10 border border-outline-variant bg-surface rounded-lg shadow-inner appearance-none text-on-surface font-body-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" placeholder="••••••••" />
                    </div>
                </div>
                
                <div class="pt-4">
                    <button type="submit" class="w-full py-3 font-title-md text-on-primary bg-primary rounded-lg hover:bg-primary-fixed-dim hover:shadow-[0_0_15px_rgba(233,193,118,0.4)] transition-all duration-300 transform active:scale-95 flex items-center justify-center gap-2">
                        <span>Iniciar Sesión</span>
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const handleLogin = () => {
    // No backend yet, just redirect to dashboard
    router.push('/dashboard');
};
</script>
