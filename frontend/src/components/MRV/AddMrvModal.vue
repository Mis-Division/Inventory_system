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
                    <button type="button" class="btn-close" aria-label="Close" @click="$emit('close')"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">

                    <!-- Form -->
                    <form class="row g-3">
                        <!-- REQUEST BY (SEARCHABLE DROPDOWN from apiRemote/employees) -->
                        <div class="col-md-4">
                            <label class="form-label">Request By</label>
                            <select id="employeeSelect" class="form-select"></select>
                        </div>

                        <!-- AUTO-FILLED DEPARTMENT -->
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" v-model="form.department" disabled />
                        </div>

                        <!-- DEPARTMENT HEAD (UNCHANGED) -->
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

                    <!-- Add Row Button -->
                    <!-- Add Row Button + Usable Filter -->
                    <div class="d-flex justify-content-between align-items-center mb-2">

                        <!-- Checkbox LEFT SIDE -->
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" v-model="usableOnly" id="usableOnlyCheck">
                            <label class="form-check-label" for="usableOnlyCheck">
                                Show Usable Items Only
                            </label>
                        </div>


                        <!-- Add button RIGHT SIDE -->
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

                                    <!-- Material Code -->
                                    <td>
                                        <select class="form-select text-center" v-model="item.id"
                                            :id="'itemSelect' + index" :disabled="item.existing" style="width: 100%;">
                                            <option value="">Select Material Code</option>
                                            <option v-for="s in filteredStockList" :key="s.id" :value="s.id">
                                                {{ s.Material_Code }}
                                            </option>

                                        </select>
                                    </td>

                                    <!-- Description -->
                                    <td>
                                        <input type="text" class="form-control" v-model="item.product_name" disabled />
                                    </td>

                                    <!-- Units -->
                                    <td>
                                        <input type="text" class="form-control" v-model="item.units" disabled />
                                    </td>

                                    <!-- Stock Qty -->
                                    <td>
                                        <input type="text" class="form-control" :value="getDisplayedQty(item)"
                                            disabled />

                                    </td>

                                    <!-- Request Qty -->
                                    <td>
                                        <input type="number" class="form-control" v-model="item.requested_qty"
                                            min="1" />


                                        <div v-if="isQtyInvalid(item)" class="text-danger small">
                                            * Requested qty exceeds usable stock
                                        </div>
                                    </td>

                                    <!-- Delete Row -->
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm" @click="removeStock(index)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>

                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Error Message -->
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
                ✅ Material Requisition Voucher Successfully created!
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-success" @click="closeSuccess">OK</button>
            </div>
        </div>
    </div>
</template>


<script setup>
import { ref, nextTick, onMounted, computed, watch } from "vue";
import api from "../../services/api";
import { apiRemote } from "../../services/api";
import { useAppStore } from "../../stores/appStore";

const emit = defineEmits(["close"]);
const appStore = useAppStore();

/* ============================================================
   CHECKBOX (show usable items only)
============================================================ */
const usableOnly = ref(false);

/* ============================================================
   FORM STATE
============================================================ */
const form = ref({
    requested_by: "",
    department: "",
    approved_by: ""
});

const showSuccess = ref(false);
const errorMessage = ref("");

/* ============================================================
   EMPLOYEES
============================================================ */
const employees = ref([]);

/* ============================================================
   STOCK LIST
============================================================ */
const stockList = ref([]);

const stockItems = ref([
    { id: "", product_name: "", units: "", Qty_main: 0, Qty_usable: 0, requested_qty: 1 }
]);

/* ============================================================
   FILTER STOCK LIST → usableOnly mode
============================================================ */
const filteredStockList = computed(() => {
    if (usableOnly.value) {
        // ⭐ FIXED: Filter by Qty_usable (correct field)
        return stockList.value.filter(i => Number(i.Qty_usable) > 0);
    }
    return stockList.value;
});

/* ============================================================
   DISPLAYED QTY (main or usable)
============================================================ */
function getDisplayedQty(item) {
    return usableOnly.value ? (item.Qty_usable ?? 0) : (item.Qty_main ?? 0);
}

/* ============================================================
   Watch usableOnly → refresh dropdowns
============================================================ */
watch(usableOnly, () => {
    nextTick(() => {
        stockItems.value.forEach((_, i) => initMaterialSelectRow(i));
    });
});

/* ============================================================
   FETCH EMPLOYEES
============================================================ */
async function fetchEmployees() {
    try {
        const res = await apiRemote.get("/employees", {
            params: { page: 1, per_page: 500 }
        });

        employees.value = res.data.data.data.filter(
            emp => emp.DEPTNAME.trim().toUpperCase() !== "RETIRED"
        );

        await nextTick();
        initEmployeeSelect();

    } catch (err) {
        console.error("❌ Employee load failed:", err);
    }
}

/* ============================================================
   INIT SELECT2 EMPLOYEE DROPDOWN
============================================================ */
function initEmployeeSelect() {
    const selector = "#employeeSelect";

    if ($(selector).data("select2")) $(selector).select2("destroy");

    $(selector)
        .select2({
            theme: "bootstrap-5",
            placeholder: "Search employee...",
            allowClear: true,
            dropdownParent: $(".modal.show"),
            width: "100%",
            minimumResultsForSearch: 0,
            data: employees.value.map(emp => ({
                id: emp.NAME,
                text: emp.NAME,
                dept: emp.DEPTNAME.trim()
            }))
        })
        .on("change", function () {
            const selected = $(this).select2("data")[0];
            form.value.requested_by = selected?.id || "";
            form.value.department = selected?.dept || "";
        });

    $(selector).val(null).trigger("change");
}

/* ============================================================
   FETCH STOCK
============================================================ */
async function fetchStock() {
    try {
        const res = await api.get("/Items/displayStocks");

        stockList.value = (res.data.data || []).map(item => ({
            id: item.id,
            Material_Code: item.Material_Code,
            product_name: item.product_name,
            units: item.units,

            // ⭐ Correct data mapping
            Qty_main: item.quantity_onhand ?? 0,
            Qty_usable: item.Usable ?? 0,
        }));

        await nextTick();
        initMaterialSelect();

    } catch (err) {
        console.error("❌ Stock load failed:", err);
    }
}

/* ============================================================
   INIT MATERIAL SELECT2 DROPDOWNS
============================================================ */
function initMaterialSelectRow(index) {
    const selector = "#itemSelect" + index;

    if ($(selector).data("select2")) $(selector).select2("destroy");

    $(selector)
        .select2({
            theme: "bootstrap-5",
            placeholder: "Material Code",
            dropdownParent: $(".modal.show"),
            width: "100%"
        })
        .on("change", function () {
            const selectedId = $(this).val();
            stockItems.value[index].id = selectedId;
            updateStockInfo(index, selectedId);
        });

    $(selector).val(stockItems.value[index].id).trigger("change");
}

function initMaterialSelect() {
    stockItems.value.forEach((_, i) => initMaterialSelectRow(i));
}

/* ============================================================
   UPDATE STOCK INFO WHEN ITEM SELECTED
============================================================ */
function updateStockInfo(index, id) {
    const s = stockList.value.find(x => x.id == id);

    if (s) {
        stockItems.value[index].product_name = s.product_name;
        stockItems.value[index].units = s.units;
        stockItems.value[index].Qty_main = s.Qty_main;
        stockItems.value[index].Qty_usable = s.Qty_usable;
    } else {
        stockItems.value[index].product_name = "";
        stockItems.value[index].units = "";
        stockItems.value[index].Qty_main = 0;
        stockItems.value[index].Qty_usable = 0;
    }
}

/* ============================================================
   ADD ITEM ROW
============================================================ */
function addItem() {
    const newIndex = stockItems.value.length;

    stockItems.value.push({
        id: "",
        product_name: "",
        units: "",
        Qty_main: 0,
        Qty_usable: 0,
        requested_qty: 1
    });

    nextTick(() => initMaterialSelectRow(newIndex));
}

/* ============================================================
   REMOVE ITEM ROW
============================================================ */
function removeStock(index) {
    stockItems.value.splice(index, 1);

    nextTick(() => {
        stockItems.value.forEach((_, i) => initMaterialSelectRow(i));
    });
}

/* ============================================================
   SAVE MRV
============================================================ */
async function saveMrv() {
    showError("");

    try {
        if (!form.value.requested_by || !form.value.department || !form.value.approved_by)
            return showError("Requested By, Department, and Department Head are required!");

        if (!stockItems.value.length)
            return showError("Add at least 1 item!");

        // ⭐ NEW VALIDATION ONLY WHEN USABLE MODE IS ON
        if (usableOnly.value) {
            for (const item of stockItems.value) {
                if (Number(item.requested_qty) > Number(item.Qty_usable)) {
                    return showError(
                        `Requested quantity (${item.requested_qty}) is greater than usable stock (${item.Qty_usable}).`
                    );
                }
            }
        }

        const payload = {
            ...form.value,
            usable_only: usableOnly.value,
            items: stockItems.value.map(i => ({
                itemcode_id: i.id,
                requested_qty: i.requested_qty,
                product_type: i.units
            }))
        };

        const res = await api.post("/Mrv/MrvCreate", payload);

        if (res.data?.success) {
            showSuccess.value = true;
        } else {
            showError(res.data?.message || "Failed to create MRV!");
        }

    } catch (err) {
        showError(err.response?.data?.message || err.message);
    }
}


/* ============================================================
   ERROR HANDLER
============================================================ */
function showError(msg) {
    errorMessage.value = msg;
    setTimeout(() => (errorMessage.value = ""), 4000);
}

/* ============================================================
   SUCCESS MODAL CLOSE
============================================================ */
function closeSuccess() {
    showSuccess.value = false;
    emit("close");
}

/* ============================================================
   ON PAGE LOAD
============================================================ */
onMounted(async () => {
    await fetchStock();
    await fetchEmployees();
});

function isQtyInvalid(item) {
    if (!usableOnly.value) return false;            // off = no validation
    if (!item.id) return false;                     // no item selected
    if (!item.requested_qty) return false;          // must input first
    if (item.Qty_usable == null) return false;      // avoid undefined

    return Number(item.requested_qty) > Number(item.Qty_usable);
}

</script>




<style scoped>
.modal-auto-fit {
    max-width: 70vw;
    width: auto;
}

.select2-results__options {
    max-height: 200px;
    overflow-y: auto;
}
</style>
