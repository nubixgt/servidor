<script setup>
const rows = [
  { id: 'EXP-2024-089', date: '24 Mar 2024', type: 'Canino', status: 'En Revisión', priority: 'Alta', color: 'error' },
  { id: 'EXP-2024-090', date: '23 Mar 2024', type: 'Felino', status: 'Pendiente', priority: 'Media', color: 'primary' },
  { id: 'EXP-2024-091', date: '22 Mar 2024', type: 'Equino', status: 'Resuelto', priority: 'Baja', color: 'outline' },
  { id: 'EXP-2024-092', date: '21 Mar 2024', type: 'Exótico', status: 'Resuelto', priority: 'Alta', color: 'error' }
];
</script>

<template>
  <div class="animate-in fade-in duration-500">
    <div class="flex justify-between items-end mb-8">
      <div>
        <h2 class="font-headline text-3xl font-extrabold text-on-surface tracking-tight">Gestión de Denuncias</h2>
        <p class="text-on-surface-variant mt-1">Visualización y búsqueda centralizada de denuncias.</p>
      </div>
      <button class="primary-gradient text-white px-4 py-2 rounded-xl font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]">add</span>
        Nueva Denuncia Manual
      </button>
    </div>

    <div class="glass-card rounded-2xl shadow-ambient overflow-hidden">
      <div class="p-4 border-b border-outline-variant/20 flex gap-4 bg-surface-container-lowest/50">
        <div class="relative flex-1">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">search</span>
          <input type="text" placeholder="Buscar por ID, denunciante o ubicación..." class="w-full bg-surface border border-outline-variant/30 rounded-lg py-2 pl-10 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
        </div>
        <select class="bg-surface border border-outline-variant/30 rounded-lg px-4 py-2 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20">
          <option>Todos los estados</option>
          <option>Pendiente</option>
          <option>En Revisión</option>
          <option>Resuelto</option>
        </select>
        <button class="px-4 py-2 bg-surface border border-outline-variant/30 rounded-lg text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-2">
          <span class="material-symbols-outlined text-[18px]">filter_list</span>
          Filtros
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-surface-container-lowest border-b border-outline-variant/20 text-xs uppercase tracking-widest text-outline">
              <th class="p-4 font-bold">ID Expediente</th>
              <th class="p-4 font-bold">Fecha</th>
              <th class="p-4 font-bold">Tipo</th>
              <th class="p-4 font-bold">Estado</th>
              <th class="p-4 font-bold">Prioridad</th>
              <th class="p-4 font-bold text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-outline-variant/10">
            <tr v-for="row in rows" :key="row.id" class="hover:bg-surface-container-lowest/50 transition-colors">
              <td class="p-4 font-bold text-on-surface">{{ row.id }}</td>
              <td class="p-4 text-on-surface-variant">{{ row.date }}</td>
              <td class="p-4 text-on-surface-variant">{{ row.type }}</td>
              <td class="p-4">
                <span class="px-2 py-1 bg-surface-container-low rounded text-xs font-semibold text-on-surface-variant">{{ row.status }}</span>
              </td>
              <td class="p-4">
                <span :class="[`bg-${row.color}-container/20`, `text-${row.color}`]" class="px-2 py-1 rounded text-xs font-bold uppercase">{{ row.priority }}</span>
              </td>
              <td class="p-4 text-right">
                <button class="text-primary hover:bg-primary-fixed/50 p-2 rounded-lg transition-colors">
                  <span class="material-symbols-outlined text-[20px]">visibility</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="p-4 border-t border-outline-variant/20 flex justify-between items-center bg-surface-container-lowest/50 text-sm text-on-surface-variant">
        <span>Mostrando 1 a 4 de 1,284 registros</span>
        <div class="flex gap-2">
          <button class="px-3 py-1 border border-outline-variant/30 rounded hover:bg-surface-container-low">Anterior</button>
          <button class="px-3 py-1 border border-outline-variant/30 rounded hover:bg-surface-container-low">Siguiente</button>
        </div>
      </div>
    </div>
  </div>
</template>
