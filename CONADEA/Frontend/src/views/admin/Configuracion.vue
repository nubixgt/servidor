<template>
  <div>
    <div class="fila-seccion"><h2>Configuración</h2></div>
    <div class="vidrio">
      <div class="opcion-config">
        <div class="datos">
          <b>Notificaciones de novedades</b>
          <span class="desc-op">Alertas fitosanitarias, climáticas y avisos del programa.</span>
        </div>
        <button class="toggle-btn" :class="{ activo: store.config.notif }" @click="toggle('notif')">
          {{ store.config.notif ? 'Activado ✓' : 'Desactivado' }}
        </button>
      </div>
      <div class="opcion-config">
        <div class="datos">
          <b>Recordatorio de racha</b>
          <span class="desc-op">Aviso diario para mantener tu racha de aprendizaje.</span>
        </div>
        <button class="toggle-btn" :class="{ activo: store.config.recordatorio }" @click="toggle('recordatorio')">
          {{ store.config.recordatorio ? 'Activado ✓' : 'Desactivado' }}
        </button>
      </div>
      <div class="opcion-config">
        <div class="datos">
          <b>Modo ahorro de datos</b>
          <span class="desc-op">Reduce imágenes y animaciones para conexiones lentas.</span>
        </div>
        <button class="toggle-btn" :class="{ activo: store.config.datos }" @click="toggle('datos')">
          {{ store.config.datos ? 'Activado ✓' : 'Desactivado' }}
        </button>
      </div>
    </div>

    <div class="vidrio" style="margin-top:14px;">
      <h3 style="margin-bottom:8px;">Datos de la cuenta</h3>
      <p style="font-size:.82rem;color:var(--texto-suave);margin-bottom:12px;">
        Tu avance se guarda en este dispositivo. Si cierras sesión, podrás retomarlo al volver a ingresar en este mismo equipo.
      </p>
      <button class="btn" @click="cerrarSesion">🚪 Cerrar sesión</button>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.js';
import { alertaConfirmar } from '../../utils/alertas.js';

const router = useRouter();
const store = useAppStore();

function toggle(clave) {
  store.actualizarConfig(clave, !store.config[clave]);
}

async function cerrarSesion() {
  const confirmar = await alertaConfirmar(
    '¿Cerrar sesión?',
    '¿Seguro que quieres salir de tu cuenta?',
    'Sí, salir',
    'No'
  );
  if (!confirmar) return;

  store.cerrarSesion();
  router.push('/login');
}
</script>

<style scoped>
.opcion-config { display: flex; align-items: center; gap: 14px; padding: 14px 0; border-bottom: 1px solid var(--borde); }
.opcion-config:last-child { border-bottom: none; }
.datos { flex: 1; }
.datos b { display: block; font-size: 0.9rem; font-family: 'Outfit', sans-serif; font-weight: 700; }
.desc-op { font-size: 0.78rem; color: var(--texto-suave); display: block; margin-top: 2px; }
.toggle-btn { border: 1.5px solid var(--borde-claro); border-bottom: 3px solid rgba(255,255,255,0.1); background: var(--vidrio-2); color: var(--texto-suave); font-size: 0.78rem; font-weight: 700; padding: 7px 14px 9px; border-radius: 12px; cursor: pointer; transition: all 0.1s ease; white-space: nowrap; }
.toggle-btn.activo { border-color: var(--verde); color: var(--verde); background: rgba(74,222,128,0.1); }
.toggle-btn:hover { transform: translateY(-1px); }
</style>
