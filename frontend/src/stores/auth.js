import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const isAuthenticated = ref(false);

    async function checkAuth() {
        try {
            const res = await fetch('/api/check_auth.php');
            const data = await res.json();
            isAuthenticated.value = data.authenticated;
            user.value = data.user || null;
        } catch (e) {
            isAuthenticated.value = false;
            user.value = null;
        }
    }

    async function login(username, password) {
        const res = await fetch('/api/login.php', {
            method: 'POST',
            body: JSON.stringify({ username, password })
        });
        
        let data;
        try {
            data = await res.json();
        } catch(e) {
            throw new Error("Backend server is not responding correctly. Is the PHP server running?");
        }
        
        if (data && data.success) {
            isAuthenticated.value = true;
            user.value = data.user;
            return true;
        }
        throw new Error(data ? data.error : "Unknown error");
    }

    async function signup(username, email, password) {
        const res = await fetch('/api/signup.php', {
            method: 'POST',
            body: JSON.stringify({ username, email, password })
        });
        
        let data;
        try {
            data = await res.json();
        } catch(e) {
            throw new Error("Backend server is not responding correctly. Is the PHP server running?");
        }

        if (data && data.success) {
            isAuthenticated.value = true;
            user.value = data.user;
            return true;
        }
        throw new Error(data ? data.error : "Unknown error");
    }

    async function logout() {
        await fetch('/api/logout.php');
        isAuthenticated.value = false;
        user.value = null;
    }

    return { user, isAuthenticated, checkAuth, login, signup, logout };
});
