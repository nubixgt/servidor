import { createRouter, createWebHistory } from 'vue-router';
import MainLayout from '../components/layout/MainLayout.vue';

// m.nubix.gt/DañosACultivos abre el dashboard; /registros es el formulario.
// Ambas pantallas comparten el layout con sidebar.
const routes = [
    {
        path: '/',
        component: MainLayout,
        children: [
            { path: '', name: 'Dashboard', component: () => import('../views/admin/Dashboard.vue') },
            { path: 'registros', name: 'DanosCultivos', component: () => import('../views/admin/DanosCultivos.vue') },
        ],
    },
    { path: '/dashboard', redirect: '/' },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
});

export default router;
