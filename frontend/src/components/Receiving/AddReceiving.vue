<template>
  <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-auto-fit" role="document">
      <div class="modal-content">

        <!-- Header -->
        <div class="modal-header bg-success text-white">
          <h5>Add Receiving Order</h5>
          <button type="button" class="btn btn-close btn-close-white" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <!-- Form -->
          <form class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="poNumber">PO# <span class="text-danger">*</span></label>
              <input type="text" id="poNumber" class="form-control" v-model="form.po_number"
                @input="form.po_number = form.po_number.replace(/[^a-zA-Z0-9\- ]/g, '')" @change="onPOChange" />
            </div>

            <div class="col-md-4">
              <label class="form-label" for="invoiceNumber">Invoice# <span class="text-danger">*</span></label>
              <input type="text" id="invoiceNumber" class="form-control" v-model="form.invoice_number"
                @input="form.invoice_number = form.invoice_number.replace(/[^a-zA-Z0-9\- ]/g, '')" />
            </div>

            <div class="col-md-4">
              <label class="form-label" for="drNumber">DR# <span class="text-danger">*</span></label>
              <input type="text" id="drNumber" class="form-control" v-model="form.dr_number"
                @input="form.dr_number = form.dr_number.replace(/[^a-zA-Z0-9\- ]/g, '')" />
            </div>

            <div class="col-md-6">
              <label class="form-label" for="supplierName">Supplier <span class="text-danger">*</span></label>
              <select id="supplierName" class="form-select" v-model="selectSupplier">
                <option value="">Select Supplier</option>
                <option v-for="supplier in suppliers" :key="supplier.supplier_id" :value="supplier.supplier_id">
                  {{ supplier.supplier_name }}
                </option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="supplyAddress">Supplier Address</label>
              <input type="text" class="form-control" id="supplyAddress" v-model="supplierDetails.address" disabled />
            </div>
          </form>

          <hr class="my-4" />

          <!-- Receiving Items -->
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Receiving Items</h6>
            <button class="btn btn-success btn-sm" @click="addItem" type="button">
              <i class="bi bi-plus-lg"></i> Add Item
            </button>
          </div>

          <div class="table-responsive" ref="tableWrapper" style="max-height: 300px; overflow-y: auto;">
            <table class="table  align-middle text-center">
              <thead class="table-light">
                <tr>
                  <th style="width: 20%">Item Code</th>
                  <th style="width: 25%">Product Name</th>
                  <th style="width: 15%;">Units</th>
                  <th style="width: 10%">Qty Ordered</th>
                  <th style="width: 10%">Qty Received</th>
                  <th style="width: 10%">Unit Cost</th>
                  <th style="width: 10%">Total</th>
                  <th style="width: 5%">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in rrItems" :key="index" :class="{ 'bg-light text-muted': item.existing }">
                  <td>
                    <select class="form-select text-center" v-model="item.ItemCode_id" style="width: 100%;"
                      :id="'itemSelect' + index" :disabled="item.existing">
                      <option value="">Select Product</option>
                      <option v-for="p in itemsList" :key="p.ItemCode_id" :value="p.ItemCode_id">
                        {{ p.ItemCode }}
                      </option>
                    </select>
                  </td>
                  <td>
                    <input type="text" class="form-control" v-model="item.product_name" :disabled="item.existing" />
                  </td>
                  <td>
                    <select :id="'unitSelect' + index" class="form-select" v-model="item.units" style="width: 100%;">
                      <option value="">Select Unit</option>
                      <option v-for="unit in units" :key="unit.id" :value="unit.unit_name">
                        {{ unit.unit_name }}
                      </option>
                    </select>
                  </td>
                  <td>
                    <input type="number" class="form-control" v-model.number="item.quantity_order" min="0"
                      :disabled="item.disable_quantity_order" />
                  </td>
                  <td>
                    <input type="number" class="form-control" v-model.number="item.quantity_received" min="0"
                      :disabled="item.existing" />
                  </td>
                  <td>
                    <input type="number" class="form-control" v-model.number="item.unit_cost" min="0" step="0.01"
                      :disabled="item.existing" />
                  </td>
                  <td>
                    {{ formatCurrency(itemTotal(item)) }}
                  </td>
                  <td>
                    <button type="button" class="btn btn-danger btn-sm" @click="removeItem(index)"
                      :disabled="item.existing">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="6" class="text-end">Grand Total</th>
                  <th colspan="2">{{ formatCurrency(grandTotal) }}</th>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="alert alert-danger py-2 mt-2">
            {{ errorMessage }}
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeModal">Cancel</button>
          <button class="btn btn-success" @click="saveRR">Create</button>
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
        ✅ Receiving Items successfully created!
      </div>
      <div class="custom-footer text-center">
        <button class="btn btn-success" @click="closeSuccess">OK</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, computed } from "vue";
import api from "../../services/api";
import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";

const emit = defineEmits(["close"]);
const showSuccess = ref(false);
const appStore = useAppStore();
const tableWrapper = ref(null);
const selectSupplier = ref("");
const suppliers = ref([]);
const supplierDetails = ref({ address: "" });
const errorMessage = ref("");

const units = ref([]);
const itemsList = ref([]);

const form = ref({
  po_number: "",
  invoice_number: "",
  dr_number: "",
});

const rrItems = ref([
  { 
    ItemCode_id: "", 
    product_name: "", 
    units: "", 
    quantity_order: 0, 
    quantity_received: 0, 
    unit_cost: 0, 
    existing: false,
    disable_quantity_order: false
  },
]);

// ---------------- FORMAT ----------------
const formatCurrency = (value) => {
  if (!value) return "0.00";
  return "₱" + Number(value).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

// ---------------- MOUNTED ----------------
onMounted(() => {
  fetchSuppliers();
  fetchItems();
  fetchUnits().then(() => nextTick(() => {
    initItemSelect();
    initUnitSelect();
  }));
});

// ---------------- FETCH UNITS ----------------
async function fetchUnits() {
  try {
    const res = await api.get("Units/display");
    units.value = res.data.data || [];
  } catch (err) {
    console.error("Failed to fetch units", err);
  }
}

// ---------------- FETCH SUPPLIERS ----------------
async function fetchSuppliers() {
  try {
    const res = await api.get("/suppliers/get_supplier");
    suppliers.value = res.data.data || [];
    await nextTick(initSupplierSelect);
  } catch (err) {
    console.error(err);
  }
}

// ---------------- FETCH ITEMS ----------------
async function fetchItems() {
  try {
    const res = await api.get("/Items/getItemCode");
    itemsList.value = res.data.data || [];
    await nextTick(initItemSelect);
  } catch (err) {
    console.error(err);
  }
}

// ---------------- SUPPLIER SELECT2 ----------------
function initSupplierSelect() {
  if ($("#supplierName").data("select2")) $("#supplierName").select2("destroy");

  $("#supplierName").select2({
    theme: "bootstrap-5",
    placeholder: "Search or select a supplier",
    allowClear: true,
    dropdownParent: $(".modal.show"),
  }).on("change", function () {
    selectSupplier.value = $(this).val();
    handleSupplierChange();
  });
}

async function handleSupplierChange() {
  if (!selectSupplier.value) return;
  try {
    const res = await api.get(`/suppliers/get_supplier/${selectSupplier.value}`);
    supplierDetails.value.address = res.data.data.address || "";
  } catch (err) {
    console.error(err);
  }
}

// ---------------- ITEM SELECT2 (PER ROW) ----------------
function initItemSelect() {
  rrItems.value.forEach((item, index) => {
    const selector = "#itemSelect" + index;

    if ($(selector).data("select2")) $(selector).select2("destroy");

    $(selector).select2({
      theme: "bootstrap-5",
      placeholder: "Search or Select Item Code",
      allowClear: true,
      dropdownParent: $(".modal.show"),
      width: "100%",
    }).on("change", async function () {
      const selectedId = $(this).val();
      const selected = itemsList.value.find(i => i.ItemCode_id == selectedId);

      if (!selected) return;

      item.ItemCode_id = selectedId;
      item.product_name = selected.product_name || "";
      item.product_type = selected.item_category || "";

      // Check backend status
      const itemStatus = await checkItemStatus(form.value.po_number, selectedId);

      if (itemStatus.exists) {
        if (itemStatus.status === "Complete") {
          showError(`Item "${selected.ItemCode}" is already fully received for this PO.`);

          rrItems.value[index] = {
            ItemCode_id: "",
            product_name: "",
            units: "",
            quantity_order: 0,
            quantity_received: 0,
            unit_cost: 0,
            existing: false,
            disable_quantity_order: false
          };

          await nextTick(initItemSelect);
          await nextTick(initUnitSelect);
        } 
        else if (itemStatus.status === "Partial") {
          const originalOrder = await fetchOriginalQuantityOrder(form.value.po_number, selectedId);

          item.quantity_order = originalOrder;
          item.disable_quantity_order = true;
          errorMessage.value = "";
        }
      } else {
        item.existing = false;
        item.disable_quantity_order = false;
        item.quantity_order = 0;
        errorMessage.value = "";
      }
    });
  });
}

// ---------------- UNIT SELECT2 (PER ROW) ----------------
function initUnitSelect() {
  rrItems.value.forEach((item, index) => {
    const selector = "#unitSelect" + index;

    if ($(selector).data("select2")) $(selector).select2("destroy");

    $(selector).select2({
      theme: "bootstrap-5",
      placeholder: "Select Unit",
      allowClear: true,
      dropdownParent: $(".modal.show"),
      width: "100%",
      minimumResultsForSearch: 0
    }).on("change", function () {
      item.units = $(this).val();
    });
  });
}

// ---------------- ADD ITEM ----------------
async function addItem() {
  rrItems.value.push({
    ItemCode_id: "",
    product_name: "",
    units: "",
    quantity_order: 0,
    quantity_received: 0,
    unit_cost: 0,
    existing: false,
    disable_quantity_order: false
  });

  await nextTick(() => {
    initItemSelect();
    initUnitSelect();
      if (rrItems.length > 5 && tableWrapper.value) {
      const container = tableWrapper.value;
      container.scrollTop = container.scrollHeight; // scroll sa bottom
    }
  });
}

function removeItem(index) {
  rrItems.value.splice(index, 1);
  nextTick(() => {
    initItemSelect();
    initUnitSelect();
  });
}

// ---------------- PO# CHANGE ----------------
async function onPOChange() {
  if (!form.value.po_number) return;

  rrItems.value = [{
    ItemCode_id: "",
    product_name: "",
    units: "",
    quantity_order: 0,
    quantity_received: 0,
    unit_cost: 0,
    existing: false,
    disable_quantity_order: false
  }];

  try {
    const res = await api.get(`/receiving/checkPOstatus/${form.value.po_number}`);
    if (res.data.status === "Complete") {
      showError("This PO Number is already COMPLETED!");
      form.value.po_number = "";
    }
  } catch {
    showError("Error checking PO status.");
  }

  await nextTick(() => {
    initItemSelect();
    initUnitSelect();
  });
}

// ---------------- SAVE RR ----------------
async function saveRR() {
  errorMessage.value = "";

  try {
    appStore.showLoading();

    if (!form.value.po_number || !form.value.invoice_number || !form.value.dr_number)
      return showError("PO#, Invoice# and DR# are required!");

    if (!selectSupplier.value) return showError("Please select a supplier!");
    if (!rrItems.value.length) return showError("Add at least one item!");

    if (rrItems.value.some(i => !i.ItemCode_id || i.quantity_received <= 0 || i.unit_cost <= 0))
      return showError("All items must have Item Code, Qty Received and Unit Cost!");

    const payload = {
      ...form.value,
      supplier_id: selectSupplier.value,
      received_by: userStore.user?.fullname,
      receive_date: new Date().toISOString().split("T")[0],
      items: rrItems.value.map(i => ({
        ItemCode_id: i.ItemCode_id,
        quantity_received: i.quantity_received,
        unit_cost: i.unit_cost,
        units: i.units,   // FIXED ✔
        quantity_order: i.quantity_order
      }))
    };

    const res = await api.post("/receiving/receive_items", payload);

    if (res.data?.success) {
      showSuccess.value = true;
    } else {
      const apiMessage = res.data?.message || res.data?.error || "Failed to save RR.";
      showError(apiMessage);
    }
  } catch (err) {
    const apiMessage = err.response?.data?.error || err.response?.data?.message || err.message;
    showError(`Failed to save: ${apiMessage}`);
  } finally {
    appStore.hideLoading();
  }
}

// ---------------- BACKEND HELPERS ----------------
async function checkItemStatus(po_number, ItemCode_id) {
  if (!po_number || !ItemCode_id) return { exists: false, status: null };
  try {
    const res = await api.get(`/receiving/check_item/${po_number}/${ItemCode_id}`);
    return res.data || { exists: false, status: null };
  } catch {
    return { exists: false, status: null };
  }
}

async function fetchOriginalQuantityOrder(po_number, ItemCode_id) {
  try {
    const res = await api.get(`/receiving/get_item_order/${po_number}/${ItemCode_id}`);
    return res.data.quantity_order || 0;
  } catch {
    return 0;
  }
}

// ---------------- COMPUTE TOTALS ----------------
function itemTotal(item) {
  return (item.quantity_received || 0) * (item.unit_cost || 0);
}

const grandTotal = computed(() =>
  rrItems.value.reduce((sum, i) => sum + itemTotal(i), 0)
);

// ---------------- ERROR HANDLER ----------------
function showError(message) {
  errorMessage.value = message;
  setTimeout(() => (errorMessage.value = ""), 5000);
}

// ---------------- MODAL ----------------
function closeSuccess() { showSuccess.value = false; closeModal(); }
function closeModal() { emit("close"); }
</script>


<style scoped>
.modal-auto-fit {
  max-width: 80vw;
  width: auto;
}

/* optional: limit dropdown height and make scrollable */
.select2-results__options {
  max-height: 200px;
  /* adjust as needed for ~5 items */
  overflow-y: auto;
}
</style>
