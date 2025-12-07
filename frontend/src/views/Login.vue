<template>
  <div class="luxe-wrapper">

    <!-- BACKGROUND ORBS -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="luxe-card">

      <!-- LOGO -->
      <img src="../assets/ISELCO1LOGO.png" class="luxe-logo" />

      <h2 class="luxe-title">Warehouse Inventory System</h2>

      <form @submit.prevent="handleLogin">

        <div class="luxe-input">
          <label>Username</label>
          <input type="text" v-model="form.username" required />
        </div>

        <div class="luxe-input">
          <label>Password</label>
          <div class="luxe-password-box">
            <input :type="showPassword ? 'text' : 'password'" v-model="form.password" required />
            <button type="button" class="toggle-pass" @click="togglePassword">
              <i v-if="showPassword" class="bi bi-eye-slash"></i>
              <i v-else class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <div v-if="error" class="luxe-error">{{ error }}</div>

        <button class="luxe-btn" :disabled="appStore.loading">
          <span v-if="!appStore.loading">Login</span>
          <span v-else>Loading...</span>
        </button>

      </form>
    </div>

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
      error.value = "Invalid login credentials.";
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
/* ANIMATED MINIMAL BACKGROUND */
.luxe-wrapper {
  height: 100vh;
  background: linear-gradient(140deg, #ffffff, #eef4f1, #dff7e3);
  background-size: 300% 300%;
  animation: bgShift 12s ease infinite;

  display: flex;
  justify-content: center;
  align-items: center;
  padding: 1rem;

  font-family: "Inter", "Poppins", sans-serif;
  position: relative;
  overflow: hidden;
}

/* BACKGROUND FLOATING ORBS (APPLE STYLE) */
.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.35;
}

.orb-1 {
  width: 260px;
  height: 260px;
  background: #4ade80;
  top: -40px;
  left: -40px;
}

.orb-2 {
  width: 300px;
  height: 300px;
  background: #22c55e;
  bottom: -60px;
  right: -60px;
}

.orb-3 {
  width: 200px;
  height: 200px;
  background: #86efac;
  top: 40%;
  left: 50%;
  transform: translateX(-50%);
}

/* BACKGROUND ANIMATION */
@keyframes bgShift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* CARD */
.luxe-card {
  width: 100%;
  max-width: 410px;
  padding: 2.4rem;
  border-radius: 22px;
  background: #ffffffd9;
  backdrop-filter: blur(20px);

  box-shadow:
    0 20px 40px rgba(0,0,0,0.06),
    0 6px 18px rgba(0,0,0,0.04),
    inset 0 0 0 1px rgba(255,255,255,0.55);

  text-align: center;
  z-index: 10;
}

/* LOGO */
.luxe-logo {
  width: 90px;
  height: 90px;
  margin: 0 auto 1rem auto;
  display: block;
}

/* TITLE */
.luxe-title {
  font-size: 1.48rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 1.9rem;
}

/* INPUT GROUP */
.luxe-input {
  text-align: left;
  margin-bottom: 1.45rem;
}

.luxe-input label {
  font-size: 0.88rem;
  font-weight: 500;
  color: #475569;
  margin-bottom: 0.4rem;
  display: block;
}

/* INPUT STYLING */
.luxe-input input {
  width: 100%;
  padding: 0.8rem 1rem;
  border-radius: 12px;
  border: 1px solid #d7dde4;
  font-size: 0.95rem;
  background: #fdfdfd;
  transition: 0.2s ease;
}

.luxe-input input:focus {
  border-color: #22c55e;
  box-shadow: 0 0 0 3px rgba(34,197,94,0.16);
  outline: none;
}

/* PASSWORD ICON */
.luxe-password-box {
  position: relative;
}

.toggle-pass {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  font-size: 1.15rem;
  color: #64748b;
  cursor: pointer;
}

/* ERROR BOX */
.luxe-error {
  background: #fee2e2;
  color: #b91c1c;
  padding: 0.7rem;
  border-radius: 10px;
  border: 1px solid #fecaca;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

/* PREMIUM BUTTON */
.luxe-btn {
  width: 100%;
  padding: 0.95rem;
  border-radius: 14px;
  background: linear-gradient(135deg, #22c55e, #15803d);
  color: white;
  font-weight: 600;
  font-size: 1rem;
  border: none;
  cursor: pointer;

  box-shadow:
    0 6px 20px rgba(34,197,94,0.30),
    inset 0 0 0 rgba(255,255,255,0.2);

  transition: 0.25s ease;
}

.luxe-btn:hover {
  transform: translateY(-3px);
  box-shadow:
    0 10px 28px rgba(34,197,94,0.45);
}

</style>
