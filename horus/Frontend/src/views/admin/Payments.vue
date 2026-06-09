<template>
  <div>
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
        <div>
            <h2 class="font-headline-lg text-primary mb-2">Pagos e Ingresos</h2>
            <p class="font-body-lg text-on-surface-variant">Control de cuotas, desembolsos y transferencias.</p>
        </div>
        <div class="flex items-center gap-2">
            <button class="bg-primary text-on-primary font-title-md py-2 px-4 rounded hover:bg-primary-fixed-dim transition-colors shadow-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">payments</span>
                Registrar Pago
            </button>
        </div>
    </div>

    <!-- Empty State / Placeholder -->
    <div class="glass-panel rounded-xl p-12 flex flex-col items-center justify-center text-center border border-surface-variant mt-12">
        <div class="w-20 h-20 bg-surface-container rounded-full flex items-center justify-center mb-4 border border-outline-variant">
            <span class="material-symbols-outlined text-4xl text-primary">engineering</span>
        </div>
        <h3 class="font-title-md text-on-surface mb-2">Módulo en Desarrollo</h3>
        <p class="font-body-sm text-on-surface-variant max-w-md">El histórico de pagos, comprobantes y validaciones de caja se activarán una vez que el backend esté conectado.</p>
    </div>
  </div>
</template>

<style scoped>
.glass-panel {
    background: linear-gradient(145deg, rgba(32, 32, 31, 0.7) 0%, rgba(28, 27, 27, 0.9) 100%);
    backdrop-filter: blur(10px);
}
</style>
