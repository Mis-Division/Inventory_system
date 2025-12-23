<template>
  <div class="custom-modal-backdrop">
    <div class="custom-modal">

      <!-- ================= HEADER ================= -->
      <div class="custom-header bg-danger text-white">
        <h5 class="mb-0">
          <i class="bi bi-exclamation-triangle me-1"></i>
          Confirm Delete
        </h5>
      </div>

      <!-- ================= BODY ================= -->
      <div class="custom-body">
        <p class="fw-semibold mb-1">
          Are you sure you want to delete this MRV?
        </p>

        <p class="text-muted mb-3">
          MRV No: <strong>{{ mrv.mrv_number ?? mrv.mrv_id }}</strong><br>
          This action cannot be undone.
        </p>

        <label class="form-label fw-semibold">
          Reason for deletion <span class="text-danger">*</span>
        </label>

        <textarea class="form-control" rows="4" v-model="remarks"
          placeholder="Please provide a reason for deleting this MRV..."></textarea>

        <small v-if="error" class="text-danger">
          {{ error }}
        </small>
      </div>

      <!-- ================= FOOTER ================= -->
      <div class="custom-footer text-end">
        <button class="btn btn-secondary me-2" @click="$emit('close')">
          Cancel
        </button>
        <button class="btn btn-danger" @click="confirmDelete">
          Yes, Delete
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import api from "../../services/api";

const props = defineProps({
  mrv: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(["close"]);

const remarks = ref("");
const error = ref("");

// ================= CONFIRM DELETE =================
const confirmDelete = async () => {
  if (!remarks.value || remarks.value.trim().length < 5) {
    error.value = "Remarks is required (minimum 5 characters).";
    return;
  }

  try {
    const res = await api.delete(
      `Mrv/deleteMrv/${props.mrv.mrv_id}`,
      {
        data: {
          remarks: remarks.value
        }
      }
    );

    if (res.data.success) {
      emit("close"); // close modal
    }

  } catch (err) {
    error.value =
      err.response?.data?.message || "Failed to delete MRV.";
  }
};
</script>

<style scoped>
.custom-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.custom-modal {
  width: 430px;
  background: #fff;
  border-radius: 14px;
  overflow: hidden;
}

.custom-header,
.custom-footer {
  padding: 12px;
}

.custom-body {
  padding: 18px;
}
</style>
