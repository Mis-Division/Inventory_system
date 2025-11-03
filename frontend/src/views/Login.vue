<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 relative">
    <!-- Login Box -->
    <form @submit.prevent="handleLogin" class="w-full max-w-md p-8 bg-white shadow-lg rounded-lg relative z-10">
      <h2 class="text-2xl font-bold mb-6 text-center">Warehouse Inventory System</h2>

      <!-- Username -->
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1" for="username">Username</label>
        <input id="username" v-model="form.username" type="text" placeholder="Enter your username" required
          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none" />
      </div>

      <!-- Password -->
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1" for="password">Password</label>
        <input id="password" v-model="form.password" type="password" placeholder="Enter your password" required
          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none" />
      </div>

      <!-- Error Message -->
      <p v-if="error" class="text-red-500 mb-4 text-sm text-center">{{ error }}</p>

      <!-- Login Button -->
      <button type="submit" :disabled="appStore.loading"
        class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 transition-colors disabled:opacity-50">
        <span v-if="!appStore.loading">Login</span>
        <span v-else>Logging in...</span>
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import api from "../services/api";
import { useAppStore } from "../stores/appStore";
import { userStore } from "../stores/userStore"; // ✅ import your user store

const router = useRouter();
const appStore = useAppStore();

const form = ref({
  username: "",
  password: "",
});
const error = ref("");

async function handleLogin() {
  error.value = "";
  appStore.showLoading();

  try {
    const res = await api.post("/login", form.value);


    const token = res?.data?.data?.access_token;
    const user = res?.data?.data?.user;

    if (!token || !user) {
      error.value = "Invalid login response from server.";
      return;
    }

    // ✅ Save token and user (map modules → accessModules)
    userStore.login(token, {
      ...user,
      accessModules: user?.modules ?? []
    });

    // ✅ Fetch user info just to ensure permissions are current
    await userStore.initUser();

    // ✅ Redirect
    router.push("/dashboard");
  } catch (err) {
    error.value = err.response?.data?.message || "Login failed";
  } finally {
    appStore.hideLoading();
  }
}
</script>
