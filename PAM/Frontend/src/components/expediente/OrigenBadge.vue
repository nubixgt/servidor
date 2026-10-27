<template>
    <span v-if="origen === 'SISTEMA'" class="badge" style="color:var(--blue7);background:var(--blue1)">Sistema</span>
    <span v-else class="note">Matriz</span>
</template>

<script setup>
defineProps({ origen: { type: String, default: '' } });
</script>
