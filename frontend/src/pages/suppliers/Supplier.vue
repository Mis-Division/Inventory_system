<template>
  <div class="main-container">
    <!-- Header -->
    <div class="custom-headers">
      <div class="w-100">
        <h1 class="m-0 mb-3">
          <i class="bi bi-info-circle text-primary"></i> Supplier
        </h1>
      </div>

      <div class="d-flex justify-content-between align-items-center w-100">
        <div class="position-relative" style="width: 35%; min-width: 300px;">
          <input type="text" v-model="searchQuery" @keyup.enter="handleSearchEnter" class="form-control pe-5"
            placeholder="Search Supplier..." style="background-color: #FCF6D9;" />

          <!-- Clear Button (X) -->
          <i v-if="searchQuery" class="bi bi-x-circle-fill text-muted" @click="clearSearch"
            style="position: absolute;right: 10px;top: 50%; transform: translateY(-50%);cursor: pointer; font-size: 1.2rem;"></i>
        </div>


        <button v-if="canAddSupplier" @click="addSupply" class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i> Supplier
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-4 text-muted">Loading suppliers...</div>

    <!-- Error -->
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- Supplier Table -->
    <div v-if="!loading" class="table-responsive">
      <table class="table table-hover  align-middle mb-2  text-center">
        <thead class="table-secondary">
          <tr>
            <th style="width: 10%;">Supplier #</th>
            <th style="width: 40%;">Supplier Name</th>
            <th style="width: 15%;">Contact #</th>
            <th style="width: 15%;">TIN</th>
            <th style="width: 15%;">VAT/NVAT</th>
            <th style="width: 5%;">Action</th>
          </tr>
        </thead>

        <tbody v-if="suppliers.length > 0">
          <tr v-for="supplier in suppliers" :key="supplier.supplier_id">
            <td>{{ supplier.supplier_no }}</td>
            <td>{{ supplier.supplier_name }}</td>
            <td>{{ supplier.contact_no }}</td>
            <td>{{ supplier.tin }}</td>
            <td>{{ supplier.vat_no }}</td>
            <td>
              <!-- Dropdown toggle -->
              <div @click="toggleDropdown(supplier.supplier_id, $event)" class="cursor-pointer">
                <i class="bi bi-three-dots"></i>
              </div>

              <!-- Teleport + Transition -->
              <teleport to="body">
                <Transition name="fade">
                  <div v-if="activeDropdown === supplier.supplier_id"
                    ref="el => (dropdownRefs.value ??= {})[supplier.supplier_id] = el" class="dropdown-menu-teleport"
                    :style="getDropdownStyle(supplier.supplier_id)" @click.stop>
                    <a href="#" @click.stop.prevent="UpdateSupplierInfo(supplier)" v-if="canEditSupplier"
                      class="text-success"><i class="bi bi-pencil me-2"></i>Edit</a>
                    <a href="#" @click.stop.prevent="DeleteSupplierInfo(supplier)" class="text-danger"
                      v-if="canDeleteSupplier">
                      <i class="bi bi-trash me-2"></i>
                      Delete</a>
                  </div>
                </Transition>
              </teleport>
            </td>
          </tr>
        </tbody>

        <tbody v-else>
          <tr>
            <td colspan="7" class="text-center py-3 text-muted">No suppliers found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav v-if="meta && meta.last_page > 1" class="mt-3">
      <ul class="pagination justify-content-end">
        <!-- Previous Button -->
        <li class="page-item" :class="{ disabled: meta.current_page === 1 }" @click="changePage(meta.current_page - 1)">
          <a class="page-link rounded-pill px-3" href="#">Previous</a>
        </li>

        <!-- Page Numbers -->
        <li v-for="page in meta.last_page" :key="page" class="page-item" :class="{ active: page === meta.current_page }"
          @click="changePage(page)">
          <a class="page-link page-circle" href="#">{{ page }}</a>
        </li>

        <!-- Next Button -->
        <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }"
          @click="changePage(meta.current_page + 1)">
          <a class="page-link rounded-pill px-3" href="#">Next</a>
        </li>
      </ul>
    </nav>
  </div>

  <!-- Modals -->
  <AddSupplier v-if="showAddSupply" @close="closeAddSupply" />
  <UpdateSupplier v-if="showSupplierInfo && selectedSupplier" :supplier="selectedSupplier"
    @close="showSupplierInfo = false" @updated="fetchSuppliers" />
  <DeleteSupplier v-if="showDeleteSupplier" :supplier="selectedSupplier" @close="closeDeleteSupplier"
    @deleted="onDeletedSupplier" />
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick, onBeforeUnmount } from "vue";
import api from "../../services/api";
import { userStore } from "../../stores/userStore";
import { useAppStore } from "../../stores/appStore";
import AddSupplier from "../../components/Supplier/AddSupplier.vue";
import UpdateSupplier from "../../components/Supplier/UpdateSupplier.vue";
import DeleteSupplier from "../../components/Supplier/DeleteSupplier.vue";

const suppliers = ref([]);
const loading = ref(true);
const error = ref(null);
const searchQuery = ref("");
const meta = ref({});
const currentPage = ref(1);
const perPage = 10;

const showAddSupply = ref(false);
const showSupplierInfo = ref(false);
const showDeleteSupplier = ref(false);
const selectedSupplier = ref(null);

const appStore = useAppStore();

// Permissions
const canAddSupplier = computed(() => userStore.canAddSupplier);
const canEditSupplier = computed(() => userStore.canEditSupplier);
const canDeleteSupplier = computed(() => userStore.canDeleteSupplier);

//Drop down Setting dito
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

// Click outside closes dropdown
function handleClickOutside(event) {
  if (!activeDropdown.value) return;
  const dropdownEl = dropdownRefs.value?.[activeDropdown.value];
  if (!dropdownEl || !dropdownEl.contains(event.target)) activeDropdown.value = null;
}

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
  fetchSuppliers();
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
});


// Auto-close dropdown when modal opens
watch([showSupplierInfo, showDeleteSupplier], ([updateVal, deleteVal]) => {
  if (updateVal || deleteVal) activeDropdown.value = null;
});

// Dropdown style
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

// end ng drop down settings

// Fetch suppliers with API search + pagination
const fetchSuppliers = async () => {
  loading.value = true;
  try {
    const response = await api.get("/suppliers/get_supplier", {
      params: {
        search: searchQuery.value,
        page: currentPage.value,
        per_page: perPage,
      },
    });

    suppliers.value = response.data.data;
    meta.value = response.data.meta;
  } catch (err) {
    error.value = err.response?.data?.message || err.message;
  } finally {
    loading.value = false;
  }
};

// Pagination handler
function changePage(page) {
  if (page < 1 || page > meta.value.last_page) return;
  currentPage.value = page;
  fetchSuppliers();
}

// Modal functions
async function addSupply() {
  appStore.showLoading();
  await new Promise((resolve) => setTimeout(resolve, 300));
  showAddSupply.value = true;
  appStore.hideLoading();
}

async function closeAddSupply() {
  showAddSupply.value = false;
  fetchSuppliers();
}

async function UpdateSupplierInfo(supplier) {
  try {
    appStore.showLoading();

    const res = await api.get(`/suppliers/get_supplier/${supplier.supplier_id}`);
    if (res?.data?.data) {
      selectedSupplier.value = res.data.data;
      await nextTick();
      showSupplierInfo.value = true;
    } else {
      console.error("No Supplier Data retrun from APi:", res.data);
    }
  } catch (err) {
    console.error("Failed to fetch Supplier Info", err);
  } finally {
    appStore.hideLoading();
  }
}


async function DeleteSupplierInfo(supplier) {
  try {
    appStore.showLoading();
    const res = await api.get(`/suppliers/get_supplier/${supplier.supplier_id}`);
    if (res?.data?.data) {
      selectedSupplier.value = res.data.data;
      showDeleteSupplier.value = true;
    } else {
      console.error("No Supplier data return from API ", res.supplier);
    }
  } catch (err) {
    console.error("Failed to fetch supplier for deletion:", err);
  } finally {
    appStore.hideLoading();
  }
}
function closeDeleteSupplier() {
  showDeleteSupplier.value = false;
  selectedSupplier.value = null;
}
async function onDeletedSupplier() {
  showDeleteSupplier.value = false;
  selectedSupplier.value = null;
  await fetchSuppliers();
}


// Search handlers
function handleSearchEnter() {
  if (!searchQuery.value.trim()) {
    showErrors("Please enter a search Supplier!");
    return; // Stop search
  }

  error.value = "";
  currentPage.value = 1;
  fetchSuppliers();
}

function showErrors(message) {
  error.value = message;
  setTimeout(() => {
    error.value = "";
  }, 5000);
}
function clearSearch(){
  searchQuery.value = "";
   fetchSuppliers();
}
</script>

<style scoped>
.table th,
.table td {
  vertical-align: middle;
}
</style>
