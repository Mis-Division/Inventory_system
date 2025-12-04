<template>
  <div class="main-container" >

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-box-seam text-primary"></i> Stocks</h2>
      <input v-model="search" type="text" class="form-control w-25" placeholder="Search Stocks..." />
    </div>

    <!-- ===== TOP CARDS ===== -->
    <div class="row g-3 mb-4">

      <!-- No Stocks (Red) -->
      <div class="col-md-3">
        <div class="status-card bg-danger">
          <div>
            <h6 class="label-text">No Stocks</h6>
            <h3 class="value-text">{{ zeroStocks }}</h3>
          </div>
          <i class="bi bi-exclamation-circle status-icon"></i>
        </div>
      </div>

      <!-- Low Stocks (Yellow) -->
      <div class="col-md-3">
        <div class="status-card bg-warning">
          <div>
            <h6 class="label-text">Low Stocks</h6>
            <h3 class="value-text">{{ lowStockCount }}</h3>
          </div>
          <i class="bi bi-exclamation-triangle-fill status-icon"></i>
        </div>
      </div>

      <!-- Total Stocks (Green) -->
      <div class="col-md-3">
        <div class="status-card bg-success">
          <div>
            <h6 class="label-text">Total Stocks</h6>
            <h3 class="value-text">{{ totalStocks }}</h3>
          </div>
          <i class="bi bi-box status-icon"></i>
        </div>
      </div>

      <!-- Total Items (Blue) -->
      <div class="col-md-3">
        <div class="status-card bg-primary">
          <div>
            <h6 class="label-text">Total Items</h6>
            <h3 class="value-text">{{ totalItems }}</h3>
          </div>
          <i class="bi bi-list-ul status-icon"></i>
        </div>
      </div>

    </div>


    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3">
      <li class="nav-item" v-for="tab in tabs" :key="tab.key">
        <a href="#" class="nav-link" :class="{ active: activeTab === tab.key }" @click.prevent="setActiveTab(tab.key)">
          {{ tab.label }}
        </a>
      </li>
    </ul>

    <!-- Loading -->
    <div v-if="loading" class="text-center my-3">
      Loading {{ currentTabLabel }} stocks...
    </div>

    <!-- Empty -->
    <div v-else-if="filteredStocks.length === 0" class="text-center my-3 text-muted">
      No stocks found for {{ currentTabLabel }}
    </div>

    <!-- TABLE -->
    <div v-else class="table-responsive">
      <table class="table table-hover table-striped align-middle text-center">
        <thead class="table-secondary">
          <tr>
            <th style="width: 15%;">Item Code</th>
            <th style="width: 25%;">Product Name</th>
            <th style="width: 30%;">Description</th>
            <th style="width: 15%;">Accounting Code</th>
            <th style="width: 10%;">Qty Stock</th>
            <th style="width: 10%;">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="stock in paginatedStocks" :key="stock.ItemCode_id">
            <td>{{ stock.ItemCode }}</td>
            <td>{{ stock.product_name }}</td>
            <td>{{ stock.description || '-' }}</td>
            <td>{{ stock.accounting_code }}</td>
            <td>{{ stock.Current_Stock }}</td>
            <td>
              <div @click="toggleDropdown(stock.ItemCode_id, $event)" class="cursor-pointer">
                <i class="bi bi-three-dots"></i>
              </div>

              <teleport to="body">
                <Transition name="fade">
                  <div v-if="activeDropdown === stock.ItemCode_id"
                    ref="el => (dropdownRefs.value ??= {})[stock.ItemCode_id] = el" class="dropdown-menu-teleport"
                    :style="getDropdownStyle(stock.ItemCode_id)" @click.stop>
                    <a href="#" @click.stop.prevent="viewLedger(stock)" class="text-warning">
                      <i class="bi bi-eye me-2"></i>View Ledger
                    </a>
                  </div>
                </Transition>
              </teleport>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <div v-if="filteredStocks.length > 0" class="d-flex justify-content-end mt-3">
      <button class="btn btn-sm btn-outline-primary me-1" :disabled="currentPage[activeTab] === 1"
        @click="changePage(currentPage[activeTab] - 1)">
        Previous
      </button>

      <button v-for="page in lastPage[activeTab]" :key="page" @click="changePage(page)" class="btn btn-sm me-1"
        :class="currentPage[activeTab] === page ? 'btn-primary rounded-circle' : 'btn-outline-primary rounded-circle'"
        style="width: 35px; height: 35px; padding: 0;">
        {{ page }}
      </button>

      <button class="btn btn-sm btn-outline-primary" :disabled="currentPage[activeTab] === lastPage[activeTab]"
        @click="changePage(currentPage[activeTab] + 1)">
        Next
      </button>
    </div>

  </div>

  <Ledger v-if="showLedger" :stock="selectedStock" @close="closeLedger" />
</template>

<script setup>
import { computed, onMounted, ref, nextTick, watch, onBeforeUnmount } from 'vue';
import api from '../../services/api';
import { userStore } from '../../stores/userStore';
import Ledger from "../../components/Stocks/ViewLedger.vue";

// ----- Tabs -----
const tabs = [
  { key: 'Line_Hardware', label: 'Line Hardware', backendValue: 'Line Hardware' },
  { key: 'Special_Hardware', label: 'Special Hardware', backendValue: 'Special Hardware' },
  { key: 'Others', label: 'Others', backendValue: 'Others' },
];

const activeTab = ref('Line_Hardware');
const stocks = ref([]);
const search = ref('');
const loading = ref(false);
const showLedger = ref(false);
const selectedStock = ref(null);

// ----- Dropdown -----
const activeDropdown = ref(null);
const dropdownPositions = ref({});
const dropdownRefs = ref({});

function toggleDropdown(id, event) {
  event.stopPropagation();
  activeDropdown.value = activeDropdown.value === id ? null : id;

  nextTick(() => {
    const rect = event.currentTarget.getBoundingClientRect();
    dropdownPositions.value[id] = { top: rect.bottom + window.scrollY, left: rect.left + window.scrollX };
  });
}

function getDropdownStyle(id) {
  const pos = dropdownPositions.value[id] || { top: 0, left: 0 };
  const dropdownWidth = 180;
  let left = pos.left;
  if (pos.left + dropdownWidth > window.innerWidth) left = Math.max(0, pos.left - dropdownWidth + 24);
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

function handleClickOutside(event) {
  if (!activeDropdown.value) return;
  const dropdownEl = dropdownRefs.value?.[activeDropdown.value];
  if (!dropdownEl || !dropdownEl.contains(event.target)) activeDropdown.value = null;
}

// ----- Permissions -----
const canEditStocks = computed(() => userStore.canEditStocks);
const canDeleteStocks = computed(() => userStore.canDeleteStocks);

// ----- Pagination -----
const currentPage = ref({ Line_Hardware: 1, Special_Hardware: 1, Others: 1 });
const perPage = 10;
const lastPage = ref({ Line_Hardware: 1, Special_Hardware: 1, Others: 1 });

// ----- Computed -----
const currentTabLabel = computed(() => tabs.find(t => t.key === activeTab.value)?.label);

const filteredStocks = computed(() => {
  const tabVal = tabs.find(t => t.key === activeTab.value).backendValue;
  return stocks.value.filter(
    s =>
      s.product_type === tabVal &&
      (!search.value || s.product_name.toLowerCase().includes(search.value.toLowerCase()))
  );
});

const paginatedStocks = computed(() => {
  const page = currentPage.value[activeTab.value];
  const start = (page - 1) * perPage;
  return filteredStocks.value.slice(start, start + perPage);
});

// ----- Methods -----
function setActiveTab(tabKey) {
  activeTab.value = tabKey;
  currentPage.value[tabKey] = 1;
}

function changePage(page) {
  if (page >= 1 && page <= lastPage.value[activeTab.value]) {
    currentPage.value[activeTab.value] = page;
  }
}

function closeLedger() {
  showLedger.value = false;
  selectedStock.value = null;
}

async function viewLedger(stock) {
  try {
    const res = await api.get(`/Products/list/${stock.ItemCode_id}`);
    if (res?.data?.data) {
      selectedStock.value = res.data.data || []; // ensure always array
      await nextTick();
      showLedger.value = true;
    } else {
      selectedStock.value = []; // fallback
      alert('Failed to fetch stock details.');
    }
  } catch (err) {
    console.error(err);
    selectedStock.value = []; // fallback
    alert('An error occurred while fetching stock details.');
  }
}



// ----- Fetch Stocks -----
const fetchStocks = async () => {
  try {
    loading.value = true;
    const res = await api.get('/Products/grouped', { params: { search: search.value } });
    stocks.value = res.data.data.stocks || [];

    // Update lastPage per tab
    tabs.forEach(tab => {
      const total = stocks.value.filter(s => s.product_type === tab.backendValue).length;
      lastPage.value[tab.key] = Math.ceil(total / perPage) || 1;
    });
  } catch (err) {
    console.error(err);
    stocks.value = [];
  } finally {
    loading.value = false;
  }
};

// ----- Watchers -----
watch(search, () => {
  currentPage.value[activeTab.value] = 1;
});

watch([showLedger], ([ledgerVisible]) => {
  if (ledgerVisible) activeDropdown.value = null;
});

// ----- Lifecycle -----
onMounted(() => {
  document.addEventListener("click", handleClickOutside);
  fetchStocks();
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
});



const zeroStocks = computed(() =>
  filteredStocks.value.filter(s => Number(s.Current_Stock) === 0).length
);

const totalStocks = computed(() =>
  filteredStocks.value.reduce((sum, s) => sum + Number(s.Current_Stock), 0)
);

const totalItems = computed(() => filteredStocks.value.length);

const lowStockCount = computed(() => {
  const list = filteredStocks.value; // ito na yung per-tab filtered mo
  return list.filter(s => Number(s.Current_Stock) <= 10).length;
});

</script>


<style scoped>
.table-responsive {
  overflow-x: auto;
}

.nav-tabs .nav-link {
  cursor: pointer;

}

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

.status-card:hover {
    transform: translateY(-5px);
}

.label-text {
  font-size: 0.85rem;
  font-weight: 600;
  opacity: 0.9;
}

.value-text {
  font-weight: 700;
  font-size: 2rem;
  margin: 0;
}

.status-icon {
  font-size: 3rem;
  opacity: 0.3;
}


.rounded-circle {
  display: inline-flex;
  justify-content: center;
  align-items: center;
}
</style>