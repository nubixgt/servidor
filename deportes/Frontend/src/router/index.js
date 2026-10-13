import { createRouter, createWebHistory } from 'vue-router'
import PublicLayout from '../layouts/PublicLayout.vue'

const titulo = (t) => ({ title: t })

const routes = [
  {
    path: '/',
    component: PublicLayout,
    children: [
      { path: '', name: 'Inicio', component: () => import('../views/public/Inicio.vue'), meta: titulo('Inicio') },
      { path: 'equipos', name: 'Equipos', component: () => import('../views/public/Equipos.vue'), meta: titulo('Equipos') },
      { path: 'equipos/:id', name: 'Equipo', component: () => import('../views/public/EquipoDetalle.vue'), meta: titulo('Equipo') },
      { path: 'jugadores', name: 'Jugadores', component: () => import('../views/public/Jugadores.vue'), meta: titulo('Jugadores') },
      { path: 'jugadores/:id', name: 'Jugador', component: () => import('../views/public/JugadorDetalle.vue'), meta: titulo('Jugador') },
      { path: 'estadisticas', name: 'Estadisticas', component: () => import('../views/public/Estadisticas.vue'), meta: titulo('Estadísticas') },
      { path: 'partidos', name: 'Partidos', component: () => import('../views/public/Partidos.vue'), meta: titulo('Partidos') },
      { path: 'partidos/:id', name: 'Partido', component: () => import('../views/public/PartidoDetalle.vue'), meta: titulo('Partido') },
      { path: 'login', name: 'Login', component: () => import('../views/public/Login.vue'), meta: titulo('Acceso') },
      { path: ':pathMatch(.*)*', name: 'NotFound', component: () => import('../views/public/NotFound.vue'), meta: titulo('Página no encontrada') },
    ],
  },
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'AdminResumen', component: () => import('../views/admin/Resumen.vue'), meta: titulo('Administración') },
      { path: 'equipos', name: 'AdminEquipos', component: () => import('../views/admin/Equipos.vue'), meta: titulo('Admin · Equipos') },
      { path: 'jugadores', name: 'AdminJugadores', component: () => import('../views/admin/Jugadores.vue'), meta: titulo('Admin · Jugadores') },
      { path: 'partidos', name: 'AdminPartidos', component: () => import('../views/admin/Partidos.vue'), meta: titulo('Admin · Partidos') },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to) => {
  document.title = `${to.meta.title || 'Liga Pulse'} · Liga Pulse`
  const haySesion = !!localStorage.getItem('deportes_token')
  if (to.matched.some((r) => r.meta.requiresAuth) && !haySesion) return '/login'
  if (to.name === 'Login' && haySesion) return '/admin'
})

export default router
