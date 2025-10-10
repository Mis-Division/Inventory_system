<template>
  <header class="bg-success text-white d-flex align-items-center justify-content-between shadow fixed-top px-4"
    style="height: 56px; z-index: 1050;">
    <!-- Left: Logo + Title -->
    <div class="d-flex align-items-center gap-2">
      <img src="../assets/ISELCO1LOGO.png" alt="Logo" class="img-icon" />
      <h2 class="h5 mb-0">Warehouse Inventory System</h2>
    </div>

    <!-- Right: User Info + Logout -->
    <div class="d-flex align-items-center gap-3">
      <div class="text-end">
        <h6 class="mb-0 fw-medium">
          Welcome,
          {{ userStore.user?.fullname || userStore.user?.username || "Guest" }}
        </h6>
        <small class="text-light opacity-75">{{ currentDateTime }}</small>
      </div>

      <button @click="userStore.logout" class="btn btn-outline-light d-flex align-items-center" title="Logout">
        <i class="bi bi-power "></i>
      </button>
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
  width: 2.5rem;
  height: 2.5rem;
  object-fit: contain;
}
.btn-outline-light:hover {
  background-color: #f30e0e;
}
</style>
