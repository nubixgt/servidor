import { createRouter, createWebHistory } from 'vue-router';

// Views
import Login from '../views/auth/Login.vue';

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
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                redirect: '/admin/visar/dashboard'
            },
            {
                path: 'admin/dashboard',
                redirect: '/admin/visar/dashboard'
            },
            {
                path: 'admin/settings',
                name: 'MAGASettings',
                component: () => import('../views/admin/Settings.vue')
            },
            {
                path: 'admin/users',
                name: 'MAGAUsers',
                component: () => import('../views/admin/Users.vue')
            },
            {
                path: 'admin/audit',
                name: 'MAGAAudit',
                component: () => import('../views/admin/AuditLogs.vue')
            },
            // VISAR Module
            {
                path: 'admin/visar/dashboard',
                name: 'VisarDashboard',
                component: () => import('../views/admin/visar/Dashboard.vue')
            },
            {
                path: 'admin/visar/exportaciones',
                name: 'VisarExportaciones',
                component: () => import('../views/admin/visar/Exportaciones.vue')
            },
            {
                path: 'admin/visar/importaciones',
                name: 'VisarImportaciones',
                component: () => import('../views/admin/visar/Importaciones.vue')
            },
            {
                path: 'admin/visar/licencias-transporte',
                name: 'VisarLicenciasTransporte',
                component: () => import('../views/admin/visar/LicenciasTransporte.vue')
            },
            {
                path: 'admin/visar/licencias-fitosanitarias',
                name: 'VisarLicenciasFitosanitarias',
                component: () => import('../views/admin/visar/LicenciasFitosanitarias.vue')
            },
            {
                path: 'admin/visar/libre-venta',
                name: 'VisarLibreVenta',
                component: () => import('../views/admin/visar/LibreVenta.vue')
            }
        ]
    }
];

const getRouterBase = () => {
    if (window.location.hostname === 'maga.nubix.gt') {
        return '/';
    }
    return '/VisarSistema/';
};

const router = createRouter({
    history: createWebHistory(getRouterBase()),
    routes
});

// ─── Navigation Guard ──────────────────────────────────────────────────────
router.beforeEach((to, from, next) => {
    const isAuthenticated = localStorage.getItem('isAuthenticated') === 'true';

    if (!to.meta.requiresAuth) {
        if (isAuthenticated && to.path === '/login') {
            const token = localStorage.getItem('token');
            if (token && !isTokenExpired(token)) {
                return next('/admin/visar/dashboard');
            }
        }
        return next();
    }

    if (!isAuthenticated) {
        return next('/login');
    }

    const token = localStorage.getItem('token');
    if (!token || isTokenExpired(token)) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('isAuthenticated');
        return next({ path: '/login', query: { expired: '1' } });
    }

    next();
});

function isTokenExpired(token) {
    try {
        const parts = token.split('.');
        if (parts.length === 3) {
            const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
            return payload.exp && payload.exp < Math.floor(Date.now() / 1000);
        }
    } catch (_) {}
    return false;
}

export default router;

