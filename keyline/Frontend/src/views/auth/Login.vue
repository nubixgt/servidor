<template>
    <div class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="p-8 bg-white rounded shadow-md w-96">
            <h1 class="mb-1 text-2xl font-bold text-center text-primary-600">KeylineGT</h1>
            <p class="mb-6 text-sm text-center text-slate-400">Sistema de registro de parcelas Keyline</p>
            <form @submit.prevent="handleLogin">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-bold text-gray-700">Usuario</label>
                    <input
                        v-model="usuario"
                        type="text"
                        required
                        autocomplete="username"
                        class="w-full px-3 py-2 border rounded shadow appearance-none"
                        placeholder="tu.usuario"
                    />
                </div>
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-bold text-gray-700">Contraseña</label>
                    <input
                        v-model="password"
                        type="password"
                        required
                        class="w-full px-3 py-2 border rounded shadow appearance-none"
                        placeholder="••••••••"
                    />
                </div>
                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full px-4 py-2 font-bold text-white bg-primary-500 rounded hover:bg-primary-600 disabled:opacity-60"
                >
                    {{ loading ? 'Ingresando…' : 'Ingresar' }}
                </button>
            </form>
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
