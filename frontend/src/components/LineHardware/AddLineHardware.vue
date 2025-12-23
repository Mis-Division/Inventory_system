<template>
  <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.55)">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-auto-fit">
      <div class="modal-content rounded-4 overflow-hidden">

        <!-- ================= HEADER ================= -->
        <div class="modal-header header-modern">
          <div class="d-flex align-items-center gap-3">

            <div class="header-icon">
              <i class="bi bi-tools"></i>
            </div>

            <div>
              <h5 class="mb-0 fw-semibold">{{ productType }} Release</h5>
              <small class="text-muted">Material Charge Ticket</small>
            </div>

            <!-- MRV SEARCH -->
            <div class="ms-4 position-relative">
              <input
                type="text"
                class="form-control form-control-sm search-pill pe-4"
                v-model="mrvNumber"
                placeholder="Enter MRV #"
                @keydown.enter.prevent="checkMrv"
              />
              <i
                v-if="mrvNumber"
                class="bi bi-x-circle-fill clear-mrv"
                @click="clearMrv"
              ></i>
            </div>
          </div>

          <button class="btn-close" @click="emit('close')"></button>
        </div>

        <!-- ================= BODY ================= -->
        <div class="modal-body bg-light">

          <!-- ALERT -->
          <div v-if="errorMessage" class="alert alert-warning rounded-3 py-2">
            <i class="bi bi-exclamation-circle me-1"></i>
            {{ errorMessage }}
          </div>

          <!-- MRV INFO -->
          <div v-if="mrvLoaded" class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label text-muted small">Requisitioner</label>
                  <input class="form-control" :value="mrvHeader.requested_by" disabled />
                </div>

                <div class="col-md-4">
                  <label class="form-label text-muted small">Received By</label>
                  <input
                    class="form-control"
                    id="employeeSelect"
                    placeholder="Receiver name"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- ITEMS TABLE -->
          <div v-if="items.length" class="card shadow-sm border-0">
            <div class="card-body p-0">

              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th class="text-end">Remaining</th>
                    <th style="width:32%">Account Code</th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="(item, index) in items"
                    :key="item.itemcode_id"
                    :class="{ 'row-disabled': item.remaining_qty === 0 }"
                  >
                    <td class="fw-medium">{{ item.ItemCode }}</td>
                    <td>{{ item.product_name }}</td>
                    <td class="text-end fw-semibold">{{ item.remaining_qty }}</td>

                    <td>
                      <div class="account-input-wrapper">
                        <input
                          type="text"
                          class="form-control form-control-sm pe-5"
                          v-model="item.account_search"
                          placeholder="Select account"
                          :disabled="item.remaining_qty === 0"
                          autocomplete="off"
                          @focus="openAccountDropdown(index, $event)"
                          @input="fetchAccounts(item.account_search)"
                        />

                        <i
                          v-if="item.account_search"
                          class="bi bi-x-circle-fill clear-account"
                          @click="clearItemAccount(index)"
                        ></i>
                      </div>
                    </td>
                  </tr>
                </tbody>

              </table>

            </div>
          </div>

          <!-- FOOTER ACTIONS -->
          <div v-if="mrvLoaded" class="d-flex justify-content-end gap-2 mt-3">
            <button class="btn btn-secondary" @click="emit('close')">
              Cancel
            </button>

            <button class="btn btn-success" @click="saveMct">
              <i class="bi bi-check-circle me-1"></i>
              Save MCT
            </button>
          </div>

        </div>
      </div>
    </div>

    <!-- ================= FIXED DROPDOWN ================= -->
    <div
      v-if="activeDropdownIndex !== null"
      class="dropdown-menu show account-dropdown-fixed"
      :style="dropdownStyle"
    >
      <div
        v-for="acc in accounts"
        :key="acc.account_code"
        class="dropdown-item cursor-pointer"
        @click="selectItemAccount(activeDropdownIndex, acc)"
      >
        <strong>{{ acc.account_code }}</strong><br />
        <small class="text-muted">{{ acc.description }}</small>
      </div>

      <div v-if="!accounts.length" class="dropdown-item text-muted">
        No results found
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from "vue";
import api, { apiRemote } from "../../services/api";

const emit = defineEmits(["close"]);

/* ================= STATE ================= */
const productType = ref("Line Hardware");
const mrvNumber = ref("");

const mrvHeader = ref({});
const items = ref([]);
const accounts = ref([]);
const employees = ref([]);

const mrvLoaded = ref(false);
const receivedBy = ref("");
const errorMessage = ref("");

const activeDropdownIndex = ref(null);
const dropdownStyle = ref({});

/* ================= MRV CHECK ================= */
async function checkMrv() {
  if (!mrvNumber.value) return;

  clearMrv(false);
  errorMessage.value = "";

  try {
    const res = await api.get("Mct/check-mrv", {
      params: {
        mrv_number: mrvNumber.value,
        product_type: productType.value
      },
      headers: { "X-Skip-Loading": true }
    });

    if (res.data.status !== "AVAILABLE") {
      errorMessage.value = res.data.message;
      return;
    }

    mrvHeader.value = {
      mrv_id: res.data.mrv_id,
      requested_by: res.data.requested_by
    };

    items.value = res.data.items.map(i => ({
      ...i,
      account_code: null,
      account_search: ""
    }));

    mrvLoaded.value = true;

  } catch (err) {
    errorMessage.value = err.response?.data?.message || "MRV validation failed.";
  }
}

/* ================= EMPLOYEE ================= */
async function fetchEmployee() {
  const res = await apiRemote.get("/employees", {
    params: { page: 1, per_page: 500 }
  });

  employees.value = res.data.data.data
    .filter(e => e.DEPTNAME.toUpperCase() !== "RETIRED")
    .map(e => ({
      id: e.NAME.trim().toUpperCase(),
      text: e.NAME.trim().toUpperCase()
    }));

  await nextTick();
  initEmployeeSelect();
}

function initEmployeeSelect() {
  const selector = "#employeeSelect";

  if ($(selector).data("select2")) {
    $(selector).select2("destroy");
  }

  $(selector)
    .select2({
      theme: "bootstrap-5",
      dropdownParent: $(".modal.show"),
      width: "100%",
      data: employees.value
    })
    .on("change", function () {
      receivedBy.value = $(this).val();
    });
}

/* ================= ACCOUNT ================= */
let accountTimeout = null;

function fetchAccounts(search = "") {
  clearTimeout(accountTimeout);
  accountTimeout = setTimeout(async () => {
    const res = await api.get("CodeAccount/Accounts", {
      params: { search },
      headers: { "X-Skip-Loading": true }
    });
    accounts.value = res.data.data || [];
  }, 300);
}

function openAccountDropdown(index, event) {
  activeDropdownIndex.value = index;

  nextTick(() => {
    const rect = event.target.getBoundingClientRect();
    dropdownStyle.value = {
      position: "fixed",
      top: `${rect.bottom + 4}px`,
      left: `${rect.left}px`,
      width: `${rect.width}px`,
      zIndex: 2000
    };
  });

  fetchAccounts(items.value[index].account_search || "");
}

function selectItemAccount(index, acc) {
  items.value[index].account_code = acc.account_code;
  items.value[index].account_search = `${acc.account_code} - ${acc.description}`;
  activeDropdownIndex.value = null;
}

function clearItemAccount(index) {
  items.value[index].account_code = null;
  items.value[index].account_search = "";
  activeDropdownIndex.value = null;
}

/* ================= SAVE ================= */
async function saveMct() {
  if (!receivedBy.value) {
    alert("Received By is required.");
    return;
  }

  const invalid = items.value.find(
    i => i.remaining_qty > 0 && !i.account_code
  );

  if (invalid) {
    alert("All remaining items must have an account code.");
    return;
  }

  const payload = {
    mrv_id: mrvHeader.value.mrv_id,
    mrv_number: mrvNumber.value,
    product_type: productType.value,
    requested_by: mrvHeader.value.requested_by,
    received_by: receivedBy.value,
    remarks: "",
    items: items.value
      .filter(i => i.remaining_qty > 0)
      .map(i => ({
        itemcode_id: i.itemcode_id,
        requested_qty: i.remaining_qty,
        account_code: i.account_code,
        remarks: null
      }))
  };

  try {
    await api.post("Mct/MctCreate", payload);
    alert("MCT saved successfully.");
    emit("close");
  } catch (err) {
    alert(err.response?.data?.message || "Failed to save MCT.");
  }
}

/* ================= CLEAR ================= */
function clearMrv(clearInput = true) {
  if (clearInput) mrvNumber.value = "";
  mrvHeader.value = {};
  items.value = [];
  mrvLoaded.value = false;
  receivedBy.value = "";
  activeDropdownIndex.value = null;
}

/* ================= CLICK OUTSIDE ================= */
function handleClickOutside(e) {
  if (
    !e.target.closest(".account-input-wrapper") &&
    !e.target.closest(".account-dropdown-fixed")
  ) {
    activeDropdownIndex.value = null;
  }
}

onMounted(async () => {
  await fetchEmployee();
  document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>


<style scoped>
.modal-auto-fit {
  max-width: 65vw;
}

/* HEADER */
.header-modern {
  background: linear-gradient(135deg, #198754, #157347);
  color: #fff;
  padding: 1rem 1.25rem;
}

.header-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: rgba(255, 255, 255, .15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

/* SEARCH */
.search-pill {
  border-radius: 20px;
  min-width: 260px;
}

.clear-mrv {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  color: #6c757d;
  z-index: 5;
}

.clear-mrv:hover {
  color: #dc3545;
}

/* TABLE */
.row-disabled {
  opacity: .55;
  background: #f8f9fa;
}

/* ACCOUNT */
.account-input-wrapper {
  position: relative;
}

.clear-account {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
}

.clear-account:hover {
  color: #dc3545;
}

/* DROPDOWN */
.account-dropdown-fixed {
  max-height: 240px;
  overflow-y: auto;
  z-index: 2000;
  border-radius: 12px;
  box-shadow: 0 8px 22px rgba(0, 0, 0, .18);
}

.cursor-pointer {
  cursor: pointer;
}

.dropdown-item:hover {
  background-color: #f1f5f9;
}
</style>
