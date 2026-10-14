<template>
    <div class="login-screen">
        <div class="app-bg login-bg" aria-hidden="true"></div>

        <header class="login-header">
            <div class="login-access"><LeafIcon class="w-4 h-4" /> Gestión de campo</div>
        </header>

        <main class="login-stage">
            <section class="login-intro" aria-labelledby="login-intro-title">
                <p class="login-eyebrow">Información que protege</p>
                <h1 id="login-intro-title">Cuidamos el campo.<br />Empezamos con<br /><span>información.</span></h1>
                <p class="login-lead">Cada reporte acerca una respuesta. Registra las afectaciones y acompaña el trabajo de quienes producen nuestros alimentos.</p>
                <div class="login-features">
                    <div><MapPinIcon class="w-4 h-4" /> Ubicación en campo</div>
                    <div><CameraIcon class="w-4 h-4" /> Evidencia visual</div>
                    <div><Squares2X2Icon class="w-4 h-4" /> Seguimiento</div>
                </div>
                <div class="login-location">
                    <LeafIcon class="w-6 h-6" />
                    <div>
                        <b>Juntos por un campo más resiliente.</b>
                        <small>Guatemala · Agricultura y desarrollo rural</small>
                    </div>
                </div>
            </section>

            <section class="login-panel" aria-labelledby="login-heading">
                <img :src="logoLogin" alt="Daños a Cultivos - Ministerio de Agricultura, Ganadería y Alimentación" class="login-logo" />
                <div class="login-panel-top">
                    <div class="login-lock"><LockClosedIcon class="w-5 h-5" /></div>
                    <span>ACCESO AL SISTEMA</span>
                </div>
                <h2 id="login-heading">Bienvenido de nuevo.</h2>
                <p class="login-sub">Inicia sesión para continuar con el registro y seguimiento de daños a cultivos.</p>

                <form class="space-y-5" @submit.prevent="ingresar">
                    <div>
                        <label class="label" for="usuario">Usuario</label>
                        <div class="login-input">
                            <UserIcon class="login-icon" />
                            <input id="usuario" v-model.trim="usuario" class="field" autocomplete="username" autofocus placeholder="Tu usuario" />
                        </div>
                    </div>
                    <div>
                        <label class="label" for="password">Contraseña</label>
                        <div class="login-input">
                            <LockClosedIcon class="login-icon" />
                            <input id="password" v-model="password" :type="ver ? 'text' : 'password'" class="field" style="padding-right: 48px;" autocomplete="current-password" placeholder="Tu contraseña" />
                            <button type="button" class="password-toggle" :aria-label="ver ? 'Ocultar contraseña' : 'Mostrar contraseña'" @click="ver = !ver">
                                <component :is="ver ? EyeSlashIcon : EyeIcon" class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary login-submit" :disabled="cargando">
                        <ArrowPathIcon v-if="cargando" class="w-5 h-5 animate-spin" />
                        <span>{{ cargando ? 'Ingresando…' : 'Ingresar al sistema' }}</span>
                        <ArrowRightIcon v-if="!cargando" class="w-5 h-5" />
                    </button>
                </form>
            </section>
        </main>

        <footer class="login-footer">
            <span>Ministerio de Agricultura, Ganadería y Alimentación</span>
            <span>Daños a Cultivos</span>
        </footer>
    </div>
</template>

<script setup>
import logoLogin from '../../assets/images/DClogin.png';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import { UserIcon, LockClosedIcon, EyeIcon, EyeSlashIcon, ArrowPathIcon, ArrowRightIcon, MapPinIcon, CameraIcon, Squares2X2Icon, SparklesIcon as LeafIcon } from '@heroicons/vue/24/outline';
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

<style scoped>
.login-screen { position: relative; max-width: 1440px; min-height: 100vh; margin: auto; padding: 32px 58px 23px; color: #fff; display: flex; flex-direction: column; }
/* Velo oscuro lateral para que el texto blanco se lea sobre la foto. */
.login-bg::after { background: linear-gradient(90deg, rgba(3, 32, 33, .83), rgba(3, 37, 36, .47) 52%, rgba(2, 33, 32, .28)), linear-gradient(0deg, rgba(3, 30, 30, .42), transparent 60%); }
.login-header { display: flex; justify-content: flex-end; }
.login-access { display: flex; gap: 8px; align-items: center; color: #e8f5e8; font-size: 11px; border: 1px solid rgba(228, 244, 228, .24); background: rgba(255, 255, 255, .05); padding: 10px 15px; border-radius: 30px; -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px); }
.login-access svg { color: #c6efb4; }
.login-stage { display: grid; grid-template-columns: minmax(0, 1fr) 445px; align-items: center; gap: 70px; flex: 1; padding: 40px 0 45px; }
.login-intro { max-width: 640px; }
.login-eyebrow { display: flex; align-items: center; gap: 8px; font-size: 10px; font-weight: 650; letter-spacing: 2px; text-transform: uppercase; color: #d0f1d9; margin-bottom: 14px; }
.login-eyebrow::before { content: ''; width: 18px; height: 1px; background: #c0e9ab; }
.login-intro h1 { font-size: 58px; font-weight: 550; line-height: 1.08; letter-spacing: -2.3px; margin: 0 0 23px; }
.login-intro h1 span { color: #c8ee9d; }
.login-lead { font-size: 14px; line-height: 1.85; color: #e0ecdf; max-width: 410px; margin-bottom: 33px; }
.login-features { display: flex; gap: 23px; flex-wrap: wrap; color: #d1e7d5; font-size: 11px; }
.login-features div { display: flex; align-items: center; gap: 7px; }
.login-features svg { color: #b4dca0; }
.login-location { margin-top: 63px; border-top: 1px solid rgba(233, 250, 229, .18); padding-top: 21px; max-width: 405px; display: flex; align-items: center; gap: 13px; color: #d7e7d2; }
.login-location b { font-size: 11px; font-weight: 550; display: block; letter-spacing: .3px; }
.login-location small { font-size: 10px; display: block; color: #abc6af; margin-top: 6px; }
.login-panel { color: #123f3b; padding: 30px 35px 29px; border-radius: 29px; border: 1px solid rgba(255, 255, 255, .85); background: linear-gradient(135deg, rgba(255, 255, 255, .97), rgba(235, 247, 235, .91)); box-shadow: 0 25px 80px rgba(0, 30, 38, .2), inset 0 1px 2px #fff; -webkit-backdrop-filter: blur(22px); backdrop-filter: blur(22px); }
.login-logo { display: block; width: 100%; max-width: 230px; height: auto; margin: 0 auto 18px; }
.login-panel-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.login-lock { height: 43px; width: 43px; display: grid; place-items: center; border-radius: 14px; background: #e1f0e4; border: 1px solid #c3dfcc; color: #138358; }
.login-panel-top > span { font-size: 9px; letter-spacing: 1.3px; font-weight: 650; color: #638171; }
.login-panel h2 { font-size: 29px; font-weight: 700; letter-spacing: -1px; margin: 0 0 9px; }
.login-sub { font-size: 12px; color: #688275; line-height: 1.65; margin-bottom: 24px; }
.login-input { position: relative; }
.login-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #7e9a86; pointer-events: none; }
.login-input .field { height: 51px; padding-left: 44px; border-radius: 12px; border-color: #d0e0d2; }
.password-toggle { position: absolute; right: 7px; top: 7px; width: 37px; height: 37px; display: grid; place-items: center; color: #708d79; border-radius: 8px; }
.password-toggle:hover { background: #edf5ec; }
.login-submit { width: 100%; min-height: 51px; border-radius: 13px; justify-content: space-between; padding-left: 19px; padding-right: 17px; }
.login-footer { display: flex; justify-content: space-between; gap: 15px; font-size: 10px; color: #d1e5cf; line-height: 1.6; }

@media (min-width: 1600px) {
    .login-screen { max-width: 1510px; padding-left: 70px; padding-right: 70px; }
    .login-stage { grid-template-columns: minmax(0, 1fr) 475px; gap: 100px; }
    .login-intro h1 { font-size: 67px; }
}
@media (max-width: 1200px) {
    .login-screen { padding: 28px 38px 22px; }
    .login-stage { gap: 45px; grid-template-columns: minmax(0, 1fr) 410px; }
    .login-intro h1 { font-size: 48px; }
}
@media (max-width: 900px) {
    .login-screen { padding: 25px; }
    .login-stage { grid-template-columns: minmax(0, 1fr) 380px; gap: 25px; }
    .login-intro h1 { font-size: 38px; letter-spacing: -1.4px; }
    .login-features { flex-direction: column; gap: 13px; }
}
@media (max-width: 700px) {
    .login-bg::after { background: linear-gradient(180deg, rgba(7, 59, 54, .71), rgba(9, 45, 43, .42) 45%, rgba(11, 52, 41, .7)); }
    .login-screen { padding: 22px 20px 25px; }
    .login-stage { grid-template-columns: 1fr; gap: 25px; padding: 24px 0 26px; }
    .login-intro { max-width: 450px; margin: auto; width: 100%; padding: 0 7px; }
    .login-intro h1 { font-size: 32px; line-height: 1.12; margin-bottom: 13px; letter-spacing: -1px; }
    .login-intro h1 br:last-of-type { display: none; }
    .login-lead { font-size: 12px; margin-bottom: 0; max-width: 330px; }
    .login-features, .login-location { display: none; }
    .login-panel { width: 100%; max-width: 450px; margin: auto; padding: 26px; border-radius: 24px; }
    .login-footer { font-size: 9px; flex-direction: column; gap: 4px; text-align: center; }
}
</style>
