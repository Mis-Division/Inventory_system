<template>
  <div class="login-wrapper">
    <div class="login-card">
      <!-- Logo + Title -->
      <div class="login-header">
        <img src="../assets/ISELCO1LOGO.png" alt="App Logo" class="login-logo" />
        <h2 class="login-title">Warehouse Inventory System</h2>
        
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <!-- Username -->
        <div class="form-group">
          <label for="username">Username</label>
          <input id="username" v-model="form.username" type="text" placeholder="Enter your username" required />
        </div>

        <!-- Password -->
        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-box">
            <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'"
              placeholder="Enter your password" required />
            <button type="button" class="toggle-password" @click="togglePassword"
              :aria-label="showPassword ? 'Hide password' : 'Show password'">
              <i v-if="showPassword" class="bi bi-eye-slash"></i>
              <i v-else class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <!-- Error -->
        <div v-if="error" class="error-msg">{{ error }}</div>

        <!-- Button -->
        <button type="submit" :disabled="appStore.loading" class="login-btn">
          <span v-if="!appStore.loading">Login</span>
          <span v-else>Logging in...</span>
        </button>
      </form>
    </div>

    <!-- Footer -->
    <p class="login-footer">&copy; 2025 ISELCO MIS-Division</p>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import api from "../services/api";
import { useAppStore } from "../stores/appStore";
import { userStore } from "../stores/userStore";

const router = useRouter();
const appStore = useAppStore();

const form = ref({
  username: "",
  password: "",
});
const error = ref("");
const showPassword = ref(false);

function togglePassword() {
  showPassword.value = !showPassword.value;
}

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

    userStore.login(token, {
      ...user,
      accessModules: user?.modules ?? [],
    });

    await userStore.initUser();
    router.push("/dashboard");
  } catch (err) {
    error.value = err.response?.data?.message || "Login failed";
  } finally {
    appStore.hideLoading();
  }
}
</script>

<style scoped>
/* ===== Layout ===== */
.login-wrapper {
  height: 100vh;
  background: linear-gradient(135deg, #c7d2fe, #dbeafe, #f1f5f9);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  font-family: "Poppins", sans-serif;
  padding: 1rem;
  overflow: hidden;
}

/* ===== Card ===== */
.login-card {
  background: #ffffff;
  padding: 2.8rem 2.2rem;
  border-radius: 20px;
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 420px;
  text-align: center;
  animation: fadeIn 0.6s ease-in-out;
  position: relative;
  z-index: 10;
}

/* ===== Logo + Header ===== */
.login-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 1.5rem;
}

.login-logo {
  width: 80px;
  height: 80px;
  object-fit: contain;
  margin-bottom: 1rem;
  border-radius: 50%;
  background-color: #f1f5f9;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.login-title {
  font-size: 1.6rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.25rem;
}

.login-subtitle {
  color: #64748b;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

/* ===== Form ===== */
.login-form {
  width: 100%;
  margin-top: 1rem;
}

.form-group {
  text-align: left;
  margin-bottom: 1.2rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.35rem;
  font-size: 0.9rem;
}

.form-group input {
  width: 100%;
  padding: 0.65rem 1rem;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 0.95rem;
  transition: all 0.2s ease;
  outline: none;
}

.form-group input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.25);
}

/* ===== Password Toggle ===== */
.password-box {
  position: relative;
}

.password-box input {
  padding-right: 2.5rem;
}

.toggle-password {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  color: #6b7280;
  cursor: pointer;
  font-size: 1.2rem;
  transition: color 0.2s;
}

.toggle-password:hover {
  color: #2563eb;
}

/* ===== Error ===== */
.error-msg {
  color: #dc2626;
  font-size: 0.85rem;
  text-align: center;
  margin-bottom: 1rem;
}

/* ===== Button ===== */
.login-btn {
  width: 100%;
  background-color: #2563eb;
  color: white;
  font-weight: 600;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  border: none;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.25s ease;
}

.login-btn:hover {
  background-color: #1d4ed8;
  box-shadow: 0 6px 18px rgba(37, 99, 235, 0.3);
}

.login-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ===== Footer ===== */
.login-footer {
  margin-top: 1.5rem;
  color: #64748b;
  font-size: 0.85rem;
  text-align: center;
}

/* ===== Animation ===== */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(15px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
