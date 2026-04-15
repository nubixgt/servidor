import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/authStore';

// Layouts
import MainLayout from '../components/layout/MainLayout.vue';

// Auth
import Login from '../views/auth/Login.vue';

// Admin Views
import ExecutiveDashboard from '../views/portal/admin/ExecutiveDashboard.vue';
import UsuariosView from '../views/portal/admin/UsuariosView.vue';
import SystemConfigView from '../views/portal/admin/SystemConfigView.vue';

// Tecnico Views
import ResponsableDashboard from '../views/portal/tecnico/ResponsableDashboard.vue';

// Modulos Views
import IniciativasView from '../views/portal/modulos/IniciativasView.vue';
import CitacionesView from '../views/portal/modulos/CitacionesView.vue';
import ComisionesView from '../views/portal/modulos/ComisionesView.vue';
import FiscalizacionView from '../views/portal/modulos/FiscalizacionView.vue';
import CompromisosView from '../views/portal/modulos/CompromisosView.vue';
import ActividadesView from '../views/portal/modulos/ActividadesView.vue';
import RedesView from '../views/portal/modulos/RedesView.vue';
import AfiliacionesView from '../views/portal/modulos/AfiliacionesView.vue';

// Herramientas Views
import KanbanView from '../views/portal/herramientas/KanbanView.vue';
import CalendarView from '../views/portal/herramientas/CalendarView.vue';
import ArchivoView from '../views/portal/herramientas/ArchivoView.vue';

const routes = [
    {
        path: '/',
        redirect: '/login'
    },
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { requiresAuth: false }
    },
    {
        path: '/portal',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            // Admin Routes
            { path: 'admin/dashboard', name: 'ExecutiveDashboard', component: ExecutiveDashboard, meta: { roles: ['administrador'] } },
            { path: 'admin/usuarios', name: 'Usuarios', component: UsuariosView, meta: { roles: ['administrador'] } },
            { path: 'admin/configuracion', name: 'Configuracion', component: SystemConfigView, meta: { roles: ['administrador'] } },
            
            // Tecnico Routes
            { path: 'tecnico/dashboard', name: 'ResponsableDashboard', component: ResponsableDashboard, meta: { roles: ['tecnico'] } },
            
            // Modules Routes (Shared, but can be restricted by category further)
            { path: 'modulos/iniciativas', name: 'Iniciativas', component: IniciativasView, meta: { roles: ['administrador', 'tecnico'], category: 'iniciativas' } },
            { path: 'modulos/citaciones', name: 'Citaciones', component: CitacionesView, meta: { roles: ['administrador', 'tecnico'], category: 'citaciones' } },
            { path: 'modulos/comisiones', name: 'Comisiones', component: ComisionesView, meta: { roles: ['administrador', 'tecnico'], category: 'comisiones' } },
            { path: 'modulos/fiscalizacion', name: 'Fiscalizacion', component: FiscalizacionView, meta: { roles: ['administrador', 'tecnico'], category: 'fiscalizacion' } },
            { path: 'modulos/compromisos', name: 'Compromisos', component: CompromisosView, meta: { roles: ['administrador', 'tecnico'], category: 'compromisos' } },
            { path: 'modulos/actividades', name: 'Actividades', component: ActividadesView, meta: { roles: ['administrador', 'tecnico'], category: 'actividades' } },
            { path: 'modulos/redes', name: 'Redes', component: RedesView, meta: { roles: ['administrador', 'tecnico'], category: 'redes' } },
            { path: 'modulos/afiliaciones', name: 'Afiliaciones', component: AfiliacionesView, meta: { roles: ['administrador', 'tecnico'], category: 'afiliaciones' } },
            
            // Shared Tools
            { path: 'herramientas/kanban', name: 'Kanban', component: KanbanView, meta: { roles: ['administrador', 'tecnico'] } },
            { path: 'herramientas/calendario', name: 'Calendario', component: CalendarView, meta: { roles: ['administrador', 'tecnico'] } },
            { path: 'herramientas/archivo', name: 'Archivo', component: ArchivoView, meta: { roles: ['administrador', 'tecnico'] } },
        ]
    }
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
});

// Route Guard Logic
router.beforeEach((to, from, next) => {
    const auth = useAuthStore();
    
    // Si la ruta requiere autenticación y el usuario no tiene rol
    if (to.meta.requiresAuth && !auth.role) {
        return next('/login');
    }
    
    // Si intenta ir a login estando ya autenticado
    if (to.path === '/login' && auth.role) {
        const redirectPath = auth.role === 'administrador' ? '/portal/admin/dashboard' : '/portal/tecnico/dashboard';
        return next(redirectPath);
    }
    
    // Validar acceso si la ruta pertenece a children (tiene roles definidos)
    if (to.meta.roles && !to.meta.roles.includes(auth.role)) {
        // Redirigir según el rol real del usuario
        const redirectPath = auth.role === 'administrador' ? '/portal/admin/dashboard' : '/portal/tecnico/dashboard';
        return next(redirectPath);
    }

    // Validar acceso de técnicos a módulos específicos
    if (auth.role === 'tecnico' && to.meta.category && to.meta.category !== auth.assignedCategory) {
        return next('/portal/tecnico/dashboard');
    }
    
    next();
});

export default router;
