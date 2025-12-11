<template>
  <div class="main-container">

    <!-- PAGE HEADER -->
    <h2 class="mb-2">
      <i class="bi bi-receipt text-primary"></i> Material Credit Ticket
    </h2>

    <!-- SEARCH + ADD BUTTON ROW -->
    <div class="d-flex justify-content-between align-items-center mb-4">

      <!-- SEARCH WITH CLEAR X -->
      <div class="position-relative" style="width: 300px;">
        <input v-model="searchQuery" @keyup.enter="handleSearch" type="text" class="form-control"
          placeholder="Search MCRT..." />

        <!-- CLEAR X ICON -->
        <i v-if="searchQuery" class="bi bi-x-circle-fill text-muted clear-icon" @click="clearSearch"></i>
      </div>

      <!-- ADD BUTTON -->
      <button v-if="canAddCreditTicket" class="btn btn-primary" @click="openCreate">
        <i class="bi bi-plus-circle me-1"></i> Add MCRT
      </button>
    </div>
    <!-- ===== TOP CARDS (2 ONLY) ===== -->
    <div class="row g-3 mb-4">

      <!-- Good as New -->
      <div class="col-md-6">
        <div class="status-card bg-success">
          <div>
            <h6 class="label-text">Good as New</h6>
            <h3 class="value-text">{{ goodAsNewCount }}</h3>
          </div>
          <i class="bi bi-star-fill status-icon"></i>
        </div>
      </div>

      <!-- For Repair -->
      <div class="col-md-6">
        <div class="status-card bg-warning">
          <div>
            <h6 class="label-text">Ussable</h6>
            <h3 class="value-text">{{ forRepairCount }}</h3>
          </div>
          <i class="bi bi-wrench-adjustable-circle-fill status-icon"></i>
        </div>
      </div>

    </div>

    <!-- EMPTY -->
    <div v-if="mcrtList.length === 0" class="text-center my-3 text-muted">
      No MCRT found.
    </div>

    <!-- ===== TABLE ===== -->
    <div v-else>
      <table class="table table-hover table-striped align-middle text-center">
        <thead class="table-secondary">
          <tr>
            <th>MCRT #</th>
            <th>Returned By</th>
            <th>Work Order</th>
            <th>Job Order</th>
            <th>Date Received</th>
            <th style="width: 10%;">Action</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="mcrt in mcrtList" :key="mcrt.mcrt_id">
            <td>{{ mcrt.mcrt_number }}</td>
            <td>{{ mcrt.returned_by }}</td>
            <td>{{ mcrt.work_order }}</td>


            <td>
              {{ mcrt.job_order }}
            </td>
            <td>{{ formatDate(mcrt.created_at) }}</td>
            <td>
              <div @click="toggleDropdown(mcrt.mcrt_id, $event)" class="cursor-pointer">
                <i class="bi bi-three-dots"></i>
              </div>

              <!-- Dropdown via Teleport -->
              <teleport to="body">
                <Transition name="fade">
                  <div v-if="activeDropdown === mcrt.mcrt_id" ref="el => (dropdownRefs.value ??= {})[mcrt.mcrt_id] = el"
                    class="dropdown-menu-teleport" :style="getDropdownStyle(mcrt.mcrt_id)" @click.stop>

                    <!-- UPDATE -->
                    <a href="#" @click.prevent="openUpdate(mcrt)" class="text-primary" v-if="canEditCreditTicket">
                      <i class="bi bi-pencil me-2"></i> Update
                    </a>

                    <!-- DELETE -->
                    <a href="#" @click.prevent="openDelete(mcrt)" class="text-danger" v-if="canDeleteCreditTicket">
                      <i class="bi bi-trash me-2"></i> Delete
                    </a>

                    <!-- ❌ NO PERMISSION -->
                    <div v-if="!canEditCreditTicket && !canDeleteCreditTicket" class="text-muted no-permission">
                      <i class="bi bi-lock me-2"></i>No permission
                    </div>

                  </div>

                </Transition>
              </teleport>

            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ===== PAGINATION ===== -->
    <div v-if="pagination.last_page > 1" class="d-flex justify-content-end mt-3">
      <button class="btn btn-sm btn-outline-primary me-1" :disabled="pagination.current_page === 1"
        @click="changePage(pagination.current_page - 1)">
        Previous
      </button>

      <button v-for="page in pagination.last_page" :key="page" @click="changePage(page)" class="btn btn-sm me-1" :class="pagination.current_page === page
        ? 'btn-primary rounded-circle'
        : 'btn-outline-primary rounded-circle'" style="width: 35px; height: 35px; padding: 0;">
        {{ page }}
      </button>

      <button class="btn btn-sm btn-outline-primary" :disabled="pagination.current_page === pagination.last_page"
        @click="changePage(pagination.current_page + 1)">
        Next
      </button>
    </div>

    <!-- MODALS -->
    <UpdateMcrt v-if="showUpdateModal" :item="selectedItem" @close="closeUpdate" @updated="reload" />
    <DeleteMcrt v-if="showDeleteModal" :item="selectedItem" @close="closeDelete" @confirm="confirmDelete" />
    <AddMcrt v-if="showAddModal" @close="showAddModal = false" @saved="handleAddSuccess" />

  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, onBeforeUnmount, computed } from "vue";
import api from "../../services/api";
import { userStore } from "../../stores/userStore";
import UpdateMcrt from "../../components/MCRT/UpdateMcrt.vue";
import DeleteMcrt from "../../components/MCRT/DeleteMcrt.vue";
import AddMcrt from "../../components/MCRT/AddMcrt.vue";

/* STATE */
const mcrtList = ref([]);
const pagination = ref({});
const searchQuery = ref("");

/* COUNTS */
const goodAsNewCount = ref(0);
const forRepairCount = ref(0);

/* DROPDOWN */
const activeDropdown = ref(null);
const dropdownRefs = ref({});
const dropdownPositions = ref({});

/* MODALS */
const selectedItem = ref(null);
const showUpdateModal = ref(false);
const showDeleteModal = ref(false);
const showAddModal = ref(false);

//load add modal
function openCreate() {
  showAddModal.value = true;
}
//users permissions

const canAddCreditTicket = computed(() => userStore.canAddCreditTicket);
const canEditCreditTicket = computed(() => userStore.canEditCreditTicket);
const canDeleteCreditTicket = computed(() => userStore.canDeleteCreditTicket);

/* LOAD DATA */
async function loadData(page = 1) {
  const res = await api.get("/Mcrt/displayMcrt", {
    params: {
      page,
      per_page: 10,
      search: searchQuery.value,
    },
  });

  mcrtList.value = res.data.data.data;
  pagination.value = res.data.data;

  computeCounts();
}

/* COUNT CONDITIONS */
function computeCounts() {
  let good = 0;
  let repair = 0;

  mcrtList.value.forEach(mcrt => {
    if (mcrt.items) {
      mcrt.items.forEach(i => {
        if (i.condition === "G") good++;
        if (i.condition === "U") repair++;
      });
    }
  });

  goodAsNewCount.value = good;
  forRepairCount.value = repair;
}


/* SEARCH */
function handleSearch() {
  loadData(1);
}

/* PAGINATION */
function changePage(page) {
  loadData(page);
}

/* DROPDOWN HANDLING */
function toggleDropdown(id, event) {
  event.stopPropagation();
  activeDropdown.value = activeDropdown.value === id ? null : id;

  nextTick(() => {
    const rect = event.currentTarget.getBoundingClientRect();

    dropdownPositions.value[id] = {
      top: rect.bottom + window.scrollY,
      left: rect.left + window.scrollX,
    };
  });
}

function getDropdownStyle(id) {
  const pos = dropdownPositions.value[id] || { top: 0, left: 0 };

  return {
    position: "absolute",
    top: pos.top + "px",
    left: (pos.left - 120) + "px",   // 👈 Shift 120px to the LEFT
    zIndex: 9999,
    background: "white",
    border: "1px solid #ddd",
    borderRadius: "6px",
    minWidth: "150px",
    padding: "0.5rem",
    boxShadow: "0 2px 6px rgba(0,0,0,0.2)",
  };
}
function handleClickOutside(event) {
  // If dropdown is not open → ignore
  if (!activeDropdown.value) return;

  // Find current dropdown element
  const dropdownEl = dropdownRefs.value[activeDropdown.value];

  // If kinlick ang labas ng dropdown → close
  if (!dropdownEl || !dropdownEl.contains(event.target)) {
    activeDropdown.value = null;
  }
}


/* MODALS */
function openUpdate(item) {
  activeDropdown.value = null;
  selectedItem.value = item;
  showUpdateModal.value = true;
}

function closeUpdate() {
  showUpdateModal.value = false;
}

function openDelete(item) {
  activeDropdown.value = null;
  selectedItem.value = item;
  showDeleteModal.value = true;
}

function closeDelete() {
  showDeleteModal.value = false;
}

async function confirmDelete() {
  try {
    await api.delete(`/Mcrt/McrtDelete/${selectedItem.value.mcrt_id}`);

    // Close modal
    showDeleteModal.value = false;

    // Close open dropdown (VERY IMPORTANT)
    activeDropdown.value = null;

    // Reload current page
    await loadData(pagination.value.current_page);

  } catch (err) {
    console.error("Failed to delete:", err);
  }
}

/* FORMAT */
function formatDate(date) {
  return date ? new Date(date).toLocaleDateString() : "-";
}

onMounted(() => {
  loadData();
  document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
});

function clearSearch() {
  searchQuery.value = "";
  loadData(1); // reload table to defaults
}
function reload() {
  loadData(pagination.value.current_page);
}

function handleAddSuccess() {
  showAddModal.value = false;
  loadData(pagination.value.current_page);
}
</script>

<style scoped>
/* ============================================================
   🌟 PAGE HEADER – Bold Engineering Style
============================================================ */
.main-container h2 {
  font-weight: 800;
  color: #2f3b52;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Search bar */
.main-container input.form-control {
  border-radius: 10px;
  padding: 10px 14px;
  border: 1px solid #c8d0db;
  background: #f7f9fb;
  transition: 0.25s ease;
}

.main-container input.form-control:focus {
  border-color: #4c82ff;
  background: white;
  box-shadow: 0 0 0 3px rgba(76, 130, 255, 0.25);
}

.clear-icon {
  color: #9aa4b7;
  transition: 0.2s ease;
}

.clear-icon:hover {
  color: #4c82ff;
}

/* ADD BUTTON */
.btn-primary {
  padding: 10px 18px;
  border-radius: 10px;
  font-weight: 600;
  background: linear-gradient(135deg, #4c82ff, #3a6be0);
  border: none;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #3a6be0, #335dcc);
}

/* ============================================================
   📊 TOP 2 CARDS – INDUSTRIAL GRADIENT LOOK
============================================================ */
.status-card {
  padding: 22px;
  border-radius: 16px;
  height: 140px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  transition: 0.25s ease;
  position: relative;
  overflow: hidden;
}

/* Gradient backgrounds – UNIQUE to MCRT */
.status-card.bg-success {
  background: linear-gradient(135deg, #46c878 0%, #2f9e54 100%);
}

.status-card.bg-warning {
  background: linear-gradient(135deg, #ffb648 0%, #ff8c00 100%);
}

.status-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.2);
}

.label-text {
  font-size: 0.9rem;
  font-weight: 600;
}

.value-text {
  font-size: 2.1rem;
  font-weight: 800;
}

.status-icon {
  font-size: 3.2rem;
  opacity: 0.25;
}

/* ============================================================
   📋 TABLE – Lifted rows + clean engineering style
============================================================ */
.table {
  border-collapse: separate !important;
  border-spacing: 0 8px !important;
}

.table thead th {
  background: #e6e9f2 !important;
  padding: 14px;
  font-size: 0.9rem;
  color: #3b4660;
  font-weight: 700;
  border: none;
}

.table tbody tr {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.07);
  transition: 0.18s ease;
}

.table tbody tr:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 14px rgba(0, 0, 0, 0.14);
}

.table td {
  padding: 12px 10px !important;
  font-weight: 500;
  color: #394155;
}

/* ============================================================
   ⋮ DROPDOWN – Floating neon-blue shadow
============================================================ */
.cursor-pointer {
  padding: 6px 10px;
  border-radius: 8px;
  transition: 0.2s ease;
}

.cursor-pointer:hover {
  background: #eef2ff;
}

.dropdown-menu-teleport {
  position: absolute;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(8px);
  border-radius: 14px;
  min-width: 160px;
  padding: 8px 0;
  border: 1px solid #d2d7e2;
  box-shadow: 0 10px 30px rgba(60, 90, 255, 0.25);
  animation: dropdownPop 0.15s ease-out;
}

@keyframes dropdownPop {
  from {
    opacity: 0;
    transform: translateY(-6px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-menu-teleport a {
  display: block;
  padding: 10px 14px;
  font-weight: 600;
  color: #3d4a66;
  transition: 0.2s;
}

.dropdown-menu-teleport a:hover {
  background: #edf1ff;
  border-radius: 10px;
}

/* ============================================================
   🔢 PAGINATION – Circle Navigation
============================================================ */
.btn-outline-primary {
  border-radius: 10px;
}

.rounded-circle {
  border-radius: 50% !important;
}

.btn-primary.rounded-circle {
  background: #4c82ff;
  border: none;
  box-shadow: 0 3px 6px rgba(76, 130, 255, 0.35);
}

.btn-outline-primary.rounded-circle:hover {
  background: rgba(76, 130, 255, 0.1);
  color: #355dcc;
}
</style>
