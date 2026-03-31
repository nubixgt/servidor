<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const selectedCase = ref(null);
const router = useRouter();

const navigate = (path) => {
  router.push(path);
};
</script>

<template>
  <div class="animate-in fade-in duration-500 space-y-8">
    <!-- Hero Greeting -->
    <section>
      <h2 class="font-headline text-3xl font-extrabold text-on-surface tracking-tight">Panel de Revisión Técnica</h2>
      <p class="text-on-surface-variant mt-1">Bienvenido de nuevo. Tienes 12 denuncias esperando revisión.</p>
    </section>

    <!-- Key Cards Bento Grid -->
    <section class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-outline-variant/10 flex flex-col justify-between">
        <div class="flex justify-between items-start">
          <div class="w-12 h-12 rounded-lg bg-primary-fixed flex items-center justify-center text-primary">
            <span class="material-symbols-outlined">folder_open</span>
          </div>
          <span class="text-xs font-bold text-outline">TOTAL</span>
        </div>
        <div class="mt-4">
          <h3 class="text-3xl font-bold font-headline">48</h3>
          <p class="text-sm text-outline">Pendientes</p>
        </div>
      </div>
      
      <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-outline-variant/10 flex flex-col justify-between">
        <div class="flex justify-between items-start">
          <div class="w-12 h-12 rounded-lg bg-error-container flex items-center justify-center text-error">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1">alarm</span>
          </div>
          <span class="text-xs font-bold text-error">CRÍTICO</span>
        </div>
        <div class="mt-4">
          <h3 class="text-3xl font-bold font-headline text-error">09</h3>
          <p class="text-sm text-outline">+5 días de espera</p>
        </div>
      </div>

      <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-outline-variant/10 flex flex-col justify-between">
        <div class="flex justify-between items-start">
          <div class="w-12 h-12 rounded-lg bg-tertiary-fixed flex items-center justify-center text-tertiary">
            <span class="material-symbols-outlined">task_alt</span>
          </div>
          <span class="text-xs font-bold text-tertiary">HOY</span>
        </div>
        <div class="mt-4">
          <h3 class="text-3xl font-bold font-headline text-tertiary">14</h3>
          <p class="text-sm text-outline">Procesadas</p>
        </div>
      </div>

      <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-outline-variant/10 flex flex-col justify-between">
        <div class="flex justify-between items-start">
          <div class="w-12 h-12 rounded-lg bg-surface-container-highest flex items-center justify-center text-on-surface-variant">
            <span class="material-symbols-outlined">visibility_off</span>
          </div>
          <span class="text-xs font-bold text-outline">NUEVAS</span>
        </div>
        <div class="mt-4">
          <h3 class="text-3xl font-bold font-headline">06</h3>
          <p class="text-sm text-outline">Sin revisar</p>
        </div>
      </div>
    </section>

    <!-- Inbox & Actions Area -->
    <section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden border border-outline-variant/10">
      <!-- Filters Header -->
      <div class="p-6 flex flex-wrap items-center justify-between gap-4 border-b border-outline-variant/5 bg-surface-container-low/50">
        <h3 class="font-headline text-xl font-bold flex items-center gap-2">
          <span class="material-symbols-outlined text-secondary">inbox</span>
          Bandeja de Casos
        </h3>
        <div class="flex items-center gap-3">
          <div class="flex bg-surface-container-low rounded-lg p-1">
            <button class="px-4 py-1.5 text-xs font-bold bg-white rounded-md shadow-sm text-primary">Todos</button>
            <button class="px-4 py-1.5 text-xs font-medium text-outline-variant hover:text-on-surface transition-colors">Urgentes</button>
            <button class="px-4 py-1.5 text-xs font-medium text-outline-variant hover:text-on-surface transition-colors">Antigüedad</button>
          </div>
          <button class="flex items-center gap-2 px-4 py-2 bg-surface-container-high rounded-lg text-sm font-medium hover:bg-surface-container-highest transition-colors">
            <span class="material-symbols-outlined text-sm">filter_list</span>
            Filtros
          </button>
        </div>
      </div>

      <!-- Case Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="text-[11px] uppercase tracking-widest text-outline font-bold border-b border-outline-variant/10">
              <th class="px-8 py-4">ID Expediente</th>
              <th class="px-6 py-4">Fecha Recepción</th>
              <th class="px-6 py-4">Especie</th>
              <th class="px-6 py-4">Municipio</th>
              <th class="px-6 py-4 text-center">Estado Legal</th>
              <th class="px-8 py-4 text-right">Acción</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/5">
            <tr class="hover:bg-surface-container-low transition-colors group">
              <td class="px-8 py-5">
                <div class="flex items-center gap-3">
                  <div class="w-2 h-2 rounded-full bg-error"></div>
                  <span class="font-bold text-sm">#2023-UB-0812</span>
                </div>
              </td>
              <td class="px-6 py-5 text-sm text-on-surface-variant">14 Oct, 2023 (10:45)</td>
              <td class="px-6 py-5">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-fixed text-on-primary-fixed text-xs font-medium">
                  <span class="material-symbols-outlined text-sm">pets</span>
                  Canino
                </span>
              </td>
              <td class="px-6 py-5 text-sm text-on-surface-variant font-medium">Villa Nueva</td>
              <td class="px-6 py-5 text-center">
                <span class="px-3 py-1 rounded-full bg-error-container text-on-error-container text-[10px] font-black uppercase tracking-tighter">Pendiente Crítico</span>
              </td>
              <td class="px-8 py-5 text-right">
                <button 
                  @click="selectedCase = '#2023-UB-0812'"
                  class="bg-gradient-to-br from-primary to-primary-container text-white px-5 py-2 rounded-full text-xs font-bold shadow-md hover:scale-105 active:opacity-80 transition-all flex items-center gap-2 ml-auto"
                >
                  <span>Revisar Caso</span>
                  <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
              </td>
            </tr>
            
            <tr class="hover:bg-surface-container-low transition-colors group">
              <td class="px-8 py-5">
                <div class="flex items-center gap-3">
                  <div class="w-2 h-2 rounded-full bg-primary"></div>
                  <span class="font-bold text-sm">#2023-UB-0815</span>
                </div>
              </td>
              <td class="px-6 py-5 text-sm text-on-surface-variant">15 Oct, 2023 (08:20)</td>
              <td class="px-6 py-5">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-fixed text-on-primary-fixed text-xs font-medium">
                  <span class="material-symbols-outlined text-sm">pets</span>
                  Felino
                </span>
              </td>
              <td class="px-6 py-5 text-sm text-on-surface-variant font-medium">Guatemala (Zona 10)</td>
              <td class="px-6 py-5 text-center">
                <span class="px-3 py-1 rounded-full bg-primary-fixed text-on-primary-fixed text-[10px] font-black uppercase tracking-tighter">En Revisión</span>
              </td>
              <td class="px-8 py-5 text-right">
                <button 
                  @click="selectedCase = '#2023-UB-0815'"
                  class="bg-surface-container-high text-on-surface px-5 py-2 rounded-full text-xs font-bold shadow-sm hover:bg-surface-container-highest transition-all flex items-center gap-2 ml-auto border border-outline-variant/20"
                >
                  <span>Continuar</span>
                  <span class="material-symbols-outlined text-sm">resume</span>
                </button>
              </td>
            </tr>

            <tr class="hover:bg-surface-container-low transition-colors group">
              <td class="px-8 py-5">
                <div class="flex items-center gap-3">
                  <div class="w-2 h-2 rounded-full bg-outline-variant"></div>
                  <span class="font-bold text-sm">#2023-UB-0819</span>
                </div>
              </td>
              <td class="px-6 py-5 text-sm text-on-surface-variant">15 Oct, 2023 (14:15)</td>
              <td class="px-6 py-5">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-fixed text-on-primary-fixed text-xs font-medium">
                  <span class="material-symbols-outlined text-sm">cruelty_free</span>
                  Ave Exótica
                </span>
              </td>
              <td class="px-6 py-5 text-sm text-on-surface-variant font-medium">Mixco</td>
              <td class="px-6 py-5 text-center">
                <span class="px-3 py-1 rounded-full bg-surface-container-highest text-on-surface-variant text-[10px] font-black uppercase tracking-tighter">Sin Asignar</span>
              </td>
              <td class="px-8 py-5 text-right">
                <button 
                  @click="selectedCase = '#2023-UB-0819'"
                  class="bg-gradient-to-br from-primary to-primary-container text-white px-5 py-2 rounded-full text-xs font-bold shadow-md hover:scale-105 transition-all flex items-center gap-2 ml-auto"
                >
                  <span>Revisar Caso</span>
                  <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
              </td>
            </tr>

            <tr class="hover:bg-surface-container-low transition-colors group">
              <td class="px-8 py-5">
                <div class="flex items-center gap-3">
                  <div class="w-2 h-2 rounded-full bg-tertiary"></div>
                  <span class="font-bold text-sm">#2023-UB-0805</span>
                </div>
              </td>
              <td class="px-6 py-5 text-sm text-on-surface-variant">12 Oct, 2023 (16:30)</td>
              <td class="px-6 py-5">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-fixed text-on-primary-fixed text-xs font-medium">
                  <span class="material-symbols-outlined text-sm">pets</span>
                  Canino
                </span>
              </td>
              <td class="px-6 py-5 text-sm text-on-surface-variant font-medium">Amatitlán</td>
              <td class="px-6 py-5 text-center">
                <span class="px-3 py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed text-[10px] font-black uppercase tracking-tighter">Validado</span>
              </td>
              <td class="px-8 py-5 text-right">
                <button 
                  @click="selectedCase = '#2023-UB-0805'"
                  class="text-secondary px-5 py-2 rounded-full text-xs font-bold hover:bg-secondary-container transition-all flex items-center gap-2 ml-auto"
                >
                  <span>Ver Detalles</span>
                  <span class="material-symbols-outlined text-sm">visibility</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination / Footer Table -->
      <div class="p-6 border-t border-outline-variant/5 flex items-center justify-between text-xs text-outline font-medium bg-surface-container-low/50">
        <p>Mostrando 4 de 12 expedientes pendientes</p>
        <div class="flex items-center gap-2">
          <button class="w-8 h-8 rounded-full border border-outline-variant/20 flex items-center justify-center hover:bg-white transition-colors">
            <span class="material-symbols-outlined text-sm">chevron_left</span>
          </button>
          <span class="px-2 font-bold text-on-surface">Página 1 de 3</span>
          <button class="w-8 h-8 rounded-full border border-outline-variant/20 flex items-center justify-center hover:bg-white transition-colors">
            <span class="material-symbols-outlined text-sm">chevron_right</span>
          </button>
        </div>
      </div>
    </section>

    <!-- Asymmetric Lower Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Case Progress Chart Placeholder -->
      <div class="lg:col-span-2 bg-surface-container-lowest p-8 rounded-xl border border-outline-variant/10 shadow-sm relative overflow-hidden min-h-[300px]">
        <div class="relative z-10">
          <h4 class="font-headline text-lg font-bold mb-6">Eficiencia de Resolución (Semanal)</h4>
          <div class="flex items-end gap-6 h-40">
            <div class="flex-1 flex flex-col items-center gap-2">
              <div class="w-full bg-primary/10 rounded-t-lg relative group h-24">
                <div class="absolute bottom-0 w-full bg-primary rounded-t-lg h-16 group-hover:h-20 transition-all duration-300"></div>
              </div>
              <span class="text-[10px] font-bold text-outline">LUN</span>
            </div>
            <div class="flex-1 flex flex-col items-center gap-2">
              <div class="w-full bg-primary/10 rounded-t-lg relative group h-24">
                <div class="absolute bottom-0 w-full bg-primary rounded-t-lg h-22 group-hover:h-24 transition-all duration-300"></div>
              </div>
              <span class="text-[10px] font-bold text-outline">MAR</span>
            </div>
            <div class="flex-1 flex flex-col items-center gap-2">
              <div class="w-full bg-primary/10 rounded-t-lg relative group h-24">
                <div class="absolute bottom-0 w-full bg-primary rounded-t-lg h-12 group-hover:h-14 transition-all duration-300"></div>
              </div>
              <span class="text-[10px] font-bold text-outline">MIE</span>
            </div>
            <div class="flex-1 flex flex-col items-center gap-2">
              <div class="w-full bg-primary/10 rounded-t-lg relative group h-24">
                <div class="absolute bottom-0 w-full bg-primary rounded-t-lg h-20 group-hover:h-22 transition-all duration-300"></div>
              </div>
              <span class="text-[10px] font-bold text-outline">JUE</span>
            </div>
            <div class="flex-1 flex flex-col items-center gap-2">
              <div class="w-full bg-primary/10 rounded-t-lg relative group h-24">
                <div class="absolute bottom-0 w-full bg-primary rounded-t-lg h-18 group-hover:h-19 transition-all duration-300"></div>
              </div>
              <span class="text-[10px] font-bold text-outline">VIE</span>
            </div>
          </div>
          
          <div class="mt-8 grid grid-cols-2 gap-4">
            <div class="bg-surface-container-low p-4 rounded-lg">
              <p class="text-[10px] text-outline font-bold uppercase mb-1">Promedio de Revisión</p>
              <p class="text-xl font-bold font-headline">1.4 <span class="text-sm font-normal text-outline">días/caso</span></p>
            </div>
            <div class="bg-surface-container-low p-4 rounded-lg">
              <p class="text-[10px] text-outline font-bold uppercase mb-1">Meta Cumplida</p>
              <p class="text-xl font-bold font-headline text-tertiary">92%</p>
            </div>
          </div>
        </div>
        <!-- Decorative Graphic -->
        <div class="absolute -right-16 -bottom-16 w-64 h-64 bg-primary/5 rounded-full blur-3xl"></div>
      </div>

      <!-- Recent Activity Feed -->
      <div class="bg-surface-container-lowest p-8 rounded-xl border border-outline-variant/10 shadow-sm">
        <h4 class="font-headline text-lg font-bold mb-6">Actividad Reciente</h4>
        <div class="space-y-6">
          <div class="flex gap-4">
            <div class="w-2 h-2 rounded-full bg-tertiary mt-2"></div>
            <div>
              <p class="text-sm font-bold">Expediente #0798 aprobado</p>
              <p class="text-xs text-outline">Revisión técnica completada con éxito.</p>
              <p class="text-[10px] text-outline-variant mt-1">Hace 24 minutos</p>
            </div>
          </div>
          <div class="flex gap-4">
            <div class="w-2 h-2 rounded-full bg-primary mt-2"></div>
            <div>
              <p class="text-sm font-bold">Nuevo caso asignado</p>
              <p class="text-xs text-outline">Denuncia desde Mixco (Crueldad Animal).</p>
              <p class="text-[10px] text-outline-variant mt-1">Hace 2 horas</p>
            </div>
          </div>
          <div class="flex gap-4">
            <div class="w-2 h-2 rounded-full bg-error mt-2"></div>
            <div>
              <p class="text-sm font-bold">Alerta de Plazo</p>
              <p class="text-xs text-outline">El caso #0744 vence en 2 horas.</p>
              <p class="text-[10px] text-outline-variant mt-1">Hace 5 horas</p>
            </div>
          </div>
          <button class="w-full py-3 rounded-xl border-2 border-dashed border-outline-variant/20 text-outline text-xs font-bold hover:bg-surface-container-low transition-all">
            Ver historial completo
          </button>
        </div>
      </div>
    </div>

    <!-- Contextual FAB -->
    <button class="fixed bottom-8 right-8 w-14 h-14 bg-gradient-to-br from-primary to-primary-container text-white rounded-full shadow-2xl flex items-center justify-center hover:scale-110 active:scale-95 transition-transform z-50">
      <span class="material-symbols-outlined" style="font-size: 28px">add</span>
    </button>

    <!-- Case History Modal -->
    <div v-if="selectedCase" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="bg-surface-container-lowest rounded-2xl shadow-ambient w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-200">
        <div class="p-6 border-b border-outline-variant/20 flex justify-between items-center bg-surface-container-low/50">
          <div>
            <h3 class="font-headline font-extrabold text-xl text-on-surface">Historial del Caso</h3>
            <p class="text-sm text-primary font-bold mt-1">{{ selectedCase }}</p>
          </div>
          <button 
            @click="selectedCase = null"
            class="w-8 h-8 rounded-full bg-surface-container-high flex items-center justify-center text-on-surface-variant hover:bg-surface-container-highest transition-colors"
          >
            <span class="material-symbols-outlined text-sm">close</span>
          </button>
        </div>
        
        <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
          <div class="relative border-l-2 border-surface-container-high ml-3 space-y-8 pb-4">
            <div class="relative pl-8">
              <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-surface-container-lowest border-4 border-primary shadow-sm"></div>
              <div class="flex justify-between items-start mb-1">
                <h5 class="font-bold text-primary">Inspección de Campo</h5>
                <span class="text-xs text-outline font-medium">Actual</span>
              </div>
              <p class="text-sm text-on-surface-variant mb-2">Asignado a Carlos Méndez. Pendiente de visita al lugar.</p>
            </div>
            <div class="relative pl-8">
              <div class="absolute -left-[7px] top-1 w-3 h-3 rounded-full bg-tertiary"></div>
              <div class="flex justify-between items-start mb-1">
                <h5 class="font-bold text-on-surface">Revisión Inicial</h5>
                <span class="text-xs text-outline font-medium">Ayer, 14:30</span>
              </div>
              <div class="bg-surface-container-lowest p-3 rounded-lg border border-outline-variant/20 mt-2">
                <p class="text-sm text-on-surface-variant">"La denuncia cumple con los requisitos. Se aprueba para inspección de campo urgente debido a la falta de agua."</p>
              </div>
            </div>
            <div class="relative pl-8">
              <div class="absolute -left-[7px] top-1 w-3 h-3 rounded-full bg-outline-variant"></div>
              <div class="flex justify-between items-start mb-1">
                <h5 class="font-bold text-on-surface">Recepción de Denuncia</h5>
                <span class="text-xs text-outline font-medium">Hace 2 días</span>
              </div>
              <div class="bg-surface-container-lowest p-3 rounded-lg border border-outline-variant/20 mt-2">
                <p class="text-sm text-on-surface-variant">Denuncia ciudadana ingresada vía portal web. Evidencia fotográfica adjunta.</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="p-6 border-t border-outline-variant/20 bg-surface-container-low/50 flex justify-end gap-3">
          <button 
            @click="selectedCase = null"
            class="px-6 py-2 rounded-full border border-outline-variant/30 text-on-surface-variant font-bold hover:bg-surface-container-high transition-colors text-sm"
          >
            Cerrar
          </button>
          <button 
            @click="navigate(`/tech/caso/${selectedCase?.replace('#', '')}`)"
            class="bg-gradient-to-br from-primary to-primary-container text-white px-6 py-2 rounded-full text-sm font-bold shadow-md hover:scale-105 transition-all flex items-center gap-2"
          >
            <span>Ir al Expediente Completo</span>
            <span class="material-symbols-outlined text-sm">arrow_forward</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
