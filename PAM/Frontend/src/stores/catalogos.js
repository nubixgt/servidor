import { defineStore } from 'pinia';
import { ref } from 'vue';
import catalogoService from '../services/catalogoService';

export const useCatalogosStore = defineStore('catalogos', () => {
    const cat = ref(null);

    async function cargar() {
        cat.value = await catalogoService.todos();
    }

    return { cat, cargar };
});
