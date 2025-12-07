<template>
    <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document" :class="{ 'modal-sm': isMobile }">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5>Add Supplier</h5>
                    <button type="button" class="btn btn-close btn-close-white" @click="closeModal"></button>
                </div>

                <div class="modal-body">
                    <form class="row">
                        <div class="col-md-12">
                            <label class="form-label" for="supplier_no">Supplier No <span
                                    class="text-danger">*</span></label>
                            <input v-model="form.supplier_no" type="text" id="supplier_name" class="form-control"
                                placeholder="Please input Supplier No" :class="{ 'is-invalid': errors.supplier_no }" />
                            <div v-if="errors.supplier_no" class="invalid-feedback">{{ errors.supplier_no }}</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="AccountCode">Account Code <span
                                    class="text-danger">*</span></label>
                            <input v-model="form.AccountCode" type="text" id="AccountCode"
                                class="form-control" placeholder="Please input Account Code"
                                :class="{ 'is-invalid': errors.AccountCode }"/>
                            <div v-if="errors.AccountCode" class="invalid-feedback">{{ errors.AccountCode }}</div>
                        </div>

                        <div class="col-md-12">
                            <label for="supplier_name" class="form-label">Supplier Name <span
                                    class="text-danger">*</span></label>
                            <input v-model="form.supplier_name" type="text" id="supplier_name" class="form-control"
                                placeholder="Please enter Supplier Name"
                                :class="{ 'is-invalid': errors.supplier_name }" />
                            <div v-if="errors.supplier_name" class="invalid-feedback">{{ errors.supplier_name }}</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="address">Address</label>
                            <input v-model="form.address" type="text" id="address" class="form-control"
                                placeholder="Please enter complete address" :class="{ 'is-invalid': errors.address }" />
                            <div v-if="errors.address" class="invalid-feedback">{{ errors.address }}</div>
                        </div>


                        <div class="col-md-12">
                            <label class="form-label" for="contact_no">Contact Number</label>
                            <input v-model="form.contact_no" type="text" id="contact_no" class="form-control"
                                maxlength="11" placeholder="0945xxxxxxx"
                                @input="form.contact_no = form.contact_no.replace(/[^0-9]/g, '')"
                                :class="{ 'is-invalid': errors.contact_no }" />
                            <div v-if="errors.contact_no" class="invalid-feedback">{{ errors.contact_no }}</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label" for="contact_person">Contact Person</label>
                            <input v-model="form.contact_person" type="text" id="contact_person" class="form-control" />
                            <div v-if="errors.contact_person" class="invalid-feedback">{{ errors.contact_person }}</div>
                        </div>


                        <div class="col-md-12">
                            <label class="form-label" for="tin">Tin#</label>
                            <input v-model="form.tin" type="text" id="tin" class="form-control"
                                @input="form.tin = form.tin.replace(/[^0-9]/g, '')"
                                placeholder="Please enter Supplier Tin#" :class="{ 'is-invalid': errors.tin }"
                                maxlength="20" />
                            <div v-if="errors.tin" class="invalid-feedback">{{ errors.tin }}</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="vat_type">VAT / NVAT REGISTER</label>
                            <input v-model="form.vat_type" type="text" id="vat_type" class="form-control"
                                placeholder="Please enter VAT / Non-VAT" :class="{ 'is-invalid': errors.vat_type }" />
                            <div v-if="errors.vat_type" class="invalid-feedback">{{ errors.vat_type }}</div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" @click="closeModal">
                        <i class="bi bi-x-circle me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-primary" @click="createSupplier">
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
                <button class="btn btn-danger" @click="showConfirm = false">Cancel</button>
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
import { reactive, ref, onBeforeUnmount, onMounted } from "vue";
import api from "../../services/api";


const emit = defineEmits(["close"]);

const modalLoading = ref(false);
const showConfirm = ref(false);
const showSuccess = ref(false);
const showError = ref(false);
const isMobile = ref(window.innerWidth < 768);


const form = reactive({
    supplier_no: "",
    supplier_name: "",
    AccountCode: "",
    contact_no: "",
    contact_person: "",
    address: "",
    tin: "",
    vat_type: "",
});

const errors = reactive({
     supplier_no: "",
    supplier_name: "",
    AccountCode: "",
    contact_no: "",
    contact_person: "",
    address: "",
    tin: "",
    vat_type: "",
});

function createSupplier() {
    if (!validateForm()) return;
    showConfirm.value = true;
}

function validateForm() {
    errors.supplier_name = form.supplier_name ? "" : "Supplier name is required";
    errors.supplier_no = form.supplier_no ? "" : "Supplier# is required";
    errors.AccountCode = form.AccountCode ? "" : "Account Code is required";
    errors.contact_no = form.contact_no ? "" : "Phone number is required";
    errors.address = form.address ? "" : "Complete address is required";
    errors.tin = form.tin ? "" : "Tin Number is Required";
    errors.contact_person = form.contact_person ? "" : "Contact Person is Required";
    errors.vat_type = form.vat_type ? "" : "VAT / NVAT is Required"
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
