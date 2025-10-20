<template>
  <router-view />
  <AlertToast />

  <!-- ✅ Global Loading Overlay -->
  <LoadingOverlay
    :show="appStore.loading"
    :logo="logoImg"
    :size="70"
    message="Loading..."
  />
</template>

<script setup>
import { onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import { useAppStore } from "./stores/appStore";
import LoadingOverlay from "./components/LoadingOverlay.vue";
import logoImg from "./assets/ISELCO1LOGO.png";
import AlertToast from "./components/AddUser/AlertToast.vue";
import "../src/assets/css/Global.css";

const appStore = useAppStore();
const router = useRouter();

let inactivityTimer = null;
const INACTIVITY_LIMIT = 15 * 60 * 1000; // 5 minutes (pwede mong baguhin)

// ✅ Logout function
function logoutUser() {
  sessionStorage.clear();
  router.push("/login");
}

// ✅ Reset inactivity timer kapag may galaw
function resetInactivityTimer() {
  clearTimeout(inactivityTimer);
  inactivityTimer = setTimeout(() => {
    logoutUser();
  }, INACTIVITY_LIMIT);
}

// ✅ Setup event listeners
function setupInactivityWatcher() {
  const events = ["mousemove", "keydown", "click", "scroll", "touchstart"];
  events.forEach(event => {
    window.addEventListener(event, resetInactivityTimer);
  });
  resetInactivityTimer(); // start agad
}

// ✅ Cleanup listeners
function removeInactivityWatcher() {
  const events = ["mousemove", "keydown", "click", "scroll", "touchstart"];
  events.forEach(event => {
    window.removeEventListener(event, resetInactivityTimer);
  });
  clearTimeout(inactivityTimer);
}

// Lifecycle hooks
onMounted(() => {
  setupInactivityWatcher();
});

onBeforeUnmount(() => {
  removeInactivityWatcher();
});
</script>

<style>
/* Global font for the whole app */
body {
  font-family: Georgia, 'Times New Roman', Times, serif;
}
</style>
