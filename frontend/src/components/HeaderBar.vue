<template>
  <header
    class="bg-dark text-white d-flex align-items-center justify-content-between shadow fixed-top px-4"
    style="height: 56px; z-index: 1050;"
  >
    <!-- Left: Logo + Title -->
    <div class="d-flex align-items-center gap-2">
      <img src="../assets/ISELCO1LOGO.png" alt="Logo" class="img-icon" />
      <h2 class="h5 mb-0">Warehouse Inventory System</h2>
    </div>

    <!-- Right: User Info -->
    <div class="d-flex flex-column align-items-end">
      <h6 class="mb-0 fw-medium">
        Welcome,
        {{ userStore.user?.fullname || userStore.user?.username || "Guest" }}
      </h6>
      <small class="text-light opacity-75">{{ currentDateTime }}</small>
    </div>
  </header>
</template>


<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { userStore } from "../stores/userStore"; // ✅ import your store

const currentDateTime = ref("");

// update time every second
function updateTime() {
  const now = new Date();
  currentDateTime.value = now.toLocaleString();
}

let interval;
onMounted(async () => {
  await userStore.initUser(); // ✅ load user once header mounts
  updateTime();
  interval = setInterval(updateTime, 1000);
});
onUnmounted(() => clearInterval(interval));
</script>
<style scoped>
.img-icon {
  width: 2rem;
  height: 2rem;
  object-fit: contain;
}
</style>
