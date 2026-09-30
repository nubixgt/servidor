<template>
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="app-bg" aria-hidden="true"></div>

        <div class="glass w-full max-w-[420px] p-6 sm:p-8">
            <div class="flex flex-col items-center text-center mb-6">
                <div class="w-16 h-16 rounded-full bg-white/80 flex items-center justify-center mb-3" style="box-shadow: 0 10px 30px rgba(11,31,77,.14);">
                    <svg viewBox="0 0 48 48" class="w-10 h-10" fill="none" aria-hidden="true">
                        <path d="M24 42V22" stroke="#056B37" stroke-width="3" stroke-linecap="round" />
                        <path d="M24 26C24 15 15 9 6 9c0 11 7 17 18 17Z" fill="#079447" />
                        <path d="M24 22C24 13 31 7 42 7c0 10-7 15-18 15Z" fill="#35B96B" />
                    </svg>
                </div>
                <h1 class="text-[28px] font-extrabold leading-tight" style="color: var(--green-dark);">Daños a Cultivos</h1>
                <p class="section-desc">Inicia sesión para continuar</p>
            </div>

            <form class="space-y-4" @submit.prevent="ingresar">
                <div>
                    <label class="label" for="usuario">Usuario</label>
                    <div class="relative">
                        <UserIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--muted);" />
                        <input id="usuario" v-model.trim="usuario" class="field" style="padding-left: 40px;" autocomplete="username" autofocus placeholder="Tu usuario" />
                    </div>
                </div>
                <div>
                    <label class="label" for="password">Contraseña</label>
                    <div class="relative">
                        <LockClosedIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--muted);" />
                        <input id="password" v-model="password" :type="ver ? 'text' : 'password'" class="field" style="padding-left: 40px; padding-right: 44px;" autocomplete="current-password" placeholder="Tu contraseña" />
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2" :aria-label="ver ? 'Ocultar contraseña' : 'Mostrar contraseña'" @click="ver = !ver">
                            <component :is="ver ? EyeSlashIcon : EyeIcon" class="w-5 h-5" style="color: var(--muted);" />
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-full" :disabled="cargando">
                    <ArrowPathIcon v-if="cargando" class="w-5 h-5 animate-spin" />
                    {{ cargando ? 'Ingresando…' : 'Iniciar sesión' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import { UserIcon, LockClosedIcon, EyeIcon, EyeSlashIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';
import authService from '../../services/authService';
import { alertError } from '../../utils/alerts';

const router = useRouter();
const usuario = ref('');
const password = ref('');
const ver = ref(false);
const cargando = ref(false);

const ingresar = async () => {
    if (!usuario.value || !password.value) {
        alertError('Ingresa tu usuario y contraseña.', 'Datos incompletos');
        return;
    }
    cargando.value = true;
    try {
        const user = await authService.login(usuario.value, password.value);
        await Swal.fire({
            icon: 'success',
            title: '¡Bienvenido!',
            text: `Sesión iniciada como ${user.usuario}.`,
            timer: 1400,
            showConfirmButton: false,
            timerProgressBar: true,
        });
        router.replace({ name: 'Dashboard' });
    } catch (e) {
        const sinRespuesta = !e.response;
        alertError(
            sinRespuesta ? 'No se pudo conectar con el servidor.' : (e.response.data?.error || 'Usuario o contraseña incorrectos.'),
            sinRespuesta ? 'Sin conexión' : 'No se pudo iniciar sesión',
        );
        password.value = '';
    } finally {
        cargando.value = false;
    }
};
</script>
