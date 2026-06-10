import { createRouter, createWebHistory } from 'vue-router';

// Views
import Login from '../views/auth/Login.vue';
import Dashboard from '../views/admin/Dashboard.vue';

// Layouts
import MainLayout from '../components/layout/MainLayout.vue';

const routes = [
    {
        path: '/',
        component: () => import('../views/Home.vue'),
        meta: { requiresAuth: false },
        children: [
            { path: '', name: 'Sondeo', component: () => import('../views/public/SondeoFormScreen.vue') },
            { path: 'resultados', name: 'Resultados', component: () => import('../views/public/ResultsScreen.vue') }
        ]
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
            { path: 'dashboard', name: 'Dashboard', component: Dashboard },
        ]
    }
];

const router = createRouter({
    history: createWebHistory('/encuesta-baile/'),
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
