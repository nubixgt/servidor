import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

import Login from '../views/auth/Login.vue';
import MainLayout from '../components/layout/MainLayout.vue';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { requiresAuth: false, title: 'Iniciar sesión' }
    },
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: { name: 'Beneficiarios' } },
            {
                path: 'beneficiarios',
                name: 'Beneficiarios',
                component: () => import('../views/beneficiarios/Listado.vue'),
                meta: { title: 'Beneficiarios' }
            },
            {
                path: 'usuarios',
                name: 'Usuarios',
                component: () => import('../views/usuarios/Usuarios.vue'),
                meta: { title: 'Usuarios', soloAdmin: true }
            },
        ]
    },
    { path: '/:pathMatch(.*)*', redirect: '/' }
];

const router = createRouter({
    // import.meta.env.BASE_URL = '/TestBases/' en producción
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
});

let sesionVerificada = false;

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    // Primera navegación: confirma con el servidor que el token guardado sigue valiendo
    if (!sesionVerificada) {
        sesionVerificada = true;
        if (auth.autenticado) await auth.verificar();
    }

    if (to.meta.requiresAuth && !auth.autenticado) {
        return { name: 'Login' };
    }
    if (to.meta.soloAdmin && !auth.esAdmin) {
        return { name: 'Beneficiarios' };
    }
    if (to.name === 'Login' && auth.autenticado) {
        return { name: 'Beneficiarios' };
    }
});

router.afterEach((to) => {
    document.title = to.meta.title ? `${to.meta.title} · Registro de Beneficiarios` : 'Registro de Beneficiarios';
});

export default router;
