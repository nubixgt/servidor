<template>
    <div class="login-page">
        <form class="login-card" @submit.prevent="submit">
            <img class="login-logo" :src="logo" alt="Logo de la Feria SANAFER 2026" width="150" height="150" />
            <span class="eyebrow">VENTA DE ENTRADAS</span>
            <h1>SANAFER <em>2026</em></h1>
            <label class="name-field">Usuario
                <input v-model="username" autofocus required maxlength="30" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="Usuario" :disabled="busy" />
            </label>
            <label class="name-field">Contraseña
                <input v-model="password" type="password" required maxlength="72" autocomplete="current-password" placeholder="Contraseña" :disabled="busy" />
            </label>
            <button class="primary" :disabled="busy || !username.trim() || !password">
                {{ busy ? 'Ingresando…' : 'Iniciar sesión' }}<ArrowRight :size="18" />
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowRight } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';
import { alertBox } from '../../utils/alert';
import logo from '../../assets/images/sanafer-2026.png';

const router = useRouter();
const auth = useAuthStore();

const username = ref('');
const password = ref('');
const busy = ref(false);

const submit = async () => {
    if (busy.value) return;
    busy.value = true;
    const res = await auth.login(username.value.trim(), password.value);
    busy.value = false;
    if (res.ok) {
        router.replace({ name: 'Home' });
    } else {
        password.value = '';
        alertBox('error', 'No se pudo iniciar sesión', res.message);
    }
};
</script>
