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
      <table v-if="!loading && suppliers.length"
        class="table table-hover table-bordered align-middle mb-2 table-striped"
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
        <tbody>
          <tr class="text-center" v-for="supplier in suppliers" :key="supplier.supplier_id">
            <td>{{ supplier.supplier_id }}</td>
            <td>{{ supplier.supplier_name }}</td>
            <td>{{ supplier.contact_person }}</td>
            <td>{{ supplier.phone }}</td>
            <td>{{ supplier.email }}</td>
            <td>
              <button :disabled="!canEditSupplier" class="btn btn-warning" title="Edit Supplier">
                <i class="bi bi-pencil"></i>
              </button>
              |
              <button :disabled="!canDeleteSupplier" class="btn btn-danger" title="Delete Supplier">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="!loading && suppliers.length === 0" class="text-center">
        No suppliers found.
      </div>
    </div>
  </div>
  <AddSupplier v-if="showAddSupply" @close="closeAddSupply" />

</template>

<script setup>
import { computed, ref, onMounted } from "vue";
import api from "../../services/api";
import { userStore } from "../../stores/userStore";
import { useAppStore } from "../../stores/appStore";
import "../../assets/css/Global.css";
import AddSupplier from "../../components/Supplier/AddSupplier.vue";


const suppliers = ref([]);
const loading = ref(true);
const error = ref(null);
const appStore = useAppStore();

//modal

const showAddSupply = ref(false);

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
