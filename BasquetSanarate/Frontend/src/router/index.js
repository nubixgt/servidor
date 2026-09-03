import { createRouter, createWebHistory } from 'vue-router';

// Layouts
import PublicLayout from '../components/layout/PublicLayout.vue';
import AdminLayout from '../components/layout/AdminLayout.vue';

// Views
import Home from '../views/public/Home.vue';
import Equipos from '../views/public/Equipos.vue';
import Jugadores from '../views/public/Jugadores.vue';
import Partidos from '../views/public/Partidos.vue';
import Estadisticas from '../views/public/Estadisticas.vue';
import Novedades from '../views/public/Novedades.vue';
import Login from '../views/auth/Login.vue';
import Dashboard from '../views/admin/Dashboard.vue';

const routes = [
  // Rutas Públicas (Bajo PublicLayout)
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

  // Ruta de Autenticación
  {
    path: '/login',
    name: 'Login',
    component: Login
  },

  // Rutas Administrativas (Bajo AdminLayout)
  {
    path: '/',
    component: AdminLayout,
    meta: { requiresAuth: true },
    children: [
      { path: 'dashboard', name: 'Dashboard', component: Dashboard }
    ]
  },

  // Redirección para cualquier ruta desconocida hacia inicio
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior() {
    return { top: 0 };
  }
});

export default router;

