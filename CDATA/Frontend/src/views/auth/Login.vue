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
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--text-secondary);">Usuario</label>
                        <div class="search-input-wrapper" style="width: 100%; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 12px; display: flex; align-items: center; padding: 0 16px;">
                            <i class="fa-solid fa-user" style="color: var(--text-muted); margin-right: 10px;"></i>
                            <input v-model="form.usuario" type="text" required style="flex: 1; background: transparent; border: none; padding: 12px 0; color: var(--text-primary); outline: none;" placeholder="admin" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--text-secondary);">Contraseña</label>
                        <div class="search-input-wrapper" style="width: 100%; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 12px; display: flex; align-items: center; padding: 0 16px;">
                            <i class="fa-solid fa-lock" style="color: var(--text-muted); margin-right: 10px;"></i>
                            <input v-model="form.password" type="password" required style="flex: 1; background: transparent; border: none; padding: 12px 0; color: var(--text-primary); outline: none;" placeholder="••••••••" />
                        </div>
                    </div>
                    

                    <button type="submit" :disabled="loading" class="btn-primary btn-block" style="margin-top: 10px; width: 100%; padding: 14px; border-radius: 12px; border: none; background: linear-gradient(135deg, #4f46e5, #06b6d4); color: white; font-weight: bold; cursor: pointer;">
                        <span v-if="!loading">Acceder al Sistema <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i></span>
                        <span v-else>Cargando...</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { onMounted, ref, reactive } from 'vue';
import Swal from 'sweetalert2';

const router = useRouter();
const loading = ref(false);

const form = reactive({
    usuario: '',
    password: ''
});

onMounted(() => {
    document.body.classList.add('dark-theme');
});

const handleLogin = async () => {
    if (!form.usuario || !form.password) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos Vacíos',
            text: 'Por favor, ingresa tu usuario y contraseña.',
            background: '#0a0f1e',
            color: '#f8fafc',
            confirmButtonColor: '#4f46e5'
        });
        return;
    }
    
    loading.value = true;
    
    try {
        const response = await fetch(`${import.meta.env.BASE_URL}Backend/api/v1/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(form)
        });
        
        const data = await response.json();
        
        if (response.ok && data.token) {
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            
            Swal.fire({
                icon: 'success',
                title: '¡Acceso Concedido!',
                text: 'Iniciando sesión...',
                background: '#0a0f1e',
                color: '#f8fafc',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                router.push('/dashboard');
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Acceso Denegado',
                text: data.error || 'Credenciales incorrectas',
                background: '#0a0f1e',
                color: '#f8fafc',
                confirmButtonColor: '#4f46e5'
            });
        }
    } catch (error) {
        console.error("Login error:", error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: 'Ocurrió un problema de red. Intenta nuevamente.',
            background: '#0a0f1e',
            color: '#f8fafc',
            confirmButtonColor: '#4f46e5'
        });
    } finally {
        loading.value = false;
    }
};
</script>

