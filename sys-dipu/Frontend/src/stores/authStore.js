import { defineStore } from 'pinia';
import { ref } from 'vue';

// Role and Category types
// Role: 'administrador' | 'tecnico'
// Category: one of the 8 categories

export const useAuthStore = defineStore('auth', () => {
    const role = ref(null); // null | 'administrador' | 'tecnico'
    const assignedCategory = ref(null); // null | Category string
    const currentView = ref('dashboard');

    function login(selectedRole, category = null) {
        role.value = selectedRole;
        assignedCategory.value = category;
        currentView.value = 'dashboard';
    }

    function logout() {
        role.value = null;
        assignedCategory.value = null;
        currentView.value = 'dashboard';
    }

    function setView(view) {
        currentView.value = view;
    }

    return { role, assignedCategory, currentView, login, logout, setView };
});
