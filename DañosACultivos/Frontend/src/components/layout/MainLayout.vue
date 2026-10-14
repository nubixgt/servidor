<template>
    <div class="min-h-screen">
        <div class="app-bg" aria-hidden="true"></div>

        <!-- Sidebar (escritorio ancho) -->
        <aside class="hidden xl:flex fixed left-4 top-4 bottom-4 w-[230px] z-40 flex-col gap-4">
            <div class="px-1">
                <img :src="logo" alt="Daños a Cultivos" class="w-full h-auto" />
            </div>
            <nav class="side flex-1 p-3 space-y-2" aria-label="Navegación principal">
                <router-link v-for="i in items" :key="i.to" :to="i.to" class="side-link" active-class="" exact-active-class="side-link-active">
                    <component :is="i.icon" class="w-6 h-6" />{{ i.label }}
                </router-link>
            </nav>
            <div class="side p-3 flex items-center gap-3 text-white">
                <div class="min-w-0 flex-1 leading-tight">
                    <p class="font-semibold truncate">{{ user?.usuario }}</p>
                    <p class="text-[12px] text-white/70 capitalize">{{ user?.rol }}</p>
                </div>
                <button class="p-2 rounded-xl hover:bg-white/15" title="Cerrar sesión" aria-label="Cerrar sesión" @click="salir"><ArrowRightOnRectangleIcon class="w-5 h-5" /></button>
            </div>
        </aside>

        <!-- Barra superior (pantallas menores) -->
        <header class="xl:hidden px-3.5 sm:px-6 pt-4">
            <div class="glass flex items-center gap-3 px-3 py-2" style="border-radius: 999px;">
                <img :src="logo" alt="Daños a Cultivos" class="h-11 w-auto flex-shrink-0" />
                <p class="font-extrabold hidden sm:block" style="color: var(--green-dark);">Daños a Cultivos</p>
                <nav class="ml-auto flex gap-1" aria-label="Navegación principal">
                    <router-link v-for="i in items" :key="i.to" :to="i.to" class="top-link" exact-active-class="top-link-active">
                        <component :is="i.icon" class="w-5 h-5" />{{ i.label }}
                    </router-link>
                    <button class="top-link" aria-label="Cerrar sesión" @click="salir"><ArrowRightOnRectangleIcon class="w-5 h-5" /></button>
                </nav>
            </div>
        </header>

        <main class="xl:pl-[262px]">
            <router-view />
        </main>
    </div>
</template>

<script setup>
import logo from '../../assets/images/DC sin fondo.png';
import { useRouter } from 'vue-router';
import { Squares2X2Icon, DocumentTextIcon, PencilSquareIcon, UsersIcon, ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline';
import authService from '../../services/authService';
import { confirmDialog, toastSuccess } from '../../utils/alerts';

const salir = async () => {
    if (!(await confirmDialog('Se cerrará tu sesión actual.', { title: '¿Cerrar sesión?', confirmText: 'Cerrar sesión' }))) return;
    authService.logout();
    await router.replace({ name: 'Login' });
    toastSuccess('Sesión cerrada correctamente');
};

const router = useRouter();
const user = authService.user();

const todosItems = [
    { to: '/', label: 'Dashboard', icon: Squares2X2Icon },
    { to: '/registros/crear', label: 'Crear Registros', icon: PencilSquareIcon },
    { to: '/registros', label: 'Registros', icon: DocumentTextIcon },
    { to: '/usuarios', label: 'Usuarios', icon: UsersIcon, roles: ['admin'] },
];
const items = todosItems.filter((i) => !i.roles || i.roles.includes(user?.rol));
</script>

<style scoped>
.side {
    background: linear-gradient(160deg, rgba(10, 57, 55, .91), rgba(13, 66, 51, .81));
    border: 1px solid rgba(255, 255, 255, .46);
    border-radius: 26px;
    -webkit-backdrop-filter: blur(25px);
    backdrop-filter: blur(25px);
    box-shadow: 0 14px 40px rgba(6, 39, 26, .15);
}
.side-link {
    display: flex; align-items: center; gap: 12px; padding: 14px;
    border-radius: 13px; color: #d2e7e0; font-size: 14px; transition: background .18s;
}
.side-link:hover { background: rgba(255, 255, 255, .08); }
.side-link-active { background: linear-gradient(130deg, #01975b, #007a57); color: #fff; box-shadow: inset 0 1px rgba(255, 255, 255, .25), 0 5px 20px rgba(0, 45, 41, .15); }
.side-link-active::after { content: ''; width: 5px; height: 5px; background: #a6eeba; border-radius: 50%; margin-left: auto; }
.top-link {
    display: flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 999px;
    font-size: 13px; font-weight: 600; color: var(--navy);
}
.top-link-active { background: linear-gradient(130deg, #01975b, #007a57); color: #fff; }
</style>
