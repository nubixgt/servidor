import { reactive } from 'vue'
import api from '../services/api'
import { llamar } from '../services/panelApi'

const CLAVES = { token: 'deportes_token', rol: 'deportes_rol', usuario: 'deportes_usuario', equipo_id: 'deportes_equipo_id', equipo_nombre: 'deportes_equipo_nombre' }

const leer = (k) => localStorage.getItem(CLAVES[k]) || ''
const sesion = reactive({ token: leer('token'), rol: leer('rol'), usuario: leer('usuario'), equipo_id: Number(leer('equipo_id')) || null, equipo_nombre: leer('equipo_nombre') })

export const homePorRol = (rol) => (rol === 'admin' ? '/admin' : rol === 'encargado' ? '/encargado' : '/login')

function guardar(token, user) {
  Object.assign(sesion, { token, rol: user.rol, usuario: user.usuario, equipo_id: user.equipo_id, equipo_nombre: user.equipo_nombre || '' })
  localStorage.setItem(CLAVES.token, token)
  localStorage.setItem(CLAVES.rol, user.rol)
  localStorage.setItem(CLAVES.usuario, user.usuario)
  localStorage.setItem(CLAVES.equipo_id, user.equipo_id ?? '')
  localStorage.setItem(CLAVES.equipo_nombre, user.equipo_nombre || '')
}

export function useAuth() {
  // Devuelve la ruta a la que debe ir el usuario según su rol.
  async function login(usuario, password) {
    const r = await llamar(api.post('/login', { usuario, password }))
    guardar(r.token, r.user)
    return homePorRol(r.user.rol)
  }

  function logout() {
    Object.values(CLAVES).forEach((k) => localStorage.removeItem(k))
    Object.assign(sesion, { token: '', rol: '', usuario: '', equipo_id: null, equipo_nombre: '' })
  }

  return { sesion, login, logout }
}
