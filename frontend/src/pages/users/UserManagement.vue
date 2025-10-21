<template>
  <div class="main-container">
    <!-- Header -->
    <div class="custom-headers">
      <h1 class="mb-3">User Management</h1>
      <div class="custom-actions">
        <input v-model="searchQuery" @input="searchUsers" type="text" class="form-control" placeholder="Search users..." />
        <button @click="addUser" :disabled="!canAddUsers" class="btn btn-primary">
          <i class="bi bi-person-plus"></i> Add User
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="table-wrapper shadow-sm rounded bg-white">
      <table class="table table-hover table-bordered align-middle mb-1 " style="font-size: 20px;">
        <thead class="table-primary">
          <tr>
            <th style="width: 5%;">ID</th>
            <th style="width: 20%;">Full Name</th>
            <th style="width: 15%;">Username</th>
            <th style="width: 15%;">Role</th>
            <th style="width: 35%;">Department</th>
            <th style="width: 10%;">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id"
            :class="canEditAddUsers ? 'table-row-clickable' : 'table-secondary text-muted'"
            @click="handleRowClick(user)" :title="canEditAddUsers ? 'Click to edit user' : 'No permission to edit'"
            style="cursor: pointer;">
            <td>{{ user.user_id }}</td>
            <td>{{ user.fullname }}</td>
            <td>{{ user.username }}</td>
            <td>{{ user.role }}</td>
            <td>{{ user.department }}</td>
            <td>
              <span class="badge" :class="user.status === 'Active' ? 'bg-primary' : 'bg-danger'">
                <i :class="user.status === 'Active' ? 'bi bi-check' : 'bi bi-x'"></i>
                {{ user.status }}
              </span>
            </td>
          </tr>
          <tr v-if="users.length === 0">
            <td colspan="6" class="text-center py-3 text-muted">No users found</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav v-if="meta.last_page > 1" class="mt-4">
      <ul class="pagination justify-content-center flex-wrap">
        <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
          <button class="page-link" @click="goToPage(meta.current_page - 1)"
            :disabled="meta.current_page === 1">Prev</button>
        </li>

        <li v-for="page in totalPagesArray" :key="page" class="page-item"
          :class="{ active: page === meta.current_page }">
          <button class="page-link" @click="goToPage(page)">{{ page }}</button>
        </li>

        <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
          <button class="page-link" @click="goToPage(meta.current_page + 1)"
            :disabled="meta.current_page === meta.last_page">Next</button>
        </li>
      </ul>
    </nav>

    <!-- Modals -->
    <UserModal v-if="showUserModal" :user="selectedUser" :can-edit="canEditAddUsers" :can-delete="canDeleteUsers"
      @close="closeUserModal" @updated="handleUserUpdated" @deleted="handleUserDeleted" />
    <AddModal v-if="showAddModal" @close="closeAddModal" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import api from "../../services/api";
import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";
import UserModal from "../../components/AddUser/UserModal.vue";
import AddModal from "../../components/AddUser/UserAddModal.vue";

const appStore = useAppStore();
const users = ref([]);
const searchQuery = ref("");
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });

// Modals
const showUserModal = ref(false);
const showAddModal = ref(false);
const selectedUser = ref(null);

// Permissions
const canEditAddUsers = computed(() => userStore.canEditUsers);
const canDeleteUsers = computed(() => userStore.canDeleteUsers);
const canAddUsers = computed(() => userStore.canAddUsers);

// Debounce timer
let searchTimeout = null;

// Fetch users from API (server-side pagination)
async function fetchUsers(query = "", page = 1) {
  appStore.showLoading();
  try {
    const res = await api.get("/users/display_user/", {
      params: { search: query, page, per_page: meta.value.per_page },
    });
    users.value = res.data?.data || [];
    meta.value = res.data?.meta || { current_page: 1, last_page: 1, per_page: 15, total: 0 };
  } catch (err) {
    console.error(err);
    users.value = [];
    meta.value = { current_page: 1, last_page: 1, per_page: 15, total: 0 };
  } finally {
    appStore.hideLoading();
  }
}

// Search users with debounce
function searchUsers() {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => fetchUsers(searchQuery.value, 1), 300);
}

// Add/Edit user modals
async function addUser() {
  appStore.showLoading();
  try {
    await new Promise((resolve) => setTimeout(resolve, 500));
    showAddModal.value = true;
  } finally {
    appStore.hideLoading();
  }
}

function closeAddModal() {
  showAddModal.value = false;
  fetchUsers(searchQuery.value, meta.value.current_page);
}

function handleRowClick(user) {
  if (!canEditAddUsers.value) return;
  openUserModal(user);
}

async function openUserModal(user) {
  appStore.showLoading();
  try {
    const res = await api.get(`/users/display_user/${user.user_id}`);
    selectedUser.value = { ...res.data?.data, modules: res.data?.data?.modules || [] };
    showUserModal.value = true;
  } finally {
    appStore.hideLoading();
  }
}

function closeUserModal() {
  showUserModal.value = false;
  selectedUser.value = null;
}

function handleUserUpdated() {
  closeUserModal();
  fetchUsers(searchQuery.value, meta.value.current_page);
}

function handleUserDeleted() {
  closeUserModal();
  fetchUsers(searchQuery.value, meta.value.current_page);
}

// Pagination
function goToPage(page) {
  if (page < 1 || page > meta.value.last_page) return;
  fetchUsers(searchQuery.value, page);
}

// Computed array of page numbers
const totalPagesArray = computed(() => Array.from({ length: meta.value.last_page }, (_, i) => i + 1));

onMounted(() => fetchUsers());
</script>


