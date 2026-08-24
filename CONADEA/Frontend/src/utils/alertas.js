import Swal from 'sweetalert2';

/**
 * Alertas estilo CONADEA (glass/verde) — equivalente web de
 * app_conadea/lib/core/widgets/app_dialog.dart, para que login, registro,
 * etc. se vean y se sientan igual en la app y en la web.
 */
const base = {
  background: 'transparent',
  color: '#FFFFFF',
  buttonsStyling: false,
  backdrop: 'rgba(6, 20, 26, 0.65)',
  customClass: {
    popup: 'conadea-swal-popup',
    confirmButton: 'conadea-swal-btn conadea-swal-btn-verde',
    cancelButton: 'conadea-swal-btn conadea-swal-btn-outline',
  },
};

export function alertaExito(titulo, mensaje, autoCerrarMs = null) {
  return Swal.fire({
    ...base,
    icon: 'success',
    title: titulo,
    html: mensaje,
    confirmButtonText: 'Entendido',
    timer: autoCerrarMs || undefined,
    timerProgressBar: !!autoCerrarMs,
    showConfirmButton: !autoCerrarMs,
  });
}

export function alertaError(mensaje, titulo = 'Ocurrió un problema') {
  return Swal.fire({
    ...base,
    icon: 'error',
    title: titulo,
    html: mensaje,
    confirmButtonText: 'Entendido',
  });
}

export async function alertaConfirmar(titulo, mensaje, textoConfirmar = 'Sí', textoCancelar = 'No') {
  const resultado = await Swal.fire({
    ...base,
    icon: 'question',
    title: titulo,
    html: mensaje,
    showCancelButton: true,
    reverseButtons: true,
    confirmButtonText: textoConfirmar,
    cancelButtonText: textoCancelar,
    customClass: {
      ...base.customClass,
      confirmButton: 'conadea-swal-btn conadea-swal-btn-rojo',
    },
  });
  return resultado.isConfirmed;
}
