<template>
    <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.55)">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-auto-fit">
            <div class="modal-content d-flex flex-column mrv-modal rounded-4 overflow-hidden">

                <!-- ================= HEADER ================= -->
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle me-1"></i>
                        Material Requisition Voucher
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="$emit('close')"></button>
                </div>

                <!-- ================= BODY ================= -->
                <div class="modal-body flex-grow-1 overflow-hidden">

                    <!-- ================= HEADER FORM ================= -->
                    <form class="row g-3 mb-2">
                        <div class="col-md-4 position-relative">
                            <label class="form-label fw-semibold">RFM #</label>
                            <input type="text" class="form-control pe-5" v-model="rfmNumber"
                                placeholder="Enter RFM Number" @keyup.enter="fetchRfm" />
                            <i v-if="rfmLoaded" class="bi bi-x-circle-fill text-muted clear-icon" @click="clearRfm"></i>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Requested By</label>
                            <input class="form-control" v-model="form.requested_by" disabled />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Department</label>
                            <input class="form-control" v-model="form.department" disabled />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Department Head</label>
                            <input class="form-control" v-model="form.approved_by" disabled />
                        </div>
                    </form>

                    <!-- ================= STATUS MESSAGE ================= -->
                    <!-- FINAL RULE: ANY EXISTING MRV = BLOCK ADD FLOW -->
                   

                    <hr class="my-2" />

                    <!-- ================= ADD ITEM BUTTON ================= -->
                    <div class="d-flex justify-content-end mb-2">
                        <button class="btn btn-sm btn-success" type="button" @click="addNewItem" :disabled="isLocked">
                            <i class="bi bi-plus-circle me-1"></i>
                            Add Item
                        </button>
                    </div>

                    <!-- ================= ITEMS TABLE ================= -->
                    <div class="table-responsive mrv-table-wrapper">
                        <table class="table table-sm align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-start">Material</th>
                                    <th>Units</th>
                                    <th>Requested</th>
                                    <th>Issued</th>
                                    <th>Stocks</th>
                                    <th>Remarks</th>
                                    <th width="40"></th>
                                </tr>
                            </thead>

                            <!-- SHOW ITEMS ONLY WHEN ADD MRV IS ALLOWED -->
                            <tbody v-if="showItems">
                                <tr v-for="(item, i) in items" :key="i">
                                    <td class="text-start">
                                        <template v-if="item.is_new">
                                            <select class="form-select form-select-sm" :id="'itemSelect' + i"></select>
                                        </template>
                                        <template v-else>
                                            <strong>{{ item.material_code }}</strong><br />
                                            <small class="text-muted">
                                                {{ item.material_description }}
                                            </small>
                                        </template>
                                    </td>

                                    <td>{{ item.units }}</td>

                                    <td class="fw-semibold">
                                        {{ item.is_new ? '-' : item.requested_qty }}
                                    </td>

                                    <td>
                                        <input type="number" class="form-control form-control-sm text-center"
                                            v-model.number="item.issued_qty"
                                            :max="item.is_new ? item.stocks : item.requested_qty" min="0"
                                            :disabled="isLocked" />
                                    </td>

                                    <td>{{ item.stocks }}</td>

                                    <td>
                                        <input type="text" class="form-control form-control-sm" v-model="item.remarks"
                                            placeholder="Remarks" :disabled="isLocked" />
                                    </td>

                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" type="button"
                                            @click="removeItem(i)" :disabled="isLocked">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>

                            <!-- EMPTY / LOCKED STATE -->
                            <tbody v-else>
                                <tr>
                                    <td colspan="7" class="text-muted py-3">
                                        {{ isLocked
                                            ? 'Items are not shown in Add MRV. Because the MRV is deleted.'
                                            : 'Enter RFM # to load items'
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- ================= ERROR MESSAGE ================= -->
                    <div v-if="errorMessage" class="alert alert-danger mt-2">
                        {{ errorMessage }}
                    </div>
                </div>

                <!-- ================= FOOTER ================= -->
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" @click="$emit('close')">
                        Close
                    </button>

                    <button class="btn btn-success" type="button" @click="saveMrv" :disabled="isLocked">
                        <i class="bi bi-check-circle me-1"></i>
                        Create MRV
                    </button>
                </div>

            </div>
        </div>
    </div>

      <div v-if="showSuccess" class="custom-modal-backdrop">
    <div class="custom-modal">
      <div class="custom-header bg-success text-white">
        <h5 class="mb-0">Success</h5>
      </div>
      <div class="custom-body text-center">
        ✅ MRV successfully created!
      </div>
      <div class="custom-footer text-center">
        <button class="btn btn-success" @click="closeSuccess">OK</button>
      </div>
    </div>
  </div>
</template>




<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount, computed } from "vue";
import api from "../../services/api";

const emit = defineEmits(["close"]);

/* ======================================================
   STATE
   ====================================================== */
const rfmNumber = ref("");
const rfmLoaded = ref(false);
const errorMessage = ref("");
const showSuccess = ref(false);
// MRV status returned from backend (PENDING / PARTIAL / APPROVED)
const mrvStatus = ref(null);

// 🔒 MAIN LOCK FLAG
// true  = ADD MRV is blocked
// false = allowed
const isLocked = ref(false);

// Header fields (read-only)
const form = ref({
    requested_by: "",
    department: "",
    approved_by: ""
});

// Items for ADD MRV (only when allowed)
const items = ref([]);

// Stock list for Select2
const stockList = ref([]);

/* ======================================================
   UI HELPERS
   ====================================================== */

// Show items ONLY if:
// - not locked
// - has items
const showItems = computed(() => {
    return !isLocked.value && items.value.length > 0;
});

/* ======================================================
   RESET STATE
   ====================================================== */
function resetState() {
    items.value = [];
    mrvStatus.value = null;
    isLocked.value = false;
    errorMessage.value = "";
}

/* ======================================================
   FETCH STOCK LIST (FOR ADD ITEM)
   ====================================================== */
async function fetchStock() {
    const res = await api.get("/Items/displayStocks");

    stockList.value = res.data.data.map(i => ({
        id: i.id,
        code: i.Material_Code,
        name: i.product_name,
        units: i.units,
        stocks: Number(i.quantity_onhand ?? 0),
        product_type: i.product_type ?? i.item_category ?? "UNKNOWN"
    }));
}

/* ======================================================
   FETCH RFM + CHECK MRV (MAIN LOGIC)
   ====================================================== */
async function fetchRfm() {
    if (!rfmNumber.value) return;

    // Always reset first
    resetState();

    /* ----------------------------------------------
       1️⃣ CHECK IF MRV ALREADY EXISTS
       ---------------------------------------------- */
    const check = await api.get(`/Mrv/check-mrv/${rfmNumber.value}`);
    const c = check.data;

    // Normalize status (backend may return Approved / APPROVED)
    const status = (c.status || "").toUpperCase();

    // 🔒 BLOCK ADD MRV IF ANY MRV EXISTS
    if (c.exists === true) {
        mrvStatus.value = status;
        isLocked.value = true;
        rfmLoaded.value = true;

        errorMessage.value =
            "MRV already exists for this RFM#. Either delete or Status Complete for the existing MRV.";

        // ⛔ STOP HERE — DO NOT FETCH RFM ITEMS
        return;
    }

    /* ----------------------------------------------
       2️⃣ FETCH RFM (ONLY IF NO MRV EXISTS)
       ---------------------------------------------- */
    const rfmRes = await api.get(`/Rfm/fetchRfmForMrv/${rfmNumber.value}`);
    if (!rfmRes.data.success) {
        errorMessage.value = "RFM not found.";
        return;
    }

    const rfm = rfmRes.data.data;

    // Fill header fields
    form.value.requested_by = rfm.requested_by;
    form.value.department = rfm.department;
    form.value.approved_by = rfm.area_engineering;

    // Load RFM items for ADD MRV
    items.value = rfm.items.map(i => ({
        is_new: false,
        itemcode_id: i.itemcode_id,
        material_code: i.material_code,
        material_description: i.material_description,
        units: i.units,
        stocks: Number(i.stocks ?? 0),
        requested_qty: Number(i.requested_qty),
        issued_qty: Number(i.requested_qty),
        remarks: i.remarks ?? "",
        product_type: i.product_type ?? "UNKNOWN"
    }));

    rfmLoaded.value = true;
}

/* ======================================================
   ADD NEW ITEM (BLOCKED WHEN LOCKED)
   ====================================================== */
function addNewItem() {
    if (isLocked.value) return;

    const idx = items.value.length;

    items.value.push({
        is_new: true,
        itemcode_id: "",
        material_code: "",
        material_description: "",
        units: "",
        stocks: 0,
        requested_qty: 0,
        issued_qty: 1,
        remarks: "",
        product_type: "UNKNOWN"
    });

    nextTick(() => initMaterialSelect(idx));
}

/* ======================================================
   SELECT2 INITIALIZATION
   ====================================================== */
function initMaterialSelect(index) {
    const selector = "#itemSelect" + index;

    if ($(selector).data("select2")) {
        $(selector).select2("destroy");
    }

    $(selector).select2({
        theme: "bootstrap-5",
        dropdownParent: $(".modal.show"),
        width: "100%",
        placeholder: "Search material",
        allowClear: true,
        data: stockList.value.map(s => ({
            id: s.id,
            text: `${s.code} - ${s.name}`,
            ...s
        })),
        escapeMarkup: m => m
    }).on("select2:select", e => {

        // Safety guard
        if (isLocked.value) return;

        const s = e.params.data;

        // Prevent duplicate items
        if (items.value.some(x => x.itemcode_id === s.id)) {
            errorMessage.value = "Item already added.";
            $(selector).val(null).trigger("change");
            return;
        }

        items.value[index] = {
            ...items.value[index],
            itemcode_id: s.id,
            material_code: s.code,
            material_description: s.name,
            units: s.units,
            stocks: s.stocks,
            product_type: s.product_type
        };
    });
}

/* ======================================================
   SAVE MRV (BLOCKED WHEN LOCKED)
   ====================================================== */
async function saveMrv() {
    // block safety
    if (isLocked.value) {
        errorMessage.value =
            "This RFM already has an MRV. Please use the Edit MRV module.";
        return;
    }

    // clear previous messages
    errorMessage.value = "";
    showSuccess.value = false;

    try {
        const res = await api.post("/Mrv/MrvCreate", {
            rfm_number: rfmNumber.value,
            requested_by: form.value.requested_by,
            department: form.value.department,
            approved_by: form.value.approved_by,
            items: items.value.map(i => ({
                itemcode_id: i.itemcode_id,
                issued_qty: i.issued_qty,
                product_type: i.product_type,
                remarks: i.remarks || null
            }))
        });

        // ✅ SUCCESS FROM BACKEND
        if (res.data?.success) {
            showSuccess.value = true;
        }
          emit("saved");

    } catch (err) {
        // ❌ BACKEND ERROR → UI
        errorMessage.value =
            err.response?.data?.message ||
            err.response?.data?.error ||
            "Failed to create MRV.";
    }
}


/* ======================================================
   REMOVE ITEM (BLOCKED WHEN LOCKED)
   ====================================================== */
function removeItem(i) {
    if (isLocked.value) return;
    items.value.splice(i, 1);
}

/* ======================================================
   CLEAR RFM INPUT
   ====================================================== */
function clearRfm() {
    rfmNumber.value = "";
    resetState();
    rfmLoaded.value = false;
}

/* ======================================================
   LIFECYCLE
   ====================================================== */
onMounted(async () => {
    await fetchStock();
    document.body.style.overflow = "hidden";
});

onBeforeUnmount(() => {
    document.body.style.overflow = "";
});

function closeSuccess() { showSuccess.value = false; closeModal(); }
function closeModal() { emit("close"); }
</script>



<style scoped>
.modal-auto-fit {
    max-width: 80vw;
}

.mrv-modal {
    max-height: 90vh;
}

.mrv-table-wrapper {
    max-height: 320px;
    overflow-y: auto;
}

.mrv-table-wrapper thead th {
    position: sticky;
    top: 0;
    background: #f8f9fa;
    z-index: 5;
}

.clear-icon {
    position: absolute;
    right: 10px;
    top: 38px;
    cursor: pointer;
}
</style>
