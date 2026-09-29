<template>
    <div class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="p-8 bg-white rounded shadow-md w-96">
            <h1 class="mb-4 text-2xl font-bold text-center">Login</h1>
            <form @submit.prevent="handleLogin">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-bold text-gray-700">Email</label>
                    <input type="email" class="w-full px-3 py-2 border rounded shadow appearance-none" placeholder="Email" />
                </div>
                <div class="mb-6">
                    <label class="block mb-2 text-sm font-bold text-gray-700">Password</label>
                    <input type="password" class="w-full px-3 py-2 border rounded shadow appearance-none" placeholder="Password" />
                </div>
                <button type="submit" class="w-full px-4 py-2 font-bold text-white bg-blue-500 rounded hover:bg-blue-700">
                    Sign In
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
const handleLogin = () => {
    console.log("Login attempt");
};
</script>
