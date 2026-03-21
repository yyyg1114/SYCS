import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const isAuthenticated = ref(false);

    async function checkAuth() {
        try {
            const res = await fetch('/api/check_auth.php');
            if (!res.ok) throw new Error("HTTP error");
            const text = await res.text();
            if (!text) throw new Error("Empty response");
            const data = JSON.parse(text);
            isAuthenticated.value = data.authenticated;
            user.value = data.user || null;
        } catch (e) {
            // Silently fail auth check if network/JSON error
            isAuthenticated.value = false;
            user.value = null;
        }
    }

    async function login(username, password) {
        try {
            const res = await fetch('/api/login.php', {
                method: 'POST',
                body: JSON.stringify({ username, password })
            });
            const text = await res.text();
            
            let data;
            try {
                data = JSON.parse(text);
            } catch(e) {
                // If the response is empty (timeout) or HTML (PHP error)
                throw new Error(res.ok ? "APIから不正な応答がありました。" : "サーバーまたはデータベースへの接続に失敗しました。PHPが起動しているか確認してください。");
            }

            if (data.success) {
                isAuthenticated.value = true;
                user.value = data.user;
                return true;
            }
            throw new Error(data.error || "Unknown error occurred");
        } catch (error) {
            if (error.name === 'TypeError') {
                throw new Error("サーバーとの通信に失敗しました。PHPサーバー(localhost:8000)を起動してください。");
            }
            throw error;
        }
    }

    async function signup(username, email, password) {
        try {
            const res = await fetch('/api/signup.php', {
                method: 'POST',
                body: JSON.stringify({ username, email, password })
            });
            const text = await res.text();
            
            let data;
            try {
                data = JSON.parse(text);
            } catch(e) {
                throw new Error(res.ok ? "APIから不正な応答がありました。" : "サーバーまたはデータベースへの接続に失敗しました。");
            }

            if (data.success) {
                isAuthenticated.value = true;
                user.value = data.user;
                return true;
            }
            throw new Error(data.error || "Unknown error occurred");
        } catch (error) {
            if (error.name === 'TypeError') {
                throw new Error("サーバーとの通信に失敗しました。PHPサーバーが動作しているか確認してください。");
            }
            throw error;
        }
    }

    async function logout() {
        try {
            await fetch('/api/logout.php');
        } catch (e) {
            console.error("Logout error", e);
        }
        isAuthenticated.value = false;
        user.value = null;
    }

    return { user, isAuthenticated, checkAuth, login, signup, logout };
});
