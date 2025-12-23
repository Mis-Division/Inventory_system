<template>
  <div class="main-container">
    <!-- ================= HEADER ================= -->
    <div class="custom-headers">
      <div class="w-100">
        <h1 class="m-0 mb-3">
          <i class="bi bi-info-circle text-primary"></i>
          Request for Materials
        </h1>
      </div>

      <div class="d-flex justify-content-between align-items-center w-100">
        <!-- Search -->
        <div class="position-relative" style="width:35%; min-width:300px;">
          <input type="text" v-model="searchQuery" @keyup.enter="handleSearchEnter" class="form-control pe-5"
            placeholder="Search Request..." style="background-color:#FCF6D9;" />
          <i v-if="searchQuery" class="bi bi-x-circle-fill text-muted" @click="clearSearch"
            style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer;"></i>
        </div>

        <!-- Add -->
        <button v-if="canAddRFM" @click="addRfm" class="btn btn-success">
          <i class="bi bi-plus-circle me-1"></i> Request Materials
        </button>
      </div>
    </div>

    <!-- ================= TABLE ================= -->
    <div v-if="!loading" class="table-responsive">
      <table class="table align-middle text-center">
        <thead>
          <tr>
            <td>RFM #</td>
            <td>Date</td>
            <td>Requested By</td>
            <td>Department</td>
            <td>Management</td>
            <td width="60"></td>
          </tr>
        </thead>

        <tbody v-if="rfms.length">
          <tr v-for="rfm in rfms" :key="rfm.rfm_id">
            <td class="fw-bold text-primary">
              {{ rfm.rfm_number }}
              <span v-if="rfm.warehouse_initial" class="badge bg-danger ms-2" title="Processed by Warehouse">
                LOCKED
              </span>
            </td>

            <td>{{ rfm.rfm_date }}</td>
            <td>{{ rfm.requested_by }}</td>
            <td>{{ rfm.department }}</td>
            <td>{{ rfm.area_engineering }}</td>

            <!-- ACTION -->
            <td>
              <div class="cursor-pointer" @click.stop="toggleDropdown(rfm.rfm_id, $event)">
                <i class="bi bi-three-dots"></i>
              </div>

              <teleport to="body">
                <div v-if="activeDropdown === rfm.rfm_id" ref="el => (dropdownRefs[rfm.rfm_id] = el)"
                  class="dropdown-menu-teleport" :style="getDropdownStyle(rfm.rfm_id)" @click.stop>
                  <!-- UPDATE (DISABLED WHEN LOCKED) -->
                  <a v-if="canEditRFM" href="#" :class="rfm.warehouse_initial ? 'disabled-link' : 'text-success'"
                    @click.prevent="!rfm.warehouse_initial && handleUpdate(rfm)">
                    <i class="bi bi-pencil me-2"></i>Update
                  </a>

                  <!-- DELETE (DISABLED WHEN LOCKED) -->
                  <a v-if="canDeleteRFM" href="#" :class="rfm.warehouse_initial ? 'disabled-link' : 'text-danger'"
                    @click.prevent="!rfm.warehouse_initial && deleteRfm(rfm)">
                    <i class="bi bi-trash me-2"></i>Delete
                  </a>

                  <!-- PRINT (ALWAYS ENABLED) -->
                  <a href="#" class="text-warning" @click.prevent="handlePrint(rfm)">
                    <i class="bi bi-printer me-2"></i>Print
                  </a>
                </div>
              </teleport>
            </td>
          </tr>
        </tbody>

        <tbody v-else>
          <tr>
            <td colspan="6" class="text-muted py-4">
              No Request for Materials found
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- MODALS -->
  <AddMaterials v-if="showAddRfmModal" @close="showAddRfmModal = false" @submit="fetchRfms" />

  <UpdateMaterials v-if="showUpdateRfmModal && selectedRequest" :request="selectedRequest" @close="closeUpdateModal"
    @updated="fetchRfms" />

  <DeleteMaterials v-if="showDeleteRfmModal && selectedRequest" :rfm="selectedRequest" @close="closeDeleteModal"
    @deleted="fetchRfms" />
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from "vue";
import { userStore } from "../../stores/userStore";
import api from "../../services/api";

import AddMaterials from "../../components/RequestMaterials/AddMaterials.vue";
import UpdateMaterials from "../../components/RequestMaterials/UpdateMaterials.vue";
import DeleteMaterials from "../../components/RequestMaterials/DeleteMaterials.vue";

/* ================= STATE ================= */
const rfms = ref([]);
const searchQuery = ref("");
const loading = ref(false);

const showAddRfmModal = ref(false);
const showUpdateRfmModal = ref(false);
const showDeleteRfmModal = ref(false);
const selectedRequest = ref(null);

/* ================= PERMISSION ================= */
const canAddRFM = computed(() => userStore.canAddRFM);
const canEditRFM = computed(() => userStore.canEditRFM);
const canDeleteRFM = computed(() => userStore.canDeleteRFM);

/* ================= DROPDOWN ================= */
const activeDropdown = ref(null);
const dropdownPositions = ref({});
const dropdownRefs = ref({});

function toggleDropdown(id, event) {
  event.stopPropagation();
  activeDropdown.value = activeDropdown.value === id ? null : id;

  nextTick(() => {
    const rect = event.currentTarget.getBoundingClientRect();
    dropdownPositions.value[id] = {
      top: rect.top + window.scrollY,
      left: rect.right + window.scrollX + 8,
    };
  });
}

function getDropdownStyle(id) {
  const pos = dropdownPositions.value[id];
  if (!pos) return {};

  const width = 180;
  let left = pos.left;

  if (left + width > window.innerWidth) {
    left = pos.left - width - 12;
  }

  return {
    position: "absolute",
    top: pos.top + "px",
    left: left + "px",
    minWidth: width + "px",
    zIndex: 3000,
  };
}

function closeDropdown() {
  activeDropdown.value = null;
}

function handleClickOutside(e) {
  if (!activeDropdown.value) return;
  const el = dropdownRefs.value[activeDropdown.value];
  if (!el || !el.contains(e.target)) activeDropdown.value = null;
}

/* ================= ACTIONS ================= */
async function handleUpdate(rfm) {
  closeDropdown();

  try {
    const res = await api.get(`/Rfm/display/${rfm.rfm_id}`);
    if (res.data.success) {
      selectedRequest.value = res.data.data;
      showUpdateRfmModal.value = true;
    }
  } catch (err) {
    alert("This RFM is already locked by the warehouse.");
  }
}

async function deleteRfm(rfm) {
  closeDropdown();

  try {
    const res = await api.get(`/Rfm/display/${rfm.rfm_id}`);
    if (res.data.success) {
      selectedRequest.value = res.data.data;
      showDeleteRfmModal.value = true;
    }
  } catch (err) {
    alert("This RFM is already locked by the warehouse.");
  }
}

function handlePrint(rfm) {
  closeDropdown();
  window.open(`/rfm/print/${rfm.rfm_id}`, "_blank");
}

/* ================= FETCH ================= */
async function fetchRfms() {
  loading.value = true;
  try {
    const res = await api.get("/Rfm/display", {
      params: { search: searchQuery.value },
    });
    rfms.value = res.data.data;
  } finally {
    loading.value = false;
  }
}

/* ================= UTIL ================= */
function handleSearchEnter() {
  fetchRfms();
}
function clearSearch() {
  searchQuery.value = "";
  fetchRfms();
}
function addRfm() {
  showAddRfmModal.value = true;
}
function closeUpdateModal() {
  showUpdateRfmModal.value = false;
  selectedRequest.value = null;
}
function closeDeleteModal() {
  showDeleteRfmModal.value = false;
  selectedRequest.value = null;
}

/* ================= LIFECYCLE ================= */
onMounted(() => {
  document.addEventListener("click", handleClickOutside);
  fetchRfms();
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>

<style>
.cursor-pointer {
  cursor: pointer;
  padding: 6px 10px;
}

.dropdown-menu-teleport {
  background: #fff;
  border-radius: 10px;
  padding: 8px 0;
  border: 1px solid #d5dbea;
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
}

.dropdown-menu-teleport a {
  display: block;
  padding: 10px 15px;
  font-weight: 600;
}

.dropdown-menu-teleport a:hover {
  background: #f1f4ff;
}

.disabled-link {
  color: #adb5bd !important;
  pointer-events: none;
  cursor: not-allowed;
}
</style>
