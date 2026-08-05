<template>
  <div>
    <div class="fila-seccion">
      <h2>Mis certificados</h2>
      <span style="font-size:.78rem;color:var(--texto-suave);">{{ modulosAprobados.length }} de {{ MODULOS.length }}</span>
    </div>
    <div class="vidrio">
      <div v-if="modulosAprobados.length">
        <div v-for="m in modulosAprobados" :key="m.id" class="curso-fila">
          <div class="cert-icono" :style="gradMod(m.id)">🎓</div>
          <div class="datos">
            <b>{{ m.t }}</b>
            <span class="pct-txt">Aprobado con {{ store.progDe(m.id).nota }}/3 · {{ store.progDe(m.id).fecha }}</span>
          </div>
          <button class="btn-cert" @click="verCert(m)">Ver certificado</button>
        </div>
      </div>
      <p v-else style="font-size:.85rem;color:var(--texto-suave);">
        Aún no tienes certificados. Completa las lecciones de un curso y aprueba su evaluación para obtener el primero. 🎓
      </p>
    </div>

    <!-- Modal Certificado -->
    <div v-if="certSeleccionado" class="fondo-modal" @click.self="certSeleccionado = null">
      <div class="certificado">
        <div class="sello">🌽</div>
        <div class="ente">MAGA · Consejo Nacional de Desarrollo Agropecuario · Programa AgroIA</div>
        <h2>Certificado de finalización</h2>
        <p class="otorga">Se otorga el presente certificado a</p>
        <div class="nombre-cert">{{ store.usuario?.nombre }}</div>
        <p class="detalle-cert">
          de <b>{{ store.usuario?.org }}</b>, {{ store.usuario?.muni }},<br>
          por haber completado satisfactoriamente el<br>
          <b>Módulo {{ certSeleccionado.id }}: {{ certSeleccionado.t }}</b><br>
          del programa de capacitación digital AgroIA,<br>
          con evaluación aprobada ({{ store.progDe(certSeleccionado.id).nota }}/3) el {{ store.progDe(certSeleccionado.id).fecha }}.
        </p>
        <div class="firmas">
          <div>Coordinación CONADEA<br>Programa AgroIA</div>
          <div>Red de técnicos validadores<br>Validación académica</div>
        </div>
        <div class="acciones-cert">
          <button class="btn btn-verde" @click="window.print()">🖨️ Imprimir / Guardar PDF</button>
          <button class="btn" @click="certSeleccionado = null">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAppStore } from '../../stores/app.js';
import { MODULOS, gradMod } from '../../data/local.js';

const store = useAppStore();
const certSeleccionado = ref(null);
const modulosAprobados = computed(() => MODULOS.filter(m => store.progDe(m.id).ok));
function verCert(m) { certSeleccionado.value = m; }
</script>

<style scoped>
.curso-fila { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--borde); }
.curso-fila:last-of-type { border-bottom: none; }
.cert-icono { width: 52px; height: 52px; border-radius: 14px; flex: none; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
.datos { flex: 1; min-width: 0; }
.datos b { font-size: 0.92rem; display: block; font-family: 'Outfit', sans-serif; font-weight: 700; }
.pct-txt { font-size: 0.78rem; color: var(--texto-suave); }
.btn-cert { border: 1.5px solid var(--oro); border-bottom: 3px solid #B45309; color: var(--oro); font-size: 0.78rem; font-weight: 700; padding: 7px 14px 9px; border-radius: 12px; background: transparent; cursor: pointer; transition: all 0.1s; }
.btn-cert:hover { background: var(--oro); color: #3A2A00; transform: translateY(-1px); }
/* Modal */
.fondo-modal { position: fixed; inset: 0; background: rgba(5,20,26,0.85); z-index: 100; display: flex; align-items: center; justify-content: center; padding: 16px; backdrop-filter: blur(8px); }
.certificado { background: #FFFDF4; color: #21302A; max-width: 680px; width: 100%; border-radius: 12px; border: 3px double #C8932B; padding: 40px 36px; text-align: center; position: relative; max-height: 92vh; overflow: auto; }
.certificado::before { content: ""; position: absolute; inset: 9px; border: 1px solid #E9B44C; border-radius: 8px; pointer-events: none; }
.sello { font-size: 2.8rem; }
.ente { font-size: 0.72rem; letter-spacing: 0.18em; text-transform: uppercase; color: #8C5E35; margin: 8px 0 16px; font-weight: 700; }
.certificado h2 { font-size: 1.8rem; color: #16382B; margin-bottom: 8px; font-family: Georgia, serif; }
.otorga { font-size: 0.9rem; color: #5C6B62; }
.nombre-cert { font-family: Georgia, serif; font-size: 1.8rem; color: #2D6A4F; font-weight: 700; margin: 16px 0; border-bottom: 1.5px solid #E9B44C; display: inline-block; padding: 0 30px 8px; }
.detalle-cert { font-size: 0.9rem; margin-bottom: 20px; line-height: 1.65; }
.firmas { display: flex; justify-content: space-around; margin-top: 30px; font-size: 0.74rem; color: #5C6B62; }
.firmas div { border-top: 1px solid #5C6B62; padding-top: 8px; width: 38%; font-weight: 600; }
.acciones-cert { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }
</style>
