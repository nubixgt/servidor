import { createRouter, createWebHistory } from 'vue-router';

// Layouts
import PublicLayout from '../components/layout/PublicLayout.vue';
import AdminLayout from '../components/layout/AdminLayout.vue';

// Vistas públicas
import Home from '../views/public/Home.vue';
import Equipos from '../views/public/Equipos.vue';
import Jugadores from '../views/public/Jugadores.vue';
import Partidos from '../views/public/Partidos.vue';
import Estadisticas from '../views/public/Estadisticas.vue';
import Novedades from '../views/public/Novedades.vue';
import Login from '../views/auth/Login.vue';
import { useAuthStore } from '../stores/auth';

const routes = [
  // Rutas Públicas (bajo PublicLayout)
  {
    path: '/',
    component: PublicLayout,
    children: [
      { path: '', name: 'Home', component: Home },
      { path: 'equipos', name: 'Equipos', component: Equipos },
      { path: 'jugadores', name: 'Jugadores', component: Jugadores },
      { path: 'partidos', name: 'Partidos', component: Partidos },
      { path: 'estadisticas', name: 'Estadisticas', component: Estadisticas },
      { path: 'novedades', name: 'Novedades', component: Novedades }
    ]
  },

  // Autenticación
  {
    path: '/login',
    name: 'Login',
    component: Login
  },

  // Panel de administración (bajo AdminLayout, requiere sesión admin)
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'AdminResumen', component: () => import('../views/admin/Resumen.vue') },
      { path: 'equipos', name: 'AdminEquipos', component: () => import('../views/admin/Equipos.vue') },
      { path: 'jugadores', name: 'AdminJugadores', component: () => import('../views/admin/Jugadores.vue') },
      { path: 'partidos', name: 'AdminPartidos', component: () => import('../views/admin/Partidos.vue') },
      { path: 'estadisticas', name: 'AdminEstadisticas', component: () => import('../views/admin/Estadisticas.vue') },
      { path: 'novedades', name: 'AdminNovedades', component: () => import('../views/admin/Novedades.vue') }
    ]
  },

  // Compatibilidad con el enlace viejo
  { path: '/dashboard', redirect: '/admin' },

  // Cualquier otra ruta -> inicio
  { path: '/:pathMatch(.*)*', redirect: '/' }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior() {
    return { top: 0 };
  }
});

router.beforeEach((to) => {
  const auth = useAuthStore();

  if (to.meta.requiresAuth) {
    if (!auth.isAuthenticated || !auth.isAdmin) {
      auth.logout();
      return { name: 'Login', query: { redirect: to.fullPath } };
    }
  }

  if (to.name === 'Login' && auth.isAuthenticated && auth.isAdmin) {
    return { path: '/admin' };
  }
});

export default router;
