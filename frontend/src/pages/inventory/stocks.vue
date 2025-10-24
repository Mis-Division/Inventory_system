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
            <button :disabled="!canEditStocks" @click="editStocks(stock)" class="btn btn-warning" title="Edit">
              <i class="bi bi-pencil"></i>
            </button>
            |
            <button :disabled="!canDeleteStocks" @click="deleteStocks(stock)" class="btn btn-danger"
              title="Delete">
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
</template>


<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import api from '../../services/api'
import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";
import AddStock from "../../components/Stocks/AddStocks.vue";

const canDeleteStocks = computed(() => userStore.canDeleteStocks);
const canEditStocks = computed(() => userStore.canEditStocks);
const canAddStocks = computed(() => userStore.canAddStocks);
const appStore = useAppStore();

const showAddStocks = ref(false);


async function AddStocks() {
  appStore.showLoading();
  await new Promise((resolve) => setTimeout(resolve, 600));
  showAddStocks.value = true;
  appStore.hideLoading();
}

async function closeAddStocks(){
  showAddStocks.value = false;
  fetchStocks();
}

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

// Change active tab
const setActiveTab = (tab) => {
  activeTab.value = tab
  pagination.value.current_page = 1
  fetchStocks()
}

// Watch search input
watch(search, (newValue) => {
  clearTimeout(debounceTimeout)
  debounceTimeout = setTimeout(() => {
    pagination.value.current_page = 1
    fetchStocks()
  }, 500)
})

// Change page
const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    pagination.value.current_page = page
    fetchStocks()
  }
}

// Fetch stocks
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

// Initial load
onMounted(() => {
  fetchStocks()
})
</script>
