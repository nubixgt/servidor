<template>
    <div class="login-page">
        <header class="login-header">
            <a :href="baseUrl" class="brand" aria-label="SANAFER, inicio">
                <img class="brand-logo" :src="logo" alt="Logo de SANAFER 2026" width="52" height="52" />
                SANAFER<span class="brand-sub">BAILE<br />SOCIAL</span>
            </a>
        </header>

        <main class="login-main">
            <div class="login-shell">
                <section class="login-story" aria-label="Baile Social SANAFER 2026">
                    <div class="login-story-top">
                        <span class="eyebrow"><i /> FERIA SANAFER 2026</span>
                        <span class="login-edition">BAILE SOCIAL</span>
                    </div>
                    <div class="login-logo-art">
                        <img :src="logo" alt="Feria SANAFER 2026: tradición y celebración" width="380" height="380" fetchpriority="high" />
                    </div>
                    <div class="login-story-copy">
                        <h2>La mejor noche<br /><em>empieza contigo.</em></h2>
                        <p>Buena música, buena compañía<br />y un lugar para compartir.</p>
                    </div>
                    <div class="login-story-bottom">
                        <Ticket :size="19" :stroke-width="1.4" />
                        <span>Una mesa. Ocho historias. Una gran noche.</span>
                        <span class="login-star" aria-hidden="true">✳</span>
                    </div>
                </section>

                <section class="login-form-panel" aria-labelledby="login-heading">
                    <div class="login-form-inner">
                        <span class="login-key"><LockKeyhole :size="23" :stroke-width="1.5" /></span>
                        <span class="eyebrow login-welcome">QUÉ BUENO TENERTE AQUÍ</span>
                        <h1 id="login-heading">Bienvenido<br /><em>de nuevo.</em></h1>
                        <p class="login-intro">Ingresa a tu cuenta para continuar.</p>

                        <form class="login-form" @submit.prevent="submit">
                            <div class="login-field">
                                <label for="login-user">Usuario</label>
                                <div class="login-input-wrap">
                                    <UserRound :size="18" :stroke-width="1.5" />
                                    <input id="login-user" v-model="username" name="username" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="Escribe tu usuario" maxlength="30" required autofocus :disabled="busy" />
                                </div>
                            </div>
                            <div class="login-field">
                                <label for="login-password">Contraseña</label>
                                <div class="login-input-wrap">
                                    <LockKeyhole :size="18" :stroke-width="1.5" />
                                    <input id="login-password" v-model="password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" placeholder="Escribe tu contraseña" maxlength="72" required :disabled="busy" />
                                    <button class="login-password-toggle" type="button" :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'" :aria-pressed="showPassword" @click="showPassword = !showPassword">
                                        <EyeOff v-if="showPassword" :size="18" />
                                        <Eye v-else :size="18" />
                                    </button>
                                </div>
                            </div>
                            <div class="login-options">
                                <button type="button" class="login-help-toggle" :aria-expanded="showHelp" aria-controls="login-help" @click="showHelp = !showHelp">
                                    ¿Olvidaste tu contraseña?<ChevronDown :size="13" />
                                </button>
                            </div>
                            <p v-if="showHelp" id="login-help" class="login-help-message">
                                Solicita el cambio de tu contraseña al administrador del evento.
                            </p>
                            <button class="primary login-submit" type="submit" :disabled="busy || !username.trim() || !password">
                                {{ busy ? 'Ingresando…' : 'Iniciar sesión' }}<ArrowRight :size="18" />
                            </button>
                        </form>
                    </div>
                </section>
            </div>
        </main>

        <footer class="login-footer">
            <span>SANAFER <b>2026</b></span>
            <span>Buena música. Buena compañía. Una gran noche.</span>
        </footer>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowRight, ChevronDown, Eye, EyeOff, LockKeyhole, Ticket, UserRound } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';
import { alertBox } from '../../utils/alert';
import logo from '../../assets/images/sanafer-2026.png';

const baseUrl = import.meta.env.BASE_URL;
const router = useRouter();
const auth = useAuthStore();

const username = ref('');
const password = ref('');
const showPassword = ref(false);
const showHelp = ref(false);
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
