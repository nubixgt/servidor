<template>
  <div>
    <!-- Grid principal del dashboard -->
    <div class="grid-dash">
      <!-- Tarjeta: Progreso general -->
      <div class="vidrio">
        <div class="cab-tarjeta">
          <h3>Tu progreso general</h3>
        </div>
        <div class="prog-flex">
          <!-- Anillo SVG -->
          <div class="anillo">
            <svg width="128" height="128" viewBox="0 0 128 128">
              <circle cx="64" cy="64" r="54" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="8"/>
              <circle
                cx="64" cy="64" r="54" fill="none"
                stroke="url(#gradAnillo)" stroke-width="8"
                stroke-linecap="round"
                :stroke-dasharray="circunferencia"
                :stroke-dashoffset="dashOffset"
                style="transform: rotate(-90deg); transform-origin: 64px 64px; filter: drop-shadow(0 0 4px rgba(74,222,128,0.5));"
              />
              <defs>
                <linearGradient id="gradAnillo" x1="0" y1="0" x2="1" y2="1">
                  <stop offset="0%" stop-color="#A3E635"/>
                  <stop offset="100%" stop-color="#4ADE80"/>
                </linearGradient>
              </defs>
            </svg>
            <div class="anillo-centro">
              <b>{{ store.pctGlobal }}%</b>
              <small>Completado</small>
            </div>
          </div>

          <div class="stats-prog">
            <div class="stat-linea">
              <span class="stat-ic">📗</span>
              Cursos completados
              <b>{{ store.modulosCompletos }}</b>
            </div>
            <div class="stat-linea">
              <span class="stat-ic">📈</span>
              En progreso
              <b>{{ store.enProgreso }}</b>
            </div>
            <div class="stat-linea">
              <span class="stat-ic">⏱️</span>
              Horas de capacitación
              <b>{{ store.horasCapacitacion }}h</b>
            </div>
          </div>
        </div>
        <button class="btn btn-ancho" @click="router.push('/perfil')">Ver mi progreso →</button>
      </div>

      <!-- Tarjeta: Racha de aprendizaje -->
      <div class="vidrio">
        <div class="cab-tarjeta">
          <h3>Racha de aprendizaje 🔥</h3>
        </div>
        <div class="racha-num">
          <b>{{ store.racha }}</b>
          <p>{{ store.racha === 1 ? 'día consecutivo' : 'días consecutivos' }}</p>
          <div class="animo">
            {{ store.racha > 0 ? '¡Sigue así!' : 'Completa una lección hoy para iniciar tu racha' }}
          </div>
        </div>
        <div class="dias-semana">
          <div
            v-for="dia in store.semanaActual"
            :key="dia.l + dia.hoy"
            class="dia"
            :class="{ hecho: dia.hecho, hoy: dia.hoy }"
          >
            <span class="marca">{{ dia.hecho ? '✓' : '' }}</span>
            {{ dia.l }}
          </div>
        </div>
      </div>

      <!-- Tarjeta: Novedades recientes -->
      <div class="vidrio">
        <div class="cab-tarjeta">
          <h3>Novedades</h3>
          <button class="ver-todas" @click="router.push('/novedades')">Ver todas</button>
        </div>
        <div
          v-for="(nov, i) in novedadesRecientes"
          :key="i"
          class="nov-item"
          :class="nov.tipo"
        >
          <div class="nov-icono">
            {{ nov.tipo === 'video' ? '🎬' : nov.tipo === 'alerta' ? '📢' : '📗' }}
          </div>
          <div>
            <b>{{ nov.t }}</b>
            <small>{{ nov.chip }} · {{ nov.fecha }}</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Segunda fila -->
    <div class="grid-dash">
      <!-- Mis cursos en progreso -->
      <div class="vidrio">
        <div class="cab-tarjeta">
          <h3>Mis cursos en progreso</h3>
          <button class="ver-todas" @click="router.push('/miscursos')">Ver todos</button>
        </div>
        <div v-for="m in cursosEnProgreso" :key="m.id" class="curso-fila">
          <div class="miniatura" :style="miniaturaStyle(m)"></div>
          <div class="datos">
            <b>{{ m.t }}</b>
            <div class="pista">
              <div class="pista-fill" :style="{ width: pct(m) + '%' }"></div>
            </div>
            <span class="pct-txt">{{ pct(m) }}% completado</span>
          </div>
          <button
            class="btn-continuar"
            :class="{ hecho: store.progDe(m.id).ok }"
            @click="irACurso(m.id)"
          >
            {{ store.progDe(m.id).ok ? 'Repasar' : 'Continuar' }}
          </button>
        </div>
        <button class="ver-todas" style="margin-top:10px;" @click="router.push('/miscursos')">
          Ver todos mis cursos →
        </button>
      </div>

      <!-- Rutas de aprendizaje -->
      <div class="vidrio">
        <div class="cab-tarjeta">
          <h3>Rutas de aprendizaje</h3>
          <button class="ver-todas" @click="router.push('/rutas')">Ver todas</button>
        </div>
        <div v-for="r in rutasPreview" :key="r.id" class="ruta" :class="r.clase">
          <div class="ruta-icono">{{ r.ic }}</div>
          <div class="ruta-datos">
            <b>{{ r.t }}</b>
            <p>{{ r.d }}</p>
            <span class="estado-ruta">{{ store.pctRuta(r) }}% completado · {{ r.mods.length }} cursos</span>
            <div class="pista" style="margin-top:6px;">
              <div class="pista-fill" :style="{ width: store.pctRuta(r) + '%' }"></div>
            </div>
          </div>
        </div>
        <button class="ver-todas" style="margin-top:10px;" @click="router.push('/rutas')">
          Explorar rutas de aprendizaje →
        </button>
      </div>

      <!-- Insignias recientes -->
      <div class="vidrio">
        <div class="cab-tarjeta">
          <h3>Insignias recientes</h3>
          <button class="ver-todas" @click="router.push('/insignias')">Ver todas</button>
        </div>
        <p v-if="insigniasRecientes.length === 0" class="txt-suave">
          Aún no tienes insignias. Completa tu primera lección para ganar 🌱 <b>Primera semilla</b>.
        </p>
        <div v-for="b in insigniasPreview" :key="b.id" class="hex-item">
          <div class="hexagono" :class="store.insignias.includes(b.id) ? (b.colorClass || '') : 'gris'">
            {{ store.insignias.includes(b.id) ? b.ic : '🔒' }}
          </div>
          <div>
            <b>{{ b.t }}</b>
            <small>{{ b.d }}</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Banner motivacional -->
    <div class="banner">
      <div class="trofeo">🏆</div>
      <div>
        <b>¡Sigue así, {{ nombreCorto }}!</b>
        <p>Vas por buen camino. Cada curso te acerca más a tus objetivos.</p>
      </div>
      <button class="btn btn-verde" @click="router.push('/perfil')">Ver mis objetivos →</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.js';
import { MODULOS, RUTAS, NOVEDADES, INSIGNIAS } from '../../data/local.js';

const router = useRouter();
const store = useAppStore();

const circunferencia = 2 * Math.PI * 54;
const dashOffset = computed(() => circunferencia * (1 - store.pctGlobal / 100));

const nombreCorto = computed(() => store.usuario?.nombre?.split(' ')[0] || 'Usuario');

const novedadesRecientes = computed(() => NOVEDADES.slice(0, 3));

const cursosEnProgreso = computed(() => {
  const conProgreso = MODULOS.filter(m => {
    const p = store.progDe(m.id);
    return p.lec.length > 0 && !p.ok;
  }).slice(0, 3);
  return conProgreso.length ? conProgreso : MODULOS.slice(0, 3);
});

const rutasPreview = computed(() => RUTAS.slice(0, 2));

const insigniasRecientes = computed(() =>
  [...store.insignias].slice(-3).reverse().map(id => INSIGNIAS.find(b => b.id === id)).filter(Boolean)
);

const insigniasPreview = computed(() =>
  insigniasRecientes.value.length ? insigniasRecientes.value : INSIGNIAS.slice(0, 2)
);

function pct(m) {
  return store.pctModulo(m);
}

function miniaturaStyle(m) {
  if (store.config.datos) {
    const c = [['#34D399', '#0F766E'], ['#A3E635', '#3F6212'], ['#FBBF24', '#B45309']];
    const col = c[(m.id - 1) % c.length];
    return `background: linear-gradient(135deg, ${col[0]}, ${col[1]});`;
  }
  return `background-image: url('${m.img}'); background-size: cover; background-position: center;`;
}

function irACurso(id) {
  router.push(`/catalogo/${id}`);
}
</script>

<style scoped>
.grid-dash {
  display: grid;
  grid-template-columns: 1.25fr 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

@media (max-width: 1280px) { .grid-dash { grid-template-columns: 1fr 1fr; } }
@media (max-width: 760px)  { .grid-dash { grid-template-columns: 1fr; } }

/* Progreso anillo */
.prog-flex { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
.anillo { position: relative; width: 128px; height: 128px; flex: none; }
.anillo-centro {
  position: absolute; inset: 0;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.anillo-centro b { font-size: 1.7rem; font-family: 'Outfit', sans-serif; font-weight: 800; }
.anillo-centro small { font-size: 0.64rem; color: var(--texto-suave); text-transform: uppercase; letter-spacing: 0.06em; }

.stats-prog { flex: 1; min-width: 170px; display: flex; flex-direction: column; gap: 12px; }
.stat-linea { display: flex; align-items: center; gap: 12px; font-size: 0.86rem; color: var(--texto-suave); }
.stat-ic { width: 32px; height: 32px; border-radius: 10px; background: var(--vidrio-2); display: flex; align-items: center; justify-content: center; font-size: 1rem; flex: none; }
.stat-linea b { margin-left: auto; font-size: 1.05rem; color: var(--texto); font-family: 'Outfit', sans-serif; }

/* Racha */
.racha-num { text-align: center; margin: 4px 0 2px; }
.racha-num b { font-size: 3.2rem; line-height: 1; font-family: 'Outfit', sans-serif; font-weight: 900; }
.racha-num p { font-size: 0.86rem; color: var(--texto-suave); margin-top: 2px; }
.animo { color: var(--verde); font-weight: 700; font-size: 0.88rem; margin-top: 4px; }
.dias-semana { display: flex; justify-content: space-between; margin-top: 18px; padding: 0 2px; }
.dia { display: flex; flex-direction: column; align-items: center; gap: 8px; font-size: 0.72rem; color: var(--texto-suave); font-weight: 700; }
.marca { width: 28px; height: 28px; border-radius: 50%; border: 1.5px solid var(--borde-claro); display: flex; align-items: center; justify-content: center; font-size: 0.8rem; transition: all 0.3s ease; }
.dia.hecho .marca { background: var(--verde-fuerte); border-color: var(--verde-fuerte); color: #06281A; font-weight: 900; box-shadow: 0 0 10px rgba(34,197,94,0.4); }
.dia.hoy .marca { box-shadow: 0 0 0 3px rgba(74,222,128,0.4); border-color: #FFFFFF; }

/* Novedades */
.nov-item { display: flex; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--borde); transition: all 0.2s; }
.nov-item:hover { padding-left: 4px; }
.nov-item:last-child { border-bottom: none; }
.nov-icono { width: 42px; height: 42px; border-radius: 12px; flex: none; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
.nov-item.video .nov-icono { background: linear-gradient(135deg, #60A5FA, #2563EB); }
.nov-item.alerta .nov-icono { background: linear-gradient(135deg, #FBBF24, #D97706); }
.nov-item.novedad .nov-icono { background: linear-gradient(135deg, #4ADE80, #15803D); }
.nov-item b { font-size: 0.86rem; display: block; font-family: 'Outfit', sans-serif; font-weight: 700; }
.nov-item small { font-size: 0.72rem; color: var(--texto-suave); opacity: 0.8; display: block; margin-top: 4px; }

/* Cursos en progreso */
.curso-fila { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--borde); transition: all 0.2s; }
.curso-fila:hover { padding-left: 4px; }
.curso-fila:last-of-type { border-bottom: none; }
.miniatura { width: 64px; height: 48px; border-radius: 12px; flex: none; border: 1px solid var(--borde-claro); transition: transform 0.3s ease; }
.curso-fila:hover .miniatura { transform: scale(1.05); }
.datos { flex: 1; min-width: 0; }
.datos b { font-size: 0.92rem; display: block; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-family: 'Outfit', sans-serif; font-weight: 700; }
.pct-txt { font-size: 0.78rem; color: var(--texto-suave); margin-top: 4px; display: block; }
.btn-continuar { border: 1.5px solid var(--verde); border-bottom: 3.5px solid var(--verde-oscuro); color: var(--verde); font-size: 0.78rem; font-weight: 700; padding: 7px 16px 9px; border-radius: 12px; flex: none; transition: all 0.1s ease; background: transparent; cursor: pointer; }
.btn-continuar:hover { background: var(--verde); color: #06281A; box-shadow: 0 4px 12px rgba(74,222,128,0.4); transform: translateY(-1.5px); border-bottom-width: 5px; }
.btn-continuar.hecho { border-color: var(--oro); border-bottom-color: #B45309; color: var(--oro); }
.btn-continuar.hecho:hover { background: var(--oro); color: #3A2A00; box-shadow: 0 4px 12px rgba(244,197,66,0.4); }

/* Rutas */
.ruta { display: flex; gap: 16px; background: var(--vidrio); border: 1px solid var(--borde); border-radius: 16px; padding: 14px; margin-bottom: 12px; transition: all 0.3s ease; }
.ruta:hover { border-color: rgba(255,255,255,0.2); background: rgba(13,38,48,0.5); }
.ruta-icono { width: 56px; height: 56px; border-radius: 14px; flex: none; font-size: 1.7rem; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #34D399, #059669); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
.ruta.azul .ruta-icono { background: linear-gradient(135deg, #60A5FA, #1D4ED8); }
.ruta.oro .ruta-icono  { background: linear-gradient(135deg, #FCD34D, #D97706); }
.ruta.verde .ruta-icono{ background: linear-gradient(135deg, #4ADE80, #15803D); }
.ruta-datos { flex: 1; min-width: 0; }
.ruta-datos b { font-size: 0.95rem; font-family: 'Outfit', sans-serif; font-weight: 700; }
.ruta-datos p { font-size: 0.78rem; color: var(--texto-suave); line-height: 1.45; margin: 4px 0 8px; }
.estado-ruta { font-size: 0.76rem; color: var(--lima); font-weight: 700; display: block; }

/* Insignias */
.hex-item { display: flex; gap: 14px; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--borde); }
.hex-item:last-child { border-bottom: none; }
.hexagono { width: 52px; height: 58px; flex: none; font-size: 1.4rem; clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%); background: linear-gradient(160deg, #FDE68A, #D97706); display: flex; align-items: center; justify-content: center; }
.hexagono.verde { background: linear-gradient(160deg, #86EFAC, #15803D); }
.hexagono.azul  { background: linear-gradient(160deg, #7DD3FC, #0369A1); }
.hexagono.gris  { background: linear-gradient(160deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05)); filter: grayscale(1); opacity: 0.4; }
.hex-item b { font-size: 0.88rem; display: block; font-family: 'Outfit', sans-serif; font-weight: 700; }
.hex-item small { font-size: 0.76rem; color: var(--texto-suave); display: block; line-height: 1.4; margin-top: 1px; }

/* Banner */
.banner { display: flex; align-items: center; gap: 24px; margin-top: 24px; background: linear-gradient(110deg, rgba(34,197,94,.2), rgba(56,189,248,.15)); border: 1px solid rgba(255,255,255,.15); border-radius: 20px; padding: 24px 30px; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); box-shadow: 0 8px 32px rgba(0,0,0,.25); flex-wrap: wrap; }
.trofeo { font-size: 3rem; filter: drop-shadow(0 0 15px rgba(74,222,128,0.6)); animation: floatTrophy 3s ease-in-out infinite; }
@keyframes floatTrophy { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
.banner b { font-size: 1.15rem; display: block; font-family: 'Outfit', sans-serif; font-weight: 800; }
.banner p { font-size: 0.86rem; color: var(--texto-suave); margin-top: 2px; }
.banner .btn { margin-left: auto; }

.txt-suave { font-size: 0.8rem; color: var(--texto-suave); }
</style>
