import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

import LoginView from './views/LoginView.vue';
import SignupView from './views/SignupView.vue';
import ChatView from './views/ChatView.vue';

const routes = [
  { path: '/', component: ChatView, meta: { requiresAuth: true } },
  { path: '/login', component: LoginView },
  { path: '/signup', component: SignupView }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  if (authStore.user === null && !authStore.isAuthenticated) {
    await authStore.checkAuth();
  }
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login');
  } else if ((to.path === '/login' || to.path === '/signup') && authStore.isAuthenticated) {
    next('/');
  } else {
    next();
  }
});

export default router;
