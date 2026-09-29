import { createRouter, createWebHistory } from 'vue-router';

// El proyecto es una sola pantalla: m.nubix.gt/DañosACultivos abre el formulario.
// Las demás pantallas del template (Home, Login, Dashboard) quedaron sin ruta.
const routes = [
    { path: '/', name: 'DanosCultivos', component: () => import('../views/admin/DanosCultivos.vue') },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
});

export default router;
