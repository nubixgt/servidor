import { createRouter, createWebHashHistory } from 'vue-router';
import { useAuthStore } from '../stores/authStore';

// Layouts
import MainLayout from '../components/layout/MainLayout.vue';

// Auth
import Login from '../views/auth/Login.vue';

// Portal Views
import Dashboard from '../views/portal/Dashboard.vue';
import CheckinRegister from '../views/portal/checkin/CheckinRegister.vue';
import CheckinList from '../views/portal/checkin/CheckinList.vue';
import SacrificioRegister from '../views/portal/sacrificio/SacrificioRegister.vue';
import SacrificioList from '../views/portal/sacrificio/SacrificioList.vue';
import DesviacionRegister from '../views/portal/desviaciones/DesviacionRegister.vue';
import DesviacionList from '../views/portal/desviaciones/DesviacionList.vue';
import DesviacionDetails from '../views/portal/desviaciones/DesviacionDetails.vue';

// Supervisiones
import SupervisionRegister from '../views/portal/supervisiones/SupervisionRegister.vue';
import SupervisionList from '../views/portal/supervisiones/SupervisionList.vue';
import SupervisionDetails from '../views/portal/supervisiones/SupervisionDetails.vue';
import SupervisionPrint from '../views/portal/supervisiones/SupervisionPrint.vue';

// No Conformidades
import NoConformidadRegister from '../views/portal/noconformidades/NoConformidadRegister.vue';
import NoConformidadList from '../views/portal/noconformidades/NoConformidadList.vue';
import NoConformidadDetails from '../views/portal/noconformidades/NoConformidadDetails.vue';
import NoConformidadPrint from '../views/portal/noconformidades/NoConformidadPrint.vue';

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
            { path: '', redirect: '/dashboard' },
            { 
                path: 'dashboard', 
                name: 'Dashboard', 
                component: Dashboard, 
                meta: { roles: ['administrador', 'inspector'] } 
            },
            { 
                path: 'checkin', 
                name: 'CheckinRegister', 
                component: CheckinRegister, 
                meta: { roles: ['inspector'] } 
            },
            { 
                path: 'checkin-list', 
                name: 'CheckinList', 
                component: CheckinList, 
                meta: { roles: ['administrador'] } 
            },
            {
                path: 'sacrificio/nuevo',
                name: 'SacrificioRegister',
                component: SacrificioRegister,
                meta: { roles: ['inspector', 'administrador'] }
            },
            {
                path: 'sacrificios',
                name: 'SacrificioList',
                component: SacrificioList,
                meta: { roles: ['administrador'] }
            },
            {
                path: 'desviaciones/nuevo',
                name: 'DesviacionRegister',
                component: DesviacionRegister,
                meta: { roles: ['inspector'] }
            },
            {
                path: 'desviaciones',
                name: 'DesviacionList',
                component: DesviacionList,
                meta: { roles: ['inspector', 'administrador'] }
            },
            {
                path: 'desviaciones/:id',
                name: 'DesviacionDetails',
                component: DesviacionDetails,
                meta: { roles: ['inspector', 'administrador'] }
            },
            // Supervisiones
            {
                path: 'supervisiones/nuevo',
                name: 'SupervisionRegister',
                component: SupervisionRegister,
                meta: { roles: ['inspector'] }
            },
            {
                path: 'supervisiones',
                name: 'SupervisionList',
                component: SupervisionList,
                meta: { roles: ['inspector', 'administrador'] }
            },
            {
                path: 'supervisiones/:id',
                name: 'SupervisionDetails',
                component: SupervisionDetails,
                meta: { roles: ['inspector', 'administrador'] }
            },
            {
                path: 'supervisiones/:id/imprimir',
                name: 'SupervisionPrint',
                component: SupervisionPrint,
                meta: { roles: ['inspector', 'administrador'] }
            },
            // No Conformidades
            {
                path: 'noconformidades/nuevo',
                name: 'NoConformidadRegister',
                component: NoConformidadRegister,
                meta: { roles: ['inspector'] }
            },
            {
                path: 'noconformidades',
                name: 'NoConformidadList',
                component: NoConformidadList,
                meta: { roles: ['inspector', 'administrador'] }
            },
            {
                path: 'noconformidades/:id',
                name: 'NoConformidadDetails',
                component: NoConformidadDetails,
                meta: { roles: ['inspector', 'administrador'] }
            },
            {
                path: 'noconformidades/:id/imprimir',
                name: 'NoConformidadPrint',
                component: NoConformidadPrint,
                meta: { roles: ['inspector', 'administrador'] }
            }
        ]
    },
    { path: '/:pathMatch(.*)*', redirect: '/login' }
];

const router = createRouter({
    history: createWebHashHistory(),
    routes
});

// Route Guard Logic
router.beforeEach((to, from, next) => {
    const auth = useAuthStore();
    
    // Si la ruta requiere autenticación y el usuario no está logueado
    if (to.meta.requiresAuth && !auth.role) {
        return next('/login');
    }
    
    // Si intenta ir a login estando ya autenticado
    if (to.path === '/login' && auth.role) {
        return next('/dashboard');
    }

    // Validar acceso según el rol
    if (to.meta.roles && !to.meta.roles.includes(auth.role)) {
        return next('/dashboard');
    }
    
    next();
});

export default router;
