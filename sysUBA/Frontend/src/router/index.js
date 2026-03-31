import { createRouter, createWebHistory } from 'vue-router';

import Layout from '../components/Layout.vue';
import Login from '../views/auth/Login.vue';
import AdminDashboard from '../views/admin/AdminDashboard.vue';
import Denuncias from '../views/admin/Denuncias.vue';
import Servicios from '../views/admin/Servicios.vue';
import Noticias from '../views/admin/Noticias.vue';
import Reportes from '../views/admin/Reportes.vue';
import TechDashboard from '../views/tech/TechDashboard.vue';
import TechInbox from '../views/tech/TechInbox.vue';
import CaseDetail from '../views/tech/CaseDetail.vue';

const routes = [
    {
        path: '/',
        name: 'Login',
        component: Login
    },
    {
        path: '/',
        component: Layout,
        children: [
            // Admin Routes
            { path: 'admin/dashboard', name: 'AdminDashboard', component: AdminDashboard },
            { path: 'admin/denuncias', name: 'AdminDenuncias', component: Denuncias },
            { path: 'admin/servicios', name: 'AdminServicios', component: Servicios },
            { path: 'admin/noticias', name: 'AdminNoticias', component: Noticias },
            { path: 'admin/reportes', name: 'AdminReportes', component: Reportes },
            
            // Tech Routes
            { path: 'tech/dashboard', name: 'TechDashboard', component: TechDashboard },
            { path: 'tech/bandeja', name: 'TechInbox', component: TechInbox },
            { path: 'tech/caso/:id', name: 'CaseDetail', component: CaseDetail },
            { path: 'tech/reportes', name: 'TechReportes', component: Reportes }
        ]
    },
    { path: '/:pathMatch(.*)*', redirect: '/' }
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
});

export default router;
