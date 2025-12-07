<template>
  <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-auto-fit">
      <div class="modal-content">
        <!-- HEADER -->
        <div class="modal-header bg-warning text-white">
          <h5>
            <i class="bi bi-pencil-square me-1"></i>
            Update Material Credit Ticket
          </h5>
          <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
        </div>

        <!-- BODY -->
        <div class="modal-body">
          <!-- LOADING -->
          <div v-if="loading" class="text-center py-4">
            Loading MCRT details...
          </div>

          <div v-else>
            <!-- HEADER FORM FIELDS (NO MCRT NO / RECEIVED BY) -->
            <form class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label fw-semibold">
                  Returned By <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" v-model="form.returned_by"
                  :class="{ 'is-invalid': fieldErrors.returned_by }" />
                <div v-if="fieldErrors.returned_by" class="error-text">
                  {{ fieldErrors.returned_by }}
                </div>
              </div>

              <div class="col-md-4">
                <label class="form-label fw-semibold">Work Order</label>
                <input type="text" class="form-control" v-model="form.work_order"
                  :class="{ 'is-invalid': fieldErrors.work_order }" />
                <div v-if="fieldErrors.work_order" class="error-text">
                  {{ fieldErrors.work_order }}
                </div>
              </div>

              <div class="col-md-4">
                <label class="form-label fw-semibold">Job Order</label>
                <input type="text" class="form-control" v-model="form.job_order"
                  :class="{ 'is-invalid': fieldErrors.job_order }" />
                <div v-if="fieldErrors.job_order" class="error-text">
                  {{ fieldErrors.job_order }}
                </div>
              </div>
            </form>

            <hr class="my-3" />

            <!-- ITEMS HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0 fw-semibold">Returned Items</h6>
              <button class="btn btn-warning btn-sm" type="button" @click="addItem">
                <i class="bi bi-plus-lg"></i> Add Item
              </button>
            </div>

            <!-- ITEMS TABLE -->
            <div class="table-responsive" ref="tableWrapper" style="max-height:300px; overflow-y:auto;">
              <table class="table align-middle text-center">
                <thead class="table-light">
                  <tr>
                    <th style="width:18%;">Item Code</th>
                    <th style="width:25%;">Description</th>
                    <th style="width:10%;">Units</th>
                    <th style="width:10%;">Qty Return</th>
                    <th style="width:15%;">Condition</th>
                    <th style="width:12%;">Cost</th>
                    <th style="width:5%;">Action</th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-for="(item, index) in mcrtItems" :key="item._rowKey" :class="{
                    'row-error':
                      itemErrors[index] &&
                      Object.keys(itemErrors[index]).length > 0
                  }">
                    <!-- ITEM CODE -->
                    <td>
                      <select class="form-select text-center" :id="'itemSelectUpdate' + index"
                        v-model="item.itemcode_id" :disabled="item.isExisting" :class="{
                          'is-invalid':
                            itemErrors[index]?.itemcode ||
                            itemErrors[index]?.duplicate
                        }">
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
                      <input type="text" class="form-control" v-model="item.description" disabled />
                    </td>

                    <!-- UNITS -->
                    <td>
                      <input type="text" class="form-control" v-model="item.units" disabled />
                    </td>

                    <!-- QTY RETURN -->
                    <td>
                      <input type="number" min="1" class="form-control text-center" v-model.number="item.qty_return"
                        :class="{ 'is-invalid': itemErrors[index]?.qty_return }" />
                      <div v-if="itemErrors[index]?.qty_return" class="error-text">
                        {{ itemErrors[index].qty_return }}
                      </div>
                    </td>

                    <!-- CONDITION -->
                    <td>
                      <select class="form-select" v-model="item.condition"
                        :class="{ 'is-invalid': itemErrors[index]?.condition }">
                        <option value="">Select condition</option>
                        <option value="G">New</option>
                        <option value="U">Ussable</option>
                      </select>
                      <div v-if="itemErrors[index]?.condition" class="error-text">
                        {{ itemErrors[index].condition }}
                      </div>
                    </td>

                    <!-- COST -->
                    <td>
                      <input type="number" min="0" step="0.01" class="form-control text-center"
                        v-model.number="item.cost" :class="{ 'is-invalid': itemErrors[index]?.cost }" />
                      <div v-if="itemErrors[index]?.cost" class="error-text">
                        {{ itemErrors[index].cost }}
                      </div>
                    </td>

                    <!-- ACTION -->
                    <td>
                      <button type="button" class="btn btn-danger btn-sm" @click="removeItem(index)"
                        :disabled="item.isExisting && mcrtItems.length === 1">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- GLOBAL ERROR -->
            <div v-if="errorMessage" class="alert alert-danger py-2 mt-2">
              {{ errorMessage }}
            </div>
          </div>
        </div>

        <!-- FOOTER -->
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeModal">
            Cancel
          </button>
          <button class="btn btn-warning" @click="saveUpdate">
            <i class="bi bi-save me-1"></i> Update
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- SUCCESS MODAL -->
  <div v-if="showSuccess" class="custom-modal-backdrop">
    <div class="custom-modal">
      <div class="custom-header bg-success text-white">
        <h5 class="mb-0">Success</h5>
      </div>
      <div class="custom-body text-center">
        ✅ MCRT successfully updated!
      </div>
      <div class="custom-footer text-center">
        <button class="btn btn-success" @click="closeSuccess">OK</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from "vue";
import api from "../../services/api";

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["close", "updated"]);

const loading = ref(true);
const errorMessage = ref("");
const showSuccess = ref(false);

const form = ref({
  mcrt_number: "",   // internal lang, hindi na pinapakita
  returned_by: "",
  work_order: "",
  job_order: "",
  received_by: "",   // internal para sa backend validation
});

const itemsList = ref([]);

const mcrtItems = ref([]); // { _rowKey, mcrt_item_id?, itemcode_id, description, units, qty_return, cost, condition, isExisting }
const fieldErrors = ref({});
const itemErrors = ref({});

const tableWrapper = ref(null);

// FETCH ITEMS (for dropdown)
async function fetchItems() {
  try {
    const res = await api.get("/Items/getItemCode");
    itemsList.value = res.data.data || [];
  } catch (err) {
    console.error("Failed to load items:", err);
  }
}

// FETCH MCRT DETAILS
async function fetchMcrtDetails() {
  try {
    const res = await api.get(`/Mcrt/displayMcrt/${props.item.mcrt_id}`);
    const data = res.data.data || res.data?.data?.data || res.data;
    const mcrt = data;

    form.value.mcrt_number = mcrt.mcrt_number || mcrt.mcrt_no || "";
    form.value.returned_by = mcrt.returned_by || "";
    form.value.work_order = mcrt.work_order || "";
    form.value.job_order = mcrt.job_order || "";
    // still needed internally for backend validation
    form.value.received_by =
      mcrt.received_by || mcrt.received_by_name || "system";

    mcrtItems.value = (mcrt.items || []).map((it, idx) => {
      const found =
        itemsList.value.find((i) => i.ItemCode_id == it.itemcode_id) || {};
      return {
        _rowKey: `existing-${it.mcrt_item_id ?? idx}`,
        mcrt_item_id: it.mcrt_item_id,
        itemcode_id: it.itemcode_id,
        description: found.product_name || it.description || "",
        units: found.units || it.units || "",
        qty_return: it.returned_qty,
        cost: it.cost,
        condition: it.condition,
        isExisting: true,
      };
    });

    if (mcrtItems.value.length === 0) {
      mcrtItems.value.push({
        _rowKey: "new-0",
        mcrt_item_id: null,
        itemcode_id: "",
        description: "",
        units: "",
        qty_return: 1,
        cost: 0,
        condition: "",
        isExisting: false,
      });
    }

    await nextTick();
    initItemSelect();
  } catch (err) {
    console.error("Failed to load MCRT details:", err);
    errorMessage.value = "Failed to load MCRT details.";
  } finally {
    loading.value = false;
  }
}

// INIT SELECT2 PER ROW
function initItemSelect() {
  mcrtItems.value.forEach((item, index) => {
    const selector = "#itemSelectUpdate" + index;

    if ($(selector).data("select2")) {
      $(selector).select2("destroy");
    }

    $(selector)
      .select2({
        theme: "bootstrap-5",
        placeholder: "Search Item",
        allowClear: true,
        dropdownParent: $(".modal.show"),
        width: "100%",
      })
      .val(item.itemcode_id ? String(item.itemcode_id) : "")
      .trigger("change")
      .prop("disabled", item.isExisting)
      .on("change", function () {
        const id = $(this).val();
        item.itemcode_id = id;

        const found = itemsList.value.find((i) => i.ItemCode_id == id);
        if (!found) return;

        item.description = found.product_name || "";
        item.units = found.units || "";
        if (!item.isExisting) {
          item.cost = found.cost || 0;
        }
      });
  });
}

// ADD NEW ITEM ROW
async function addItem() {
  const newRow = {
    _rowKey: `new-${Date.now()}`,
    mcrt_item_id: null,
    itemcode_id: "",
    description: "",
    units: "",
    qty_return: 1,
    cost: 0,
    condition: "",
    isExisting: false,
  };

  mcrtItems.value.push(newRow);
  await nextTick();
  initItemSelect();
}

// REMOVE ITEM
function removeItem(index) {
  const item = mcrtItems.value[index];

  // For now, protect existing items from being deleted by UI
  if (item.isExisting) {
    return;
  }

  mcrtItems.value.splice(index, 1);
  nextTick(initItemSelect);
}

// VALIDATION
function validateForm() {
  fieldErrors.value = {};
  itemErrors.value = {};
  let valid = true;

  if (!form.value.returned_by) {
    fieldErrors.value.returned_by = "Returned By is required.";
    valid = false;
  }

  if (!mcrtItems.value.length) {
    errorMessage.value = "At least one item is required.";
    valid = false;
  }

  const codes = mcrtItems.value.map((i) => i.itemcode_id);

  mcrtItems.value.forEach((item, idx) => {
    itemErrors.value[idx] = {};

    if (!item.itemcode_id) {
      itemErrors.value[idx].itemcode = "ItemCode required.";
      valid = false;
    }

    if (
      item.itemcode_id &&
      codes.filter((c) => c === item.itemcode_id).length > 1
    ) {
      itemErrors.value[idx].duplicate = "Duplicate ItemCode.";
      valid = false;
    }

    if (!item.description) {
      itemErrors.value[idx].description = "Description is required.";
      valid = false;
    }

    if (!item.units) {
      itemErrors.value[idx].units = "Units are required.";
      valid = false;
    }

    if (!item.qty_return || item.qty_return < 1) {
      itemErrors.value[idx].qty_return = "Qty must be at least 1.";
      valid = false;
    }

    if (item.cost === "" || item.cost < 0) {
      itemErrors.value[idx].cost = "Cost must be 0 or greater.";
      valid = false;
    }

    if (!item.condition) {
      itemErrors.value[idx].condition = "Condition is required.";
      valid = false;
    } else if (!["G", "U"].includes(item.condition)) {
      itemErrors.value[idx].condition = "Invalid condition value.";
      valid = false;
    }
  });

  nextTick(() => {
    const errRow = document.querySelector(".row-error");
    if (errRow) {
      errRow.scrollIntoView({ behavior: "smooth", block: "center" });
    }
  });

  return valid;
}

// SAVE UPDATE
async function saveUpdate() {
  errorMessage.value = "";

  if (!validateForm()) return;

  try {
    const payload = {
      returned_by: form.value.returned_by,
      received_by: form.value.received_by, // still required sa backend validation
      work_order: form.value.work_order,
      job_order: form.value.job_order,
      items: mcrtItems.value.map((it) => ({
        mcrt_item_id: it.mcrt_item_id ?? null,
        itemcode_id: it.itemcode_id,
        returned_qty: it.qty_return,
        cost: it.cost,
        condition: it.condition,
      })),
    };

    const res = await api.put(
      `/Mcrt/updateMcrt/${props.item.mcrt_id}`,
      payload
    );

    if (res.data?.success) {
      showSuccess.value = true;
    } else {
      errorMessage.value = res.data?.message || "Failed to update MCRT.";
    }
  } catch (err) {
    console.error("Update failed:", err);
    errorMessage.value =
      err.response?.data?.message ||
      "Failed to update MCRT due to an error.";
  }
}

function closeSuccess() {
  showSuccess.value = false;
  emit("updated");
  emit("close");
}

function closeModal() {
  emit("close");
}

onMounted(async () => {
  await fetchItems();
  await fetchMcrtDetails();
});
</script>

<style scoped>
.modal-auto-fit {
  max-width: 60vw;
  width: auto;
}

/* validation */
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

/* success modal */
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
