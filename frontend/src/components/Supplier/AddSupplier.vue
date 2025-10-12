<template>
    <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document"  :class="{ 'modal-sm': isMobile }">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5>Add Supplier</h5>
                    <button type="button" class="btn btn-close btn-close-white" @click="closeModal"></button>
                </div>

                <div class="modal-body">
                    <form class="row">
                        <div class="col-md-6">
                            <label class="form-label" for="supplier_name">Supplier Name <span
                                    class="text-danger">*</span></label>
                            <input v-model="form.supplier_name" type="text" id="supplier_name" class="form-control"
                                placeholder="Please input Supplier Name"
                                :class="{ 'is-invalid': errors.supplier_name }" />
                            <div v-if="errors.supplier_name" class="invalid-feedback">{{ errors.supplier_name }}</div>
                        </div>

                        <div class="col-md-6">
                            <label for="contact_person" class="form-label">Contact Person <span
                                    class="text-danger">*</span></label>
                            <input v-model="form.contact_person" type="text" id="contact_person" class="form-control"
                                placeholder="Please enter Contact Person"
                                :class="{ 'is-invalid': errors.contact_person }" />
                            <div v-if="errors.contact_person" class="invalid-feedback">{{ errors.contact_person }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="email">Email Address</label>
                            <input v-model="form.email" type="email" id="email" class="form-control"
                                placeholder="Please enter a valid email address"
                                :class="{ 'is-invalid': errors.email }" />
                            <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input v-model="form.phone" type="text" id="phone" class="form-control" maxlength="11"
                                placeholder="0945xxxxxxx" @input="form.phone = form.phone.replace(/[^0-9]/g, '')"
                                :class="{ 'is-invalid': errors.phone }" />
                            <div v-if="errors.phone" class="invalid-feedback">{{ errors.phone }}</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label" for="address">Address</label>
                            <input v-model="form.address" type="text" id="address" class="form-control"
                                placeholder="Please enter complete address" :class="{ 'is-invalid': errors.address }" />
                            <div v-if="errors.address" class="invalid-feedback">{{ errors.address }}</div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" @click="closeModal">
                        <i class="bi bi-x-circle me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-success" @click="createSupplier">
                        <i class="bi bi-plus-circle me-1"></i>Save
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
                <p>Are you sure you want to create this supplier?</p>
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
                ✅ Supplier successfully created!
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
                ❌ Failed to create supplier. Please try again.
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-danger" @click="showError = false">Close</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onBeforeUnmount, onMounted  } from "vue";
import api from "../../services/api";


const emit = defineEmits(["close"]);

const modalLoading = ref(false);
const showConfirm = ref(false);
const showSuccess = ref(false);
const showError = ref(false);
const isMobile = ref(window.innerWidth < 768);


const form = reactive({
    supplier_name: "",
    contact_person: "",
    email: "",
    phone: "",
    address: "",
});

const errors = reactive({
    supplier_name: "",
    contact_person: "",
    email: "",
    phone: "",
    address: "",
});

function createSupplier() {
    if (!validateForm()) return;
    showConfirm.value = true;
}

function validateForm() {
    errors.supplier_name = form.supplier_name ? "" : "Supplier name is required";
    errors.contact_person = form.contact_person ? "" : "Contact person is required";
    errors.email = form.email ? "" : "Email address is required";
    errors.phone = form.phone ? "" : "Phone number is required";
    errors.address = form.address ? "" : "Complete address is required";
    return Object.values(errors).every((e) => !e);
}

async function confirmSave() {
    showConfirm.value = false;
    if (!validateForm()) return;
    try {
        modalLoading.value = true;
        await api.post("/suppliers/create_supplier/", form);
        showSuccess.value = true;
    } catch (err) {
        showError.value = true;
    } finally {
        modalLoading.value = false;
    }
}

function closeModal() {
    emit("close");
}

function closeSuccess() {
    showSuccess.value = false;
    emit("close");
}
function handleResize() {
  isMobile.value = window.innerWidth < 768;
}
onMounted(() => window.addEventListener("resize", handleResize));
onBeforeUnmount(() => window.removeEventListener("resize", handleResize));
</script>
