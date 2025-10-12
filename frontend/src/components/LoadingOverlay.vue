<template>
  <div 
    v-if="show" 
    class="fixed top-0 left-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center bg-dark bg-opacity-50"
    style="z-index: 9999;"
  >
    <img 
      :src="logo" 
      :alt="altText"
      class="spinner"
      :style="typeof size === 'number' ? { width: size + 'px', height: size + 'px' } : {}"
    />
    <p v-if="message" class="mt-3 text-white fw-bold">{{ message }}</p>
  </div>
</template>

<script setup>
const props = defineProps({
  show: { type: Boolean, default: false },
  logo: { type: String, default: "../assets/ISELCO1LOGO.png" },
  size: { type: [String, Number], default: 70 },
  message: { type: String, default: "" },
  altText: { type: String, default: "Loading..." }
});
</script>

<style scoped>
.spinner {
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
