<template>
    <div class="bg-background text-on-background font-body-lg min-h-screen flex selection:bg-primary-container selection:text-on-primary-container">
        <Sidebar />

        <div class="flex-1 md:ml-64 flex flex-col min-h-screen overflow-x-hidden relative">
            <TopNavbar />

            <main class="flex-1 mt-20 flex flex-col min-h-screen overflow-x-hidden p-container-padding-mobile md:p-container-padding-desktop space-y-section-gap w-full max-w-[1440px] mx-auto">
                <router-view></router-view>
            </main>
        </div>
    </div>
</template>

<script setup>
import Sidebar from './Sidebar.vue';
import TopNavbar from './TopNavbar.vue';
</script>
