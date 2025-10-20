<template>
        <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0, 0, 0, 0.5);">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                        <div class="modal-content">
                                <div class="modal-header bg-warning text-white">
                                        <h5 class="modal-title">Update Item Code</h5>
                                        <button type="button" class="btn-close" aria-label="Close"
                                                @click="closeModal"></button>
                                </div>
                                <!-- Body -->
                                <div class="modal-body">
                                        <form @submit.prevent="showConfirm = true">
                                                <div class="mb-3">
                                                        <label class="form-label">Item Code</label>
                                                        <input v-model="form.ItemCode" type="text" class="form-control"
                                                                required />
                                                </div>

                                                <div class="mb-3">
                                                        <label class="form-label">Description</label>
                                                        <textarea v-model="form.description" class="form-control"
                                                                rows="3" required></textarea>
                                                </div>

                                                <div v-if="errorMessage" class="alert alert-danger py-2">
                                                        {{ errorMessage }}
                                                </div>
                                                <div class="text-end">
                                                        <button type="button" class="btn btn-secondary me-2"
                                                                @click="closeModal">Cancel</button>
                                                        <button type="submit" class="btn btn-success"
                                                                :disabled="loading">
                                                                <span v-if="loading"
                                                                        class="spinner-border spinner-border-sm me-2"></span>
                                                                Save Changes
                                                        </button>
                                                </div>
                                        </form>
                                </div>
                        </div>
                </div>

                <!-- Confirm Modal -->
                <div v-if="showConfirm" class="custom-modal-backdrop">
                        <div class="custom-modal">
                                <div class="custom-header bg-primary text-white">
                                        <h5 class="mb-0">Confirm Action</h5>
                                </div>
                                <div class="custom-body">
                                        <p>Are you sure you want to update this Item Code?</p>
                                </div>
                                <div class="custom-footer">
                                        <button class="btn btn-warning" @click="showConfirm = false">Cancel</button>
                                        <button class="btn btn-success" @click="confirmSave">Yes, Update</button>
                                </div>
                        </div>
                </div>

                <!-- Success Modal -->
                <div v-if="showSuccess" class="custom-modal-backdrop">
                        <div class="custom-modal">
                                <div class="custom-header bg-success text-white">
                                        <h5 class="mb-0">Success</h5>
                                </div>
                                <div class="custom-body text-center">
                                        ✅ Item Code successfully updated!
                                </div>
                                <div class="custom-footer text-center">
                                        <button class="btn btn-success" @click="closeSuccess">OK</button>
                                </div>
                        </div>
                </div>

                <!-- Error Modal -->
                <div v-if="showError" class="custom-modal-backdrop">
                        <div class="custom-modal">
                                <div class="custom-header bg-danger text-white">
                                        <h5 class="mb-0">Error</h5>
                                </div>
                                <div class="custom-body text-center">
                                        ❌ Failed to update Item Code. Please try again.
                                </div>
                                <div class="custom-footer text-center">
                                        <button class="btn btn-danger" @click="showError = false">Close</button>
                                </div>
                        </div>
                </div>
        </div>
</template>

<script setup>
import { ref, watch } from "vue";
import api from "../../services/api";
import { useAppStore } from "../../stores/appStore";

// Props
const props = defineProps({
        item: {
                type: Object,
                required: true,
        },
});

// Emits
const emit = defineEmits(["close", "updated"]);

const appStore = useAppStore();
const form = ref({
        ItemCode: "",
        description: "",
});

const loading = ref(false);
const errorMessage = ref("");
const showConfirm = ref(false);
const showSuccess = ref(false);
const showError = ref(false);

// Watch for prop changes
watch(
        () => props.item,
        (newItem) => {
                if (newItem) {
                        form.value.ItemCode = newItem.ItemCode || "";
                        form.value.description = newItem.description || "";
                }
        },
        { immediate: true }
);

// ✅ Confirm Update Action
async function confirmSave() {
        showConfirm.value = false;
        await updateItemCode();
}

// ✅ Update Function
async function updateItemCode() {
        if (!props.item?.ItemCode_id) {
                errorMessage.value = "Invalid item selected.";
                return;
        }

        try {
                loading.value = true;
                appStore.showLoading();
                const payload = {
                        ItemCode: form.value.ItemCode,
                        description: form.value.description,
                };
                await api.put(`/Items/updateItemCode/${props.item.ItemCode_id}`, payload);
                showSuccess.value = true;
                emit("updated"); // Refresh parent list
        } catch (err) {
                console.error("❌ Update failed:", err);
                showError.value = true;
                errorMessage.value = err.response?.data?.message || "Failed to update item code.";
        } finally {
                loading.value = false;
                appStore.hideLoading();
        }
}

// ✅ Close modals
function closeModal() {
        emit("close");
}
function closeSuccess() {
        showSuccess.value = false;
        closeModal();
}
</script>
