<template>
  <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.55);">

    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content rounded-4 overflow-hidden shadow">

        <!-- ================= HEADER ================= -->
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">
            <i class="bi bi-trash me-2"></i> Delete Request for Materials
          </h5>
          <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
        </div>

        <!-- ================= BODY ================= -->
        <div class="modal-body text-center">

          <p class="fw-semibold mb-2">
            Are you sure you want to delete this Request?
          </p>

          <div class="form-control text-center bg-light fw-bold border-0 shadow-sm">
            {{ localRfm.rfm_number }}
          </div>

          <small class="text-muted d-block mt-2">
            This action will mark the request as deleted.
          </small>
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
        <div class="modal-footer d-flex justify-content-end gap-2">
          <button class="btn btn-secondary" :disabled="loading" @click="closeModal">
            <i class="bi bi-x me-1"></i> Close
          </button>

          <button class="btn btn-danger" :disabled="loading" @click="deleteRfm">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            <i v-else class="bi bi-trash me-1"></i>
            Delete
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch } from "vue";
import api from "../../services/api";

const props = defineProps({
  rfm: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["close", "deleted"]);

const remarks = ref("");
const loading = ref(false);
const error = ref("");

// 🔒 local safe copy
const localRfm = reactive({});

// sync kapag nagpalit ng selected RFM
watch(
  () => props.rfm,
  (newVal) => {
    Object.assign(localRfm, newVal || {});
  },
  { immediate: true }
);

function closeModal() {
  if (loading.value) return;
  emit("close");
}

async function deleteRfm() {
  if (!localRfm.rfm_id) {
    alert("Invalid RFM ID");
    return;
  }
  if (!remarks.value || remarks.value.trim().length < 5) {
    error.value = "Remarks is required (minimum 5 characters).";
    return;
  }

  loading.value = true;

  try {
    const res = await api.delete(`/Rfm/delete/${localRfm.rfm_id}`,
      {
        data: {
          delete_remarks: remarks.value
        }
      }
    );
    if (res.data.success) {
      // ✅ notify parent
      emit("deleted", localRfm.rfm_id);
      emit("close");
    }

  } catch (error) {
    error.value = error.response?.data?.message || "Failed to delete RFM.";
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.modal {
  z-index: 1055;
}
</style>
