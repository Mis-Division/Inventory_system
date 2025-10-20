<template>
    <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-m modal-dialog-centered" role="document" :class="{ 'modal-sm': isMobile }">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5>Add Item Code</h5>
                    <button type="button" class="btn btn-close btn-close-white" @click="closeModal"></button>
                </div>

                <div class="modal-body">
                    <form class="row">
                        <!-- Item Code -->
                        <div class="col-md-12">
                            <label class="form-label" for="ItemCode">Item Code <span
                                    class="text-danger">*</span></label>
                            <input v-model="form.ItemCode" type="text" id="ItemCode" class="form-control"
                                placeholder="Please input Item Code" :class="{ 'is-invalid': errors.ItemCode }"
                                @input="form.ItemCode = form.ItemCode.replace(/[^0-9]/g, '')" maxlength="20" />
                            <div v-if="errors.ItemCode" class="invalid-feedback">{{ errors.ItemCode }}</div>
                        </div>

                        <!-- Description -->
                        <div class="col-md-12 mt-3">
                            <label class="form-label" for="description">Description <span
                                    class="text-danger">*</span></label>
                            <input v-model="form.description" type="text" id="description" class="form-control"
                                placeholder="Please input Description" :class="{ 'is-invalid': errors.description }" />
                            <div v-if="errors.description" class="invalid-feedback">{{ errors.description }}</div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" @click="closeModal">
                        <i class="bi bi-x-circle me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-success" @click="createItemCode" :disabled="modalLoading">
                        <i class="bi bi-plus-circle me-1"></i>
                        <span v-if="!modalLoading">Save</span>
                        <span v-else>Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div v-if="showConfirm" class="custom-modal-backdrop">
        <div class="custom-modal">
            <div class="custom-header bg-primary text-white">
                <h5 class="mb-0">Confirm Action</h5>
            </div>
            <div class="custom-body">
                <p>Are you sure you want to create this Item Code?</p>
            </div>
            <div class="custom-footer">
                <button class="btn btn-warning" @click="showConfirm = false">Cancel</button>
                <button class="btn btn-success" @click="confirmSave">Yes, Create</button>
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
                ✅ Item Code successfully created!
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
                ❌ Failed to create Item Code. Please try again.
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-danger" @click="showError = false">Close</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onBeforeUnmount, onMounted } from "vue";
import api from "../../services/api";

const emit = defineEmits(["close"]);

const modalLoading = ref(false);
const showConfirm = ref(false);
const showSuccess = ref(false);
const showError = ref(false);
const isMobile = ref(window.innerWidth < 768);

const form = reactive({
    ItemCode: "",
    description: "",
});

const errors = reactive({
    ItemCode: "",
    description: "",
});

// ✅ Proper validation with return value
function validateForm() {
    errors.ItemCode = form.ItemCode ? "" : "Please input Item Code";
    errors.description = form.description ? "" : "Please input the Description";

    return !errors.ItemCode && !errors.description;
}

// ✅ Show confirmation only if valid
function createItemCode() {
    if (!validateForm()) return;
    showConfirm.value = true;
}

// ✅ Send request after confirmation
async function confirmSave() {
    showConfirm.value = false;
    if (!validateForm()) return;

    try {
        modalLoading.value = true;
        await api.post("/Items/createCode", form);
        showSuccess.value = true;
    } catch (err) {
        showError.value = true;
    } finally {
        modalLoading.value = false;
    }
}

// ✅ Success modal close handler
function closeSuccess() {
    showSuccess.value = false;
    closeModal();
}

// ✅ Close modal and emit event to parent
function closeModal() {
    emit("close");
}

// ✅ Handle window resize for responsiveness
function handleResize() {
    isMobile.value = window.innerWidth < 768;
}

onMounted(() => window.addEventListener("resize", handleResize));
onBeforeUnmount(() => window.removeEventListener("resize", handleResize));
</script>
