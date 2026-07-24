import { createRouter, createWebHistory } from 'vue-router';

// Views
import Login from '../views/auth/Login.vue';
import ModelDirectory from '../views/admin/ModelDirectory.vue';
import LiveJudging from '../views/admin/LiveJudging.vue';
import Leaderboard from '../views/admin/Leaderboard.vue';
import Dashboard from '../views/admin/Dashboard.vue';

// Layouts
import MainLayout from '../components/layout/MainLayout.vue';

const routes = [
    {
        path: '/',
        redirect: '/login'
    },
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { requiresAuth: false }
    },
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            { path: 'directory', name: 'ModelDirectory', component: ModelDirectory },
            { path: 'judging', name: 'LiveJudging', component: LiveJudging },
            { path: 'dashboard', name: 'Dashboard', component: Dashboard },
            { path: 'leaderboard', name: 'Leaderboard', component: Leaderboard, meta: { roles: ['admin'] } },
        ]
    }
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
});

// Guard de autenticación: exige token para las rutas del panel, evita volver al login si ya hay sesión,
// y restringe rutas cuyo meta.roles no incluya el rol del usuario logueado (ej. Tabla de Posiciones es solo admin).
router.beforeEach((to, from, next) => {
    const isAuthenticated = !!localStorage.getItem('token');

    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
    } else if (to.name === 'Login' && isAuthenticated) {
        next({ name: 'ModelDirectory' });
    } else if (to.meta.roles) {
        let usuario = null;
        try {
            usuario = JSON.parse(localStorage.getItem('usuario'));
        } catch {
            usuario = null;
        }

        if (!to.meta.roles.includes(usuario?.rol)) {
            next({ name: 'ModelDirectory' });
        } else {
            next();
        }
    } else {
        next();
    }
});

export default router;
