<template>
    <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-m modal-dialog-centered" :class="{ 'modal-sm': isMobile }">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5><i class="bi bi-info-circle"></i> Add Item</h5>
                    <button class="btn btn-close btn-close-white" @click="closeModal"></button>
                </div>

                <div class="modal-body">
                    <form class="row">

                        <!-- PRODUCT NAME -->
                        <div class="col-md-12">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input v-model="form.product_name" type="text" class="form-control"
                                placeholder="Please input Product Name"
                                :class="{ 'is-invalid': errors.product_name }" />
                            <div v-if="errors.product_name" class="invalid-feedback">
                                {{ errors.product_name }}
                            </div>
                        </div>

                        <!-- DESCRIPTION -->
                        <div class="col-md-12 mt-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <input v-model="form.description" type="text" class="form-control"
                                placeholder="Please input Description"
                                :class="{ 'is-invalid': errors.description }" />
                            <div v-if="errors.description" class="invalid-feedback">
                                {{ errors.description }}
                            </div>
                        </div>

                        <!-- ACCOUNTING CODE -->
                        <div class="col-md-12 mt-3">
                            <label class="form-label">Acct. Code</label>
                            <input v-model="form.accounting_code" type="text" class="form-control"
                                placeholder="Please input Accounting Code"
                                :class="{ 'is-invalid': errors.accounting_code }" />
                            <div v-if="errors.accounting_code" class="invalid-feedback">
                                {{ errors.accounting_code }}
                            </div>
                        </div>

                        <!-- CATEGORY -->
                        <div class="col-md-12 mt-3">
                            <label class="form-label">Item Category</label>
                            <select v-model="form.item_category" class="form-select">
                                <option value="">Select Category</option>
                                <option value="Line Hardware">Line Hardware</option>
                                <option value="Special Hardware">Special Hardware</option>
                                <option value="Others">Others</option>
                            </select>
                            <div v-if="errors.item_category" class="invalid-feedback">{{ errors.item_category }}</div>
                        </div>

                        <!-- UNITS -->
                        <div class="col-md-12 mt-3">
                            <label class="form-label">Units</label>
                            <select class="form-select" id="unitsName" v-model="selectUnits" style="width: 100%;">
                                <option value="">Select Unit</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.unit_name">
                                    {{ unit.unit_name }}
                                </option>
                            </select>
                            <div v-if="errors.units" class="invalid-feedback">{{ errors.units }}</div>
                        </div>

                    </form>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-warning" @click="closeModal">
                        <i class="bi bi-x-circle me-1"></i> Close
                    </button>
                    <button class="btn btn-success" @click="createItemCode" :disabled="modalLoading">
                        <i class="bi bi-plus-circle me-1"></i>
                        <span v-if="!modalLoading">Create</span>
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
                <p>Are you sure you want to create this item?</p>
            </div>
            <div class="custom-footer">
                <button class="btn btn-warning" @click="showConfirm = false">Cancel</button>
                <button class="btn btn-success" @click="confirmSave">Yes, Create</button>
            </div>
        </div>
    </div>

    <!-- Success -->
    <div v-if="showSuccess" class="custom-modal-backdrop">
        <div class="custom-modal">
            <div class="custom-header bg-success text-white">
                <h5 class="mb-0">Success</h5>
            </div>
            <div class="custom-body text-center">
                ✅ Item successfully created!
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-success" @click="closeSuccess">OK</button>
            </div>
        </div>
    </div>

    <!-- Error -->
    <div v-if="showError" class="custom-modal-backdrop">
        <div class="custom-modal">
            <div class="custom-header bg-danger text-white">
                <h5 class="mb-0">Error</h5>
            </div>
            <div class="custom-body text-center">
                ❌ Failed to create Item. Please try again.
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-danger" @click="showError = false">Close</button>
            </div>
        </div>
    </div>
</template>


<script setup>
import { reactive, ref, onBeforeUnmount, onMounted, nextTick } from "vue";
import api from "../../services/api";

const emit = defineEmits(["close"]);

const modalLoading = ref(false);
const showConfirm = ref(false);
const showSuccess = ref(false);
const showError = ref(false);
const isMobile = ref(window.innerWidth < 768);
const units = ref([]);
const selectUnits = ref("");

const form = reactive({
    product_name: "",
    description: "",
    accounting_code: "",
    item_category: "",
    units: "",
});

const errors = reactive({
    product_name: "",
    description: "",
    // accounting_code: "",
    item_category: "",
    units: ""
});

// Validation
function validateForm() {
    errors.product_name = form.product_name ? "" : "Please input product name";
    errors.description = form.description ? "" : "Please input description";
    errors.item_category = form.item_category ? "" : "Please select item category";
    errors.units = form.units ? "" : "Please select units";

    return !errors.product_name && !errors.description && !errors.item_category && !errors.units;
}

// ---------------- FETCH UNITS ----------------
async function fetchUnits() {
    try {
        const res = await api.get("Units/display");
        units.value = res.data.data || [];
        await nextTick(initUnitSelect)
    } catch (err) {
        console.error("Failed to fetch units", err);
    }
}
// ---------------- UNIT SELECT2 (PER ROW) ----------------
function initUnitSelect() {
    // Destroy previous select2 instance
    if ($("#unitsName").data("select2")) {
        $("#unitsName").select2("destroy");
    }

    // Initialize Select2 properly
    $("#unitsName").select2({
        theme: "bootstrap-5",
        placeholder: "Select Unit",
        allowClear: true,
        dropdownParent: $(".modal.show"), // spelling fix: dropdownParent
    }).on("change", function () {
        selectUnits.value = $(this).val();
        form.units = $(this).val(); // FIX: save to form
    });
}


function createItemCode() {
    if (!validateForm()) return;
    showConfirm.value = true;
}

async function confirmSave() {
    showConfirm.value = false;

    try {
        modalLoading.value = true;

        await api.post("/Items/createCode", form); // ItemCode AUTO-GENERATED in Laravel

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

onMounted(() => {
    window.addEventListener("resize", handleResize);
    fetchUnits().then(() => nextTick(() => {
        initUnitSelect();
    }));
});
onBeforeUnmount(() => window.removeEventListener("resize", handleResize));
</script>
