<script setup>
import { LayoutDashboardIcon, ShieldIcon, UsersIcon, CalendarDaysIcon, LogOutIcon } from 'lucide-vue-next'

const enlaces = [
  { ruta: '/encargado', nombre: 'Resumen', icono: LayoutDashboardIcon, exacta: true },
  { ruta: '/encargado/mi-equipo', nombre: 'Mi equipo', icono: ShieldIcon },
  { ruta: '/encargado/jugadores', nombre: 'Mis jugadores', icono: UsersIcon },
  { ruta: '/encargado/partidos', nombre: 'Mis partidos', icono: CalendarDaysIcon },
]

defineEmits(['salir'])
</script>

<template>
  <aside class="admin-sidebar">
    <p class="eyebrow">MI EQUIPO</p>
    <nav class="admin-nav" aria-label="Panel del encargado">
      <RouterLink v-for="e in enlaces" :key="e.ruta" :to="e.ruta" :active-class="e.exacta ? '' : 'active'" exact-active-class="active">
        <component :is="e.icono" :size="17" /> {{ e.nombre }}
      </RouterLink>
      <button type="button" @click="$emit('salir')"><LogOutIcon :size="17" /> Salir</button>
    </nav>
  </aside>
</template>
