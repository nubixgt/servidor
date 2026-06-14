import { createRouter, createWebHistory } from 'vue-router';

// Views
import Login from '../views/auth/Login.vue';
import Dashboard from '../views/admin/Dashboard.vue';
import RegistrosList from '../views/admin/RegistrosList.vue';
import NuevoRegistro from '../views/admin/NuevoRegistro.vue';
import Catalogo from '../views/admin/Catalogo.vue';

// Layouts
import MainLayout from '../components/layout/MainLayout.vue';

const routes = [
    {
        path: '/',
        redirect: '/admin/dashboard'
    },
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { requiresAuth: false }
    },
    {
        path: '/admin',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: '/admin/dashboard' },
            { path: 'dashboard', name: 'Dashboard', component: Dashboard },
            { path: 'registros', name: 'Registros', component: RegistrosList },
            { path: 'nuevo-registro', name: 'NuevoRegistro', component: NuevoRegistro },
            { path: 'catalogo', name: 'Catalogo', component: Catalogo }
        ]
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
