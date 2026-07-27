import { createRouter, createWebHashHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'
import InscripcionEquipo from '../views/InscripcionEquipo.vue'
import MiEquipo from '../views/MiEquipo.vue'
import InscripcionJugador from '../views/InscripcionJugador.vue'

const routes = [
  {
    path: '/',
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
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('deportes_token')
  
  if (to.meta.requiresAuth && !token) {
    next('/login')
  } else if (to.name === 'Login' && token) {
    next('/mi-equipo')
  } else {
    next()
  }
})

export default router
