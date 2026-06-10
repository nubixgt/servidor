import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

// Layouts
import MainLayout from '../components/layout/MainLayout.vue';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: () => import('../views/auth/Login.vue'),
        meta: { requiresAuth: false }
    },
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: '/dashboard' },
            { path: 'dashboard', name: 'Dashboard', component: () => import('../views/admin/Dashboard.vue') },
            { path: 'personnel', name: 'Personnel', component: () => import('../views/admin/Personnel.vue') },
            { path: 'vehicles', name: 'Vehicles', component: () => import('../views/admin/Vehicles.vue') },
            { path: 'machinery', name: 'Machinery', component: () => import('../views/admin/Machinery.vue') },
            { path: 'projects', name: 'Projects', component: () => import('../views/admin/Projects.vue') },
            { path: 'project-incomes', name: 'ProjectIncomes', component: () => import('../views/admin/ProjectIncomes.vue') },
            { path: 'clients', name: 'Clients', component: () => import('../views/admin/Clients.vue') },
            { path: 'finance', name: 'Finance', component: () => import('../views/admin/Finance.vue') },
            { path: 'recurrents', name: 'Recurrents', component: () => import('../views/admin/Recurrents.vue') },
            { path: 'inventory', name: 'Inventory', component: () => import('../views/admin/Inventory.vue') },
            { path: 'suppliers', name: 'Suppliers', component: () => import('../views/admin/Suppliers.vue') },

            { path: 'users', name: 'Users', component: () => import('../views/admin/Users.vue') },
            { path: 'tech-machinery', name: 'MachineryStatus', component: () => import('../views/tecnico/MachineryStatus.vue') },
            { path: 'tech-projects', name: 'TechProjects', component: () => import('../views/tecnico/TechProjects.vue') },
            { path: 'bank-conciliation', name: 'BankConciliation', component: () => import('../views/admin/BankConciliation.vue'), meta: { requiresRole: 'admin' } },
            { path: 'bitacora-mantenimiento', name: 'BitacoraMantenimiento', component: () => import('../views/admin/BitacoraMantenimiento.vue'), meta: { requiresRole: 'admin' } },
            { path: 'budgets-estimations', name: 'BudgetsEstimations', component: () => import('../views/admin/BudgetsEstimations.vue'), meta: { requiresRole: 'admin' } },
            { path: 'credits-accounts-payable', name: 'CreditsAccountsPayable', component: () => import('../views/admin/CreditsAccountsPayable.vue'), meta: { requiresRole: 'admin' } },
            { path: 'digital-documents', name: 'DigitalDocuments', component: () => import('../views/admin/DigitalDocuments.vue'), meta: { requiresRole: 'admin' } },
            { path: 'notifications-alerts', name: 'NotificationsAlerts', component: () => import('../views/admin/NotificationsAlerts.vue'), meta: { requiresRole: 'admin' } },
            { path: 'payroll-expenses', name: 'PayrollExpenses', component: () => import('../views/admin/PayrollExpenses.vue'), meta: { requiresRole: 'admin' } },
        ]
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/login'
    }
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
});

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    
    if (to.meta.requiresAuth && !authStore.userRole) {
        next('/login');
    } else if (to.path === '/login' && authStore.userRole) {
        if (authStore.userRole === 'tecnico') {
            next('/tech-machinery');
        } else {
            next('/dashboard');
        }
    } else {
        next();
    }
});

export default router;
