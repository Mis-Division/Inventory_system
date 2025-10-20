<template>
  <div class="main-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
      <h1 class="mb-3">ItemCode Management</h1>

      <div class="d-flex align-items-center gap-2" style="white-space: nowrap;">
        <input v-model="searchQuery" @input="fetchItemCode" type="text" class="form-control"
          placeholder="Search ItemCode and Descriptions..." style="width: 500px; flex: 0 0 auto;" />
        <button :disabled="!cadAddCategory" @click="AddItem" class="btn btn-primary">
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
      <table class="table table-hover table-bordered align-middle mb-2 text-center">
        <thead class="table-secondary">
          <tr>
            <th style="width: 5%;">ID</th>
            <th style="width: 15%;">Item Code</th>
            <th style="width: 40%;">Descriptions</th>
            <th style="width: 10%;">Action</th>
          </tr>
        </thead>

        <tbody v-if="Items.length > 0">
          <tr v-for="item in Items" :key="item.ItemCode_id">
            <td>{{ item.ItemCode_id }}</td>
            <td>{{ item.ItemCode }}</td>
            <td>{{ item.description }}</td>
            <td>
              <button :disabled="!canEditCategory" @click="editCategory(item)" class="btn btn-warning" title="Edit">
                <i class="bi bi-pencil"></i>
              </button>
              |
              <button :disabled="!canDeleteCategory" @click="deleteCategories(item)" class="btn btn-danger"
                title="Delete">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>

        <tbody v-else>
          <tr>
            <td colspan="4" class="text-center py-3 text-muted">
              No Item Code found.
            </td>
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

  <!-- Add Modal -->
  <addCategory v-if="showAddCategory" @close="closeAddCategory" />

  <!-- ✅ Update Modal -->
  <updateCategory v-if="showUpdateCategory && selectedItemCode" :item="selectedItemCode"
    @close="closeUpdateCategory" @updated="fetchItemCode" />

  <!-- Delete Modal -->
  <deleteCategory v-if="showDeleteCategory" :item="selectedItemCode" @close="closeDeleteCategory"
    @deleted="onItemDeleted" />
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from "vue";
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
const perPage = 10;

// Modals
const showAddCategory = ref(false);
const showUpdateCategory = ref(false);
const showDeleteCategory = ref(false);

// Selected item for editing
const selectedItemCode = ref(null);

const appStore = useAppStore();

// Permissions
const cadAddCategory = computed(() => userStore.cadAddCategory);
const canEditCategory = computed(() => userStore.canEditCategory);
const canDeleteCategory = computed(() => userStore.canDeleteCategory);

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

onMounted(fetchItemCode);
</script>

<style scoped>
.table th,
.table td {
  vertical-align: middle;
}
</style>
