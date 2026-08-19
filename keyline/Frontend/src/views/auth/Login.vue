<template>
    <div class="auth-screen">
        <div class="auth-card glass">
            <div class="auth-brand">
                <div class="brand-mark">
                    <svg viewBox="0 0 48 48" width="34" height="34">
                        <defs><linearGradient id="gradAuth" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#7be495"/><stop offset="1" stop-color="#0f8a45"/></linearGradient></defs>
                        <path d="M6 30 Q18 10 42 14 Q26 20 22 40 Q16 26 6 30Z" fill="url(#gradAuth)"/>
                        <path d="M44 8 Q34 6 28 16 Q36 16 40 24 Q46 16 44 8Z" fill="#32b9e8" opacity=".9"/>
                    </svg>
                </div>
                <div>
                    <h1>Keyline<span>GT</span></h1>
                    <p>Suelo y agua para el futuro</p>
                </div>
            </div>
            <form @submit.prevent="handleLogin">
                <div class="field">
                    <label>Usuario</label>
                    <input v-model="usuario" type="text" required autocomplete="username" placeholder="tu.usuario" />
                </div>
                <div class="field">
                    <label>Contraseña</label>
                    <input v-model="password" type="password" required autocomplete="current-password" placeholder="••••••••" />
                </div>
                <button type="submit" :disabled="loading" class="btn btn-primary btn-block">
                    {{ loading ? 'Ingresando…' : 'Ingresar al sistema' }}
                </button>
            </form>
            <div class="auth-foot">
                <span>Sistema de monitoreo de parcelas Keyline · Guatemala</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { toastSuccess, alertError } from '../../utils/alerts';

const usuario = ref('');
const password = ref('');
const loading = ref(false);

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
