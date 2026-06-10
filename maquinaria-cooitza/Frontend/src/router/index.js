import { createRouter, createWebHistory } from 'vue-router';

// Views
import Login from '../views/auth/Login.vue';
import AdminDashboard from '../views/admin/AdminDashboard.vue';
import TecnicoDashboard from '../views/tecnico/TecnicoDashboard.vue';
import TecnicoPiloto from '../views/tecnico/TecnicoPiloto.vue';

const routes = [
    {
        path: '/',
        name: 'Home',
        component: () => import('../views/public/Home.vue'),
        meta: { requiresAuth: false }
    },
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { requiresAuth: false }
    },
    {
        path: '/admin',
        name: 'AdminDashboard',
        component: AdminDashboard,
        meta: { requiresAuth: false }
    },
    {
        path: '/tecnico',
        name: 'TecnicoDashboard',
        component: TecnicoDashboard,
        meta: { requiresAuth: false }
    },
    {
        path: '/piloto',
        name: 'TecnicoPiloto',
        component: TecnicoPiloto,
        meta: { requiresAuth: false }
    }
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
});

// Basic Guard Placeholder
router.beforeEach((to, from, next) => {
    // Implement Auth Check logic here
    const isAuthenticated = false; // Replace with store check

    if (to.meta.requiresAuth && !isAuthenticated) {
        // next('/login'); // Uncomment to enable auth guard
        next(); // Temporary allow all for template
    } else {
        next();
    }
});

export default router;
