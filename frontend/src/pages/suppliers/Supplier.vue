<template>
  <div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
      <h1 class="mb-3">Supplier List</h1>
      <div class="d-flex gap-2 flex-wrap mt-2 mt-md-0">
        <input type="text" class="form-control" placeholder="Search Supplier..." style="max-width: 200px;" />
        <button :disabled="!canAddSupplier" @click="addSupply" class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i> Add
        </button>
      </div>
    </div>

    <!-- Loading Indicator -->
    <div v-if="loading" class="text-center">Loading suppliers...</div>

    <!-- Error Message -->
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- Table -->
    <div class="table-wrapper">
      <table class="table table-hover table-bordered align-middle mb-2 table-striped"
        style="font-size: 20px; margin-top: 10px;">
        <thead class="table-secondary text-center">
          <tr>
            <th style="width: 5%;">ID</th>
            <th style="width: 30%;">Supplier Name</th>
            <th style="width: 15%;">Contact Person</th>
            <th style="width: 15%;">Phone Number</th>
            <th style="width: 25%;">Email</th>
            <th style="width: 9%;">Action</th>
          </tr>
        </thead>
        <tbody v-if="!loading && suppliers.length">
          <tr class="text-center" v-for="supplier in suppliers" :key="supplier.supplier_id">
            <td>{{ supplier.supplier_id }}</td>
            <td>{{ supplier.supplier_name }}</td>
            <td>{{ supplier.contact_person }}</td>
            <td>{{ supplier.phone }}</td>
            <td>{{ supplier.email }}</td>
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
        <tbody v-else-if="!loading && !suppliers.length">
          <tr>
            <td colspan="6" class="text-center py-3 text-muted">
              No suppliers found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <AddSupplier v-if="showAddSupply" @close="closeAddSupply" />
  <UpdateSupplier v-if="showSupplierInfo" :supplier="selectedSupplier" @close="showSupplierInfo = false"
    @updated="fetchSuppliers" />
  <DeleteSupplier v-if="deleteSupplier" :supplier="selectedSupplier" @close="deleteSupplier = false"
    @updated="fetchSuppliers" />

</template>

<script setup>
import { computed, ref, onMounted } from "vue";
import api from "../../services/api";
import { userStore } from "../../stores/userStore";
import { useAppStore } from "../../stores/appStore";
import "../../assets/css/Global.css";
import AddSupplier from "../../components/Supplier/AddSupplier.vue";
import UpdateSupplier from "../../components/Supplier/UpdateSupplier.vue";
import DeleteSupplier from "../../components/Supplier/DeleteSupplier.vue";

const suppliers = ref([]);
const loading = ref(true);
const error = ref(null);
const appStore = useAppStore();

//modal
const showSupplierInfo = ref(false);
const deleteSupplier = ref(false);
const showAddSupply = ref(false);
const selectedSupplier = ref(null);

// ✅ Use consistent naming
const canAddSupplier = computed(() => userStore.canAddSupplier);
const canEditSupplier = computed(() => userStore.canEditSupplier);
const canDeleteSupplier = computed(() => userStore.canDeleteSupplier);

//show modal sir for addSupply

async function addSupply() {
  try {
    appStore.showLoading();
    await new Promise(resolve => setTimeout(resolve, 500));
    showAddSupply.value = true;
  } catch (err) {
    console.error("show preparing modal:", err);
  } finally {
    appStore.hideLoading();
  }
}
async function closeAddSupply() {
  showAddSupply.value = false;
  fetchSuppliers();
}
// show modal Sir for update supplier
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

//
async function DeleteSupplierInfo(supplier) {
  try {
    appStore.showLoading();
    const res = await api.get(`/suppliers/get_supplier/${supplier.supplier_id}`);
    selectedSupplier.value = res.data.supplier;
    deleteSupplier.value = true;
  } catch (err) {
    console.error("Failed to fetch supplier for deletion:", err);
  } finally {
    appStore.hideLoading();
  }
}

const fetchSuppliers = async () => {
  try {
    // ✅ Axios syntax (no need for response.ok or .json())
    const response = await api.get("/suppliers/get_supplier");
    suppliers.value = response.data.suppliers;
  } catch (err) {
    error.value = err.response?.data?.message || err.message;
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchSuppliers();
});
</script>
