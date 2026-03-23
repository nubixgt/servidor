import { createRouter, createWebHistory } from 'vue-router';

// Views
import Login from '../views/Login.vue';

// Layouts
import Layout from '../components/Layout.vue';

// Admin Views
import AdminDashboard from '../views/admin/Dashboard.vue';
import AdminUsers from '../views/admin/Users.vue';
import AdminLocations from '../views/admin/Locations.vue';
import AdminNewTransaction from '../views/admin/NewTransaction.vue';
import AdminReports from '../views/admin/Reports.vue';

// Tech Views
import TechDashboard from '../views/tech/Dashboard.vue';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
    },
    {
        path: '/admin',
        component: Layout,
        children: [
            { path: '', name: 'AdminDashboard', component: AdminDashboard },
            { path: 'users', name: 'AdminUsers', component: AdminUsers },
            { path: 'locations', name: 'AdminLocations', component: AdminLocations },
            { path: 'new', name: 'AdminNewTransaction', component: AdminNewTransaction },
            { path: 'reports', name: 'AdminReports', component: AdminReports },
        ]
    },
    {
        path: '/tech',
        component: Layout,
        children: [
            { path: '', name: 'TechDashboard', component: TechDashboard },
            // Placeholder routes for tech
            { path: 'history', name: 'TechHistory', component: () => import('../views/Placeholder.vue') },
            { path: 'new-ingreso', name: 'TechNewIngreso', component: () => import('../views/Placeholder.vue') },
            { path: 'new-egreso', name: 'TechNewEgreso', component: () => import('../views/Placeholder.vue') },
        ]
    },
    { path: '/:pathMatch(.*)*', redirect: '/login' }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// Auth Guard Check
// Implement store logic as needed later
router.beforeEach((to, from, next) => {
    // For now, allow navigation since data will be hardcoded,
    // though in a real scenario we'd check auth state.
    next(); 
});

export default router;
