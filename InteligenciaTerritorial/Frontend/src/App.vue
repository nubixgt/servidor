<template>
  <div class="app">
    <Sidebar />
    <MapComponent />
    <InfoPanel />
    <div class="toast" v-if="toastMsg">{{ toastMsg }}</div>
  </div>
</template>

<script setup>
import { onMounted, ref, provide } from 'vue';
import { useMunicipiosStore } from './stores/municipios';
import Sidebar from './components/Sidebar.vue';
import MapComponent from './components/MapComponent.vue';
import InfoPanel from './components/InfoPanel.vue';

const store = useMunicipiosStore();
const toastMsg = ref('');

const showToast = (msg) => {
  toastMsg.value = msg;
  setTimeout(() => { toastMsg.value = ''; }, 3000);
};

provide('showToast', showToast);

onMounted(() => {
  store.fetchDepartamentos();
  store.fetchMunicipios();
});
</script>

<style>
/* Global CSS is imported in style.css */
</style>
