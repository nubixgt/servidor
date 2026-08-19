import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

// Layouts
import MainLayout from '../components/layout/MainLayout.vue';

const ALL_ROLES = ['tecnico', 'supervisor', 'administrador'];
const SUPERVISION_ROLES = ['supervisor', 'administrador'];

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: () => import('../views/auth/Login.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            { path: 'dashboard', name: 'Dashboard', component: () => import('../views/dashboard/Dashboard.vue'), meta: { roles: SUPERVISION_ROLES } },
            { path: 'mis-parcelas', name: 'MisParcelas', component: () => import('../views/tecnico/MisParcelas.vue'), meta: { roles: ['tecnico'] } },
            { path: 'parcelas', name: 'ParcelasList', component: () => import('../views/parcelas/ParcelasList.vue'), meta: { roles: SUPERVISION_ROLES } },
            { path: 'parcelas/nueva', name: 'ParcelaNueva', component: () => import('../views/parcelas/ParcelaForm.vue'), meta: { roles: ALL_ROLES } },
            { path: 'parcelas/:id/editar', name: 'ParcelaEditar', component: () => import('../views/parcelas/ParcelaForm.vue'), props: true, meta: { roles: ALL_ROLES } },
            { path: 'bioindicadores', name: 'Bioindicadores', component: () => import('../views/bioindicadores/Bioindicadores.vue'), meta: { roles: SUPERVISION_ROLES } },
            { path: 'reportes', name: 'Reportes', component: () => import('../views/reportes/Reportes.vue'), meta: { roles: SUPERVISION_ROLES } },
            { path: 'variables', name: 'Variables', component: () => import('../views/variables/Variables.vue'), meta: { roles: ALL_ROLES } },
            { path: 'equipo', name: 'Usuarios', component: () => import('../views/usuarios/Usuarios.vue'), meta: { roles: SUPERVISION_ROLES } },
            { path: 'configuracion', name: 'Configuracion', component: () => import('../views/configuracion/Configuracion.vue'), meta: { roles: ALL_ROLES } },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
});

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();
    await auth.ensureLoaded();

    if (to.meta.requiresAuth === false) {
        if (to.name === 'Login' && auth.isAuthenticated) {
            return next(auth.homeRoute);
        }
        return next();
    }

    if (!auth.isAuthenticated) {
        return next({ name: 'Login' });
    }

    if (to.meta.roles && !to.meta.roles.includes(auth.role)) {
        return next(auth.homeRoute);
    }

    if (to.path === '/') {
        return next(auth.homeRoute);
    }

    next();
});

export default router;
