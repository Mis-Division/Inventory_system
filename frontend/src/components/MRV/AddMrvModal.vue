<template>
    <!-- Modal -->
    <div class="modal fade show" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-l modal-auto-fit" role="document">
            <div class="modal-content">

                <!-- Header -->
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle "></i> Material Requisition Voucher
                    </h5>
                    <button type="button" class="btn-close" aria-label="Close" @click="closeModal"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">

                    <!-- Form -->
                    <form class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Request By</label>
                            <input type="text" class="form-control" v-model="form.requested_by" />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <select class="form-select" v-model="form.department" required>
                                <option value="">Please Select Department</option>
                                <option value="Internal Service Department">Internal Service Department</option>
                                <option value="Technical Support Department">Technical Support Department</option>
                                <option value="Area Office Management Department">Area Office Management Department
                                </option>
                                <option value="Finance Service Department">Finance Service Department</option>
                                <option value="Audit Service Department">Audit Service Department</option>
                                <option value="Energy Trading Service Department">Energy Trading Service Department
                                </option>
                                <option value="General Manager">Office of General Manager</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Department Head</label>
                            <select class="form-select" v-model="form.approved_by" required>
                                <option value="">Please Select Department Head</option>
                                <option value="MARLON O. SALINAS">MARLON O. SALINAS</option>
                                <option value="Engr. Teodorico B. Dumlao">Engr. Teodorico B. Dumlao</option>
                                <option value="Engr. Bonifacio Bibat Jr.">Engr. Bonifacio Bibat Jr.</option>
                                <option value="Leizza M. Niguidula, CPA">Leizza M. Niguidula, CPA</option>
                                <option value="Manilyn C. Baldos, CPAt">Manilyn C. Baldos, CPAt</option>
                                <option value="Bonaleth M. Solima, CPA">Bonaleth M. Solima, CPA</option>
                                <option value="GLEN MARK F. AQUINO, CPA">GLEN MARK F. AQUINO, CPA</option>
                            </select>
                        </div>
                    </form>

                    <hr class="my-4" />

                    <!-- Receiving Items -->
                    <div class="d-flex justify-content-end align-items-center mb-2">
                        <button class="btn btn-success btn-sm" @click="addItem" type="button">
                            <i class="bi bi-plus-lg"></i> Add Item
                        </button>
                    </div>


                    <!-- Item Table -->
                    <div class="table_responsive" ref="tableWrapper" style="max-height: 300px; overflow-y: auto;">
                        <table class="table align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20%;">Material Code</th>
                                    <th style="width: 35%;">Description</th>
                                    <th style="width: 20%;">Units</th>
                                    <th style="width: 10%;">Stock Qty</th>
                                    <th style="width: 10%;">Request Qty</th>
                                    <th style="width: 5%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in stockItems" :key="index">
                                    <!-- Material Code (searchable) -->
                                    <td>
                                        <select class="form-select text-center" v-model="item.id"
                                            :id="'itemSelect' + index" :disabled="item.existing" style="width: 100%;">
                                            <option value="">Select Material Code</option>
                                            <option v-for="s in stockList" :key="s.id" :value="s.id">
                                                {{ s.Material_Code }}
                                            </option>
                                        </select>
                                    </td>

                                    <!-- Description -->
                                    <td>
                                        <input type="text" class="form-control" v-model="item.product_name" disabled />
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" v-model="item.units" disabled />
                                    </td>
                                    <!-- Qty -->
                                    <td>
                                        <input type="text" class="form-control" v-model="item.Qty" disabled />
                                    </td>

                                    <!-- Request Qty -->
                                    <td>
                                        <input type="number" class="form-control" v-model="item.requested_qty"
                                            min="0" />
                                    </td>

                                    <!-- Delete -->
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm" @click="removeStock(index)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Error -->
                    <div v-if="errorMessage" class="alert alert-danger py-2 mt-2">
                        {{ errorMessage }}
                    </div>

                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" @click="$emit('close')">Close</button>
                    <button class="btn btn-success" @click="saveMrv">Create</button>
                </div>

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
                ✅ Material Requisition Vouchers Successfully created!
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-success" @click="closeSuccess">OK</button>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, nextTick, onMounted } from "vue";
import api from "../../services/api";
import { useAppStore } from "../../stores/appStore";

const emit = defineEmits(["close"]);

const units = ref([]);
const showSuccess = ref(false);
const errorMessage = ref("");
const stockList = ref([]);
const appStore = useAppStore();
const stockItems = ref([
    { id: "", product_name: "", units: "", Qty: "", requested_qty: "", existing: false }
]);

const form = ref({
    requested_by: "",
    department: "",
    approved_by: ""
});

// =============================================================
// FETCH STOCKS (Material Code)
// =============================================================
async function fetchStock() {
    try {
        const res = await api.get("/Items/displayStocks");
        stockList.value = res.data.data.data || [];

        await nextTick();
        initMaterialSelect(); // init Select2 for material code
    } catch (err) {
        console.error(err);
    }
}


// =============================================================
// MATERIAL CODE SELECT2 (itemSelect)
// =============================================================
function initMaterialSelectRow(index) {
    const selector = "#itemSelect" + index;

    if ($(selector).data("select2")) $(selector).select2("destroy");

    $(selector)
        .select2({
            theme: "bootstrap-5",
            placeholder: "Material Code",
            allowClear: true,
            dropdownParent: $(".modal.show"),
            width: "100%",
            minimumResultsForSearch: 0
        })
        .on("change", function () {
            const selectedId = $(this).val();
            stockItems.value[index].id = selectedId;
            updateStockInfo(index, selectedId);
        });

    // sync Select2 with v-model
    $(selector).val(stockItems.value[index].id).trigger("change");
}

function initMaterialSelect() {
    stockItems.value.forEach((_, index) => initMaterialSelectRow(index));
}


// =============================================================
// UPDATE MATERIAL INFO WHEN SELECTED
// =============================================================
function updateStockInfo(index, selectedId) {
    const selected = stockList.value.find((s) => s.id == selectedId);

    if (selected) {
        stockItems.value[index].product_name = selected.product_name;
        stockItems.value[index].Qty = selected.Qty;
        stockItems.value[index].units = selected.units;
    } else {
        stockItems.value[index].product_name = "";
        stockItems.value[index].Qty = "";
        stockItems.value[index].units = "";
    }
}

// =============================================================
// ADD NEW ROW
// =============================================================
function addItem() {
    const newIndex = stockItems.value.length;

    stockItems.value.push({
        id: "",
        product_name: "",
        units: "",
        Qty: "",
        requested_qty: "",
        existing: false
    });

    nextTick(() => {
        initMaterialSelectRow(newIndex);
       
    });
}

// =============================================================
// REMOVE ROW
// =============================================================
function removeStock(index) {
    stockItems.value.splice(index, 1);

    nextTick(() => {
        stockItems.value.forEach((_, i) => {
            initMaterialSelectRow(i);
         
        });
    });
}

// =============================================================
// MOUNT EVENT (Load & Initialize Everything)
// =============================================================
onMounted(async () => {
 

    await fetchStock(); // then load material codes
    await nextTick();
    initMaterialSelect();
});


// =============================================================
// Save ng MRV 
// =============================================================

async function saveMrv() {
    showError(""); // clear error

    try {
        appStore.showLoading();
        // Basic form validation
        if (!form.value.requested_by || !form.value.department || !form.value.approved_by)
            return showError("Requested By, Department and Department Head are required!");

        // Must have at least 1 item
        if (!stockItems.value.length)
            return showError("Add at least 1 item!");


        // Check requested_qty > 0
        if (stockItems.value.some(i => !i.requested_qty || i.requested_qty <= 0))
            return showError("Requested Quantity must be greater than 0!");

        // Check requested_qty <= stock qty
        if (stockItems.value.some(i => Number(i.requested_qty) > Number(i.Qty)))
            return showError("Requested Quantity cannot exceed Stock Quantity!");

        // Build payload
        const payload = {
            ...form.value,
            items: stockItems.value.map((i) => ({
                itemcode_id: i.id,
                requested_qty: i.requested_qty,
                product_type: i.units
            }))
        };

        // Send request
        const res = await api.post("/Mrv/MrvCreate", payload);

        if (res.data?.success) {
            showSuccess.value = true;
        } else {
            const apiMessage =
                res.data?.message ||
                res.data?.error ||
                "Failed to save Material Requisition Voucher!";
            showError(apiMessage);
        }
    } catch (err) {
        const apiMessage =
            err.response?.data?.error ||
            err.response?.data?.message ||
            err.message;
        showError(`Failed to Save: ${apiMessage}`);
    }finally{
        appStore.hideLoading();
    }
}



// ---------------- ERROR HANDLER ----------------
function showError(message) {
    errorMessage.value = message;
    setTimeout(() => (errorMessage.value = ""), 5000);
}


// ---------------- MODAL ----------------
function closeSuccess() {
    showSuccess.value = false;
    closeModal();

}

function closeModal() { emit("close"); }
</script>


<style scoped>
.modal-auto-fit {
    max-width: 70vw;
    width: auto;
}

/* Optional: limit dropdown height */
.select2-results__options {
    max-height: 200px;
    overflow-y: auto;
}
</style>
