<template>
  <div>
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
        <div>
            <h2 class="font-headline-lg text-primary mb-2">Inversionistas</h2>
            <p class="font-body-lg text-on-surface-variant">Gestión de socios capitalistas y aportaciones.</p>
        </div>
        <div class="flex items-center gap-2">
            <button class="bg-primary text-on-primary font-title-md py-2 px-4 rounded hover:bg-primary-fixed-dim transition-colors shadow-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">person_add</span>
                Nuevo Inversionista
            </button>
        </div>
    </div>

    <!-- Overview Grid -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-surface-container-high p-6 rounded-xl border border-outline-variant relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary/5 rounded-full group-hover:bg-primary/10 transition-colors"></div>
            <h3 class="font-label-caps text-on-surface-variant mb-4">Capital Activo</h3>
            <p class="font-headline-lg text-on-surface mb-1">Q8,500,000</p>
            <div class="flex items-center gap-2 text-primary font-body-sm">
                <span class="material-symbols-outlined text-[16px]">arrow_upward</span>
                <span>+2.1% este mes</span>
            </div>
        </div>

        <div class="bg-surface-container-high p-6 rounded-xl border border-outline-variant relative overflow-hidden group">
            <h3 class="font-label-caps text-on-surface-variant mb-4">Total de Inversionistas</h3>
            <p class="font-headline-lg text-on-surface mb-1">45</p>
            <div class="flex items-center gap-2 text-on-surface-variant font-body-sm">
                <span>Socios de clase A y B</span>
            </div>
        </div>

        <div class="bg-surface-container-high p-6 rounded-xl border border-outline-variant relative overflow-hidden group">
            <h3 class="font-label-caps text-on-surface-variant mb-4">Retorno Promedio</h3>
            <p class="font-headline-lg text-on-surface mb-1">14.2%</p>
            <div class="flex items-center gap-2 text-on-surface-variant font-body-sm">
                <span>Anualizado</span>
            </div>
        </div>
    </section>

    <!-- Empty State / Placeholder -->
    <div class="glass-panel rounded-xl p-12 flex flex-col items-center justify-center text-center border border-surface-variant">
        <div class="w-20 h-20 bg-surface-container rounded-full flex items-center justify-center mb-4 border border-outline-variant">
            <span class="material-symbols-outlined text-4xl text-primary">engineering</span>
        </div>
        <h3 class="font-title-md text-on-surface mb-2">Módulo en Desarrollo</h3>
        <p class="font-body-sm text-on-surface-variant max-w-md">La lista detallada de inversionistas, estados de cuenta y proyecciones de retorno estarán disponibles en la próxima fase del sistema.</p>
    </div>
  </div>
</template>

<style scoped>
.glass-panel {
    background: linear-gradient(145deg, rgba(32, 32, 31, 0.7) 0%, rgba(28, 27, 27, 0.9) 100%);
    backdrop-filter: blur(10px);
}
</style>
