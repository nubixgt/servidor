<script setup>
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const caseId = route.params.id || '#UBA-2024-8842'
</script>

<template>
  <div class="animate-in fade-in duration-500">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
      <div>
        <nav class="flex items-center gap-2 text-xs font-bold text-outline uppercase tracking-widest mb-2">
          <button @click="router.push('/tech/bandeja')" class="hover:text-primary transition-colors">Casos</button>
          <span class="material-symbols-outlined text-[10px]">chevron_right</span>
          <span class="text-primary">Vista Detallada</span>
        </nav>
        <h2 class="font-headline text-4xl font-extrabold text-on-surface tracking-tight">{{ caseId }}</h2>
        <p class="text-on-surface-variant mt-1 font-body">Reportado el 24 Oct, 2024 • San José Sector 4</p>
      </div>
      <div class="flex items-center gap-3">
        <span class="px-4 py-2 rounded-full bg-error-container text-on-error-container text-xs font-bold flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-error animate-pulse"></span>Prioridad Alta
        </span>
        <span class="px-4 py-2 rounded-full bg-primary-fixed text-on-primary-fixed text-xs font-bold flex items-center gap-2">
          <span class="material-symbols-outlined text-sm">pending_actions</span>En Progreso
        </span>
        <button class="bg-gradient-to-br from-primary to-primary-container text-white px-6 py-2.5 rounded-full font-bold shadow-lg hover:shadow-primary/20 transition-all flex items-center gap-2">
          <span class="material-symbols-outlined text-sm">check_circle</span>Resolver Caso
        </button>
      </div>
    </div>

    <!-- Bento Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- Left: Case Info -->
      <div class="lg:col-span-3 space-y-6">
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/20 shadow-sm">
          <h3 class="font-headline font-bold text-lg mb-4 flex items-center gap-2 text-primary">
            <span class="material-symbols-outlined">person</span>Denunciante
          </h3>
          <div class="space-y-4">
            <div>
              <label class="text-[10px] font-bold text-outline uppercase tracking-wider block">Nombre Completo</label>
              <p class="text-sm font-semibold text-on-surface">Elena Rodriguez</p>
            </div>
            <div>
              <label class="text-[10px] font-bold text-outline uppercase tracking-wider block">Contacto</label>
              <p class="text-sm font-semibold text-on-surface">+506 8832-1109</p>
            </div>
            <div>
              <label class="text-[10px] font-bold text-outline uppercase tracking-wider block">Usuario Verificado</label>
              <div class="mt-1 flex items-center gap-1 text-tertiary font-bold text-xs">
                <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1">verified</span>
                Identidad Confirmada
              </div>
            </div>
          </div>
        </div>

        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/20 shadow-sm">
          <h3 class="font-headline font-bold text-lg mb-4 flex items-center gap-2 text-primary">
            <span class="material-symbols-outlined">pets</span>Datos del Animal
          </h3>
          <div class="space-y-4">
            <div class="flex justify-between items-center bg-surface-container-low p-3 rounded-lg">
              <span class="text-sm text-on-surface-variant">Especie</span>
              <span class="text-sm font-bold px-3 py-1 bg-white rounded-md shadow-sm">Canino</span>
            </div>
            <div class="flex justify-between items-center bg-surface-container-low p-3 rounded-lg">
              <span class="text-sm text-on-surface-variant">Cantidad</span>
              <span class="text-sm font-bold px-3 py-1 bg-white rounded-md shadow-sm">02</span>
            </div>
            <div>
              <label class="text-[10px] font-bold text-outline uppercase tracking-wider block mb-2">Descripción Detallada</label>
              <p class="text-sm text-on-surface-variant leading-relaxed italic">
                "Dos perros de tamaño mediano encontrados en un lote abandonado. Uno parece tener una herida en la pata. Falta de agua o refugio."
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Center: Timeline -->
      <div class="lg:col-span-6">
        <div class="bg-surface-container-lowest rounded-xl p-8 border border-outline-variant/20 shadow-sm h-full">
          <div class="flex items-center justify-between mb-8">
            <h3 class="font-headline font-extrabold text-xl flex items-center gap-3">
              <span class="w-2 h-8 bg-secondary rounded-full"></span>Historial y Línea de Tiempo
            </h3>
            <button class="text-primary text-sm font-bold flex items-center gap-1 hover:underline">
              <span class="material-symbols-outlined text-sm">filter_list</span>Filtrar
            </button>
          </div>

          <div class="relative space-y-8 before:content-[''] before:absolute before:left-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-container-highest">
            <div class="relative pl-12">
              <div class="absolute left-[5px] top-1 w-5 h-5 rounded-full bg-surface-container-lowest border-4 border-primary shadow-sm z-10"></div>
              <div class="bg-surface-container-low/30 p-4 rounded-xl border border-outline-variant/20">
                <div class="flex justify-between items-start mb-2">
                  <h4 class="text-sm font-bold">Caso Asignado a Marcus Thorne</h4>
                  <span class="text-[10px] font-medium text-outline">HOY, 09:15 AM</span>
                </div>
                <p class="text-sm text-on-surface-variant">Asignación automática del sistema basada en proximidad y nivel de urgencia.</p>
              </div>
            </div>
            <div class="relative pl-12">
              <div class="absolute left-[5px] top-1 w-5 h-5 rounded-full bg-surface-container-lowest border-4 border-tertiary shadow-sm z-10"></div>
              <div class="bg-surface-container-low/30 p-4 rounded-xl border border-outline-variant/20">
                <div class="flex justify-between items-start mb-2">
                  <div class="flex items-center gap-2">
                    <h4 class="text-sm font-bold">Nuevo Comentario</h4>
                    <span class="text-[10px] px-2 py-0.5 bg-tertiary-fixed text-on-tertiary-fixed rounded font-bold uppercase">Nota de Campo</span>
                  </div>
                  <span class="text-[10px] font-medium text-outline">OCT 24, 04:30 PM</span>
                </div>
                <p class="text-sm text-on-surface-variant mb-3">"Visita inicial al sitio completada. Se les dio raciones de emergencia."</p>
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded bg-surface-container-high flex items-center justify-center">
                    <span class="material-symbols-outlined text-sm text-outline">picture_as_pdf</span>
                  </div>
                  <span class="text-xs font-bold text-primary cursor-pointer hover:underline">inspeccion_inicial_v1.pdf</span>
                </div>
              </div>
            </div>
            <div class="relative pl-12">
              <div class="absolute left-[5px] top-1 w-5 h-5 rounded-full bg-surface-container-lowest border-4 border-outline-variant shadow-sm z-10"></div>
              <div class="bg-surface-container-low/30 p-4 rounded-xl border border-outline-variant/20">
                <div class="flex justify-between items-start mb-2">
                  <h4 class="text-sm font-bold">Caso Abierto</h4>
                  <span class="text-[10px] font-medium text-outline">OCT 24, 02:00 PM</span>
                </div>
                <p class="text-sm text-on-surface-variant">Reporte recibido vía App Ciudadana #4492.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Evidence, Map, Form -->
      <div class="lg:col-span-3 space-y-6">
        <!-- Evidence Gallery -->
        <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/20 shadow-sm">
          <h3 class="font-headline font-bold text-md mb-4 flex items-center gap-2 text-primary uppercase tracking-wide text-xs">
            <span class="material-symbols-outlined text-lg">photo_library</span>Galería de Evidencia
          </h3>
          <div class="grid grid-cols-2 gap-2">
            <img class="w-full h-24 object-cover rounded-lg hover:scale-105 transition-transform cursor-pointer"
              src="https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&q=80&w=400&h=300"
              alt="Evidencia 1" referrerpolicy="no-referrer" />
            <img class="w-full h-24 object-cover rounded-lg hover:scale-105 transition-transform cursor-pointer"
              src="https://images.unsplash.com/photo-1537151608804-ea6f11840315?auto=format&fit=crop&q=80&w=400&h=300"
              alt="Evidencia 2" referrerpolicy="no-referrer" />
            <div class="w-full h-24 bg-surface-container-high rounded-lg flex flex-col items-center justify-center text-outline group cursor-pointer border-2 border-dashed border-outline-variant/50 hover:border-primary/50 transition-colors">
              <span class="material-symbols-outlined">add</span>
              <span class="text-[10px] font-bold">AÑADIR</span>
            </div>
          </div>
        </div>

        <!-- Map Location -->
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-sm overflow-hidden">
          <div class="p-4 flex items-center justify-between bg-surface-container-low/50">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-primary">location_on</span>
              <span class="text-xs font-bold">SAN JOSÉ, SECTOR 4</span>
            </div>
            <button class="text-[10px] font-bold text-primary hover:underline">EXPANDIR</button>
          </div>
          <div class="h-40 bg-surface-container-high relative">
            <img class="w-full h-full object-cover opacity-60 grayscale"
              src="https://images.unsplash.com/photo-1524661135-423995f22d0b?auto=format&fit=crop&q=80&w=600&h=400"
              alt="Mapa" referrerpolicy="no-referrer" />
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center animate-ping"></div>
              <div class="absolute w-4 h-4 rounded-full bg-primary border-2 border-white shadow-lg"></div>
            </div>
          </div>
        </div>

        <!-- Action Form -->
        <div class="bg-surface-container-low rounded-xl p-6 border border-primary/10 shadow-sm">
          <h3 class="font-headline font-bold text-md mb-4 flex items-center gap-2 text-primary">
            <span class="material-symbols-outlined">edit_note</span>Actualizar Caso
          </h3>
          <form class="space-y-4">
            <textarea class="w-full h-24 rounded-xl border-none bg-surface-container-lowest focus:ring-2 focus:ring-secondary text-sm placeholder:text-outline p-3 shadow-sm" placeholder="Escribe notas u observaciones técnicas..."></textarea>
            <div class="border-2 border-dashed border-outline-variant/50 rounded-xl p-4 flex flex-col items-center justify-center bg-surface-container-lowest group hover:border-primary transition-colors cursor-pointer">
              <span class="material-symbols-outlined text-outline group-hover:text-primary mb-2">upload_file</span>
              <p class="text-[10px] font-bold text-outline uppercase">Soltar PDF Aquí</p>
            </div>
            <button class="w-full py-3 bg-primary text-white font-bold rounded-full text-sm shadow-md hover:bg-primary-container transition-all" type="button">
              Publicar Actualización
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
