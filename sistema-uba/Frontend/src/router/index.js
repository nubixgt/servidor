import { createRouter, createWebHistory } from 'vue-router';

// Layouts
import MainLayout from '../components/layout/MainLayout.vue';
import PublicLayout from '../components/layout/PublicLayout.vue';

// Auth
import Login from '../views/auth/Login.vue';

// Public views
import PublicHome from '../views/public/Home.vue';
import ComoReportar from '../views/public/ComoReportar.vue';
import DenunciaForm from '../views/public/DenunciaForm.vue';
import DenunciaExito from '../views/public/DenunciaExito.vue';
import Informacion from '../views/public/Informacion.vue';
import Noticias from '../views/public/Noticias.vue';

// Admin views
import AdminDashboard from '../views/admin/Dashboard.vue';
import Denuncias from '../views/admin/Denuncias.vue';
import Servicios from '../views/admin/Servicios.vue';
import AdminNoticias from '../views/admin/Noticias.vue';
import Reportes from '../views/admin/Reportes.vue';

// Tech views
import TechDashboard from '../views/tech/Dashboard.vue';
import Bandeja from '../views/tech/Bandeja.vue';
import Caso from '../views/tech/Caso.vue';

const routes = [
    // Rutas públicas
    {
        path: '/',
        component: PublicLayout,
        children: [
            { path: '', name: 'Home', component: PublicHome },
            { path: 'como-reportar', name: 'ComoReportar', component: ComoReportar },
            { path: 'denuncia', name: 'DenunciaForm', component: DenunciaForm },
            { path: 'denuncia/exito', name: 'DenunciaExito', component: DenunciaExito },
            { path: 'informacion', name: 'Informacion', component: Informacion },
            { path: 'noticias', name: 'Noticias', component: Noticias },
        ]
    },
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { requiresAuth: false }
    },
    {
        path: '/admin',
        component: MainLayout,
        meta: { requiresAuth: true, role: 'admin' },
        children: [
            { path: '', redirect: 'dashboard' },
            { path: 'dashboard', name: 'AdminDashboard', component: AdminDashboard },
            { path: 'denuncias', name: 'Denuncias', component: Denuncias },
            { path: 'servicios', name: 'Servicios', component: Servicios },
            { path: 'noticias', name: 'AdminNoticias', component: AdminNoticias },
            { path: 'reportes', name: 'Reportes', component: Reportes },
        ]
    },
    {
        path: '/tech',
        component: MainLayout,
        meta: { requiresAuth: true, role: 'tech' },
        children: [
            { path: '', redirect: 'dashboard' },
            { path: 'dashboard', name: 'TechDashboard', component: TechDashboard },
            { path: 'bandeja', name: 'Bandeja', component: Bandeja },
            { path: 'caso/:id', name: 'Caso', component: Caso },
            { path: 'reportes', name: 'TechReportes', component: Reportes },
        ]
    }
];

const router = createRouter({
    history: createWebHistory('/sistema-uba/Frontend/dist/'),
    routes
});

// Basic Guard Placeholder
router.beforeEach((to, from, next) => {
    // Implement Auth Check logic here
    const isAuthenticated = false; // Replace with store check

    if (to.meta.requiresAuth && !isAuthenticated) {
        // next('/login'); // Uncomment to enable auth guard
        next(); // Temporary allow all for template
    } else {
        next();
    }
});

export default router;
