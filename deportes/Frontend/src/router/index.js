import { createRouter, createWebHashHistory } from 'vue-router'
import Home from '../views/Home.vue'
import InscripcionEquipo from '../views/InscripcionEquipo.vue'
import InscripcionJugador from '../views/InscripcionJugador.vue'
import Listado from '../views/Listado.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home
  },
  {
    path: '/listado',
    name: 'Listado',
    component: Listado
  },
  {
    path: '/inscripcion-equipo',
    name: 'InscripcionEquipo',
    component: InscripcionEquipo
  },
  {
    path: '/inscripcion-jugador',
    name: 'InscripcionJugador',
    component: InscripcionJugador
  },
  {
    // Redirect old routes or nav links
    path: '/equipos',
    redirect: '/listado'
  },
  {
    path: '/jugadores',
    redirect: '/listado' // Or create a dedicated players list later
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

export default router
