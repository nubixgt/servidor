<template>
    <div class="grid min-h-screen lg:grid-cols-[1fr_minmax(420px,520px)]">
        <!-- Panel institucional -->
        <aside class="relative hidden bg-ink-800 text-white lg:flex lg:flex-col lg:justify-between lg:p-14">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded bg-white/10">
                    <TableCellsIcon class="h-5 w-5 text-brand-200" />
                </span>
                <span class="text-sm font-semibold uppercase tracking-[0.18em] text-ink-300">TestBases</span>
            </div>

            <div class="max-w-md">
                <h1 class="text-4xl font-semibold leading-tight">Registro de<br />Beneficiarios</h1>
                <p class="mt-5 text-base leading-relaxed text-ink-300">
                    Consulta de la base de entrega con seguimiento por persona: comentarios e imágenes de respaldo
                    en cada registro.
                </p>
            </div>

            <p class="text-xs text-ink-400">Acceso restringido a personal autorizado.</p>
        </aside>

        <!-- Formulario -->
        <main class="flex items-center justify-center bg-paper px-6 py-12">
            <div class="w-full max-w-sm">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="grid h-9 w-9 place-items-center rounded bg-ink-800">
                        <TableCellsIcon class="h-5 w-5 text-brand-200" />
                    </span>
                    <span class="text-sm font-semibold text-ink-800">Registro de Beneficiarios</span>
                </div>

                <h2 class="text-2xl font-semibold text-ink-900">Iniciar sesión</h2>
                <p class="mt-1 text-sm text-ink-500">Ingresa con tu usuario y contraseña.</p>

                <p v-if="route.query.expirada && !error"
                    class="mt-6 rounded border border-amber/30 bg-amber/10 px-3 py-2 text-sm text-ink-800">
                    Tu sesión expiró. Vuelve a ingresar.
                </p>
                <p v-if="error" class="mt-6 rounded border border-danger/30 bg-danger/5 px-3 py-2 text-sm text-danger"
                    role="alert">
                    {{ error }}
                </p>

                <form class="mt-6 space-y-5" @submit.prevent="enviar" novalidate>
                    <div>
                        <label class="etiqueta" for="usuario">Usuario</label>
                        <input id="usuario" v-model.trim="usuario" class="campo" type="text" autocomplete="username"
                            autocapitalize="none" spellcheck="false" maxlength="40" autofocus />
                    </div>

                    <div>
                        <label class="etiqueta" for="clave">Contraseña</label>
                        <div class="relative">
                            <input id="clave" v-model="clave" class="campo pr-10" :type="verClave ? 'text' : 'password'"
                                autocomplete="current-password" maxlength="200" />
                            <button type="button"
                                class="absolute inset-y-0 right-0 grid w-10 place-items-center text-ink-400 hover:text-ink-700"
                                :aria-label="verClave ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                @click="verClave = !verClave">
                                <component :is="verClave ? EyeSlashIcon : EyeIcon" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primario w-full py-2.5" :disabled="cargando">
                        <span v-if="cargando"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        {{ cargando ? 'Ingresando…' : 'Ingresar' }}
                    </button>
                </form>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { EyeIcon, EyeSlashIcon, TableCellsIcon } from '@heroicons/vue/20/solid';
import { useAuthStore } from '../../stores/auth';
import { mensajeDeError } from '../../services/api';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const usuario = ref('');
const clave = ref('');
const verClave = ref(false);
const cargando = ref(false);
const error = ref('');

const enviar = async () => {
    error.value = '';
    if (!usuario.value || !clave.value) {
        error.value = 'Escribe tu usuario y contraseña';
        return;
    }
    cargando.value = true;
    try {
        await auth.login(usuario.value, clave.value);
        router.push({ name: 'Beneficiarios' });
    } catch (e) {
        error.value = mensajeDeError(e, 'No se pudo iniciar sesión');
        clave.value = '';
    } finally {
        cargando.value = false;
    }
};
</script>
