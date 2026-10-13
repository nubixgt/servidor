<script setup>
import { LayoutDashboardIcon, ShieldIcon, UsersIcon, CalendarDaysIcon, LogOutIcon } from 'lucide-vue-next'

const enlaces = [
  { ruta: '/admin', nombre: 'Resumen', icono: LayoutDashboardIcon, exacta: true },
  { ruta: '/admin/equipos', nombre: 'Equipos', icono: ShieldIcon },
  { ruta: '/admin/jugadores', nombre: 'Jugadores', icono: UsersIcon },
  { ruta: '/admin/partidos', nombre: 'Partidos', icono: CalendarDaysIcon },
]

defineEmits(['salir'])
</script>

<template>
  <aside class="admin-sidebar">
    <p class="eyebrow">CONTROL DE LA LIGA</p>
    <nav class="admin-nav" aria-label="Administración">
      <RouterLink v-for="e in enlaces" :key="e.ruta" :to="e.ruta" :active-class="e.exacta ? '' : 'active'" exact-active-class="active">
        <component :is="e.icono" :size="17" /> {{ e.nombre }}
      </RouterLink>
      <button type="button" @click="$emit('salir')"><LogOutIcon :size="17" /> Salir</button>
    </nav>
  </aside>
</template>
