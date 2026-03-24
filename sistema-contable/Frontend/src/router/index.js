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
import TechNewTransaction from '../views/tech/NewTransaction.vue';
import TechHistory from '../views/tech/History.vue';

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
            // Tech explicit routes
            { path: 'history', name: 'TechHistory', component: TechHistory },
            { path: 'new-ingreso', name: 'TechNewIngreso', component: TechNewTransaction },
            { path: 'new-egreso', name: 'TechNewEgreso', component: TechNewTransaction },
        ]
    },
    { path: '/:pathMatch(.*)*', redirect: '/login' }
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
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
