import { createRouter, createWebHistory } from 'vue-router';

// Views
import Login from '../views/auth/Login.vue';
import Home from '../views/public/Home.vue';

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
                name: 'Home',
                component: Home
            },
            {
                path: 'admin/dashboard',
                name: 'AdminDashboard',
                component: Home
            },
            {
                path: 'admin/productores',
                name: 'Productores',
                component: () => import('../views/admin/Productores.vue')
            },
            {
                path: 'admin/sanidad',
                name: 'Sanidad',
                component: () => import('../views/admin/Sanidad.vue')
            },
            {
                path: 'admin/licencias',
                name: 'Licencias',
                component: () => import('../views/admin/Licencias.vue')
            },
            {
                path: 'admin/extension',
                name: 'Extension',
                component: () => import('../views/admin/Extension.vue')
            },
            {
                path: 'admin/seguridad-alimentaria',
                name: 'SeguridadAlimentaria',
                component: () => import('../views/admin/SeguridadAlimentaria.vue')
            },
            {
                path: 'admin/actividades-despacho',
                name: 'ActividadesDespacho',
                component: () => import('../views/admin/ActividadesDespacho.vue')
            },
            {
                path: 'admin/votaciones',
                name: 'Votaciones',
                component: () => import('../views/admin/Votaciones.vue')
            },
            {
                path: 'admin/presupuesto',
                name: 'Presupuesto',
                component: () => import('../views/admin/Presupuesto.vue')
            },
            {
                path: 'admin/reports',
                name: 'MAGAReports',
                component: () => import('../views/admin/Reports.vue')
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
            // VISAN Module
            {
                path: 'admin/visan/dashboard',
                name: 'VisanDashboard',
                component: () => import('../views/admin/visan/Dashboard.vue')
            },
            {
                path: 'admin/visan/dapca',
                name: 'VisanDapca',
                component: () => import('../views/admin/visan/Dapca.vue')
            },
            {
                path: 'admin/visan/tabla',
                name: 'VisanTabla',
                component: () => import('../views/admin/visan/Tabla.vue')
            },
            {
                path: 'admin/visan/editar',
                name: 'VisanEditar',
                component: () => import('../views/admin/visan/Editar.vue')
            },
            // VIDER Module
            {
                path: 'admin/vider/dashboard',
                name: 'ViderDashboard',
                component: () => import('../views/admin/vider/Dashboard.vue')
            },
            {
                path: 'admin/vider/tabla',
                name: 'ViderTabla',
                component: () => import('../views/admin/vider/Tabla.vue')
            },
            {
                path: 'admin/vider/tobanik',
                name: 'ViderTobanik',
                component: () => import('../views/admin/vider/Tobanik.vue')
            },
            {
                path: 'admin/vider/importar',
                name: 'ViderImportar',
                component: () => import('../views/admin/vider/Importar.vue')
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
            },
            // Climatologia Module
            {
                path: 'admin/clima/dashboard',
                name: 'ClimaDashboard',
                component: () => import('../views/admin/clima/ClimaDashboard.vue')
            },
            {
                path: 'admin/clima/mapa',
                name: 'ClimaMapa',
                component: () => import('../views/admin/clima/MapaRegistros.vue')
            },
            {
                path: 'admin/clima/registros',
                name: 'ClimaRegistros',
                component: () => import('../views/admin/clima/GestionRegistros.vue')
            },
            {
                path: 'admin/clima/alertas',
                name: 'ClimaAlertas',
                component: () => import('../views/admin/clima/GestionAlertas.vue')
            }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
});

// ─── Navigation Guard ──────────────────────────────────────────────────────
// Valida autenticación Y expiración del token en cada cambio de ruta
router.beforeEach((to, from, next) => {
    // ⚠️ MODO DESARROLLO: Login deshabilitado, acceso automático como Admin
    localStorage.setItem('isAuthenticated', 'true');
    localStorage.setItem('token', 'dev.bypass.token');
    localStorage.setItem('user', JSON.stringify({
        id: 1,
        nombre: 'Administrador (Dev)',
        email: 'admin@maga.gob.gt',
        rol: 'admin'
    }));

    if (to.path === '/login') {
        return next('/admin/dashboard');
    }
    
    return next();
});

// Helper: retorna true si el token JWT ya venció
function isTokenExpired(token) {
    try {
        const parts = token.split('.');
        if (parts.length === 3) {
            const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
            return payload.exp && payload.exp < Math.floor(Date.now() / 1000);
        }
    } catch (_) { /* token mal formado */ }
    return false;
}

export default router;

