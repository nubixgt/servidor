import { createRouter, createWebHistory } from 'vue-router';

// Views
import Login from '../views/auth/Login.vue';
import Dashboard from '../views/admin/Dashboard.vue';
import Usuarios from '../views/admin/Usuarios.vue';
import Clientes from '../views/admin/Clientes.vue';
import Ventas from '../views/admin/Ventas.vue';
import Catalogo from '../views/admin/Catalogo.vue';
import Pagos from '../views/admin/Pagos.vue';

// Layouts
import MainLayout from '../components/layout/MainLayout.vue';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { requiresAuth: false }
    },
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true }, // Re-enabled for live auth
        redirect: '/dashboard',
        children: [
            { path: 'dashboard', name: 'Dashboard', component: Dashboard },
            { path: 'usuarios', name: 'Usuarios', component: Usuarios },
            { path: 'clientes', name: 'Clientes', component: Clientes },
            { path: 'ventas', name: 'Ventas', component: Ventas },
            { path: 'catalogo', name: 'Catalogo', component: Catalogo },
            { path: 'pagos', name: 'Pagos', component: Pagos }
        ]
    }
];

const router = createRouter({
    history: createWebHistory('/emagro/'),
    routes
});

// Basic Guard Placeholder
router.beforeEach((to, from, next) => {
    // Implement Auth Check logic here
    const isAuthenticated = !!localStorage.getItem('token');

    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login'); 
    } else {
        next();
    }
});

export default router;
