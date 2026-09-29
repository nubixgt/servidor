import { createRouter, createWebHistory } from 'vue-router';

// m.nubix.gt/DañosACultivos abre el formulario; /dashboard muestra los registros.
// Las demás pantallas del template (Home, Login) quedaron sin ruta.
const routes = [
    { path: '/', name: 'DanosCultivos', component: () => import('../views/admin/DanosCultivos.vue') },
    { path: '/dashboard', name: 'Dashboard', component: () => import('../views/admin/Dashboard.vue') },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
});

export default router;
