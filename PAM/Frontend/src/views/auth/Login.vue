<template>
    <section id="login" class="login">
        <form id="loginForm" class="panel gl" autocomplete="on" novalidate @submit.prevent="enviar">
            <img class="lg" :src="logo" alt="Ministerio de Trabajo y Previsión Social">
            <div>
                <h1>{{ cambio ? 'Cambie su contraseña' : 'Programa del Adulto Mayor' }}</h1>
                <p>{{ cambio ? 'Por seguridad debe definir una contraseña personal antes de continuar.' : 'Sistema Integral de Gestión y Monitoreo. Ingrese con su usuario institucional.' }}</p>
            </div>
            <div v-if="!cambio">
                <div class="field"><label for="lu">Usuario</label><input id="lu" ref="primero" v-model="usuario" name="username" autocomplete="username" required></div>
                <div class="field" style="margin-top:10px"><label for="lc">Contraseña</label><input id="lc" v-model="clave" name="password" type="password" autocomplete="current-password" required></div>
            </div>
            <div v-else>
                <div class="field"><label for="ca">Contraseña actual</label><input id="ca" ref="primero" v-model="actual" type="password" autocomplete="current-password"></div>
                <div class="field" style="margin-top:10px"><label for="cn">Nueva contraseña <small>· 10+ caracteres, mayúsculas, minúsculas y números</small></label><input id="cn" v-model="nueva" type="password" autocomplete="new-password"></div>
                <div class="field" style="margin-top:10px"><label for="cc">Repita la nueva contraseña</label><input id="cc" v-model="repetida" type="password" autocomplete="new-password"></div>
            </div>
            <div id="loginErr" class="err" role="alert">{{ error }}</div>
            <button id="loginBtn" class="btn pri lg" type="submit" :disabled="enviando">{{ cambio ? 'Guardar y continuar' : 'Ingresar' }}</button>
            <div class="pie">Uso exclusivo del personal autorizado. Toda acción queda registrada en la bitácora de auditoría.</div>
        </form>
    </section>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import logo from '../../assets/img/logo-mintrab.png';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const usuario = ref('');
const clave = ref('');
const actual = ref('');
const nueva = ref('');
const repetida = ref('');
const error = ref(auth.mensajeLogin);
const enviando = ref(false);
const primero = ref(null);

// con la sesión iniciada pero la contraseña temporal vigente, solo se pide el cambio
const cambio = computed(() => auth.autenticado && auth.usuario.debe_cambiar_clave);

function entrar() {
    const destino = route.query.redirect || (auth.puede('dashboard.ver') ? '/dashboard' : '/beneficiarios');
    router.replace(destino);
}

async function enviar() {
    error.value = '';
    enviando.value = true;
    try {
        if (cambio.value) {
            if (!actual.value || !nueva.value) throw new Error('Complete los campos');
            if (nueva.value !== repetida.value) throw new Error('Las contraseñas nuevas no coinciden');
            await auth.cambiarClave(actual.value, nueva.value);
            entrar();
        } else {
            if (!usuario.value.trim() || !clave.value) throw new Error('Escriba su usuario y contraseña');
            const d = await auth.login(usuario.value.trim(), clave.value);
            clave.value = '';
            if (d.usuario.debe_cambiar_clave) actual.value = '';
            else entrar();
        }
    } catch (err) {
        error.value = err.message;
    }
    enviando.value = false;
}

const enfocar = async () => { await nextTick(); primero.value && primero.value.focus(); };
watch(cambio, enfocar);
onMounted(() => {
    auth.mensajeLogin = '';
    enfocar();
});
</script>
