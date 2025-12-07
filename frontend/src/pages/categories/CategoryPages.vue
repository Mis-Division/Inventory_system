<template>
  <div class="main-container">
    <!-- Header -->
    <div class="custom-headers">
      <div class="w-100">
        <h1 class="mb-3"><i class="bi bi-info-circle text-primary"></i> Items</h1>
        <!-- upload excel file -->
                <!-- <input type="file" @change="uploadFile" class="form-control"> -->
      </div>

      <div class="d-flex justify-content-between align-items-center w-100">
        <div class="position-relative" style="width: 35%; min-width: 300px;">
          <input type="text" v-model="searchQuery" @keyup.enter="handleSearchEnter" class="form-control pe-5"
            placeholder="Search Items..." style="background-color: #FCF6D9;" />

          <i v-if="searchQuery" class="bi bi-x-circle-fill text-muted" @click="clearSearch"
            style="position: absolute;right: 10px;top: 50%; transform: translateY(-50%);cursor: pointer; font-size: 1.2rem;">
          </i>
        </div>

        <button v-if="cadAddCategory" @click="AddItem" class="btn btn-success">
          <i class="bi bi-plus-circle me-1"></i> Item Code
        </button>
      </div>
    </div>

    <!-- CATEGORY TABS -->
    <ul class="nav nav-tabs category-tabs" role="tablist">
      <li class="nav-item" v-for="cat in categories" :key="cat.value">
        <button class="nav-link" :class="{ active: activeCategory === cat.value }" @click="changeCategory(cat.value)"
          type="button">
          {{ cat.label }}
        </button>
      </li>
    </ul>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-4 text-muted">Loading...</div>

    <!-- Error -->
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- TABLE -->
    <div v-if="!loading">
      <table class="table table-hover align-middle mb-2 text-center">
        <thead class="table-secondary">
          <tr>
            <th style="width: 10%;">Item Code</th>
            <th style="width: 30%;">Product Name</th>
            <th style="width: 20%;">Descriptions</th>
            <th style="width: 20%;">Acct. Code</th>
            <th style="width: 15%;">Item Category</th>
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
              <div @click="toggleDropdown(item.ItemCode_id, $event)" class="cursor-pointer">
                <i class="bi bi-three-dots"></i>
              </div>

              <!-- TELEPORT DROPDOWN -->
              <teleport to="body">
                <Transition name="fade">
                  <div v-if="activeDropdown === item.ItemCode_id"
                    ref="el => (dropdownRefs.value ??= {})[item.ItemCode_id] = el" class="dropdown-menu-teleport"
                    :style="getDropdownStyle(item.ItemCode_id)" @click.stop>

                    <a href="#" @click.stop.prevent="editCategory(item)" v-if="canEditCategory" class="text-success"><i
                        class="bi bi-pencil me-2"></i>Edit</a>

                    <a href="#" @click.stop.prevent="deleteCategories(item)" class="text-danger"
                      v-if="canDeleteCategory">
                      <i class="bi bi-trash me-2"></i>Delete</a>

                  </div>
                </Transition>
              </teleport>
            </td>
          </tr>
        </tbody>

        <tbody v-else>
          <tr>
            <td colspan="7" class="text-center py-3 text-muted">
              No items found for this category.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
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
  </div>

  <!-- MODALS -->
  <addCategory v-if="showAddCategory" @close="closeAddCategory" />
  <updateCategory v-if="showUpdateCategory && selectedItemCode" :item="selectedItemCode" @close="closeUpdateCategory"
    @updated="fetchItemCode" />
  <deleteCategory v-if="showDeleteCategory" :item="selectedItemCode" @close="closeDeleteCategory"
    @deleted="onItemDeleted" />
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch, onBeforeUnmount } from "vue";
import api from "../../services/api";
import { userStore } from "../../stores/userStore";
import { useAppStore } from "../../stores/appStore";
import addCategory from "../../components/Category/AddCategory.vue";
import updateCategory from "../../components/Category/UpdateCategory.vue";
import deleteCategory from "../../components/Category/DeleteCategory.vue";

/* ============================================================
   STATE
============================================================ */
const Items = ref([]);
const loading = ref(true);
const error = ref(null);
const searchQuery = ref("");
const meta = ref({});
const currentPage = ref(1);
const perPage = 50;

// Category tabs
const categories = [
  { label: "Line Hardware", value: "Line Hardware" },
  { label: "Special Hardware", value: "Special Hardware" },
  { label: "MotorPool", value: "MotorPool" },
  { label: "Tools", value: "Tools" },
  { label: "PPE", value: "PPE" },
  { label: "Gen Plant", value: "Gen Plant" }
];

const activeCategory = ref("Line Hardware");

// Modals
const showAddCategory = ref(false);
const showUpdateCategory = ref(false);
const showDeleteCategory = ref(false);

// Selected Item
const selectedItemCode = ref(null);

const appStore = useAppStore();
const cadAddCategory = computed(() => userStore.canAddCategory);
const canEditCategory = computed(() => userStore.canEditCategory);
const canDeleteCategory = computed(() => userStore.canDeleteCategory);

/* ============================================================
   DROPDOWN MENU TELEPORT
============================================================ */
const activeDropdown = ref(null);
const dropdownPositions = ref({});
const dropdownRefs = ref({});

function toggleDropdown(id, event) {
  event.stopPropagation();
  activeDropdown.value = activeDropdown.value === id ? null : id;

  nextTick(() => {
    const rect = event.currentTarget.getBoundingClientRect();
    dropdownPositions.value[id] = {
      top: rect.bottom + window.scrollY,
      left: rect.left + window.scrollX
    };
  });
}

function handleClickOutside(event) {
  if (!activeDropdown.value) return;
  const el = dropdownRefs.value?.[activeDropdown.value];
  if (!el || !el.contains(event.target)) activeDropdown.value = null;
}

function getDropdownStyle(id) {
  const pos = dropdownPositions.value[id] || { top: 0, left: 0 };
  const width = 180;

  let left = pos.left;
  if (pos.left + width > window.innerWidth) left = pos.left - width + 24;

  return {
    position: "absolute",
    top: pos.top + "px",
    left: left + "px",
    zIndex: 3000,
    background: "white",
    border: "1px solid #ddd",
    borderRadius: "4px",
    minWidth: width + "px",
    boxShadow: "0 2px 8px rgba(0,0,0,0.15)"
  };
}

/* ============================================================
   CATEGORY + FETCH
============================================================ */
function changeCategory(cat) {
  activeCategory.value = cat;
  currentPage.value = 1;
  fetchItemCode();
}

async function fetchItemCode() {
  loading.value = true;

  try {
    const response = await api.get("/Items/getItemCode", {
      params: {
        search: searchQuery.value,
        page: currentPage.value,
        per_page: perPage,
        category: activeCategory.value, // ❤️ KEY PART
      }
    });

    Items.value = response.data.data;
    meta.value = response.data.meta;

  } catch (err) {
    error.value = err.response?.data?.message || err.message;
  } finally {
    loading.value = false;
  }
}

/* ============================================================
   PAGINATION
============================================================ */
function changePage(page) {
  if (page < 1 || page > meta.value.last_page) return;
  currentPage.value = page;
  fetchItemCode();
}

/* ============================================================
   SEARCH
============================================================ */
function handleSearchEnter() {
  if (!searchQuery.value.trim()) return showErrors("Please enter a search term!");
  currentPage.value = 1;
  fetchItemCode();
}

function clearSearch() {
  searchQuery.value = "";
  fetchItemCode();
}

function showErrors(msg) {
  error.value = msg;
  setTimeout(() => (error.value = ""), 4000);
}

/* ============================================================
   MODALS
============================================================ */
async function AddItem() {
  appStore.showLoading();
  await new Promise((r) => setTimeout(r, 200));
  showAddCategory.value = true;
  appStore.hideLoading();
}

async function closeAddCategory() {
  showAddCategory.value = false;
  fetchItemCode();
}

async function editCategory(item) {
  const res = await api.get(`/Items/getItemCode/${item.ItemCode_id}`);
  selectedItemCode.value = res.data.data;
  showUpdateCategory.value = true;
}

function closeUpdateCategory() {
  showUpdateCategory.value = false;
  fetchItemCode();
}

async function deleteCategories(item) {
  const res = await api.get(`/Items/getItemCode/${item.ItemCode_id}`);
  selectedItemCode.value = res.data.data;
  showDeleteCategory.value = true;
}

function closeDeleteCategory() {
  showDeleteCategory.value = false;
}

async function onItemDeleted() {
  showDeleteCategory.value = false;
  fetchItemCode();
}

/* ============================================================
   INIT
============================================================ */
onMounted(() => {
  document.addEventListener("click", handleClickOutside);
  fetchItemCode();
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
});

//upload excel file
// async function uploadFile(e) {
//   const file = e.target.files[0];
//   let form = new FormData();
//   form.append("file", file);

//   try {
//     const res = await api.post("/items/import", form, {
//       headers: { "Content-Type": "multipart/form-data" }
//     });

//     alert("Import Success!");
//   } catch (err) {
//     alert("Import Failed: " + err.response.data.message);
//   }
// }
</script>
<style scoped>
.category-tabs {
  margin-top: 8px;      /* Distance from header */
  margin-bottom: 12px;  /* Distance to the table (Ito ang importante) */
}
/* ============================================================
   🌟 HEADER AREA – Sleek & Modern (DIFFERENT STYLE)
============================================================ */
.custom-headers {
  background: linear-gradient(135deg, #ffffff 0%, #f4f7ff 100%);
  padding: 18px 25px;
  border-radius: 14px;
  border: 1px solid #e5e9f2;
  margin-bottom: 18px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}

.custom-headers h1 {
  font-weight: 700;
  font-size: 1.8rem;
  color: #344767;
}

/* ============================================================
   🔍 SEARCH BAR – Different design (Material-lite)
============================================================ */
.custom-headers input {
  border-radius: 50px !important;
  padding: 12px 18px;
  padding-right: 45px !important;
  border: 1.5px solid #cdd5ea;
  background: #ffffff;
  transition: 0.25s;
}

.custom-headers input:focus {
  border-color: #5c8dff;
  box-shadow: 0 0 0 3px rgba(92,141,255,0.15);
  background: #f9fbff;
}

.custom-headers i.bi-x-circle-fill {
  color: #97a0b9 !important;
}

/* ADD ITEM BUTTON */
.custom-headers .btn-success {
  background: #4caf50;
  border-radius: 8px;
  padding: 10px 16px;
  font-weight: 600;
  transition: 0.2s;
}

.custom-headers .btn-success:hover {
  background: #43a047;
}

/* ============================================================
   🗂 CATEGORY TABS – Clean & smooth pill design
============================================================ */
.category-tabs {
  border-bottom: none !important;
  margin-bottom: 15px;
  display: flex;
  gap: 6px;
}

.category-tabs .nav-link {
  border: none !important;
  background: #eef1f7;
  padding: 8px 18px;
  border-radius: 50px !important;
  font-weight: 600;
  color: #50618e;
  transition: 0.25s ease;
}

.category-tabs .nav-link.active {
  background: #5c8dff;
  color: white !important;
  box-shadow: 0 3px 6px rgba(92, 141, 255, 0.3);
}

.category-tabs .nav-link:hover {
  background: #dce3f9;
}

/* ============================================================
   📦 TABLE DESIGN – Different from receiving order
============================================================ */
.table {
  border-collapse: separate !important;
  border-spacing: 0 8px !important;
}

.table thead th {
  background: #f0f3fa;
  font-weight: 700;
  padding: 14px;
  color: #44516e;
  border-bottom: none;
}

.table tbody tr {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.06);
  transition: 0.2s ease;
}

.table tbody tr:hover {
  transform: scale(1.01);
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

.table td {
  padding: 14px 10px !important;
  font-size: 0.95rem;
  color: #3f4b66;
}

/* ============================================================
   ⋮ ACTION DROPDOWN – frosted floating menu 
============================================================ */
.cursor-pointer {
  padding: 6px 10px;
  border-radius: 10px;
  cursor: pointer;
  transition: 0.2s ease;
}

.cursor-pointer:hover {
  background: #eef2ff;
}

.dropdown-menu-teleport {
  position: absolute;
  background: rgba(255,255,255,0.9);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  border: 1px solid #dde3f3;
  padding: 8px 0;
  min-width: 150px;
  animation: fadeIn 0.15s ease-out;
  box-shadow: 0 6px 16px rgba(0,0,0,0.12);
}

.dropdown-menu-teleport a {
  display: block;
  padding: 10px 14px;
  font-weight: 600;
  color: #40507a;
  transition: 0.2s;
}

.dropdown-menu-teleport a:hover {
  background: #f0f3ff;
  border-radius: 8px;
}

/* Animation */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-5px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ============================================================
   🔢 PAGINATION – Minimal rounded style
============================================================ */
.pagination .page-link {
  border-radius: 10px !important;
  padding: 7px 14px;
  margin: 0 3px;
  font-weight: 600;
  color: #5c6b8a;
  border: 1px solid #ccd3e2;
  transition: 0.2s;
}

.pagination .page-item.active .page-link {
  background: #5c8dff;
  color: white;
  border: none;
  box-shadow: 0 3px 6px rgba(92, 141, 255, 0.3);
}

.pagination .page-link:hover {
  background: #eef2ff;
  color: #3e4d7b;
}

/* Circle numbers */
.page-circle {
  border-radius: 50% !important;
}

</style>