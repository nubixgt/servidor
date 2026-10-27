import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

// Views
import Login from '../views/auth/Login.vue';

// Layouts
import MainLayout from '../components/layout/MainLayout.vue';

const inicio = () => (useAuthStore().puede('dashboard.ver') ? '/dashboard' : '/beneficiarios');

/**
 * permiso: permiso (o lista; basta uno) para ver la pantalla.
 * datos:   la pantalla necesita el cubo del tablero (se muestra "Cargando…" hasta tenerlo).
 */
const vista = (path, archivo, permiso, datos = false) => ({
    path,
    name: path,
    component: () => import(`../views/admin/${archivo}.vue`),
    meta: { permiso, datos }
});

const routes = [
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { requiresAuth: false }
    },
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            { path: '', name: 'inicio', redirect: inicio },
            vista('dashboard', 'Dashboard', 'dashboard.ver', true),
            vista('territorio', 'Territorio', 'dashboard.ver', true),
            vista('alertas', 'Alertas', 'alertas.ver', true),
            vista('beneficiarios', 'Beneficiarios', 'beneficiarios.ver'),
            vista('solicitudes', 'Solicitudes', 'dashboard.ver', true),
            vista('visitas', 'Visitas', 'dashboard.ver', true),
            vista('resoluciones', 'Resoluciones', 'dashboard.ver', true),
            vista('nomina', 'Nomina', 'dashboard.ver', true),
            vista('estados', 'Estados', 'dashboard.ver', true),
            vista('calidad', 'Calidad', 'calidad.ver', true),
            vista('reportes', 'Reportes', 'reportes.ver'),
            vista('importaciones', 'Importaciones', 'importacion.ejecutar'),
            vista('admin', 'Administracion', ['usuarios.gestionar', 'roles.gestionar', 'auditoria.ver', 'catalogos.gestionar']),
        ]
    },
    { path: '/:pathMatch(.*)*', redirect: '/' }
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();
    // al recargar la página se intenta restaurar la sesión con la cookie de renovación
    if (!auth.intentada) await auth.reanudar();

    if (to.name === 'login') {
        return auth.autenticado && !auth.usuario.debe_cambiar_clave ? inicio() : true;
    }
    if (to.matched.some((r) => r.meta.requiresAuth)) {
        if (!auth.autenticado) {
            // la raíz redirige a la pantalla de inicio: no se guarda como destino
            const desde = to.redirectedFrom ? to.redirectedFrom.fullPath : to.fullPath;
            return { name: 'login', query: desde === '/' ? {} : { redirect: desde } };
        }
        if (auth.usuario.debe_cambiar_clave) return { name: 'login' };
        if (to.meta.permiso && !auth.puedeVer(to.meta.permiso)) return inicio();
    }
    return true;
});

export default router;
