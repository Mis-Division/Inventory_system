<template>
  <transition name="fade">
    <div
      v-if="appStore.alert.visible"
      class="fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg text-white z-50"
      :class="{
        'bg-green-500': appStore.alert.type === 'success',
        'bg-red-500': appStore.alert.type === 'error',
        'bg-yellow-500': appStore.alert.type === 'warning',
      }"
    >
      {{ appStore.alert.message }}
    </div>
  </transition>
</template>

<script setup>
import { useAppStore } from "../../stores/appStore";
const appStore = useAppStore();
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.4s, transform 0.4s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
