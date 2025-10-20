<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 relative">
    <!-- Login Box -->
    <form @submit.prevent="handleLogin" class="w-full max-w-md p-8 bg-white shadow-lg rounded-lg relative z-10">
      <h2 class="text-2xl font-bold mb-6 text-center">Warehouse Inventory System</h2>

      <div class="mb-4">
        <label class="block text-sm font-medium mb-1" for="username">Username</label>
        <input 
          id="username" 
          v-model="form.username" 
          type="text" 
          placeholder="Enter your username" 
          required
          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none" 
        />
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium mb-1" for="password">Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          placeholder="Enter your password"
          required
          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
        />
      </div>

      <p v-if="error" class="text-red-500 mb-4 text-sm">{{ error }}</p>

      <button 
        type="submit" 
        :disabled="appStore.loading"
        class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 transition-colors disabled:opacity-50"
      >
        Login
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import api from "../services/api";
import { useAppStore } from "../stores/appStore";

const router = useRouter();
const appStore = useAppStore();

const form = ref({ username: "", password: "" });
const error = ref("");

async function handleLogin() {
  error.value = "";
  appStore.showLoading();

  try {
    const res = await api.post("/login", form.value);

    const token = res?.data?.access_token;
    if (!token) {
      error.value = "Login failed: no token returned";
      return;
    }

    sessionStorage.setItem("access_token", token);
    api.defaults.headers.common["Authorization"] = `Bearer ${token}`;

    router.push("/dashboard");
  } catch (err) {
    error.value = err.response?.data?.message || "Login failed";
  } finally {
    appStore.hideLoading();
  }
}
</script>