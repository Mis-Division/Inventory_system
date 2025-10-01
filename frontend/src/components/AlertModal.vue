<template>
  <div v-if="visible" class="modal-overlay">
    <div class="modal-container">
      <!-- Title -->
      <h2 class="modal-title">{{ title }}</h2>

      <!-- Message -->
      <p class="modal-message">{{ message }}</p>

      <!-- Confirm buttons -->
      <div v-if="type === 'confirm'" class="modal-actions">
        <button @click="respond(true)" class="btn btn-danger">Yes</button>
        <button @click="respond(false)" class="btn btn-secondary">No</button>
      </div>

      <!-- Alert button -->
      <div v-else class="modal-actions">
        <button @click="respond(true)" class="btn btn-primary">OK</button>
      </div>
    </div>
  </div>
</template>
<style src="../assets/css/ConfirmModal.css"></style>
<script setup>
import { ref, watch } from "vue";
// import "../../../";

const props = defineProps({
  title: { type: String, default: "Notice" },
  message: { type: String, required: true },
  modelValue: { type: Boolean, default: false },
  type: { type: String, default: "alert" }, // "alert" or "confirm"
});

const emit = defineEmits(["update:modelValue", "response"]);
const visible = ref(props.modelValue);

// keep local `visible` in sync with v-model
watch(
  () => props.modelValue,
  (val) => {
    visible.value = val;
  }
);

function respond(answer) {
  visible.value = false;
  emit("update:modelValue", false); // close modal
  emit("response", answer); // ✅ send true (Yes/OK) or false (No)
}
</script>
