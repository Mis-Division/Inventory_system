<template>
        <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-m modal-dialog-centered" role="document">
                        <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                        <h5 class="mb-0"><i class="bi bi-trash me-2"></i> Delete</h5>
                                        <button type="button" class=" btn btn-close btn-close-whte"
                                                @click="closeModal"></button>
                                </div>
                                <div class="modal-body">
                                        <form class="row">
                                                <div class="col-12 text-center">
                                                        <label class="form=label fw-bold mb-3">
                                                                Are you sure you want to Delete this Record!
                                                        </label>
                                                        <div
                                                                class="form-control bg-light text-center fw-semibold border-0 shadow-sm">
                                                                {{ localCategory.ItemCode }}
                                                        </div>
                                                </div>
                                        </form>
                                </div>
                                <div class="modal-footer">
                                        <button class="btn btn-primary" @click="closeModal">
                                                <i class="bi bi-x me-1"></i>Close
                                        </button>
                                        <button class="btn btn-danger" @click="deleteCategoty">
                                                <i class="bi bi-trash me-1"></i>Delete
                                        </button>
                                </div>
                        </div>
                </div>
        </div>
        <!-- ✅ Success Modal -->
        <div v-if="showSuccess" class="custom-modal-backdrop">
                <div class="custom-modal">
                        <div class="custom-header bg-success text-white">
                                <h5 class="mb-0">Success</h5>
                        </div>
                        <div class="custom-body text-center">
                                ✅ Category Successfully Deleted!
                        </div>
                        <div class="custom-footer text-center">
                                <button class="btn btn-success" @click="closeSuccess">OK</button>
                        </div>
                </div>
        </div>
</template>
<script setup>
import { useAppStore } from '../../stores/appStore';
import { reactive, ref } from 'vue';
import api from "../../services/api";

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["close", "deleted"]);
const appStore = useAppStore();
const localCategory = reactive({ ...props.item });
const showSuccess = ref(false);

async function deleteCategoty() {
  try {
    appStore.showLoading();
    await api.delete(`/Items/deleteItemCode/${localCategory.ItemCode_id}`);
    showSuccess.value = true;
  } catch (err) {
    console.error("Delete Failed", err);
  } finally {
    appStore.hideLoading();
  }
}

function closeModal() {
  emit("close");
}

function closeSuccess() {
  showSuccess.value = false;
  emit("deleted"); // ✅ Now trigger parent refresh/close
}
</script>
