import { createRouter, createWebHistory } from 'vue-router';
import { useAppStore } from '../stores/app.js';

// ──── Layouts ────────────────────────────────────────────────────────────────
import MainLayout from '../components/layout/MainLayout.vue';

// ──── Vistas públicas / auth ──────────────────────────────────────────────────
import Home  from '../views/public/Home.vue';
import Login from '../views/auth/Login.vue';

// ──── Vistas admin ────────────────────────────────────────────────────────────
import Dashboard     from '../views/admin/Dashboard.vue';
import CrearCurso    from '../views/admin/CrearCurso.vue';
import MisCursos     from '../views/admin/MisCursos.vue';
import Catalogo      from '../views/admin/Catalogo.vue';
import DetalleCurso  from '../views/admin/DetalleCurso.vue';
import DetalleCursoReal from '../views/admin/DetalleCursoReal.vue';
import Rutas         from '../views/admin/Rutas.vue';
import Calendario    from '../views/admin/Calendario.vue';
import Novedades     from '../views/admin/Novedades.vue';
import Insignias     from '../views/admin/Insignias.vue';
import Certificados  from '../views/admin/Certificados.vue';
import Foros         from '../views/admin/Foros.vue';
import Ayuda         from '../views/admin/Ayuda.vue';
import Perfil        from '../views/admin/Perfil.vue';
import Configuracion from '../views/admin/Configuracion.vue';

const routes = [
    // ── Raíz ────────────────────────────────────────────────────────────────
    {
        path: '/',
        name: 'Home',
        component: Home,
        meta: { requiresAuth: false }
    },

    // ── Autenticación ────────────────────────────────────────────────────────
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { requiresAuth: false }
    },

    // ── Panel Admin (protegido, usa MainLayout) ──────────────────────────────
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: 'dashboard',
                name: 'AdminDashboard',
                component: Dashboard,
                meta: { title: 'Inicio · AgroIA' }
            },
            {
                path: 'miscursos',
                name: 'AdminMisCursos',
                component: MisCursos,
                meta: { title: 'Mis Cursos · AgroIA' }
            },
            {
                path: 'catalogo',
                name: 'AdminCatalogo',
                component: Catalogo,
                meta: { title: 'Catálogo de Cursos · AgroIA' }
            },
            {
                path: 'catalogo/:id',
                name: 'AdminDetalleCurso',
                component: DetalleCurso,
                meta: { title: 'Detalle del Curso · AgroIA' }
            },
            {
                // Curso real del Backend (GET /cursos/{id}) — separado de
                // /catalogo/:id (catálogo de ejemplo local) porque los ids
                // autoincrementales de la base de datos podrían coincidir
                // por casualidad con los del catálogo mock.
                path: 'curso/:id',
                name: 'AdminDetalleCursoReal',
                component: DetalleCursoReal,
                meta: { title: 'Detalle del Curso · AgroIA' }
            },
            {
                path: 'rutas',
                name: 'AdminRutas',
                component: Rutas,
                meta: { title: 'Rutas de Aprendizaje · AgroIA' }
            },
            {
                path: 'calendario',
                name: 'AdminCalendario',
                component: Calendario,
                meta: { title: 'Calendario · AgroIA' }
            },
            {
                path: 'novedades',
                name: 'AdminNovedades',
                component: Novedades,
                meta: { title: 'Novedades · AgroIA' }
            },
            {
                path: 'insignias',
                name: 'AdminInsignias',
                component: Insignias,
                meta: { title: 'Mis Insignias · AgroIA' }
            },
            {
                path: 'certificados',
                name: 'AdminCertificados',
                component: Certificados,
                meta: { title: 'Mis Certificados · AgroIA' }
            },
            {
                path: 'foros',
                name: 'AdminForos',
                component: Foros,
                meta: { title: 'Foros · AgroIA' }
            },
            {
                path: 'ayuda',
                name: 'AdminAyuda',
                component: Ayuda,
                meta: { title: 'Ayuda · AgroIA' }
            },
            {
                path: 'perfil',
                name: 'AdminPerfil',
                component: Perfil,
                meta: { title: 'Mi Perfil · AgroIA' }
            },
            {
                path: 'configuracion',
                name: 'AdminConfiguracion',
                component: Configuracion,
                meta: { title: 'Configuración · AgroIA' }
            },
            {
                path: 'crear-curso',
                name: 'AdminCrearCurso',
                component: CrearCurso,
                // Backend/src/Controllers/CursoController.php -> POST /cursos
                // exige rol Administrador; el guard de abajo hace lo mismo en la web.
                meta: { title: 'Crear curso · AgroIA', requiresAdmin: true }
            }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
    scrollBehavior() {
        return { top: 0 };
    }
});

// ── Guard de autenticación ────────────────────────────────────────────────────
router.beforeEach((to, from, next) => {
    // Usamos el store de Pinia para verificar el estado de autenticación
    // NOTA: useAppStore() funciona fuera de setup() solo si Pinia ya está instalado
    // En el beforeEach se llama después de que la app está montada, por lo que funciona.
    const store = useAppStore();

    if (to.meta.requiresAuth && !store.estaLogueado) {
        next('/login');
    } else if (to.meta.requiresAdmin && store.usuario?.rol !== 'Administrador') {
        next('/dashboard');
    } else if ((to.name === 'Login' || to.name === 'Home') && store.estaLogueado) {
        next('/dashboard');
    } else {
        next();
    }
});

// ── Actualizar título de página ───────────────────────────────────────────────
router.afterEach((to) => {
    document.title = to.meta.title || 'Aula Virtual · MAGA AgroIA';
});

export default router;
