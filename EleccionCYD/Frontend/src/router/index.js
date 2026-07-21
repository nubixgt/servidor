import { createRouter, createWebHistory } from 'vue-router';

// Views
import Login from '../views/auth/Login.vue';
import ModelDirectory from '../views/admin/ModelDirectory.vue';
import LiveJudging from '../views/admin/LiveJudging.vue';
import Leaderboard from '../views/admin/Leaderboard.vue';

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
            { path: 'leaderboard', name: 'Leaderboard', component: Leaderboard },
        ]
    }
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
});

// Guard de autenticación: exige token para las rutas del panel, y evita volver al login si ya hay sesión.
router.beforeEach((to, from, next) => {
    const isAuthenticated = !!localStorage.getItem('token');

    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
    } else if (to.name === 'Login' && isAuthenticated) {
        next({ name: 'ModelDirectory' });
    } else {
        next();
    }
});

export default router;
