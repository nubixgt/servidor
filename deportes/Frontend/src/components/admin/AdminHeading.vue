<script setup>
import { PlusIcon } from 'lucide-vue-next'
import { useAuth } from '../../composables/useAuth'

defineProps({ title: String, addLabel: String })
defineEmits(['add'])

const { sesion } = useAuth()
</script>

<template>
  <header class="admin-heading">
    <div>
      <p class="eyebrow">{{ sesion.rol === 'admin' ? 'Administrador' : sesion.equipo_nombre }} · {{ sesion.usuario }}</p>
      <h1>{{ title }}<span class="lime">.</span></h1>
    </div>
    <slot name="actions">
      <button v-if="addLabel" class="btn btn-primary" @click="$emit('add')"><PlusIcon :size="17" />{{ addLabel }}</button>
    </slot>
  </header>
</template>
