<template>
    <teleport to="body">
        <div class="modal fade show d-block rfm-modal" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-xl modal-dialog-scrollable modal-auto-fit">
                <div class="modal-content">

                    <!-- HEADER -->
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-clipboard-plus"></i>
                            Request for Materials (RFM)
                        </h5>
                        <button class="btn-close btn-close-white" @click="$emit('close')"></button>
                    </div>

                    <!-- BODY -->
                    <div class="modal-body">
                        <div class="row g-3">

                            <!-- LEFT PANEL -->
                            <div class="col-md-6">

                                <div class="rfm-box">
                                    <label>Work Description</label>
                                    <textarea v-model="form.work_description" rows="2" class="form-control"></textarea>
                                </div>

                                <div class="rfm-box">
                                    <label>Location/s</label>
                                    <input v-model="form.location" class="form-control">
                                </div>

                                <div class="rfm-box">
                                    <label>Purpose</label>
                                    <select class="form-select mb-1" v-model="form.purpose">
                                        <option value="" disabled>Select Purpose</option>
                                        <option value="Retirement">RETIREMENT</option>
                                        <option value="Replacement">REPLACEMENT</option>
                                    </select>

                                    <input v-model="form.primary_lines" class="form-control mb-1"
                                        placeholder="Primary Lines Retired">
                                    <input v-model="form.secondary_lines" class="form-control mb-1"
                                        placeholder="Secondary Lines Retired">
                                    <input v-model="form.busted_transformer" class="form-control mb-1"
                                        placeholder="Busted Transformer">
                                    <input v-model="form.service_drop_wire" class="form-control"
                                        placeholder="Service Drop Wire">
                                </div>

                                <div class="rfm-box">
                                    <label>MCO’s Details</label>
                                    <textarea v-model="form.mco_details" rows="2" class="form-control"></textarea>
                                </div>

                                <div class="rfm-box">
                                    <label>Other Important Remarks</label>
                                    <textarea v-model="form.remarks" rows="2" class="form-control"></textarea>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label>Requested By</label>
                                        <select id="employeeSelect" class="form-select">
                                            <option value=""></option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label>Department / Area</label>
                                        <input v-model="form.department" class="form-control" disabled>
                                    </div>
                                </div>

                                <div class="row g-2 mt-2">
                                    <div class="col-md-6">
                                        <label>Management</label>
                                        <select id="selectHeads" class="form-control">
                                            <option value=""></option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label>Date</label>
                                        <input type="date" v-model="form.office_date" class="form-control">
                                    </div>
                                </div>

                            </div>

                            <!-- RIGHT PANEL -->
                            <div class="col-md-6">
                                <div class="rfm-box h-100">

                                    <label>Cut-out Assembly</label>
                                    <input v-model="form.cut_out_assembly" class="form-control mb-2">

                                    <label>Meters</label>
                                    <input v-model="form.meters" class="form-control mb-2">

                                    <label>Poles</label>
                                    <input v-model="form.poles" class="form-control mb-3">

                                    <hr>

                                    <div class="mt-3">
                                        <label class="fw-semibold">Search Material</label>
                                        <select id="rfmMaterialSelect" class="form-select mb-3">
                                            <option value=""></option>
                                        </select>
                                    </div>

                                    <div v-if="selectedStock" class="row g-2 mb-2">
                                        <div class="col-4">
                                            <small class="text-muted">Units</small>
                                            <input class="form-control text-center" :value="selectedStock.units"
                                                disabled>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Qty Available</small>
                                            <input class="form-control text-center"
                                                :value="selectedStock.quantity_onhand" disabled>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Request Qty</small>
                                            <input type="number" min="1" class="form-control text-center"
                                                v-model="requestQty">
                                        </div>
                                    </div>

                                    <div class="mt-2">
                                        <button class="btn btn-success btn-sm w-100" :disabled="!canAdd"
                                            @click="addMaterial">
                                            <i class="bi bi-plus-lg"></i> Add Material
                                        </button>
                                    </div>

                                    <label class="fw-semibold mb-2 mt-3">
                                        Requested Materials
                                    </label>

                                    <div class="table-responsive rfm-materials-table">
                                        <table class="table table-bordered text-center align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Material Code</th>
                                                    <th>Description</th>
                                                    <th>Units</th>
                                                    <th width="90">Qty</th>
                                                    <th width="50"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(item, i) in form.items" :key="i">
                                                    <td>{{ item.material_code }}</td>
                                                    <td class="text-start">{{ item.description }}</td>
                                                    <td>{{ item.units }}</td>
                                                    <td>{{ item.requested_qty }}</td>
                                                    <td>
                                                        <button class="btn btn-danger btn-sm" @click="removeItem(i)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>

                                                <tr v-if="!form.items.length">
                                                    <td colspan="5" class="text-muted py-3">
                                                        No materials added
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="modal-footer">
                        <button class="btn btn-warning" @click="$emit('close')">
                            <i class="bi bi-x"></i> Close
                        </button>

                        <!-- ✅ disabled only, no validation text -->
                        <button class="btn btn-success" :disabled="!isFormValid" @click="submit">
                            <i class="bi bi-save"></i> Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>



<script setup>
import { ref, reactive, computed, onMounted, nextTick } from "vue";
import api, { apiRemote } from "../../services/api";
import { useAppStore } from "../../stores/appStore";


const emit = defineEmits(["close", "submit"]);

const stockList = ref([]);
const selectedStockId = ref("");
const requestQty = ref("");
const employees = ref([]);
const deptHeads = ref([]);

const appStore = useAppStore();

/* ================= STATE ================= */


const selectedStock = computed(() =>
    stockList.value.find(s => s.id == selectedStockId.value)
);

const canAdd = computed(() =>
    selectedStock.value &&
    requestQty.value > 0 &&
    requestQty.value <= selectedStock.value.quantity_onhand
);

/* ✅ ADD management */
const form = reactive({
    work_description: "",
    location: "",
    purpose: "",
    primary_lines: "",
    secondary_lines: "",
    busted_transformer: "",
    service_drop_wire: "",
    mco_details: "",
    remarks: "",
    requested_by: "",
    department: "",
    management: "",       // ✅ department head
    office_date: "",
    warehouse_date: "",
    cut_out_assembly: "",
    meters: "",
    poles: "",
    items: []
});

/* ✅ Save enable / disable only */
const isFormValid = computed(() => {
    return (
        form.work_description &&
        form.location &&
        form.purpose &&
        form.requested_by &&
        form.department &&
        form.management &&
        form.office_date &&
        form.items.length > 0
    );
});

/* ================= STOCK ================= */
async function fetchStock() {
    const res = await api.get("/Items/displayStocks", {
    headers: { "X-Skip-Loading": true }
  });
  stockList.value = res.data.data;
    await nextTick();
    initMaterialSelect();
}

/* ================= EMPLOYEES ================= */
async function fetchEmployees() {
    const res = await apiRemote.get("/employees", {
    headers: { "X-Skip-Loading": true },
    params: { page: 1, per_page: 500 }
  });
  employees.value = res.data.data.data
        .filter(e => e.DEPTNAME.toUpperCase() !== "RETIRED")
        .map(e => ({ ...e, NAME: e.NAME.trim().toUpperCase() }));

    await nextTick();
    initEmployeeSelect();
}

function initEmployeeSelect() {
    $("#employeeSelect").select2({
        theme: "bootstrap-5",
        dropdownParent: $(".modal.show"),
        width: "100%",
        placeholder: "Please Select Employee",
        allowClear: true,
        data: employees.value.map(e => ({
            id: e.NAME,
            text: e.NAME,
            dept: e.DEPTNAME
        }))
    })
        .on("select2:select", e => {
            form.requested_by = e.params.data.id;
            form.department = e.params.data.dept;
        })
        .on("select2:clear", () => {
            form.requested_by = "";
            form.department = "";
        });
}

/* ================= DEPARTMENT HEADS ================= */
async function fetchDeptHeads() {
  const res = await api.get("/DepartmentHeads/heads", {
    headers: { "X-Skip-Loading": true }
  });
  deptHeads.value = res.data.data;
    await nextTick();
    initDeptHeadSelect();
}

function initDeptHeadSelect() {
    const selector = "#selectHeads";

    if ($(selector).data("select2")) {
        $(selector).select2("destroy");
    }

    $(selector).select2({
        theme: "bootstrap-5",
        dropdownParent: $(".modal.show"),
        width: "100%",
        placeholder: "Please Select Department Head",
        allowClear: true,
        data: deptHeads.value.map(h => ({
            id: h.depthead_name,
            text: `${h.depthead_name}`
        }))
    })

        .on("select2:select", function (e) {
            const value = e.params.data.id;

          

            form.management = value; // ✅ THIS IS THE KEY
        })

        .on("select2:clear", function () {
            form.management = "";
        });
}


/* ================= MATERIAL ================= */
function initMaterialSelect() {
    $("#rfmMaterialSelect").select2({
        theme: "bootstrap-5",
        dropdownParent: $(".modal.show"),
        width: "100%",
        placeholder: "Select Material",
        allowClear: true,
        data: stockList.value.map(s => ({
            id: s.id,
            text: `${s.Material_Code} - ${s.product_name}`
        }))
    })
        .on("select2:select", e => {
            selectedStockId.value = e.params.data.id;
        })
        .on("select2:clear", () => {
            selectedStockId.value = "";
            requestQty.value = "";
        });
}

/* ================= ITEMS ================= */
function addMaterial() {
    if (form.items.some(i => i.itemcode_id == selectedStock.value.id)) {
        alert("Material already added.");
        return;
    }

    form.items.push({
        itemcode_id: selectedStock.value.id,
        material_code: selectedStock.value.Material_Code,
        description: selectedStock.value.product_name,
        units: selectedStock.value.units,
        requested_qty: requestQty.value
    });

    selectedStockId.value = "";
    requestQty.value = "";
    $("#rfmMaterialSelect").val(null).trigger("change");
}

function removeItem(i) {
    form.items.splice(i, 1);
}


async function submit() {
    appStore.showLoading();
    if (!isFormValid.value) return;

    try {
        const payload = {
            rfm_date: form.office_date,

            work_description: form.work_description,
            location: form.location,
            work_type: form.purpose,

            primary_lines_retired: form.primary_lines,
            secondary_lines_retired: form.secondary_lines,
            cut_of_assembly: form.cut_out_assembly,
            meters: form.meters,
            poles: form.poles,
            busted_transformer: form.busted_transformer,
            service_drop_wire: form.service_drop_wire,

            mco_details: form.mco_details,
            remarks: form.remarks,

            requested_by: form.requested_by,
            department: form.department,
            area_engineering: form.management,

            // warehouse fields intentionally blank
            warehouse_initial: null,
            warehouse_date: null,

            items: form.items.map(i => ({
                itemcode_id: i.itemcode_id,
                material_description: i.description,
                requested_qty: String(i.requested_qty),
                remarks: i.remarks ?? null
            }))
        };

        await api.post("/Rfm/create", payload);

        // ✅ SUCCESS ALERT
        alert("RFM successfully saved.");

        // ✅ AFTER SUCCESS
        emit("submit"); // refresh parent
        emit("close");  // close modal

    } catch (error) {
        console.error("RFM SAVE ERROR:", error);

        alert(
            error.response?.data?.message ??
            "Failed to save RFM. Please try again."
        );
    }finally{
        appStore.hideLoading();
    }
}



/* ================= INIT ================= */
onMounted(async () => {
    await fetchStock();
    await fetchEmployees();
    await fetchDeptHeads();
});
</script>






<style scoped>
.modal-auto-fit {
    max-height: calc(100vh - 140px);
    margin-top: 25px;
}

.rfm-modal .modal-dialog {
    margin-top: 95px;
}

.rfm-box {
    border: 1px solid #dee2e6;
    padding: 10px;
    margin-bottom: 8px;
    border-radius: 6px;
}

.rfm-materials-table {
    max-height: 410px;
    overflow-y: auto;
}
</style>
