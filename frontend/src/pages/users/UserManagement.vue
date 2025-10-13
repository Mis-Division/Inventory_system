<template>
  <div class="main-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
      <h2 class="mb-0">User Management</h2>

      <div class="d-flex gap-3 flex-wrap mt-2 mt-md-0">
        <input v-model="searchQuery" type="text" class="form-control" placeholder="Search users..."
          style="max-width: 200px;" />
        <button @click="addUser" :disabled="!canAddUsers" class="btn btn-primary">
          <i class="bi bi-person-plus"></i> Add User
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="table-wrapper shadow-sm rounded bg-white">
      <table class="table table-hover table-bordered align-middle mb-1 table-striped" style="font-size: 20px;">
        <thead class="table-primary ">
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
          <tr v-for="user in paginatedUsers" :key="user.id"
            :class="canEditAddUsers ? 'table-row-clickable' : 'table-secondary text-muted'"
            @click="handleRowClick(user)" :title="canEditAddUsers ? 'Click to edit user' : 'No permission to edit'"
            style="cursor: pointer;">
            <td>{{ user.id }}</td>
            <td>{{ user.fullname }}</td>
            <td>{{ user.username }}</td>

            <td>{{ user.role }}</td>
            <td>{{ user.department }}</td>
            <td>
              <span class="badge" :class="{
                'bg-primary': user.status === 'Active',
                'bg-danger': user.status === 'Inactive'
              }">
                <i :class="{
                  'bi bi-check me-1': user.status === 'Active',
                  'bi bi-x me-1': user.status === 'Inactive'
                }"></i>
                {{ user.status }}
              </span>
            </td>
          </tr>

          <tr v-if="filteredUsers.length === 0">
            <td colspan="6" class="text-center py-3 text-muted">
              No users found
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav v-if="totalPages > 1" class="mt-4">
      <ul class="pagination justify-content-center flex-wrap">
        <li class="page-item" :class="{ disabled: currentPage === 1 }">
          <button class="page-link" @click="prevPage">Prev</button>
        </li>
        <li v-for="page in totalPages" :key="page" class="page-item" :class="{ active: page === currentPage }">
          <button class="page-link" @click="goToPage(page)">
            {{ page }}
          </button>
        </li>
        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
          <button class="page-link" @click="nextPage">Next</button>
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
const currentPage = ref(1);
const pageSize = 15;

// Modals
const showUserModal = ref(false);
const showAddModal = ref(false);
const selectedUser = ref(null);

// Permissions
const canEditAddUsers = computed(() => userStore.canEditUsers);
const canDeleteUsers = computed(() => userStore.canDeleteUsers);
const canAddUsers = computed(() => userStore.canAddUsers);

// === Add User Modal ===
async function addUser() {
  try {
    appStore.showLoading();
    await new Promise(resolve => setTimeout(resolve, 500));
    showAddModal.value = true;
  } catch (err) {
    console.error("Error preparing modal:", err);
  } finally {
    appStore.hideLoading();
  }
}

function closeAddModal() {
  showAddModal.value = false;
  fetchUsers(); // Refresh list after adding a new user
}

// === Edit User Modal ===
function handleRowClick(user) {
  if (!canEditAddUsers.value) return;
  openUserModal(user);
}

async function openUserModal(user) {
  try {
    appStore.showLoading();
    const res = await api.get(`/users/display_user/${user.id}`);
    selectedUser.value = {
      ...res.data?.data,
      modules: res.data?.data?.modules || [],
    };
    showUserModal.value = true;
  } catch (err) {
    console.error(err);
  } finally {
    appStore.hideLoading();
  }
}

function closeUserModal() {
  showUserModal.value = false;
  selectedUser.value = null;
}

// === Handlers for Update/Delete ===
function handleUserUpdated() {
  showUserModal.value = false;
  selectedUser.value = null;
  fetchUsers();
}

function handleUserDeleted() {
  showUserModal.value = false;
  selectedUser.value = null;
  fetchUsers();
}

// === Fetch Users ===
async function fetchUsers() {
  appStore.showLoading();
  try {
    const res = await api.get("/users/display_user/");
    users.value = res.data?.data || [];
  } catch (err) {
    console.error(err);
    users.value = [];
  } finally {
    appStore.hideLoading();
  }
}

// === Filter & Pagination ===
const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value;
  const q = searchQuery.value.toLowerCase();
  return users.value.filter((u) =>
    (u.fullname?.toLowerCase().includes(q) ?? false) ||
    (u.username?.toLowerCase().includes(q) ?? false) ||
    (u.role?.toLowerCase().includes(q) ?? false) ||
    (u.status?.toLowerCase().includes(q) ?? false) ||
    String(u.id ?? "").includes(q)
  );
});

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / pageSize));
const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  return filteredUsers.value.slice(start, start + pageSize);
});

function goToPage(page) { currentPage.value = page; }
function nextPage() { if (currentPage.value < totalPages.value) currentPage.value++; }
function prevPage() { if (currentPage.value > 1) currentPage.value--; }

onMounted(() => fetchUsers());
</script>

<style scoped>
.main-container {
  padding-left: 50px;
  padding-right: 20px;
  padding-top: 20px;
  padding-bottom: 20px;
  width: 100%;

  overflow-y: hidden;
  box-sizing: border-box;
  transition: padding-left 0.3s;
}

.table-wrapper {
  width: 100%;
  overflow: hidden;
}

.table-wrapper table {
  width: 100%;
  table-layout: fixed;
}

.table-wrapper th,
.table-wrapper td {
  word-break: break-word;
}

@media (max-width: 768px) {
  .main-container {
    padding-left: 15px;
    padding-right: 15px;
  }
}
</style>
