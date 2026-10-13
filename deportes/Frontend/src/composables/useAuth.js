import { ref } from 'vue'

// Etapa 2 (sin backend): cualquier usuario y contraseña no vacíos inician sesión.
// Se conservan las claves de localStorage que ya usa el guard del router.
const usuario = ref(localStorage.getItem('deportes_usuario') || '')

export function useAuth() {
  const login = (nombre) => {
    localStorage.setItem('deportes_token', 'demo')
    localStorage.setItem('deportes_rol', 'admin')
    localStorage.setItem('deportes_usuario', nombre)
    usuario.value = nombre
  }
  const logout = () => {
    ;['deportes_token', 'deportes_rol', 'deportes_usuario'].forEach((k) => localStorage.removeItem(k))
    usuario.value = ''
  }
  return { usuario, login, logout }
}
