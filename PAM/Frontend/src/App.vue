<template>
  <div class="aurora" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
  <router-view></router-view>
  <AppTooltip />
  <AppToast />
</template>

<script setup>
import AppToast from './components/common/AppToast.vue';
import AppTooltip from './components/common/AppTooltip.vue';
</script>
