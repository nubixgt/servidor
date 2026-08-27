import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'
import InscripcionEquipo from '../views/InscripcionEquipo.vue'
import MiEquipo from '../views/MiEquipo.vue'
import InscripcionJugador from '../views/InscripcionJugador.vue'
import Estadisticas from '../views/Estadisticas.vue'

const routes = [
  {
    path: '/',
    redirect: '/inicio'
  },
  {
    path: '/inicio',
    name: 'Home',
    component: Home
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  },
  {
    path: '/inscripcion-equipo',
    name: 'InscripcionEquipo',
    component: InscripcionEquipo
  },
  {
    path: '/mi-equipo',
    name: 'MiEquipo',
    component: MiEquipo,
    meta: { requiresAuth: true }
  },
  {
    path: '/inscripcion-jugador',
    name: 'InscripcionJugador',
    component: InscripcionJugador,
    meta: { requiresAuth: true }
  },
  {
    path: '/mi-equipo/inactivos',
    name: 'JugadoresInactivos',
    component: () => import('../views/MiEquipo/JugadoresInactivos.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/admin',
    name: 'AdminPanel',
    component: () => import('../views/AdminPanel.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/estadisticas',
    name: 'Estadisticas',
    component: Estadisticas
  },
  {
    path: '/registrar-partido',
    name: 'RegistrarPartido',
    component: () => import('../views/RegistrarPartido.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/historial-partidos',
    name: 'HistorialPartidos',
    component: () => import('../views/HistorialPartidos.vue')
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/inicio'
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('deportes_token')
  const rol = localStorage.getItem('deportes_rol')
  
  if (to.meta.requiresAuth && !token) {
    next('/login')
  } else if (to.meta.requiresAdmin && rol !== 'admin') {
    next('/mi-equipo')
  } else if (to.name === 'Login' && token) {
    if (rol === 'admin') {
      next('/admin')
    } else {
      next('/mi-equipo')
    }
  } else {
    next()
  }
})

export default router
