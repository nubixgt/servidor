<template>
    <Teleport to="body">
        <div class="cmdk" role="dialog" aria-modal="true" aria-label="Consulta en lenguaje natural" @mousedown.self="cerrar">
            <div class="panel gl">
                <div class="inrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z" /></svg>
                    <input ref="entrada" v-model="texto" autocomplete="off" placeholder="Pregunte en español: “cuántos en nómina mayores de 80 en Alta Verapaz”" aria-label="Pregunta"
                        @input="programar" @keydown.enter.prevent="enter" @keydown.esc="cerrar">
                    <button class="ibtn" aria-label="Cerrar" style="width:34px;height:34px" @click="cerrar"><Icono name="close" :size="16" /></button>
                </div>
                <div class="res">
                    <template v-if="!consulta">
                        <div class="lbl">Pruebe con</div>
                        <div class="ex"><button v-for="e in EJEMPLOS" :key="e" @click="probar(e)">{{ e }}</button></div>
                    </template>
                    <template v-else-if="consulta.tags.length">
                        <div class="answer">
                            <div class="an">{{ fmt(conteo.c) }}</div>
                            <div class="at"><b>registros</b> ({{ fmt(conteo.p) }} personas únicas) cumplen la consulta.<br><span class="note">{{ pc(conteo.c, tablero.cube.TOTAL_NAC, 2) }}% del total de su ámbito</span></div>
                            <button class="btn pri" style="margin-left:auto" @click="aplicar">Aplicar al tablero</button>
                        </div>
                        <div>
                            <div class="lbl" style="margin-bottom:6px">Interpretación</div>
                            <div class="parsed"><span v-for="t in consulta.tags" :key="t">{{ t }}</span></div>
                        </div>
                    </template>
                    <template v-else-if="personas.length">
                        <div>
                            <div class="lbl" style="margin-bottom:4px">Expedientes encontrados ({{ totalPersonas }})</div>
                            <button v-for="p in personas" :key="p.id" class="pp" @click="abrirExp(p.id)">
                                <b>{{ p.nombre }}</b><span><EstadoBadge :i="p.estado_orden" /></span>
                                <small>Exp. {{ p.numero || 's/n' }} · {{ p.dpi || '—' }} · {{ p.municipio }}, {{ p.departamento }}</small>
                            </button>
                        </div>
                    </template>
                    <div v-else class="note">No reconocí filtros en la pregunta. Mencione un departamento, un estado (en nómina, en trámite, suspendidos, fallecidos), una edad o un año.</div>
                </div>
                <div class="foot"><span>Enter aplica los filtros al tablero · Esc cierra</span><span>Reglas locales: departamento, municipio, estado, nómina, visita, edad, año y antigüedad</span></div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import EstadoBadge from '../common/EstadoBadge.vue';
import Icono from '../common/Icono.vue';
import { useToast } from '../../composables/useToast';
import expedienteService from '../../services/expedienteService';
import { useAuthStore } from '../../stores/auth';
import { useExpedienteStore } from '../../stores/expediente';
import { useFiltrosStore } from '../../stores/filtros';
import { useTableroStore } from '../../stores/tablero';
import { useUiStore } from '../../stores/ui';
import { EJEMPLOS, parseQ } from '../../utils/consulta';
import { countWith } from '../../utils/cubo';
import { fmt, pc } from '../../utils/formato';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();
const tablero = useTableroStore();
const filtros = useFiltrosStore();
const ui = useUiStore();
const expediente = useExpedienteStore();
const { toast } = useToast();

const entrada = ref(null);
const texto = ref('');
const consulta = ref(null); // { f, tags }
const conteo = ref({ c: 0, p: 0 });
const personas = ref([]);
const totalPersonas = ref('');
let temporizador = 0;
let secuencia = 0;

function cerrar() {
    ui.preguntar = false;
}

function probar(e) {
    texto.value = e;
    interpretar();
    entrada.value.focus();
}

function programar() {
    clearTimeout(temporizador);
    temporizador = setTimeout(interpretar, 220);
}

function interpretar() {
    const q = texto.value.trim();
    personas.value = [];
    if (!q) { consulta.value = null; return; }
    const r = parseQ(q, tablero.cube);
    consulta.value = r;
    if (r.tags.length) {
        const vacios = Object.fromEntries(Object.keys(filtros.f).map((k) => [k, null]));
        conteo.value = countWith(tablero.cube, { ...vacios, ...r.f });
        return;
    }
    // sin filtros reconocidos: se busca como nombre / documento
    if (q.length >= 3 && auth.puede('beneficiarios.ver')) {
        const token = ++secuencia;
        expedienteService.listar({ q, limite: 6, orden: 'relevancia' }).then((res) => {
            if (token !== secuencia) return;
            personas.value = res.items || [];
            totalPersonas.value = res.total_es_minimo ? 'más de 10,000' : fmt(res.total);
        }).catch(() => {});
    }
}

function enter() {
    clearTimeout(temporizador);
    interpretar();
    aplicar();
}

function aplicar() {
    if (!consulta.value || !consulta.value.tags.length) return;
    filtros.aplicar(consulta.value.f);
    cerrar();
    if (!['dashboard', 'territorio', 'beneficiarios'].includes(route.name)) router.push('/dashboard');
    toast('Consulta aplicada al tablero');
}

function abrirExp(id) {
    cerrar();
    expediente.abrir(id);
}

onMounted(() => entrada.value.focus());
</script>
