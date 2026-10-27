<template>
    <div v-if="mensaje" class="toast" role="status">{{ mensaje }}</div>
</template>

<script setup>
import { useToast } from '../../composables/useToast';

const { mensaje } = useToast();
</script>
