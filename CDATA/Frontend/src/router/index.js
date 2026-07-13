import { createRouter, createWebHistory } from 'vue-router';

// Views
import Login from '../views/auth/Login.vue';
import Dashboard from '../views/admin/Dashboard.vue';
import Cruce from '../views/admin/Cruce.vue';
import Consulta from '../views/admin/Consulta.vue';

// Layouts
import MainLayout from '../components/layout/MainLayout.vue';

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
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: false }, // Disabling auth for now to preview
        children: [
            { path: 'dashboard', name: 'Dashboard', component: Dashboard },
            { path: 'cruce', name: 'Cruce', component: Cruce },
            { path: 'consulta', name: 'Consulta', component: Consulta },
            { path: '', redirect: '/dashboard' }
        ]
    }
];

const getRouterBase = () => {
    if (window.location.hostname === 'cdata.nubix.gt') {
        return '/';
    }
    return '/CDATA/';
};

const router = createRouter({
    history: createWebHistory(getRouterBase()),
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
