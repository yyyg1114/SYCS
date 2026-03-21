<template>
  <div class="auth-box">
    <h2>SYCS Register</h2>
    <p class="subtitle">Create an account to join the community</p>

    <div v-if="errorMsg" class="error-message">
      {{ errorMsg }}
    </div>

    <form @submit.prevent="handleSignup">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" v-model="username" required autocomplete="username">
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" v-model="email" required autocomplete="email">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" v-model="password" required autocomplete="new-password">
      </div>
      <button type="submit" class="btn-primary" :disabled="isLoading">
        {{ isLoading ? 'Registering...' : 'Sign Up' }}
      </button>
    </form>
    
    <div class="auth-links">
      Already have an account? <router-link to="/login">Login</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const username = ref('');
const email = ref('');
const password = ref('');
const errorMsg = ref('');
const isLoading = ref(false);

const router = useRouter();
const authStore = useAuthStore();

const handleSignup = async () => {
    errorMsg.value = '';
    isLoading.value = true;
    try {
        await authStore.signup(username.value, email.value, password.value);
        router.push('/');
    } catch (e) {
        errorMsg.value = e.message;
    } finally {
        isLoading.value = false;
    }
};
</script>
