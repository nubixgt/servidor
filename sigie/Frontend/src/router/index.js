import { createRouter, createWebHistory } from 'vue-router';
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
            }
        ]
    },
    { path: '/:pathMatch(.*)*', redirect: '/login' }
];

const router = createRouter({
    base: import.meta.env.BASE_URL,
    history: createWebHistory(import.meta.env.BASE_URL),
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
