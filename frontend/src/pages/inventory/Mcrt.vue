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
      <button class="btn btn-primary" @click="openCreate">
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
            <h6 class="label-text">For Repair</h6>
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
    <div v-else class="table-responsive">
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
                    <a href="#" @click.prevent="openUpdate(mcrt)" class="text-primary">
                      <i class="bi bi-pencil me-2"></i>Update
                    </a>

                    <a href="#" @click.prevent="openDelete(mcrt)" class="text-danger">
                      <i class="bi bi-trash me-2"></i>Delete
                    </a>
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
    <AddMcrt v-if="showAddModal" @close="showAddModal = false" />

  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, onBeforeUnmount } from "vue";
import api from "../../services/api";
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
        if (i.condition === "Good as new") good++;
        if (i.condition === "For Repair") repair++;
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
</script>

<style scoped>
.status-card {
  padding: 20px;
  border-radius: 12px;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 120px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.label-text {
  font-size: 0.85rem;
  font-weight: 600;
}

.value-text {
  font-size: 2rem;
  font-weight: bold;
}

.status-icon {
  font-size: 3rem;
  opacity: 0.3;
}

.cursor-pointer {
  cursor: pointer;
}

.dropdown-menu-teleport a {
  display: block;
  padding: 8px;
}

.dropdown-menu-teleport a:hover {
  background: #f5f5f5;
}

.clear-icon {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1.1rem;
  cursor: pointer;
}

.clear-icon:hover {
  color: #000;
}
</style>
