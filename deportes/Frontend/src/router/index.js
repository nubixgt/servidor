import { createRouter, createWebHistory } from 'vue-router'
import PublicLayout from '../layouts/PublicLayout.vue'
import { homePorRol } from '../composables/useAuth'

const titulo = (t) => ({ title: t })

// Vistas compartidas por admin y encargado (el servidor limita lo que ve cada rol).
const Resumen = () => import('../views/admin/Resumen.vue')
const Jugadores = () => import('../views/admin/Jugadores.vue')
const Partidos = () => import('../views/admin/Partidos.vue')

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
    meta: { requiresAuth: true, rol: 'admin' },
    children: [
      { path: '', name: 'AdminResumen', component: Resumen, meta: titulo('Administración') },
      { path: 'equipos', name: 'AdminEquipos', component: () => import('../views/admin/Equipos.vue'), meta: titulo('Admin · Equipos') },
      { path: 'jugadores', name: 'AdminJugadores', component: Jugadores, meta: titulo('Admin · Jugadores') },
      { path: 'posiciones', name: 'AdminPosiciones', component: () => import('../views/admin/Posiciones.vue'), meta: titulo('Admin · Posiciones') },
      { path: 'partidos', name: 'AdminPartidos', component: Partidos, meta: titulo('Admin · Partidos') },
    ],
  },
  {
    path: '/encargado',
    component: () => import('../layouts/EncargadoLayout.vue'),
    meta: { requiresAuth: true, rol: 'encargado' },
    children: [
      { path: '', name: 'EncargadoResumen', component: Resumen, meta: titulo('Mi panel') },
      { path: 'mi-equipo', name: 'EncargadoEquipo', component: () => import('../views/encargado/MiEquipo.vue'), meta: titulo('Mi equipo') },
      { path: 'jugadores', name: 'EncargadoJugadores', component: Jugadores, meta: titulo('Mis jugadores') },
      { path: 'partidos', name: 'EncargadoPartidos', component: Partidos, meta: titulo('Mis partidos') },
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
  const token = localStorage.getItem('deportes_token')
  const rol = localStorage.getItem('deportes_rol')

  const zona = to.matched.find((r) => r.meta.requiresAuth)
  if (zona && !token) return '/login'
  if (zona && zona.meta.rol !== rol) return homePorRol(rol) // no se puede cruzar de panel
  if (to.name === 'Login' && token) return homePorRol(rol)
})

export default router
