<template>
  <header
    class="bg-slate-800 text-white h-14 flex items-center shadow-md fixed top-0 left-0 right-0 z-50 px-6"
  >
     <!-- Logo + Title -->
    <div class="flex items-center gap-2">
      <img
        src="../assets/ISELCO1LOGO.png"
        alt="Logo"
        class="h-8 w-8 object-contain"
      />
      <h1 class="text-lg font-bold">Warehouse Inventory System</h1>
    </div>

    <!-- Right side -->
    <div class="flex flex-col items-end ml-auto">
      <h1 class="text-base font-medium">
        Welcome, {{ userStore.user?.fullname || userStore.user?.username || "Guest" }}
      </h1>
      <span class="text-xs text-gray-300">{{ currentDateTime }}</span>
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
