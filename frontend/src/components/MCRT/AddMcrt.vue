<template>
    <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-auto-fit">
            <div class="modal-content">

                <!-- HEADER -->
                <div class="modal-header bg-primary text-white">
                    <h5><i class="bi bi-info-circle"></i> Add Material Credit Ticket</h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
                </div>

                <!-- BODY -->
                <div class="modal-body">

                    <!-- HEADER FORM FIELDS -->
                    <form class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Returned By <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.returned_by"
                                :class="{ 'is-invalid': fieldErrors.returned_by }" placeholder="Enter name...">
                            <div v-if="fieldErrors.returned_by" class="error-text">
                                {{ fieldErrors.returned_by }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Work Order <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.work_order"
                                :class="{ 'is-invalid': fieldErrors.work_order }">
                            <div v-if="fieldErrors.work_order" class="error-text">
                                {{ fieldErrors.work_order }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Job Order <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.job_order"
                                :class="{ 'is-invalid': fieldErrors.job_order }">
                            <div v-if="fieldErrors.job_order" class="error-text">
                                {{ fieldErrors.job_order }}
                            </div>
                        </div>

                    </form>

                    <hr class="my-4">

                    <!-- ITEMS HEADER -->
                    <div class="d-flex justify-content-end mb-2">
                        <button class="btn btn-primary btn-sm" @click="addItem">
                            <i class="bi bi-plus-lg"></i> Add Item
                        </button>
                    </div>

                    <!-- ITEMS TABLE -->
                    <div class="table-responsive" ref="tableWrapper" style="max-height:300px; overflow-y:auto;">
                        <table class="table align-middle text-center">

                            <thead class="table-light">
                                <tr>
                                    <th style="width:18%">Item Code</th>
                                    <th style="width:25%">Description</th>
                                    <th style="width:10%">Units</th>
                                    <th style="width:10%">Qty Return</th>
                                    <th style="width:15%">Condition</th>
                                    <th style="width:12%">Cost</th>
                                    <th style="width:5%">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-for="(item, index) in mcrtItems" :key="index"
                                    :class="{ 'row-error': itemErrors[index] && Object.keys(itemErrors[index]).length > 0 }">

                                    <!-- ITEM CODE SELECT2 -->
                                    <td>
                                        <select class="form-select text-center" :id="'itemSelect' + index"
                                            v-model="item.itemcode_id"
                                            :class="{ 'is-invalid': itemErrors[index]?.itemcode || itemErrors[index]?.duplicate }">
                                            <option value="">Select Item</option>
                                            <option v-for="i in itemsList" :key="i.ItemCode_id" :value="i.ItemCode_id">
                                                {{ i.ItemCode }}
                                            </option>
                                        </select>

                                        <div v-if="itemErrors[index]?.itemcode" class="error-text">
                                            {{ itemErrors[index].itemcode }}
                                        </div>

                                        <div v-if="itemErrors[index]?.duplicate" class="error-text">
                                            Duplicate ItemCode.
                                        </div>
                                    </td>

                                    <!-- DESCRIPTION -->
                                    <td>
                                        <input type="text" class="form-control" disabled v-model="item.description"
                                            :class="{ 'is-invalid': itemErrors[index]?.description }">

                                        <div v-if="itemErrors[index]?.description" class="error-text">
                                            {{ itemErrors[index].description }}
                                        </div>
                                    </td>

                                    <!-- UNITS -->
                                    <td>
                                        <input type="text" class="form-control" disabled v-model="item.units"
                                            :class="{ 'is-invalid': itemErrors[index]?.units }">

                                        <div v-if="itemErrors[index]?.units" class="error-text">
                                            {{ itemErrors[index].units }}
                                        </div>
                                    </td>

                                    <!-- QTY -->
                                    <td>
                                        <input type="number" min="1" class="form-control text-center"
                                            v-model.number="item.qty_return"
                                            :class="{ 'is-invalid': itemErrors[index]?.qty }">

                                        <div v-if="itemErrors[index]?.qty" class="error-text">
                                            {{ itemErrors[index].qty }}
                                        </div>
                                    </td>

                                    <!-- CONDITION -->
                                    <td>
                                        <select class="form-select" v-model="item.condition">
                                            <option disabled value="">Select condition</option>
                                            <option value="G">Good as New</option>
                                            <option value="U">Ussable</option>
                                        </select>

                                    </td>

                                    <!-- COST -->
                                    <td>
                                        <input type="number" min="0" step="0.01" class="form-control text-center"
                                            v-model.number="item.cost"
                                            :class="{ 'is-invalid': itemErrors[index]?.cost }">

                                        <div v-if="itemErrors[index]?.cost" class="error-text">
                                            {{ itemErrors[index].cost }}
                                        </div>
                                    </td>

                                    <!-- DELETE ROW -->
                                    <td>
                                        <button class="btn btn-danger btn-sm" @click="removeItem(index)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>

                                </tr>
                            </tbody>

                        </table>
                    </div>

                    <div v-if="errorMessage" class="alert alert-danger py-2 mt-2">
                        {{ errorMessage }}
                    </div>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeModal">Cancel</button>
                    <button class="btn btn-primary" @click="saveMcrt">Create</button>
                </div>

            </div>
        </div>
    </div>

    <!-- SUCCESS MODAL -->
    <div v-if="showSuccess" class="custom-modal-backdrop">
        <div class="custom-modal">
            <div class="custom-header bg-primary text-white">
                <h5 class="mb-0">Success</h5>
            </div>
            <div class="custom-body text-center">
                ✅ MCRT successfully created!
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-primary" @click="closeSuccess">OK</button>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted, nextTick } from "vue";
import api from "../../services/api";

const emit = defineEmits(["close"]);

// HEADER FIELDS
const form = ref({
    returned_by: "",
    work_order: "",
    job_order: "",
});

// ERROR STATES
const fieldErrors = ref({});
const itemErrors = ref({});
const errorMessage = ref("");
const showSuccess = ref(false);

// ITEM LIST FROM API
const itemsList = ref([]);

// ITEMS OF THE MCRT
const mcrtItems = ref([
    {
        itemcode_id: "",
        description: "",
        units: "",
        qty_return: 1,
        condition: "",
        cost: 0,
    },
]);

async function fetchItems() {
    try {
        const res = await api.get("/Items/getItemCode");
        itemsList.value = res.data.data || [];
    } catch (err) {
        console.error("Failed loading items");
    }
}

// INIT SELECT2
function initItemSelect() {
    mcrtItems.value.forEach((item, index) => {
        const selector = "#itemSelect" + index;

        if ($(selector).data("select2")) $(selector).select2("destroy");

        $(selector).select2({
            theme: "bootstrap-5",
            placeholder: "Search Item",
            allowClear: true,
            dropdownParent: $(".modal.show"),
            width: "100%"
        }).on("change", function () {
            const id = $(this).val();
            item.itemcode_id = id;

            const found = itemsList.value.find(i => i.ItemCode_id == id);
            if (!found) return;

            item.description = found.product_name;
            item.units = found.units;
            item.cost = found.cost || 0;
        });
    });
}

// ADD ITEM ROW
async function addItem() {
    mcrtItems.value.push({
        itemcode_id: "",
        description: "",
        units: "",
        qty_return: 1,
        condition: "",
        cost: 0,
    });

    await nextTick(initItemSelect);
}

// REMOVE ITEM
function removeItem(i) {
    mcrtItems.value.splice(i, 1);
    nextTick(initItemSelect);
}

// VALIDATION
function validateForm() {
    fieldErrors.value = {};
    itemErrors.value = {};
    let valid = true;

    if (!form.value.returned_by) {
        fieldErrors.value.returned_by = "Required.";
        valid = false;
    }

    if (!form.value.work_order) {
        fieldErrors.value.work_order = "Required.";
        valid = false;
    }

    if (!form.value.job_order) {
        fieldErrors.value.job_order = "Required.";
        valid = false;
    }

    // ITEM-LEVEL
    const codes = mcrtItems.value.map(i => i.itemcode_id);

    mcrtItems.value.forEach((item, idx) => {
        itemErrors.value[idx] = {};

        if (!item.itemcode_id) {
            itemErrors.value[idx].itemcode = "ItemCode required.";
            valid = false;
        }

        if (item.itemcode_id && codes.indexOf(item.itemcode_id) !== idx) {
            itemErrors.value[idx].duplicate = "Duplicate ItemCode.";
            valid = false;
        }

        if (!item.description) {
            itemErrors.value[idx].description = "Missing description.";
            valid = false;
        }

        if (!item.units) {
            itemErrors.value[idx].units = "Missing units.";
            valid = false;
        }

        if (!item.qty_return || item.qty_return < 1) {
            itemErrors.value[idx].qty = "Qty must be ≥ 1.";
            valid = false;
        }

        if (item.cost < 0 || item.cost === "") {
            itemErrors.value[idx].cost = "Invalid cost.";
            valid = false;
        }
    });

    nextTick(() => {
        const err = document.querySelector(".row-error");
        if (err) err.scrollIntoView({ behavior: "smooth", block: "center" });
    });

    return valid;
}

// SAVE MCRT
async function saveMcrt() {
    // Run UI validation
    if (!validateForm()) return;

    try {
        const payload = {
            returned_by: form.value.returned_by,
            work_order: form.value.work_order,
            job_order: form.value.job_order,

            items: mcrtItems.value.map(i => ({
                itemcode_id: i.itemcode_id,
                returned_qty: i.qty_return,
                cost: i.cost,
                condition: i.condition, // EXACT string: Good as new / For Repair
            }))
        };

        const res = await api.post("/Mcrt/McrtCreate", payload);

        if (res.data?.success) {
            showSuccess.value = true;
        } else {
            showError(res.data?.message || "Failed to save.");
        }

    } catch (err) {
        showError(err.response?.data?.message || "Failed to save.");
    }
}


function showError(msg) {
    errorMessage.value = msg;
    setTimeout(() => (errorMessage.value = ""), 4000);
}

// CLOSE SUCCESS MODAL
function closeSuccess() {
    showSuccess.value = false;
    emit("close");
}

// CLOSE MAIN MODAL
function closeModal() {
    emit("close");
}

onMounted(async () => {
    await fetchItems();
    await nextTick(initItemSelect);
});
</script>
<style scoped>
.modal-auto-fit {
    max-width: 60vw;
    width: auto;
}

/* Validation */
.is-invalid {
    border: 1px solid #dc3545 !important;
    box-shadow: 0 0 3px rgba(220, 53, 69, 0.5);
}

.row-error {
    background: #ffe5e5 !important;
}

.error-text {
    color: #dc3545;
    font-size: 0.75rem;
}

/* Success Modal */
.custom-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 3000;
}

.custom-modal {
    width: 350px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    animation: popIn 0.2s ease-in-out;
}

.custom-header,
.custom-footer {
    padding: 15px;
}

.custom-body {
    padding: 25px 15px;
    font-size: 1.1rem;
}

@keyframes popIn {
    from {
        transform: scale(0.9);
        opacity: 0;
    }

    to {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
