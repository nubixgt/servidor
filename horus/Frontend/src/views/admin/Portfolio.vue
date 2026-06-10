<template>
  <div>
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
        <div>
            <h2 class="font-headline-lg text-primary mb-2 tracking-wide font-medium">Análisis de Cartera</h2>
            <p class="font-body-sm text-on-surface-variant">Visualización de riesgos, proyecciones y salud del portafolio.</p>
        </div>
        <div class="flex items-center gap-2">
            <button class="bg-surface-container-high/50 border border-white/10 text-on-surface font-body-sm py-2 px-5 rounded-xl hover:border-primary/50 hover:text-primary transition-colors shadow-[0_8px_32px_rgba(0,0,0,0.1)] backdrop-blur-sm flex items-center gap-2 font-medium">
                <span class="material-symbols-outlined text-lg">download</span>
                Exportar Reporte
            </button>
        </div>
    </div>

    <!-- Empty State / Placeholder -->
    <div class="bg-surface-container-high/30 backdrop-blur-xl rounded-2xl p-12 flex flex-col items-center justify-center text-center border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] mt-12">
        <div class="w-20 h-20 bg-surface-container/50 rounded-full flex items-center justify-center mb-4 border border-outline-variant/30">
            <span class="material-symbols-outlined text-4xl text-primary drop-shadow-[0_0_10px_rgba(233,193,118,0.5)]">engineering</span>
        </div>
        <h3 class="font-body-lg text-on-surface mb-2 font-medium">Módulo en Desarrollo</h3>
        <p class="font-body-sm text-on-surface-variant max-w-md">El análisis avanzado y los algoritmos de predicción de morosidad se habilitarán en la siguiente iteración.</p>
    </div>
  </div>
</template>
