<template>
    <div class="min-h-screen">
        <div class="app-bg" aria-hidden="true"></div>

        <div class="shell">
            <aside class="sidebar">
                <div class="brand"><img :src="logo" alt="Daños a Cultivos" /></div>
                <div class="navlabel">Espacio de trabajo</div>
                <nav class="nav" aria-label="Navegación principal">
                    <router-link v-for="i in items" :key="i.to" :to="i.to" class="nav-link" active-class="" exact-active-class="active">
                        <component :is="i.icon" class="w-5 h-5" /><span class="navtext">{{ i.label }}</span>
                    </router-link>
                </nav>
                <div class="side-note">
                    <LeafIcon class="w-5 h-5" />
                    <strong>Información que<br />protege el campo.</strong>
                    <p>Cada registro ayuda a comprender y atender las afectaciones.</p>
                </div>
                <div class="side-bottom">
                    <div class="avatar side-avatar">{{ iniciales }}</div>
                    <div class="account-text">
                        <strong>{{ user?.usuario }}</strong>
                        <small class="capitalize">{{ user?.rol }}</small>
                    </div>
                    <button class="logout-button" title="Cerrar sesión" aria-label="Cerrar sesión" @click="salir"><ArrowRightOnRectangleIcon class="w-4 h-4" /></button>
                </div>
            </aside>

            <main class="main">
                <header class="topbar">
                    <div class="ministry"><b>MAGA</b><span>Ministerio de Agricultura,<br />Ganadería y Alimentación</span></div>
                    <div class="topmeta">
                        <span class="date-label">{{ hoy }}</span>
                        <div class="avatar top-avatar">{{ iniciales }}</div>
                        <button class="logout-top" title="Cerrar sesión" aria-label="Cerrar sesión" @click="salir"><ArrowRightOnRectangleIcon class="w-4 h-4" /></button>
                    </div>
                </header>

                <section class="hero">
                    <img :src="heroImg" class="hero-image" alt="" aria-hidden="true" />
                    <div>
                        <div class="eyebrow">Sistema de información agrícola</div>
                        <h1 v-html="hero[0]"></h1>
                        <p>{{ hero[1] }}</p>
                    </div>
                    <div v-if="route.path !== '/registros/crear'" class="hero-action">
                        <router-link to="/registros/crear" class="btn btn-primary">
                            <PlusIcon class="w-5 h-5" />Nuevo registro<ArrowRightIcon class="w-5 h-5" />
                        </router-link>
                        <small>Del campo a la toma de decisiones.</small>
                    </div>
                </section>

                <router-view />

                <footer class="footer">
                    <span>Daños a Cultivos · Ministerio de Agricultura, Ganadería y Alimentación</span>
                    <span>Del campo a la toma de decisiones</span>
                </footer>
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import {
    Squares2X2Icon, DocumentTextIcon, PencilSquareIcon, UsersIcon, ArrowRightOnRectangleIcon,
    PlusIcon, ArrowRightIcon, SparklesIcon as LeafIcon,
} from '@heroicons/vue/24/outline';
import logo from '../../assets/images/DC sin fondo.png';
import heroImg from '../../assets/images/Fondo.jpeg';
import authService from '../../services/authService';
import { confirmDialog, toastSuccess } from '../../utils/alerts';

const salir = async () => {
    if (!(await confirmDialog('Se cerrará tu sesión actual.', { title: '¿Cerrar sesión?', confirmText: 'Cerrar sesión' }))) return;
    authService.logout();
    await router.replace({ name: 'Login' });
    toastSuccess('Sesión cerrada correctamente');
};

const router = useRouter();
const route = useRoute();
const user = authService.user();
const iniciales = (user?.usuario || '?').slice(0, 2).toUpperCase();
const hoy = new Date().toLocaleDateString('es-GT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

const todosItems = [
    { to: '/', label: 'Dashboard', icon: Squares2X2Icon },
    { to: '/registros/crear', label: 'Crear Registros', icon: PencilSquareIcon },
    { to: '/registros', label: 'Registros', icon: DocumentTextIcon },
    { to: '/usuarios', label: 'Usuarios', icon: UsersIcon, roles: ['admin'] },
];
const items = todosItems.filter((i) => !i.roles || i.roles.includes(user?.rol));

// Titular de la portada según la pantalla.
const HEROES = {
    Dashboard: ['Un campo mejor protegido<br>empieza con información.', 'Registra, analiza y da seguimiento a las afectaciones en cultivos desde un solo lugar.'],
    CrearRegistro: ['Cada detalle cuenta.<br>Registra lo que ocurre en campo.', 'Ubicación, cultivo, daños y evidencia: toda la información en un proceso claro.'],
    Registros: ['La historia del campo,<br>en cada registro.', 'Explora las afectaciones reportadas y encuentra la información que necesitas.'],
    Usuarios: ['Un equipo conectado<br>con el campo.', 'Organiza a las personas que convierten las observaciones en información útil.'],
};
const hero = computed(() => HEROES[route.name] || HEROES.Dashboard);
</script>

<style scoped>
.shell { position: relative; max-width: 1760px; padding: 22px 30px 25px; margin: auto; display: grid; grid-template-columns: 224px minmax(0, 1fr); gap: 28px; min-height: 100vh; }
.main { min-width: 0; }

.sidebar {
    height: calc(100vh - 44px); position: sticky; top: 22px; display: flex; flex-direction: column; padding: 26px 16px 16px; color: #fff;
    border: 1px solid rgba(255, 255, 255, .46); border-radius: 26px;
    background: linear-gradient(160deg, rgba(10, 57, 55, .91), rgba(13, 66, 51, .81));
    -webkit-backdrop-filter: blur(25px); backdrop-filter: blur(25px);
    box-shadow: 0 14px 40px rgba(6, 39, 26, .15);
}
.brand { margin-bottom: 14px; padding: 6px; border-radius: 18px; background: rgba(255, 255, 255, .92); box-shadow: inset 0 1px #fff; }
.brand img { width: 100%; height: auto; display: block; }
.navlabel { color: #a0c6b8; text-transform: uppercase; font-size: 9px; letter-spacing: 1.8px; padding: 0 14px; margin: 10px 0 13px; }
.nav { display: grid; gap: 8px; }
.nav-link { padding: 14px; border-radius: 13px; display: flex; align-items: center; gap: 12px; color: #d2e7e0; font-size: 13px; transition: background .18s; }
.nav-link:hover { background: rgba(255, 255, 255, .08); }
.nav-link.active { background: linear-gradient(130deg, #01975b, #007a57); color: #fff; box-shadow: inset 0 1px rgba(255, 255, 255, .25), 0 5px 20px rgba(0, 45, 41, .15); }
.nav-link.active::after { content: ''; width: 5px; height: 5px; background: #a6eeba; border-radius: 50%; margin-left: auto; }
.side-note { margin-top: auto; padding: 20px 13px 24px; }
.side-note svg { color: #c4f0ab; margin-bottom: 12px; }
.side-note strong { display: block; font-size: 14px; line-height: 1.45; }
.side-note p { font-size: 11px; color: #b1ccc0; margin: 8px 0 0; line-height: 1.55; }
.side-bottom { border-top: 1px solid rgba(255, 255, 255, .13); padding: 18px 7px 3px; display: flex; align-items: center; gap: 12px; }
.side-avatar { border: 1px solid #aecbb6; background: rgba(255, 255, 255, .08); color: #fff; }
.account-text { min-width: 0; flex: 1; line-height: 1.2; }
.account-text strong { display: block; font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.account-text small { display: block; margin-top: 4px; font-size: 10px; color: #abc9bc; }
.logout-button { background: rgba(255, 255, 255, .08); color: #e2f2e4; border: 1px solid rgba(255, 255, 255, .19); border-radius: 10px; padding: 8px; display: grid; place-items: center; }
.logout-button:hover { background: rgba(255, 255, 255, .18); }

.topbar { height: 53px; display: flex; align-items: center; justify-content: space-between; color: #fff; margin-bottom: 17px; padding: 0 3px; }
.ministry { font-size: 10px; line-height: 1.4; display: flex; align-items: center; gap: 13px; }
.ministry b { font-size: 22px; letter-spacing: -1px; border-right: 1px solid rgba(255, 255, 255, .4); padding-right: 13px; }
.topmeta { display: flex; gap: 14px; align-items: center; }
.date-label { font-size: 11px; color: #f1f8f3; text-transform: capitalize; }
.logout-top { display: none; background: rgba(255, 255, 255, .13); color: #fff; border: 1px solid rgba(255, 255, 255, .33); border-radius: 10px; padding: 8px; place-items: center; }

.hero { min-height: 212px; color: #fff; border-radius: 24px; padding: 32px 34px; margin-bottom: 22px; border: 1px solid rgba(255, 255, 255, .4); position: relative; overflow: hidden; isolation: isolate; background: #0b423b; box-shadow: 0 14px 35px rgba(11, 59, 42, .09); display: flex; align-items: center; justify-content: space-between; gap: 22px; }
.hero-image { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center 44%; z-index: 0; pointer-events: none; }
.hero::before { content: ''; position: absolute; inset: 0; z-index: 1; background: linear-gradient(90deg, rgba(3, 39, 44, .92) 0%, rgba(3, 39, 44, .52) 57%, rgba(3, 39, 44, .06) 100%); pointer-events: none; }
.hero > div { position: relative; z-index: 2; }
.eyebrow { font-size: 9px; font-weight: 650; letter-spacing: 2px; text-transform: uppercase; display: flex; gap: 8px; align-items: center; color: #d0f1d9; margin-bottom: 11px; }
.eyebrow::before { content: ''; width: 18px; height: 1px; background: #c0e9ab; }
.hero h1 { font-size: 36px; line-height: 1.13; letter-spacing: -1.3px; font-weight: 600; max-width: 650px; margin: 0 0 12px; }
.hero p { font-size: 12px; color: #e0efe7; max-width: 470px; margin: 0; line-height: 1.55; }
.hero-action { align-self: flex-end; display: grid; gap: 10px; text-align: right; }
.hero-action small { font-size: 10px; color: #e6f1e9; }

.footer { display: flex; justify-content: space-between; gap: 10px; color: #355c48; font-size: 9px; padding: 23px 3px 0; }

@media (min-width: 1600px) {
    .shell { gap: 35px; }
    .hero { min-height: 245px; }
    .hero h1 { font-size: 42px; }
}
@media (max-width: 1200px) {
    .shell { padding: 18px; grid-template-columns: 190px minmax(0, 1fr); gap: 20px; }
    .sidebar { padding: 22px 12px; height: calc(100vh - 36px); top: 18px; }
    .hero { padding: 27px; }
    .hero h1 { font-size: 32px; }
}
@media (max-width: 980px) {
    .shell { grid-template-columns: 78px minmax(0, 1fr); gap: 16px; }
    .sidebar { padding: 18px 10px; }
    .navlabel, .navtext, .side-note, .account-text { display: none; }
    .nav-link { justify-content: center; padding: 15px 10px; }
    .nav-link.active::after { display: none; }
    .side-bottom { padding: 17px 8px 3px; margin-top: auto; flex-direction: column; }
    .hero-action { display: none; }
    .hero h1 { font-size: 35px; }
    .hero { min-height: 200px; }
}
@media (max-width: 700px) {
    .shell { display: block; padding: 13px 13px 92px; }
    .sidebar { height: 66px; position: fixed; top: auto; bottom: 10px; left: 12px; right: 12px; z-index: 30; flex-direction: row; border-radius: 20px; padding: 7px; background: rgba(11, 73, 62, .92); box-shadow: 0 5px 35px rgba(7, 48, 39, .27); }
    .brand, .side-bottom { display: none; }
    .nav { display: flex; width: 100%; gap: 4px; }
    .nav-link { flex: 1; flex-direction: column; gap: 5px; padding: 6px 1px; font-size: 9px; }
    .navtext { display: block; text-align: center; }
    .topbar { height: 44px; margin-bottom: 12px; }
    .ministry { font-size: 8px; gap: 8px; }
    .ministry b { font-size: 20px; padding-right: 8px; }
    .date-label, .top-avatar { display: none; }
    .logout-top { display: grid; }
    .hero { border-radius: 20px; padding: 23px; min-height: 203px; margin-bottom: 18px; }
    .hero h1 { font-size: 28px; max-width: 360px; letter-spacing: -1px; }
    .hero p { font-size: 11px; max-width: 300px; }
    .eyebrow { font-size: 8px; letter-spacing: 1.2px; }
    .footer { display: block; font-size: 8px; line-height: 1.7; }
    .footer span { display: block; }
}
</style>
