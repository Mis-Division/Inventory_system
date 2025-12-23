<template>
    <teleport to="body">
        <div class="modal fade show d-block rfm-modal" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-xl modal-dialog-scrollable modal-auto-fit">
                <div class="modal-content">

                    <!-- HEADER -->
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil-square"></i>
                            Update Request for Materials (RFM)
                        </h5>
                        <button class="btn-close btn-close-white" @click="emit('close')"></button>
                    </div>

                    <!-- BODY -->
                    <div class="modal-body">

                        <!-- LOADING STATE -->
                        <div v-if="!ready" class="text-center py-5">
                            <div class="spinner-border text-success"></div>
                            <p class="mt-2 text-muted">Loading data...</p>
                        </div>

                        <!-- FORM -->
                        <div v-else class="row g-3">

                            <!-- LEFT -->
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
                                        <option disabled value="">Select Purpose</option>
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
                                        <select id="employeeSelect" class="form-select"></select>
                                    </div>

                                    <div class="col-md-6">
                                        <label>Department / Area</label>
                                        <input v-model="form.department" class="form-control" disabled>
                                    </div>
                                </div>

                                <div class="row g-2 mt-2">
                                    <div class="col-md-6">
                                        <label>Management</label>
                                        <select id="selectHeads" class="form-control"></select>
                                    </div>

                                    <div class="col-md-6">
                                        <label>Date</label>
                                        <input type="date" v-model="form.office_date" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT -->
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
                                        <option disabled value=""></option>
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

                                    <label class="fw-semibold mb-2 mt-3">Requested Materials</label>

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
                                                <tr v-for="item in form.items" :key="item.itemcode_id">
                                                    <td>{{ item.material_code }}</td>
                                                    <td class="text-start">{{ item.description }}</td>
                                                    <td>{{ item.units }}</td>
                                                    <td>{{ item.requested_qty }}</td>
                                                    <td>
                                                        <button class="btn btn-danger btn-sm"
                                                            @click="removeItem(item.itemcode_id)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>

                                                <tr v-if="!form.items.length">
                                                    <td colspan="5" class="text-muted py-3">No materials added</td>
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
                        <button class="btn btn-warning" @click="emit('close')">
                            <i class="bi bi-x"></i> Close
                        </button>

                        <button class="btn btn-success" :disabled="!isFormValid" @click="submit">
                            <i class="bi bi-save"></i> Update
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

/* ================= PROPS / EMITS ================= */
const props = defineProps({
  request: { type: Object, required: true }
});
const emit = defineEmits(["close", "updated"]);
const appStore = useAppStore();

/* ================= STATE ================= */
const ready = ref(false);

const stockList = ref([]);
const employees = ref([]);
const deptHeads = ref([]);

const selectedStockId = ref("");
const requestQty = ref("");

/* ================= FORM ================= */
const form = reactive({
  rfm_id: null,
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
  management: "",
  office_date: "",
  cut_out_assembly: "",
  meters: "",
  poles: "",
  items: []
});

/* ================= COMPUTED ================= */
const selectedStock = computed(() =>
  stockList.value.find(s => s.id == selectedStockId.value)
);

const canAdd = computed(() =>
  selectedStock.value &&
  requestQty.value > 0 &&
  requestQty.value <= selectedStock.value.quantity_onhand
);

const isFormValid = computed(() =>
  form.work_description &&
  form.location &&
  form.purpose &&
  form.requested_by &&
  form.department &&
  form.management &&
  form.office_date &&
  form.items.length > 0
);

/* ================= PREFILL (DATA ONLY) ================= */
function fillForm(data) {
  form.rfm_id = data.rfm_id;
  form.work_description = data.work_description;
  form.location = data.location;
  form.purpose = data.work_type;

  form.primary_lines = data.primary_lines_retired;
  form.secondary_lines = data.secondary_lines_retired;
  form.cut_out_assembly = data.cut_of_assembly;
  form.meters = data.meters;
  form.poles = data.poles;
  form.busted_transformer = data.busted_transformer;
  form.service_drop_wire = data.service_drop_wire;

  form.mco_details = data.mco_details;
  form.remarks = data.remarks;

  form.requested_by = data.requested_by;
  form.department = data.department;
  form.management = data.area_engineering;
  form.office_date = data.rfm_date;

  form.items = data.items.map(i => ({
    itemcode_id: i.itemcode_id,
    material_code: i.material_code,
    description: i.material_description,
    units: i.units,
    requested_qty: i.requested_qty
  }));
}

/* ================= API LOADERS ================= */
async function fetchStock() {
  const res = await api.get("/Items/displayStocks", {
    headers: { "X-Skip-Loading": true }
  });
  stockList.value = res.data.data;
}

async function fetchEmployees() {
  const res = await apiRemote.get("/employees", {
    params: { page: 1, per_page: 1000 },
    headers: { "X-Skip-Loading": true }
  });

  employees.value = res.data.data.data.map(e => ({
    id: e.NAME.trim(),
    text: e.NAME.trim(),
    dept: e.DEPTNAME
  }));
}

async function fetchDeptHeads() {
  const res = await api.get("/DepartmentHeads/heads", {
    headers: { "X-Skip-Loading": true }
  });

  deptHeads.value = res.data.data.map(h => ({
    id: h.depthead_name,
    text: h.depthead_name
  }));
}

/* ================= SELECT2 INIT ================= */
function initEmployeeSelect() {
  $("#employeeSelect").select2({
    theme: "bootstrap-5",
    dropdownParent: $(".modal.show"),
    width: "100%",
    placeholder: "Select Employee",
    allowClear: true,
    data: employees.value
  }).on("change", function () {
    const emp = employees.value.find(e => e.id === $(this).val());
    form.requested_by = emp?.id || "";
    form.department = emp?.dept || "";
  });
}

function initDeptHeadSelect() {
  $("#selectHeads").select2({
    theme: "bootstrap-5",
    dropdownParent: $(".modal.show"),
    width: "100%",
    placeholder: "Select Department Head",
    allowClear: true,
    data: deptHeads.value
  }).on("change", function () {
    form.management = $(this).val() || "";
  });
}

function initMaterialSelect() {
  // destroy if re-init
  if ($("#rfmMaterialSelect").data("select2")) {
    $("#rfmMaterialSelect").select2("destroy");
  }

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
  }).on("change", function () {
    selectedStockId.value = $(this).val();
  });

  // ✅ FORCE placeholder on init
  $("#rfmMaterialSelect").val(null).trigger("change");
}


/* ================= ITEMS ================= */
function addMaterial() {
  if (form.items.some(i => i.itemcode_id === selectedStock.value.id)) return;

  form.items.push({
    itemcode_id: selectedStock.value.id,
    material_code: selectedStock.value.Material_Code,
    description: selectedStock.value.product_name,
    units: selectedStock.value.units,
    requested_qty: requestQty.value
  });

  requestQty.value = "";
  selectedStockId.value = "";
  $("#rfmMaterialSelect").val(null).trigger("change");
}

function removeItem(id) {
  form.items = form.items.filter(i => i.itemcode_id !== id);
}

/* ================= UPDATE ================= */
async function submit() {
  appStore.showLoading();
  try {
    await api.put(`/Rfm/update/${form.rfm_id}`, {
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
      items: form.items.map(i => ({
        itemcode_id: i.itemcode_id,
        requested_qty: String(i.requested_qty),
        material_description: i.description,
      }))
    });
 alert("RFM successfully Update.");
    emit("updated");
    emit("close");
  } finally {
    appStore.hideLoading();
  }
}

/* ================= INIT (CORRECT ORDER) ================= */
onMounted(async () => {
  // 1️⃣ fetch all data
  await Promise.all([
    fetchStock(),
    fetchEmployees(),
    fetchDeptHeads()
  ]);

  // 2️⃣ show form (DOM exists)
  ready.value = true;
  await nextTick();

  // 3️⃣ init select2
  initEmployeeSelect();
  initDeptHeadSelect();
  initMaterialSelect();

  // 4️⃣ fill data
  fillForm(props.request);

  // 5️⃣ DIRECT SYNC
  await nextTick();
  $("#employeeSelect").val(form.requested_by).trigger("change");
  $("#selectHeads").val(form.management).trigger("change");
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