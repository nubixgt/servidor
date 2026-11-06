import Swal from 'sweetalert2';

// Pre-themed SweetAlert2 instance matching the app's dark/neon look.
const swal = Swal.mixin({
  background: '#121316',
  color: '#f1f0ee',
  confirmButtonColor: '#caf300',
  cancelButtonColor: '#30333a',
  customClass: {
    popup: 'font-sans rounded-3xl border border-white/15',
    confirmButton: 'font-black uppercase tracking-wide text-[#121700]',
    cancelButton: 'font-black uppercase tracking-wide',
  },
});

/**
 * Lo que el atleta escribió en un diálogo, como texto.
 *
 * SweetAlert2 no devuelve siempre una cadena en `result.value`:
 *
 *   con `input`      -> el texto escrito, o `null` (un radio sin marcar)
 *   SIN `input`      -> **`true`**, el booleano (`confirm(instance, true)`)
 *
 * Y ahí estaba el fallo. Los diálogos de Garmin piden la contraseña sólo
 * cuando NO hay una guardada, así que el mismo `await swal.fire(...)` devuelve
 * una cadena o un `true` según el perfil. El código hacía
 * `(r.value || '').trim()`, que con `true` revienta con «r.value.trim is not a
 * function» -- y como ninguno de esos manejadores tenía `try/catch`, la
 * excepción se perdía en la consola y en la pantalla no pasaba absolutamente
 * nada: el botón de eliminar el plan, el de generar y el de subir a Garmin
 * dejaron de responder en cuanto el atleta guardó su contraseña.
 *
 * Se arregla aquí y no en cada sitio porque la trampa la pone la librería, y
 * volverá a ponerla en el siguiente diálogo condicional que alguien escriba.
 */
export function textoDe(resultado) {
  const v = resultado?.value;
  return typeof v === 'string' ? v.trim() : '';
}

export default swal;
