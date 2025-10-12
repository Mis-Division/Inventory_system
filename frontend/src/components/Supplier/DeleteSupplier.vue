<template>
    <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document" :class="{ 'modal-sm': isMobile }">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-trash me-2"></i>Delete Supplier</h5>
                    <button type="button" class="btn btn-close btn-close-white" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <form class="row g-3">
                        <div class="col-12 text-center">
                            <label class="form-label fw-bold mb-3">
                                Are you sure you want to delete?
                            </label>
                            <div class="form-control bg-light text-center fw-semibold border-0 shadow-sm">
                                {{ localSupplier.supplier_name }}
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeModal">
                        <i class="bi bi-x me-1"></i>Cancel
                    </button>
                    <button class="btn btn-danger" @click="deleteSupplier">
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
                ✅ Supplier successfully deleted!
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-success" @click="closeSuccess">OK</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from "vue";
import api from "../../services/api";
import { useAppStore } from "../../stores/appStore";

const props = defineProps({
    supplier: {
        type: Object,
        required: true,
    },
});
const emit = defineEmits(["close", "updated"]);

const appStore = useAppStore();
const localSupplier = reactive({ ...props.supplier });
const showSuccess = ref(false);

async function deleteSupplier() {
    try {
        appStore.showLoading();
        await api.delete(`/suppliers/delete_supplier/${localSupplier.supplier_id}`);
        emit("updated");
        await new Promise((resolve) => setTimeout(resolve, 200));
        emit("close");
        showSuccess.value = true;
        await new Promise((resolve) => setTimeout(resolve, 1500));
        showSuccess.value = false;
    } catch (err) {
        console.error("Delete failed:", err);
    } finally {
        appStore.hideLoading();
    }
}


function closeModal() {
    emit("close");
}

function closeSuccess() {
    showSuccess.value = false;
    emit("updated");
}
</script>
