<template>
    <VistaHeader eyebrow="Control" titulo="Administración" desc="Usuarios, roles y permisos configurables, parámetros y bitácora de auditoría." />
    <div class="card">
        <div class="subtabs">
            <button v-for="[k, l] in tabs" :key="k" :class="{ on: tab === k }" @click="tab = k">{{ l }}</button>
        </div>
        <AdminUsuarios v-if="tab === 'usuarios'" />
        <AdminRoles v-else-if="tab === 'roles'" />
        <AdminBitacora v-else-if="tab === 'bitacora'" />
        <AdminParametros v-else-if="tab === 'parametros'" />
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import AdminBitacora from '../../components/admin/AdminBitacora.vue';
import AdminParametros from '../../components/admin/AdminParametros.vue';
import AdminRoles from '../../components/admin/AdminRoles.vue';
import AdminUsuarios from '../../components/admin/AdminUsuarios.vue';
import VistaHeader from '../../components/common/VistaHeader.vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const tabs = computed(() => [['usuarios', 'Usuarios', 'usuarios.gestionar'], ['roles', 'Roles y permisos', 'roles.gestionar'], ['bitacora', 'Bitácora de auditoría', 'auditoria.ver'], ['parametros', 'Parámetros y catálogos', 'catalogos.gestionar']]
    .filter((t) => auth.puede(t[2])));
const tab = ref(tabs.value[0] && tabs.value[0][0]);
</script>
