<template>
  <div class="main-container">
    <!-- Header -->
    <div class="custom-headers">
      <div class="w-100">
        <h1 class="mb-3"><i class="bi bi-info-circle text-primary"></i> Items</h1>
      </div>


      <div class="d-flex justify-content-between align-items-center w-100">
        <div class="d-flex gap-2">
          <input v-model="searchQuery" @keyup.enter="handleSearchEnter" type="text" class="form-control"
            style="width: 35%; min-width: 300px;" placeholder="Search ItemCode and Descriptions..." />
          <button class="btn btn-primary" @click="handleSearchEnter">
            <i class="bi bi-search me-1"></i> Search
          </button>
        </div>
        <button v-if="cadAddCategory" @click="AddItem" class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i> Item Code
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-4 text-muted">
      Loading ItemCode...
    </div>

    <!-- Error -->
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- Item Code Table -->
    <div v-if="!loading" class="table-responsive">
      <table class="table table-hover align-middle mb-2 text-center">
        <thead class="table-secondary">
          <tr>
            <th style="width: 15%;">Item Code</th>
            <th style="width: 30%;">Product Name</th>
            <th style="width: 20%;">Descriptions</th>
            <th style="width: 20%;">Acct. Code</th>
            <th style="width: 10%;">Item Category</th>
            <th style="width: 5%;">Action</th>
          </tr>
        </thead>

        <tbody v-if="Items.length > 0">
          <tr v-for="item in Items" :key="item.ItemCode_id">
            <td>{{ item.ItemCode }}</td>
            <td>{{ item.product_name }}</td>
            <td>{{ item.description }}</td>
            <td>{{ item.accounting_code }}</td>
            <td>{{ item.item_category }}</td>
            <td>
              <!-- Dropdown toggle -->
              <div @click="toggleDropdown(item.ItemCode_id, $event)" class="cursor-pointer">
                <i class="bi bi-three-dots"></i>
              </div>

              <!-- Teleport + Transition -->
              <teleport to="body">
                <Transition name="fade">
                  <div v-if="activeDropdown ===item.ItemCode_id" ref="el => (dropdownRefs.value ??= {})[item.ItemCode_id] = el"
                    class="dropdown-menu-teleport" :style="getDropdownStyle(item.ItemCode_id)" @click.stop>
                    <a href="#" @click.stop.prevent="editCategory(item)" v-if="canEditCategory"
                      class="text-success"><i class="bi bi-pencil me-2"></i>Edit</a>
                    <a href="#" @click.stop.prevent="deleteCategories(item)" class="text-danger" v-if="canDeleteCategory">
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
            <td colspan="7" class="text-center py-3 text-muted">
              No Item Code found.
            </td>
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

  <!-- Add Modal -->
  <addCategory v-if="showAddCategory" @close="closeAddCategory" />

  <!-- ✅ Update Modal -->
  <updateCategory v-if="showUpdateCategory && selectedItemCode" :item="selectedItemCode" @close="closeUpdateCategory"
    @updated="fetchItemCode" />

  <!-- Delete Modal -->
  <deleteCategory v-if="showDeleteCategory" :item="selectedItemCode" @close="closeDeleteCategory"
    @deleted="onItemDeleted" />
</template>

<script setup>
import { ref, computed, onMounted, nextTick , watch, onBeforeUnmount} from "vue";
import api from "../../services/api";
import { userStore } from "../../stores/userStore";
import { useAppStore } from "../../stores/appStore";
import addCategory from "../../components/Category/AddCategory.vue";
import updateCategory from "../../components/Category/UpdateCategory.vue";
import deleteCategory from "../../components/Category/DeleteCategory.vue";

const Items = ref([]);
const loading = ref(true);
const error = ref(null);
const searchQuery = ref("");
const meta = ref({});
const currentPage = ref(1);
const perPage = 30;

// Modals
const showAddCategory = ref(false);
const showUpdateCategory = ref(false);
const showDeleteCategory = ref(false);

// Selected item for editing
const selectedItemCode = ref(null);

const appStore = useAppStore();

// Permissions
const cadAddCategory = computed(() => userStore.canAddCategory);
const canEditCategory = computed(() => userStore.canEditCategory);
const canDeleteCategory = computed(() => userStore.canDeleteCategory);

//dropdown menu settings dito
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
    fetchItemCode();
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
});


// Auto-close dropdown when modal opens
watch([showUpdateCategory, showDeleteCategory], ([updateVal, deleteVal]) => {
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


//end ng dropdown menu dito 


// ✅ Add Item Modal
async function AddItem() {
  appStore.showLoading();
  await new Promise((resolve) => setTimeout(resolve, 300));
  showAddCategory.value = true;
  appStore.hideLoading();
}

async function closeAddCategory() {
  showAddCategory.value = false;
  fetchItemCode();
}

// ✅ Edit Item Modal
async function editCategory(item) {
  try {
    appStore.showLoading();

    const res = await api.get(`/Items/getItemCode/${item.ItemCode_id}`);

    if (res?.data?.data) {
      selectedItemCode.value = res.data.data;
      await nextTick(); // wait for reactivity
      showUpdateCategory.value = true;
    } else {
      console.error("❌ No item data returned from API:", res.data);
    }
  } catch (err) {
    console.error("❌ Failed to fetch Item Code info:", err);
  } finally {
    appStore.hideLoading();
  }
}

async function closeUpdateCategory() {
  showUpdateCategory.value = false;
  fetchItemCode();
}


// delete Section
async function deleteCategories(item) {
  try {
    appStore.showLoading();
    const res = await api.get(`/Items/getItemCode/${item.ItemCode_id}`);

    if (res?.data?.data) {
      selectedItemCode.value = res.data.data;
      showDeleteCategory.value = true;
    } else {
      console.error("❌ No item data returned from API:", res.data);
    }
  } catch (err) {
    console.error("❌ Failed to fetch item for deletion:", err);
  } finally {
    appStore.hideLoading();
  }
}

function closeDeleteCategory() {
  showDeleteCategory.value = false;
  selectedItemCode.value = null;
}

async function onItemDeleted() {
  showDeleteCategory.value = false;
  selectedItemCode.value = null;
  await fetchItemCode(); // ✅ refresh table after successful delete
}

// ✅ Fetch Item Codes
async function fetchItemCode() {
  loading.value = true;
  try {
    const response = await api.get("/Items/getItemCode", {
      params: {
        search: searchQuery.value,
        page: currentPage.value,
        per_page: perPage,
      },
    });

    Items.value = response.data.data;
    meta.value = response.data.meta;
  } catch (err) {
    error.value = err.response?.data?.message || err.message;
  } finally {
    loading.value = false;
  }
}

// Pagination
function changePage(page) {
  if (page < 1 || page > meta.value.last_page) return;
  currentPage.value = page;
  fetchItemCode();
}

// Search handlers
function handleSearchEnter() {
  if (!searchQuery.value.trim()) {
    showErrors("Please enter a search term!");
    return; // Stop search
  }

  error.value = "";
  currentPage.value = 1;
  fetchItemCode();
}

function showErrors(message) {
  error.value = message;
  setTimeout(() => {
    error.value = "";
  }, 5000);
}
</script>
