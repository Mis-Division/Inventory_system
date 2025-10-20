<template>
  <div class="main-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
      <h1 class="mb-3">Supplier Management</h1>

      <div class="d-flex align-items-center gap-2" style="white-space: nowrap;">
        <input v-model="searchQuery" @input="fetchSuppliers" type="text" class="form-control"
          placeholder="Search Supplier..." style="width: 500px; flex: 0 0 auto;" />
        <button :disabled="!canAddSupplier" @click="addSupply" class="btn btn-primary">
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
      <table class="table table-hover table-bordered align-middle mb-2  text-center">
        <thead class="table-secondary">
          <tr>
            <th style="width: 5%;">ID</th>
            <th style="width: 10%;">Supplier #</th>
            <th style="width: 20%;">Supplier Name</th>
            <th style="width: 15%;">Contact #</th>
            <th style="width: 15%;">TIN</th>
            <th style="width: 15%;">VAT/NVAT</th>
            <th style="width: 15%;">Action</th>
          </tr>
        </thead>

        <tbody v-if="suppliers.length > 0">
          <tr v-for="supplier in suppliers" :key="supplier.supplier_id">
            <td>{{ supplier.supplier_id }}</td>
            <td>{{ supplier.supplier_no }}</td>
            <td>{{ supplier.supplier_name }}</td>
            <td>{{ supplier.contact_no }}</td>
            <td>{{ supplier.tin }}</td>
            <td>{{ supplier.vat_no }}</td>
            <td>
              <button :disabled="!canEditSupplier" @click="UpdateSupplierInfo(supplier)" class="btn btn-warning"
                title="Edit Supplier">
                <i class="bi bi-pencil"></i>
              </button>
              |
              <button :disabled="!canDeleteSupplier" @click="DeleteSupplierInfo(supplier)" class="btn btn-danger"
                title="Delete Supplier">
                <i class="bi bi-trash"></i>
              </button>
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
      <ul class="pagination justify-content-center">
        <li class="page-item" :class="{ disabled: meta.current_page === 1 }" @click="changePage(meta.current_page - 1)">
          <a class="page-link">Previous</a>
        </li>

        <li v-for="page in meta.last_page" :key="page" class="page-item" :class="{ active: page === meta.current_page }"
          @click="changePage(page)">
          <a class="page-link">{{ page }}</a>
        </li>

        <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }"
          @click="changePage(meta.current_page + 1)">
          <a class="page-link">Next</a>
        </li>
      </ul>
    </nav>
  </div>

  <!-- Modals -->
  <AddSupplier v-if="showAddSupply" @close="closeAddSupply" />
  <UpdateSupplier v-if="showSupplierInfo" :supplier="selectedSupplier" @close="showSupplierInfo = false"
    @updated="fetchSuppliers" />
  <DeleteSupplier v-if="showDeleteSupplier" :supplier="selectedSupplier" @close="closeDeleteSupplier"
    @deleted="onDeletedSupplier" />
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
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
    selectedSupplier.value = res.data.supplier;
    showSupplierInfo.value = true;
  } catch (err) {
    console.error("Failed to fetch supplier info:", err);
  } finally {
    appStore.hideLoading();
  }
}

async function DeleteSupplierInfo(supplier) {
  try {
    appStore.showLoading();
    const res = await api.get(`/suppliers/get_supplier/${supplier.supplier_id}`);
    if(res?.data?.supplier){
      selectedSupplier.value = res.data.supplier;
      showDeleteSupplier.value = true;
    }else{
      console.error("No Supplier data return from API ", res.supplier);
    }
  } catch (err) {
    console.error("Failed to fetch supplier for deletion:", err);
  } finally {
    appStore.hideLoading();
  }
}
function closeDeleteSupplier(){
  showDeleteSupplier.value = false;
  selectedSupplier.value = null;
}
async function onDeletedSupplier() {
  showDeleteSupplier.value = false;
  selectedSupplier.value = null;
  await fetchSuppliers();
}

onMounted(() => {
  fetchSuppliers();
});
</script>

<style scoped>
.table th,
.table td {
  vertical-align: middle;
}
</style>
