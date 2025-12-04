<template>
  <div class="main-container">
    <!-- Header -->
    <div class="custom-headers">
      <div class="w-100">
        <h1 class="m-0 mb-3">
          <i class="bi bi-info-circle text-primary"></i> Material Requisition Voucher
        </h1>
      </div>

      <div class="d-flex justify-content-between align-items-center w-100">
        <!-- Search -->
        <div class="position-relative" style="width: 35%; min-width: 300px;">
          <input type="text" v-model="searchQuery" @keyup.enter="handleSearchEnter" class="form-control pe-5"
            placeholder="Search Request..." style="background-color: #FCF6D9;" />

          <!-- Clear Button (X) -->
          <i v-if="searchQuery" class="bi bi-x-circle-fill text-muted" @click="clearSearch"
            style="position: absolute;right: 10px;top: 50%; transform: translateY(-50%);cursor: pointer; font-size: 1.2rem;"></i>
        </div>
        <!-- Add MRV button -->
        <button v-if="canAddMVR" @click="addMrv" class="btn btn-success">
          <i class="bi bi-plus-circle me-1"></i> MRV
        </button>
      </div>
    </div>

    <!-- Loading / Error -->
    <div v-if="loading" class="text-center py-4 text-muted">
      Loading Material Requisition Voucher...
    </div>
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- Table -->
    <div v-if="!loading" class="table-responsive">
      <table class="table table-hover align-middle mb-2 text-center">
        <thead class="table-secondary">
          <tr>
            <td>MRV#</td>
            <td>Requested by</td>
            <td>Department</td>
            <td>Approved By</td>
            <td>Status</td>
            <td>Action</td>
          </tr>
        </thead>
        <tbody v-if="request.length > 0">
          <tr v-for="mrv in request" :key="mrv.mrv_id" :class="{
            'table-success': mrv.status?.toLowerCase() === 'approved',
            'table-danger': mrv.status?.toLowerCase() === 'pending'}">
            <td>{{ mrv.mrv_number }}</td>
            <td>{{ mrv.requested_by }}</td>
            <td>{{ mrv.department }}</td>
            <td>{{ mrv.approved_by }}</td>
            <td>{{ mrv.status }}</td>
            <td>
              <div @click="toggleDropdown(mrv.mrv_id, $event)" class="cursor-pointer">
                <i class="bi bi-three-dots"></i>
              </div>

              <teleport to="body">
                <transition name="fade">
                  <div v-if="activeDropdown === mrv.mrv_id" ref="el => (dropdownRefs.value ??= {})[mrv.mrv_id] = el"
                    class="dropdown-menu-teleport" :style="getDropdownStyle(mrv.mrv_id)" @click.stop>
                    <a href="#" v-if="canEditMVR" class="text-success" @click.stop.prevent>
                      <i class="bi bi-pencil me-2"></i>Edit
                    </a>
                    <a href="#" v-if="canDeleteMVR" class="text-danger" @click.stop.prevent>
                      <i class="bi bi-trash me-2"></i>Delete
                    </a>
                  </div>
                </transition>
              </teleport>
            </td>
          </tr>

        </tbody>
        <tbody v-else>
          <tr>
            <td colspan="6">No MRV found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav v-if="meta && meta.last_page > 1" class="mt-3">
      <ul class="pagination justify-content-end">
        <li class="page-item" :class="{ disabled: meta.current_page === 1 }" @click="changePage(meta.current_page - 1)">
          <a class="page-link rounded-pill px-3" href="#">Previous</a>
        </li>

        <li v-for="page in meta.last_page" :key="page" class="page-item" :class="{ active: page === meta.current_page }"
          @click="changePage(page)">
          <a class="page-link page-circle" href="#">{{ page }}</a>
        </li>

        <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }"
          @click="changePage(meta.current_page + 1)">
          <a class="page-link rounded-pill px-3" href="#">Next</a>
        </li>
      </ul>
    </nav>

    <!-- Modals -->
    <AddMrvModal v-if="showAddMrvModal" @close="closeAddMrvModal()" />
    <EditMrvModal v-if="showEditMrvModal" @close="closeEditMrvModal()" />
    <DeleteMrvModal v-if="showDeleteMrvModal" @close="closeDeleteMrvModal()" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch, onBeforeUnmount } from "vue";
import { userStore } from "../../stores/userStore";
import AddMrvModal from "../../components/MRV/AddMrvModal.vue";
import EditMrvModal from "../../components/MRV/EditMrvModal.vue";
import DeleteMrvModal from "../../components/MRV/DeleteMvrModal.vue";
import api from "../../services/api";

// State
const searchQuery = ref("");
const request = ref([]);
const loading = ref(true);
const error = ref(null);
const meta = ref({});
const currentPage = ref(1);
const perPage = 30;

// Modals
const showAddMrvModal = ref(false);
const showEditMrvModal = ref(false);
const showDeleteMrvModal = ref(false);

// Permissions
const canAddMVR = computed(() => userStore.canAddMRV);
const canEditMVR = computed(() => userStore.canEditMRV);
const canDeleteMVR = computed(() => userStore.canDeleteMRV);

// Dropdown
const activeDropdown = ref(null);
const dropdownPositions = ref({});
const dropdownRefs = ref({});

// Dropdown toggle
function toggleDropdown(id, event) {
  event.stopPropagation();
  activeDropdown.value = activeDropdown.value === id ? null : id;
  nextTick(() => {
    const rect = event.currentTarget.getBoundingClientRect();
    dropdownPositions.value[id] = { top: rect.bottom + window.scrollY, left: rect.left + window.scrollX };
  });
}

// Close dropdown on outside click
function handleClickOutside(event) {
  if (!activeDropdown.value) return;
  const dropdownEl = dropdownRefs.value?.[activeDropdown.value];
  if (!dropdownEl || !dropdownEl.contains(event.target)) activeDropdown.value = null;
}

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
  fetchMrv();
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
});

// Close dropdown when modal opens
watch([showEditMrvModal, showDeleteMrvModal], ([editVal, deleteVal]) => {
  if (editVal || deleteVal) activeDropdown.value = null;
});

// Dropdown position style
function getDropdownStyle(id) {
  const pos = dropdownPositions.value[id] || { top: 0, left: 0 };
  const dropdownWidth = 180;
  let left = pos.left;
  if (pos.left + dropdownWidth > window.innerWidth) left = pos.left - dropdownWidth + 24;
  return {
    position: "absolute",
    top: pos.top + "px",
    left: left + "px",
    zIndex: 3000,
    backgroundColor: "white",
    border: "1px solid #ddd",
    borderRadius: "4px",
    minWidth: dropdownWidth + "px",
    boxShadow: "0 2px 8px rgba(0,0,0,0.15)"
  };
}

// Fetch MRV data
async function fetchMrv() {
  loading.value = true;
  error.value = null;
  try {
    const res = await api.get("Mrv/MrvDisplay", {
      params: {
        query: searchQuery.value,
        page: currentPage.value,
        per_page: perPage
      }
    });
    request.value = res.data.data;
    meta.value = res.data.meta;
  } catch (err) {
    error.value = err.response?.data?.message || err.message;
  } finally {
    loading.value = false;
  }
}

// Search handlers
function handleSearchEnter() {
  if (!searchQuery.value.trim()) {
    showError("Please enter a search term!");
    return; // Stop search
  }

  error.value = "";
  currentPage.value = 1;
  fetchMrv();
}

function showError(message) {
  error.value = message;
  setTimeout(() => {
    error.value = "";
  }, 5000);
}


// Pagination
function changePage(page) {
  if (page < 1 || page > meta.value.last_page) return;
  currentPage.value = page;
  fetchMrv();
}

// Placeholder modal functions
function addMrv() {
  showAddMrvModal.value = true;
}
function closeAddMrvModal() {
  showAddMrvModal.value = false;
}
function closeEditMrvModal() {
  showEditMrvModal.value = false;
}
function closeDeleteMrvModal() {
  showDeleteMrvModal.value = false;
}



function clearSearch() {
  searchQuery.value = "";
  fetchMrv(); // reload table / reset results

}

</script>

<style>
.cursor-pointer {
  cursor: pointer;
}

.dropdown-menu-teleport {
  transition: opacity 0.2s;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
