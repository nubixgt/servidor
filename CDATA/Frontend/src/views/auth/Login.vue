<template>
    <div>
        <!-- Background Animated Blobs -->
        <div class="liquid-bg">
            <div class="blob blob-purple"></div>
            <div class="blob blob-blue"></div>
            <div class="blob blob-cyan"></div>
        </div>

        <div class="app-container" style="justify-content: center; align-items: center; min-height: 100vh;">
            <div class="glass-panel" style="width: 100%; max-width: 400px; padding: 40px;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <div class="logo-icon" style="justify-content: center; margin-bottom: 16px;">
                        <div class="hexagon-layer shadow-blue" style="width: 56px; height: 56px; font-size: 24px;">
                            <i class="fa-solid fa-database"></i>
                        </div>
                    </div>
                    <h1 style="font-family: var(--font-heading); font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">CENTRO DE DATOS</h1>
                    <p style="font-size: 14px; color: var(--text-secondary); font-weight: 500;">Iniciar Sesión</p>
                </div>

                <form @submit.prevent="handleLogin" style="display: flex; flex-direction: column; gap: 20px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--text-secondary);">Correo Electrónico</label>
                        <div class="search-input-wrapper" style="width: 100%; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 12px; display: flex; align-items: center; padding: 0 16px;">
                            <i class="fa-solid fa-envelope" style="color: var(--text-muted); margin-right: 10px;"></i>
                            <input type="email" style="flex: 1; background: transparent; border: none; padding: 12px 0; color: var(--text-primary); outline: none;" placeholder="ejemplo@correo.com" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--text-secondary);">Contraseña</label>
                        <div class="search-input-wrapper" style="width: 100%; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 12px; display: flex; align-items: center; padding: 0 16px;">
                            <i class="fa-solid fa-lock" style="color: var(--text-muted); margin-right: 10px;"></i>
                            <input type="password" style="flex: 1; background: transparent; border: none; padding: 12px 0; color: var(--text-primary); outline: none;" placeholder="••••••••" />
                        </div>
                    </div>

                    <button type="button" @click="handleLogin" class="btn-primary btn-block" style="margin-top: 10px;">
                        <span>Acceder al Sistema <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { onMounted } from 'vue';

const router = useRouter();

onMounted(() => {
    document.body.classList.add('dark-theme');
});

const handleLogin = () => {
    console.log("Login attempt");
    // Redirect to dashboard for demo
    router.push('/dashboard');
};
</script>

