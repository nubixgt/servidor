import { createRouter, createWebHistory } from 'vue-router';
import MainLayout from '../components/layout/MainLayout.vue';
import authService from '../services/authService';

// /login es pública; el resto (dashboard y /registros) exige sesión y comparte el layout con sidebar.
const routes = [
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            { path: '', name: 'Dashboard', component: () => import('../views/admin/Dashboard.vue') },
            { path: 'registros', name: 'DanosCultivos', component: () => import('../views/admin/DanosCultivos.vue') },
        ],
    },
    { path: '/login', name: 'Login', component: () => import('../views/auth/Login.vue') },
    { path: '/dashboard', redirect: '/' },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
});

router.beforeEach((to) => {
    const autenticado = authService.isAuthenticated();
    if (to.meta.requiresAuth && !autenticado) return { name: 'Login' };
    if (to.name === 'Login' && autenticado) return { name: 'Dashboard' };
});

export default router;
