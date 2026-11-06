import { createRouter, createWebHistory } from 'vue-router';
import { useAppStore } from '../stores/appStore';
import { verPantalla } from '../services/experimentos';

// Auth View
import Login from '../views/auth/Login.vue';

// Layout
import MainLayout from '../components/layout/MainLayout.vue';

// Admin Views
import DashboardView from '../views/admin/DashboardView.vue';
import ProfileView from '../views/admin/ProfileView.vue';
import PlanView from '../views/admin/PlanView.vue';
import HistorialView from '../views/admin/HistorialView.vue';
import CuadernoView from '../views/admin/CuadernoView.vue';
import AnalyticsView from '../views/admin/AnalyticsView.vue';
import ReportsView from '../views/admin/ReportsView.vue';
import UsersAdminView from '../views/admin/UsersAdminView.vue';
import NotificationsAdminView from '../views/admin/NotificationsAdminView.vue';
import MetricsAdminView from '../views/admin/MetricsAdminView.vue';
import WelcomeView from '../views/admin/WelcomeView.vue';

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
    path: '/admin',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/admin/dashboard' },
      { path: 'dashboard', name: 'AdminDashboard', component: DashboardView },
      { path: 'perfil', name: 'AdminProfile', component: ProfileView },
      { path: 'plan', name: 'AdminPlan', component: PlanView },
      { path: 'historial', name: 'AdminHistorial', component: HistorialView },
      { path: 'cuaderno', name: 'AdminCuaderno', component: CuadernoView },
      { path: 'analytics', name: 'AdminAnalytics', component: AnalyticsView },
      { path: 'reports', name: 'AdminReports', component: ReportsView },
      { path: 'usuarios', name: 'AdminUsers', component: UsersAdminView, meta: { requiresAdmin: true } },
      { path: 'notificaciones', name: 'AdminNotifications', component: NotificationsAdminView, meta: { requiresAdmin: true } },
      { path: 'metricas', name: 'AdminMetrics', component: MetricsAdminView, meta: { requiresAdmin: true } },
      { path: 'bienvenida', name: 'AdminWelcome', component: WelcomeView },
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login'
  }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
});

router.beforeEach((to) => {
  const store = useAppStore();
  const isAuthenticated = !!store.token;

  if (to.meta.requiresAuth && !isAuthenticated) {
    return '/login';
  }

  if (to.path === '/login' && isAuthenticated) {
    return '/admin/dashboard';
  }

  if (to.meta.requiresAdmin && !store.isAdmin) {
    return '/admin/dashboard';
  }

  return true;
});

// Qué pantallas se abren (Beta 3.0, F0), sólo con sesión y sin esperar a la respuesta.
router.afterEach((to) => {
  const store = useAppStore();
  if (store.token && to.name) verPantalla(String(to.name));
});

export default router;
