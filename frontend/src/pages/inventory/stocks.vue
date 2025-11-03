<template>
  <div class="page-container">
    <div class="custom-headers">
      <h1 class="mb-3">Stock Management</h1>

      <div class="custom-actions">
        <input type="text" class="form-control" v-model="search" placeholder="Search Stocks..." />
        <button class="btn btn-primary" :disabled="!canAddStocks" @click="AddStocks">
          <i class="bi bi-plus-circle me-1"></i> Add Stocks
        </button>
      </div>
    </div>
    <!-- Tabs -->
    <div class="tabs">
      <button v-for="tab in tabs" :key="tab" @click="setActiveTab(tab)"
        :class="['tab-button', activeTab === tab ? 'tab-active' : '']">
        {{ tab.replace('_', ' ') }}
      </button>
    </div>



    <!-- Loading -->
    <div v-if="loading" class="loading-text">
      Loading {{ activeTab.replace('_', ' ') }} stocks...
    </div>

    <!-- Empty -->
    <div v-else-if="stocks.length === 0" class="empty-text">
      No stocks found for {{ activeTab.replace('_', ' ') }}
    </div>

    <!-- Table -->
    <table v-else class="table-container">
      <thead>
        <tr>
          <th>Item Code</th>
          <th>Product Name</th>
          <th>Description</th>
          <th>Quantity Onhand</th>
          <th>Quantity In Stock</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="stock in stocks" :key="stock.id">
          <td>{{ stock.item_code }}</td>
          <td>{{ stock.product_name }}</td>
          <td>{{ stock.descriptions || '-' }}</td>
          <td>{{ stock.quantity_onhand }}</td>
          <td>{{ stock.quantity_in_stock }}</td>
          <td>
            <button :disabled="!canEditStocks" @click="EditStocks(stock)" class="btn btn-warning" title="Edit">
              <i class="bi bi-pencil"></i>
            </button>
            |
            <button :disabled="!canDeleteStocks" @click="deleteStocks(stock)" class="btn btn-danger" title="Delete">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Pagination -->
    <div v-if="pagination.total > 0" class="pagination">
      <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
        class="pagination-button">
        Previous
      </button>

      <div>
        <button v-for="page in pagination.last_page" :key="page" @click="changePage(page)"
          :class="['page-number', pagination.current_page === page ? 'page-active' : '']">
          {{ page }}
        </button>
      </div>

      <button @click="changePage(pagination.current_page + 1)"
        :disabled="pagination.current_page === pagination.last_page" class="pagination-button">
        Next
      </button>
    </div>
  </div>
  <AddStock v-if="showAddStocks" @close="closeAddStocks" />
  <UpdateStock v-if="showUpdateStocks && selectedStock" :stock="selectedStock"  @close="closeUpdateStocks" @updated="fetchStocks" />
</template>


<script setup>
import { ref, onMounted, watch, computed, nextTick } from 'vue'
import api from '../../services/api'
import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";
import AddStock from "../../components/Stocks/AddStocks.vue";
import UpdateStock from "../../components/Stocks/UpdateStocks.vue";

const appStore = useAppStore();

const canDeleteStocks = computed(() => userStore.canDeleteStocks);
const canEditStocks = computed(() => userStore.canEditStocks);
const canAddStocks = computed(() => userStore.canAddStocks);

const showAddStocks = ref(false);
const showUpdateStocks = ref(false);
const selectedStock = ref(null); // ✅ renamed for clarity

// ✅ Add Stocks
async function AddStocks() {
  appStore.showLoading();
  await new Promise((resolve) => setTimeout(resolve, 400));
  showAddStocks.value = true;
  appStore.hideLoading();
}

// ✅ Edit Stocks by ID
async function EditStocks(stock) {
  try {
    appStore.showLoading();
    const res = await api.get(`/stocks/getStocks/${stock.id}`);
    if (res?.data.data) {
      selectedStock.value = res.data.data;
      await nextTick();
      showUpdateStocks.value = true; // ✅ fixed
    } else {
      console.error("No stock data returned from API:", res.data);
    }
  } catch (err) {
    console.error("Failed to fetch stock info:", err); // ✅ fixed
  } finally {
    appStore.hideLoading(); // ✅ fixed
  }
}

// ✅ Close Add Modal
async function closeAddStocks() {
  showAddStocks.value = false;
  fetchStocks();
}

// ✅ Close Update Modal
async function closeUpdateStocks() {
  showUpdateStocks.value = false;
  fetchStocks();
}

// Tabs
const tabs = ['Line_Hardware', 'Special_Hardware', 'Others']
const activeTab = ref('Line_Hardware')
const stocks = ref([])
const pagination = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
})
const loading = ref(false)
const search = ref('')
let debounceTimeout = null

// ✅ Change Active Tab
const setActiveTab = (tab) => {
  activeTab.value = tab
  pagination.value.current_page = 1
  fetchStocks()
}

// ✅ Search Debounce
watch(search, (newValue) => {
  clearTimeout(debounceTimeout)
  debounceTimeout = setTimeout(() => {
    pagination.value.current_page = 1
    fetchStocks()
  }, 500)
})

// ✅ Pagination
const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    pagination.value.current_page = page
    fetchStocks()
  }
}

// ✅ Fetch Stocks
async function fetchStocks() {
  try {
    loading.value = true
    const res = await api.get('/stocks/getStocks', {
      params: {
        product_type: activeTab.value,
        search: search.value,
        page: pagination.value.current_page,
        per_page: pagination.value.per_page,
      },
    })

    const data = res.data.data
    stocks.value = data.stocks || []
    pagination.value = data.pagination || pagination.value
  } catch (err) {
    console.error('Error fetching stocks:', err)
    stocks.value = []
  } finally {
    loading.value = false
  }
}

// ✅ Initial load
onMounted(() => {
  fetchStocks()
})
</script>

