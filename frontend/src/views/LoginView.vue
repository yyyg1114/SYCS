<template>
  <div class="auth-box">
    <h2>SYCS Login</h2>
    <p class="subtitle">Welcome back to the chat</p>
    
    <div v-if="errorMsg" class="error-message">
      {{ errorMsg }}
    </div>

    <form @submit.prevent="handleLogin">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" v-model="username" required autocomplete="username">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" v-model="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn-primary" :disabled="isLoading">
        {{ isLoading ? 'Logging in...' : 'Login' }}
      </button>
    </form>
    
    <div class="auth-links">
      Don't have an account? <router-link to="/signup">Sign up</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const username = ref('');
const password = ref('');
const errorMsg = ref('');
const isLoading = ref(false);

const router = useRouter();
const authStore = useAuthStore();

const handleLogin = async () => {
    errorMsg.value = '';
    isLoading.value = true;
    try {
        await authStore.login(username.value, password.value);
        router.push('/');
    } catch (e) {
        errorMsg.value = e.message;
    } finally {
        isLoading.value = false;
    }
};
</script>
