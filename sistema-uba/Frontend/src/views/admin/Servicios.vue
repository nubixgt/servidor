<template>
  <div class="animate-in fade-in duration-500 space-y-8">
    <!-- Header Section -->
    <div class="flex justify-between items-end">
      <div>
        <nav class="flex gap-2 text-xs text-on-surface-variant mb-2">
          <span>Administración</span>
          <span>/</span>
          <span class="text-primary font-semibold">Servicios Veterinarios</span>
        </nav>
        <h2 class="text-4xl font-extrabold font-headline text-on-surface tracking-tight">Servicios Autorizados</h2>
        <p class="text-on-surface-variant mt-1">Gestión de clínicas y centros de atención veterinaria registrados en el sistema.</p>
      </div>
      <button class="bg-gradient-to-br from-primary to-primary-container text-white px-6 py-3 rounded-full font-bold flex items-center gap-2 shadow-lg shadow-primary/20 hover:scale-105 active:scale-95 transition-all">
        <span class="material-symbols-outlined">add</span>
        <span>Add New Service</span>
      </button>
    </div>

    <!-- Dashboard Stats Tonal Shift -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-surface-container-lowest p-6 rounded-xl border border-outline-variant/10 shadow-sm">
        <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Total Clínicas</p>
        <div class="flex items-baseline gap-2">
          <h3 class="text-3xl font-black text-primary">124</h3>
          <span class="text-tertiary text-xs font-bold flex items-center"><span class="material-symbols-outlined text-xs">trending_up</span> +3%</span>
        </div>
      </div>
      <div class="bg-surface-container-lowest p-6 rounded-xl border border-outline-variant/10 shadow-sm">
        <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Activas</p>
        <div class="flex items-baseline gap-2">
          <h3 class="text-3xl font-black text-tertiary">118</h3>
          <span class="text-on-surface-variant text-[10px]">95.1% Operatividad</span>
        </div>
      </div>
      <div class="bg-surface-container-lowest p-6 rounded-xl border border-outline-variant/10 shadow-sm">
        <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Calificación Promedio</p>
        <div class="flex items-baseline gap-2">
          <h3 class="text-3xl font-black text-secondary">4.8</h3>
          <div class="flex text-secondary">
            <span class="material-symbols-outlined text-xs filled">star</span>
          </div>
        </div>
      </div>
      <div class="bg-surface-container-lowest p-6 rounded-xl border border-outline-variant/10 shadow-sm">
        <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Pendientes</p>
        <div class="flex items-baseline gap-2">
          <h3 class="text-3xl font-black text-error">6</h3>
          <span class="text-on-surface-variant text-[10px]">En revisión</span>
        </div>
      </div>
    </div>

    <!-- Bento Grid of Services -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Card 1 -->
      <div class="glass-card rounded-xl overflow-hidden group hover:shadow-2xl transition-all duration-500 border border-white/40 flex flex-col h-full">
        <div class="relative h-48">
          <img alt="Veterinary Clinic Interior" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDXZ_S9GjS_JiHZpJy5E9xbdnamPAL9H67LP2kxcUQ_c31UYT5hEFdxdECAihFYHBami56lvg-s0lOCEu5sbeqWwKv1a-XXcekbfWdAkWWc01keUH6hRQ3t9v459Z791pF1gy0ZVtx6kpEx8Uf1zpPP44YuvGW472keH9mN1RuuLJe8qfibo_CE25YT5HrEFQ04n0ypGc9p4uwMEJ5oeRLIZb-RX3G0iV9rwe0qhJyB8mnNwx2UIikLFRTBd0lLFkYG6T7GBDkYMJRS" />
          <div class="absolute top-4 right-4">
            <span class="bg-tertiary-fixed text-on-tertiary-fixed px-3 py-1 rounded-full text-xs font-bold uppercase tracking-tighter">Active</span>
          </div>
        </div>
        <div class="p-6 flex-grow space-y-4">
          <div class="flex justify-between items-start">
            <div>
              <h4 class="text-xl font-bold text-on-surface">Centro Veterinario Central</h4>
              <div class="flex items-center gap-1 text-secondary mt-1">
                <span class="material-symbols-outlined text-sm filled">star</span>
                <span class="material-symbols-outlined text-sm filled">star</span>
                <span class="material-symbols-outlined text-sm filled">star</span>
                <span class="material-symbols-outlined text-sm filled">star</span>
                <span class="material-symbols-outlined text-sm filled">star</span>
                <span class="text-xs font-bold ml-1">5.0</span>
              </div>
            </div>
          </div>
          <div class="flex items-start gap-2 text-on-surface-variant text-sm">
            <span class="material-symbols-outlined text-base">location_on</span>
            <span>Avenida Reforma 12-45, Zona 10, Ciudad de Guatemala</span>
          </div>
          <div class="pt-4 flex gap-3">
            <button class="flex-1 bg-surface-container-high text-on-surface py-2 rounded-lg font-bold text-sm flex items-center justify-center gap-2 hover:bg-surface-container-highest transition-colors">
              <span class="material-symbols-outlined text-sm">edit</span>
              Edit
            </button>
            <button class="px-4 py-2 border border-error/20 text-error rounded-lg font-bold text-sm hover:bg-error-container/50 transition-colors flex items-center justify-center">
              <span class="material-symbols-outlined text-sm">delete</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Card 2 -->
      <div class="glass-card rounded-xl overflow-hidden group hover:shadow-2xl transition-all duration-500 border border-white/40 flex flex-col h-full">
        <div class="relative h-48">
          <img alt="Pet Care Center" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD1r3VgW4mw6_4yuxBh2pJASJdeP52-tHxZX1ToVO5VOydj3wGnHAc27XExf5gwRVjPHRuXMISbpZ3VG_TdXpydfH1WDK2tt6fElkuwLuthnCE4aWRK1KZPgtNeRRcT0wrykacTvo_2n-bQLJ8VsN1K_7TEz5D_VkQrBOBnD6BEBIs0_V9EoDhT75DbOm2ta1MLp7RGaFyqoWuz71Ocq0H17TFqmoc7-x7D5JK7y5Dns4_joZLagqknQhTrYIqjEQ-14oL8sHm25x-s" />
          <div class="absolute top-4 right-4">
            <span class="bg-tertiary-fixed text-on-tertiary-fixed px-3 py-1 rounded-full text-xs font-bold uppercase tracking-tighter">Active</span>
          </div>
        </div>
        <div class="p-6 flex-grow space-y-4">
          <div class="flex justify-between items-start">
            <div>
              <h4 class="text-xl font-bold text-on-surface">Huellitas Care</h4>
              <div class="flex items-center gap-1 text-secondary mt-1">
                <span class="material-symbols-outlined text-sm filled">star</span>
                <span class="material-symbols-outlined text-sm filled">star</span>
                <span class="material-symbols-outlined text-sm filled">star</span>
                <span class="material-symbols-outlined text-sm filled">star</span>
                <span class="material-symbols-outlined text-sm">star</span>
                <span class="text-xs font-bold ml-1">4.2</span>
              </div>
            </div>
          </div>
          <div class="flex items-start gap-2 text-on-surface-variant text-sm">
            <span class="material-symbols-outlined text-base">location_on</span>
            <span>Calzada Roosevelt 45-88, Zona 11</span>
          </div>
          <div class="pt-4 flex gap-3">
            <button class="flex-1 bg-surface-container-high text-on-surface py-2 rounded-lg font-bold text-sm flex items-center justify-center gap-2 hover:bg-surface-container-highest transition-colors">
              <span class="material-symbols-outlined text-sm">edit</span>
              Edit
            </button>
            <button class="px-4 py-2 border border-error/20 text-error rounded-lg font-bold text-sm hover:bg-error-container/50 transition-colors flex items-center justify-center">
              <span class="material-symbols-outlined text-sm">delete</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Card 3 (Inactive Example) -->
      <div class="glass-card rounded-xl overflow-hidden group hover:shadow-2xl transition-all duration-500 border border-white/40 flex flex-col h-full opacity-80 grayscale-[0.3]">
        <div class="relative h-48">
          <img alt="Veterinary Office" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBXhH_oNnjdf88JK7Wj-KuW0SQoKV7LpXYlAHRT69hb7u43dcNhhD-nVGHwl4nLUtifex0nMqS20j3G4t8kIZq9c4tjmDm4BbsppuyASpzBGP-SrAkf8mFZaIIKLsA_gsxf2R0QqHeSn1JRYrJ085Yipx8TaHg-oFYil7R2b1GpVPkmtIBf75Gun17sb6BXLLYryYcTJOrBiVjb6zs0tPqA_-pfMhg4m_Hv1R1MPhfRKJYXBLZlRkanbZfYq-K5REe2EmzLE2rLz1Yg" />
          <div class="absolute top-4 right-4">
            <span class="bg-error-container text-on-error-container px-3 py-1 rounded-full text-xs font-bold uppercase tracking-tighter">Inactive</span>
          </div>
        </div>
        <div class="p-6 flex-grow space-y-4">
          <div class="flex justify-between items-start">
            <div>
              <h4 class="text-xl font-bold text-on-surface">Clínica San Francisco</h4>
              <div class="flex items-center gap-1 text-secondary mt-1">
                <span class="material-symbols-outlined text-sm filled">star</span>
                <span class="material-symbols-outlined text-sm filled">star</span>
                <span class="material-symbols-outlined text-sm filled">star</span>
                <span class="material-symbols-outlined text-sm">star</span>
                <span class="material-symbols-outlined text-sm">star</span>
                <span class="text-xs font-bold ml-1">3.5</span>
              </div>
            </div>
          </div>
          <div class="flex items-start gap-2 text-on-surface-variant text-sm">
            <span class="material-symbols-outlined text-base">location_on</span>
            <span>Km. 15 Carretera a El Salvador</span>
          </div>
          <div class="pt-4 flex gap-3">
            <button class="flex-1 bg-surface-container-high text-on-surface py-2 rounded-lg font-bold text-sm flex items-center justify-center gap-2 hover:bg-surface-container-highest transition-colors">
              <span class="material-symbols-outlined text-sm">edit</span>
              Edit
            </button>
            <button class="px-4 py-2 border border-error/20 text-error rounded-lg font-bold text-sm hover:bg-error-container/50 transition-colors flex items-center justify-center">
              <span class="material-symbols-outlined text-sm">delete</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- List View Alternative -->
    <div class="bg-surface-container-lowest rounded-2xl overflow-hidden shadow-sm">
      <div class="px-8 py-4 bg-surface-container-low border-b border-outline-variant/10 flex justify-between items-center">
        <h3 class="font-bold text-on-surface">Recent Registrations</h3>
        <div class="flex gap-4">
          <button class="text-sm font-semibold text-primary">View All</button>
          <button class="text-sm font-semibold text-on-surface-variant"><span class="material-symbols-outlined text-base">filter_list</span></button>
        </div>
      </div>
      <div class="divide-y divide-outline-variant/5">
        <!-- List Item 1 -->
        <div class="px-8 py-4 flex items-center justify-between hover:bg-surface-container-low transition-colors group">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-primary-fixed flex items-center justify-center text-on-primary-fixed font-bold text-lg">
              HP
            </div>
            <div>
              <p class="font-bold text-on-surface">Hospital de Mascotas Paws</p>
              <p class="text-xs text-on-surface-variant">Zona 15, Vista Hermosa I</p>
            </div>
          </div>
          <div class="flex items-center gap-12">
            <div class="flex flex-col items-center">
              <span class="text-xs text-on-surface-variant uppercase font-bold tracking-widest">Rating</span>
              <div class="flex text-secondary scale-75">
                <span class="material-symbols-outlined filled">star</span>
                <span class="material-symbols-outlined filled">star</span>
                <span class="material-symbols-outlined filled">star</span>
                <span class="material-symbols-outlined filled">star</span>
                <span class="material-symbols-outlined">star</span>
              </div>
            </div>
            <span class="bg-tertiary-fixed text-on-tertiary-fixed px-3 py-1 rounded-full text-[10px] font-black uppercase">Active</span>
            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <button class="p-2 text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined">edit</span></button>
              <button class="p-2 text-on-surface-variant hover:text-error"><span class="material-symbols-outlined">delete</span></button>
            </div>
          </div>
        </div>

        <!-- List Item 2 -->
        <div class="px-8 py-4 flex items-center justify-between hover:bg-surface-container-low transition-colors group">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-secondary-fixed flex items-center justify-center text-on-secondary-fixed font-bold text-lg">
              VV
            </div>
            <div>
              <p class="font-bold text-on-surface">Veterinaria Valle Verde</p>
              <p class="text-xs text-on-surface-variant">Carretera Interamericana Km. 22</p>
            </div>
          </div>
          <div class="flex items-center gap-12">
            <div class="flex flex-col items-center">
              <span class="text-xs text-on-surface-variant uppercase font-bold tracking-widest">Rating</span>
              <div class="flex text-secondary scale-75">
                <span class="material-symbols-outlined filled">star</span>
                <span class="material-symbols-outlined filled">star</span>
                <span class="material-symbols-outlined filled">star</span>
                <span class="material-symbols-outlined">star</span>
                <span class="material-symbols-outlined">star</span>
              </div>
            </div>
            <span class="bg-tertiary-fixed text-on-tertiary-fixed px-3 py-1 rounded-full text-[10px] font-black uppercase">Active</span>
            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <button class="p-2 text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined">edit</span></button>
              <button class="p-2 text-on-surface-variant hover:text-error"><span class="material-symbols-outlined">delete</span></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
</script>
