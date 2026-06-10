import { createRouter, createWebHistory } from 'vue-router';

// Views
import Login from '../views/auth/Login.vue';
import Dashboard from '../views/admin/Dashboard.vue';

// Layouts
import MainLayout from '../components/layout/MainLayout.vue';

const routes = [
    {
        path: '/',
        redirect: '/dashboard'
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
            { path: 'clients', name: 'Clients', component: () => import('../views/admin/Clients.vue') },
            { path: 'referidos', name: 'Referidos', component: () => import('../views/admin/Referidos.vue') },
            { path: 'loans', name: 'Loans', component: () => import('../views/admin/Loans.vue') },
            { path: 'investors', name: 'Investors', component: () => import('../views/admin/Investors.vue') },
            { path: 'payments', name: 'Payments', component: () => import('../views/admin/Payments.vue') },
            { path: 'portfolio', name: 'Portfolio', component: () => import('../views/admin/Portfolio.vue') },
        ]
    }
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
});

router.beforeEach((to, from, next) => {
    const isAuthenticated = !!localStorage.getItem('token');

    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
    } else if (to.path === '/login' && isAuthenticated) {
        next('/dashboard');
    } else {
        next();
    }
});

export default router;
