<template>
  <!-- BACKDROP -->
  <div class="delete-backdrop">
    <div class="delete-dialog">
      <div class="delete-container">

        <!-- HEADER -->
        <div class="delete-header">
          <h5>
            <i class="bi bi-trash-fill me-2"></i>
            Delete MCRT
          </h5>
          <button class="btn-close" @click="close"></button>
        </div>

        <!-- BODY -->
        <div class="delete-body">
          <p class="title-text">
            Are you sure you want to delete
          </p>

          <p class="mcrt-text">
            <strong>{{ item.mcrt_number || item.mcrt_no }}</strong>
          </p>

          <p class="desc-text">
            This will reverse all returned items back to stock and mark this MCRT as deleted.
          </p>

          <!-- ERROR -->
          <div v-if="errorMessage" class="alert alert-danger py-2 mt-3">
            {{ errorMessage }}
          </div>
        </div>

        <!-- FOOTER -->
        <div class="delete-footer">
          <button class="btn btn-light px-4" @click="close" :disabled="loading">
            Cancel
          </button>

          <button 
            class="btn btn-danger px-4" 
            @click="confirmDelete" 
            :disabled="loading"
          >
            <i v-if="loading" class="spinner-border spinner-border-sm me-2"></i>
            Delete
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import api from "../../services/api";

const props = defineProps({
  item: { type: Object, required: true }
});

const emit = defineEmits(["close", "confirm"]);
const loading = ref(false);
const errorMessage = ref("");

function close() {
  emit("close");
}

async function confirmDelete() {
  errorMessage.value = "";
  loading.value = true;

  try {
    const res = await api.delete(`/Mcrt/McrtDelete/${props.item.mcrt_id}`);

    if (res.data?.success) {
      emit("confirm");
      emit("close");
    } else {
      errorMessage.value = res.data?.message || "Failed to delete MCRT.";
    }
  } catch (err) {
    errorMessage.value =
      err.response?.data?.message || "An error occurred during deletion.";
  }

  loading.value = false;
}
</script>

<style scoped>
/* Backdrop */
.delete-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 3000;
}

/* Dialog wrapper */
.delete-dialog {
  width: 420px;
  max-width: 90%;
}

/* Modal container */
.delete-container {
  background: #fff;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
  animation: fadeIn 0.25s ease-out;
}

/* Header */
.delete-header {
  padding: 16px 20px;
  background: #dc3545;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.delete-header h5 {
  margin: 0;
  font-weight: 600;
}

/* Body */
.delete-body {
  padding: 25px 25px 10px;
  text-align: center;
}

.title-text {
  font-size: 1.1rem;
  margin-bottom: 5px;
}

.mcrt-text {
  font-size: 1.3rem;
  margin-bottom: 10px;
  color: #dc3545;
}

.desc-text {
  font-size: 0.9rem;
  color: #6c757d;
}

/* Footer */
.delete-footer {
  padding: 15px 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid #eee;
}

/* Animation */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px) scale(0.96); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
