<template>
  <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-auto-fit">
      <div class="modal-content">

        <!-- Header -->
        <div class="modal-header bg-warning text-white">
          <h5>Update Receiving Report</h5>
          <button type="button" class="btn btn-close btn-close-white" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <!-- Header Info -->
          <form class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label">PO#</label>
              <input type="text" class="form-control" v-model="rrData.po_number" @change="onPOChange" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Invoice#</label>
              <input type="text" class="form-control" v-model="rrData.invoice_number" />
            </div>
            <div class="col-md-4">
              <label class="form-label">DR#</label>
              <input type="text" class="form-control" v-model="rrData.dr_number" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Supplier</label>
              <select id="supplierSelect" class="form-select" v-model="rrData.supplier_id">
                <option value="">Select Supplier</option>
                <option v-for="supplier in suppliers" :key="supplier.supplier_id" :value="supplier.supplier_id">
                  {{ supplier.supplier_name }}
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Address</label>
              <input type="text" class="form-control" v-model="rrData.address" disabled />
            </div>
          </form>

          <hr class="my-4" />

          <!-- Items Header -->
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Receiving Items</h6>
          </div>

          <!-- Items Table -->
          <div class="table-responsive">
            <table class="table table-bordered text-center">
              <thead class="table-light">
                <tr>
                  <th style="width: 20%">Item Code</th>
                  <th style="width: 25%">Product Name</th>
                  <th style="width: 15%">Units</th>
                  <th style="width: 10%">Qty Ordered</th>
                  <th style="width: 10%">Qty Received</th>
                  <th style="width: 10%">Unit Cost</th>
                  <th style="width: 10%">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in rrData.items" :key="index">
                  <td>
                    <select :id="'itemSelect' + index" style="width: 100%;" class="form-select"
                      v-model="item.ItemCode_id">
                      <option value="">Select Item</option>
                      <option v-for="p in itemsList" :key="p.ItemCode_id" :value="p.ItemCode_id">
                        {{ p.ItemCode }}
                      </option>
                    </select>
                  </td>
                  <td>
                    <input type="text" class="form-control" v-model="item.product_name" disabled />
                  </td>
                  <td>
                    <input type="text"  class="form-control" v-model="item.units" disabled />
                  </td>
                  <td>
                    <input type="number" class="form-control" v-model.number="item.quantity_order" min="0" />
                  </td>
                  <td>
                    <input type="number" class="form-control" v-model.number="item.quantity_received" min="0"
                      @input="checkReceived(index)" />
                  </td>
                  <td>
                    <input type="number" class="form-control" v-model.number="item.unit_cost" min="0" />
                  </td>
                  <td>{{ formatCurrency(item.quantity_received * item.unit_cost) }}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="6" class="text-end">Grand Total</th>
                  <th colspan="1">{{ formatCurrency(grandTotal) }}</th>
                </tr>
              </tfoot>
            </table>
          </div>

          <div v-if="errorMessage" class="alert alert-danger py-2 mt-2">
            {{ errorMessage }}
          </div>

        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeModal">Close</button>
          <button class="btn btn-warning" @click="updateRR">Update</button>
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
        ✅ Receiving Items successfully Updated!
      </div>
      <div class="custom-footer text-center">
        <button class="btn btn-success" @click="closeSuccess">OK</button>
      </div>
    </div>
  </div>

  <!-- Confirm Modal -->
  <div v-if="showConfirm" class="custom-modal-backdrop">
    <div class="custom-modal">
      <div class="custom-header bg-primary text-white">
        <h5 class="mb-0">Confirm Action</h5>
      </div>
      <div class="custom-body">
        <p>Are you sure you want to change this item to {{ pendingItemChange?.selected.ItemCode }}? This may affect
          stock.</p>
      </div>
      <div class="custom-footer">
        <button class="btn btn-warning" @click="showConfirm = false">Cancel</button>
        <button class="btn btn-success" @click="confirmSave">Yes, Update</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted, nextTick, computed } from "vue";
import api from "../../services/api";
import { useAppStore } from "../../stores/appStore";

const props = defineProps({ rr: Object });
const emit = defineEmits(["close", "updated"]);

const rrData = reactive({ ...props.rr });

// Ensure every item has needed fields
rrData.items.forEach(i => {
  i.ItemCode_id = i.ItemCode_id || '';
  i.product_name = i.product_name || '';
  i.unit_cost = i.unit_cost || 0;
  i.quantity_order = i.quantity_order || 0;
  i.quantity_received = i.quantity_received || 0;
  i.units = i.units || ''; // optional
});

const suppliers = ref([]);
const itemsList = ref([]);
const errorMessage = ref("");
const showSuccess = ref(false);
const showConfirm = ref(false);
let pendingItemChange = null;


const appStore = useAppStore();

// =========================
// UTILITY
// =========================
const formatCurrency = (value) => {
  if (!value) return '₱0.00';
  return '₱' + Number(value).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

function showError(msg) {
  errorMessage.value = msg;
  setTimeout(() => errorMessage.value = "", 5000);
}

function closeModal() {
  emit("close");
}

function closeSuccess() {
  showSuccess.value = false;
  closeModal();
}

// =========================
// FETCHING DATA
// =========================



async function fetchSuppliers() {
  try {
    const res = await api.get("/suppliers/get_supplier");
    suppliers.value = res.data.data || [];

    const current = suppliers.value.find(s => s.supplier_name === rrData.supplier_name);
    if (current) rrData.supplier_id = current.supplier_id;

    await nextTick();
    initSupplierSelect();
  } catch (err) { console.error(err); }
}

async function fetchItems() {
  try {
    const res = await api.get("/Items/getItemCode");
    itemsList.value = res.data.data || [];

    rrData.items.forEach(rrItem => {
      const match = itemsList.value.find(i => i.ItemCode === rrItem.ItemCode);
      rrItem.ItemCode_id = match ? match.ItemCode_id : "";
      rrItem.old_ItemCode_id = rrItem.ItemCode_id;
    });

    await nextTick();
    initItemSelects();
  } catch (err) { console.error(err); }
}

// =========================
// SELECT2 SUPPLIER
// =========================
function initSupplierSelect() {
  const selector = "#supplierSelect";

  if ($(selector).data("select2")) $(selector).select2("destroy");

  $(selector).select2({
    theme: "bootstrap-5",
    placeholder: "Select supplier",
    allowClear: true,
    dropdownParent: $(".modal.show")
  })
    .val(rrData.supplier_id)
    .trigger('change.select2')
    .on("change", function () {
      const selected = suppliers.value.find(s => s.supplier_id == $(this).val());
      if (selected) {
        rrData.supplier_id = selected.supplier_id;
        rrData.supplier_name = selected.supplier_name;
        rrData.address = selected.address;
      } else {
        rrData.supplier_id = "";
        rrData.supplier_name = "";
        rrData.address = "";
      }
    });
}

// =========================
// ITEM SELECT + CONFIRM MODAL
// =========================
function initItemSelects() {
  rrData.items.forEach((item, index) => {
    const selector = "#itemSelect" + index;
    const unitSelector = "#unitSelect" + index;

    if ($(selector).data("select2")) $(selector).select2("destroy");

    $(selector).select2({
      theme: "bootstrap-5",
      placeholder: "Search Item",
      allowClear: true,
      dropdownParent: $(".modal.show")
    });

    if (item.ItemCode_id) {
      $(selector).val(item.ItemCode_id.toString()).trigger("change.select2");
    }

    $(selector).off("change").on("change", function () {
      const selectedId = $(this).val();
      const selected = itemsList.value.find(i => i.ItemCode_id.toString() === selectedId);
  

      if (selected) {
        pendingItemChange = { item, selected, selector };
        showConfirm.value = true;

        // revert temporary
        $(selector).val(item.ItemCode_id?.toString() || "").trigger("change.select2");
      } else {
        // clear other fields
        item.old_ItemCode_id = item.ItemCode_id;
        item.ItemCode_id = "";
        item.ItemCode = "";
        item.product_name = "";
        item.units = "";
        item.unit_cost = 0;
        item.quantity_order = 0;
        item.quantity_received = 0;
      }
    });
  });
}

// CONFIRM ITEM CHANGE
function confirmSave() {
  if (!pendingItemChange) return;

  const { item, selected, selector } = pendingItemChange;

  item.ItemCode_id = selected.ItemCode_id;
  item.ItemCode = selected.ItemCode;
  item.product_name = selected.product_name;
  item.unit_cost = selected.unit_cost ?? 0;
  item.quantity_order = selected.default_quantity ?? 0;
  item.units = selected.units; // optional

  if (item.quantity_received > item.quantity_order) {
    item.quantity_received = item.quantity_order;
  }

  $(selector).val(selected.ItemCode_id.toString()).trigger("change.select2");

  pendingItemChange = null;
  showConfirm.value = false;
}

// =========================
// TOTALS
// =========================
const grandTotal = computed(() =>
  rrData.items.reduce((sum, item) =>
    sum + ((item.quantity_received || 0) * (item.unit_cost || 0)), 0)
);

// =========================
// UPDATE RR
// =========================
async function updateRR() {
  showError("");
  showSuccess.value = false;

  try {
    appStore.showLoading();

    if (!rrData.po_number || !rrData.invoice_number || !rrData.dr_number) {
      showError("PO#, Invoice#, and DR# are required");
      return;
    }
    if (!rrData.supplier_id) {
      showError("Select a supplier");
      return;
    }

    const payload = {
      supplier_id: rrData.supplier_id,
      po_number: rrData.po_number,
      dr_number: rrData.dr_number,
      invoice_number: rrData.invoice_number,
      remarks: rrData.remarks ?? "",
      items: rrData.items.map(i => ({
        id: i.id ?? null,
        ItemCode_id: i.ItemCode_id,
        quantity_order: Number(i.quantity_order),
        quantity_received: Number(i.quantity_received),
        unit_cost: Number(i.unit_cost),
        total_cost: Number(i.quantity_received) * Number(i.unit_cost),
        units: i.units,
      }))
    };

    const res = await api.put(`/receiving/update_rr/${rrData.r_id}`, payload);

    if (res.data?.message) {
      showSuccess.value = true;
      emit("updated");
    } else {
      showError("Failed to update RR");
    }

  } catch (err) {
    console.error(err?.response?.data || err);
    showError(err?.response?.data?.message || err.message);
  } finally {
    appStore.hideLoading();
  }
}

// =========================
// RECEIVED VALIDATION
// =========================
function checkReceived(index) {
  const item = rrData.items[index];

  if (item.quantity_received > item.quantity_order) {
    item.quantity_received = item.quantity_order;
  }
  if (item.quantity_received < 0) {
    item.quantity_received = 0;
  }
}

// =========================
// MOUNT
// =========================
onMounted(async () => {
  await fetchSuppliers();
  await fetchItems();

 // initItemSelects(); // if you have this separate for items
});

</script>



<style scoped>
.modal-auto-fit {
  max-width: 90vw;
  width: auto;
}

.select2-dropdown {
  max-height: 200px;
  /* adjust sa gusto mo */
  overflow-y: auto;
}
</style>
