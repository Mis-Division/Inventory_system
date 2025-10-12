<template>
    <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document" :class="{ 'modal-sm': isMobile }">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5>Update Supplier</h5>
                    <button type="button" class="btn btn-close btn-close-white" @click="closeModal"></button>
                </div>

                <div class="modal-body">
                    <form class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Supplier Name <span class="text-danger">*</span></label>
                            <input v-model="localSupplier.supplier_name" type="text" class="form-control" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Contact Person <span class="text-danger">*</span></label>
                            <input v-model="localSupplier.contact_person" type="text" class="form-control" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input v-model="localSupplier.email" type="text" class="form-control" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input v-model="localSupplier.phone" type="text" class="form-control" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <input v-model="localSupplier.address" type="text" class="form-control" />
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeModal">  <i class="bi bi-x me-1"></i>Cancel</button>
                    <button class="btn btn-success" @click="updateSupplier">  <i class="bi bi-pencil me-1"></i>Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ Confirmation Modal -->
    <div v-if="showConfirm" class="custom-modal-backdrop">
        <div class="custom-modal">
            <div class="custom-header bg-primary text-white">
                <h5 class="mb-0">Confirm Action</h5>
            </div>
            <div class="custom-body">
                <p>Are you sure you want to update this supplier?</p>
            </div>
            <div class="custom-footer">
                <button class="btn btn-warning" @click="showConfirm = false">Cancel</button>
                <button class="btn btn-success" @click="confirmSave">Yes, Update</button>
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
                ✅ Supplier successfully updated!
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-success" @click="closeSuccess">OK</button>
            </div>
        </div>
    </div>

    <!-- ❌ Error Modal -->
    <div v-if="showError" class="custom-modal-backdrop">
        <div class="custom-modal">
            <div class="custom-header bg-danger text-white">
                <h5 class="mb-0">Error</h5>
            </div>
            <div class="custom-body text-center">
                ❌ Failed to update supplier. Please try again.
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-danger" @click="showError = false">Close</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted, nextTick } from "vue";
import api from "../../services/api";
import { useAppStore } from "../../stores/appStore";

const appStore = useAppStore();
const modalLoading = ref(false);
const showConfirm = ref(false);
const showSuccess = ref(false);
const showError = ref(false);
const isMobile = ref(window.innerWidth < 768);

const props = defineProps({
    supplier: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["close", "updated"]);
const localSupplier = reactive({ ...props.supplier });

function closeModal() {
    emit("close");
}

function updateSupplier() {
    showConfirm.value = true;
}

async function confirmSave() {
    try {
        appStore.showLoading();
        modalLoading.value = true;
        await api.put(`/suppliers/update_supplier/${localSupplier.supplier_id}`,localSupplier);
        showConfirm.value = false;
        showSuccess.value = true;
        emit("updated");
    } catch (err) {
        console.error("Update failed:", err);
        showError.value = true;
    } finally {
        modalLoading.value = false;
        appStore.hideLoading();
    }
}

async function closeSuccess() {
    showSuccess.value = false;

    // ✅ Wait for DOM update before showing loading
    await nextTick();

    // ✅ Show global loading overlay briefly after success
    appStore.showLoading();
    await new Promise((resolve) => setTimeout(resolve, 1000));
    appStore.hideLoading();

    emit("close");
}

function handleResize() {
    isMobile.value = window.innerWidth < 768;
}

onMounted(() => window.addEventListener("resize", handleResize));
</script>
