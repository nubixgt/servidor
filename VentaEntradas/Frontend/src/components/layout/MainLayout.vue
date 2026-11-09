<template>
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Sidebar Placeholder -->
        <aside class="w-64 bg-white shadow-md">
            <div class="p-4 font-bold text-xl">App Name</div>
            <nav class="mt-4">
                <router-link to="/dashboard" class="block px-4 py-2 hover:bg-gray-100">Dashboard</router-link>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <router-view></router-view>
        </main>
    </div>
</template>

<script setup>
</script>
