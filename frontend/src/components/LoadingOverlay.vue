<template>
  <div 
    v-if="show" 
    class="fixed inset-0 bg-black/10 backdrop-blur-sm flex flex-col items-center justify-center z-50"
  >
    <img 
      :src="logo" 
      :alt="altText"
      class="animate-spin rounded-full object-cover"
      :class="typeof size === 'string' ? size : ''"
      :style="typeof size === 'number' ? { width: size + 'px', height: size + 'px' } : {}"
    />
    <p v-if="message" class="mt-4 text-white text-lg">{{ message }}</p>
  </div>
</template>

<script setup>
import { defineProps } from "vue";

const props = defineProps({
  show: { type: Boolean, default: false },
  logo: { type: String, default: "../assets/ISELCO1LOGO.png" },
  size: { type: [String, Number], default: 70 }, // now default is 50px
  message: { type: String, default: "" },
  altText: { type: String, default: "Loading..." }
});
</script>
