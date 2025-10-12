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
import { useAppStore } from "./stores/appStore";
import LoadingOverlay from "./components/LoadingOverlay.vue";
import logoImg from "./assets/ISELCO1LOGO.png";
import AlertToast from "./components/AddUser/AlertToast.vue";
import "../src/assets/css/Global.css";

const appStore = useAppStore();
</script>
<style>
/* Global font for the whole app */
body {
  font-family: Georgia, 'Times New Roman', Times, serif;
}

</style>


