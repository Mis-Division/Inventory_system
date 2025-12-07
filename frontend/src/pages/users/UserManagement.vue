<template>
  <div class="main-container">
    <!-- Header -->
    <div class="custom-headers">
      <h1 class="mb-3"><i class="bi bi-info-circle text-primary"></i> User's</h1>
      <div class="custom-actions">
        <input v-model="searchQuery" @input="searchUsers" type="text" class="form-control"
          placeholder="Search users..." />
        <button @click="addUser" v-if="canAddUsers" class="btn btn-primary">
          <i class="bi bi-person-plus"></i> Add User
        </button>
      </div>
    </div>

    <!-- Table -->
    <div  class="table-responsive">
      <table class="table table-hover  align-middle mb-2 text-center" style="font-size: 20px;">
        <thead class="table-secondary">
          <tr>
            <th style="width: 5%;">ID</th>
            <th style="width: 20%;">Full Name</th>
            <th style="width: 15%;">Username</th>
            <th style="width: 15%;">Role</th>
            <th style="width: 25%;">Department</th>
            <th style="width: 10%;">Status</th>
            <th style="width: 10%;" class="text-center">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="user in users" :key="user.id" class="align-middle">
            <td>{{ user.user_id }}</td>
            <td>{{ user.fullname }}</td>
            <td>{{ user.username }}</td>
            <td>{{ user.role }}</td>
            <td>{{ user.department }}</td>
            <td>
              <span class="badge" :class="user.status === 'Active' ? 'bg-primary' : 'bg-danger'">
                <i :class="user.status === 'Active' ? 'bi bi-check' : 'bi bi-x'
                  "></i>
                {{ user.status }}
              </span>
            </td>

            <!-- ✅ Actions -->
            <td class="text-center">
              <button v-if="canEditAddUsers" @click="editUser(user)" class="btn btn-sm btn-warning me-2"
                title="Edit User">
                <i class="bi bi-pencil"></i>
              </button>

              <button v-if="canDeleteUsers" @click="deleteUser(user)" class="btn btn-sm btn-danger"
                title="Delete User">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>

          <tr v-if="users.length === 0">
            <td colspan="7" class="text-center py-3 text-muted">
              No users found
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav v-if="meta.last_page > 1" class="mt-4">
      <ul class="pagination justify-content-center flex-wrap">
        <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
          <button class="page-link" @click="goToPage(meta.current_page - 1)" :disabled="meta.current_page === 1">
            Prev
          </button>
        </li>

        <li v-for="page in totalPagesArray" :key="page" class="page-item"
          :class="{ active: page === meta.current_page }">
          <button class="page-link" @click="goToPage(page)">
            {{ page }}
          </button>
        </li>

        <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
          <button class="page-link" @click="goToPage(meta.current_page + 1)"
            :disabled="meta.current_page === meta.last_page">
            Next
          </button>
        </li>
      </ul>
    </nav>

    <!-- Modals -->
    <UserModal v-if="showUserModal" :user="selectedUser" :can-edit="canEditAddUsers" :can-delete="canDeleteUsers"
      @close="closeUserModal" @updated="handleUserUpdated"  />
    <AddModal v-if="showAddModal" @close="closeAddModal" />
    <DeleteUser v-if="showDeleteUser" :user="selectedUser" 
    @close="closeDeleteUser" @deleted="onDeletedUser" />
</div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import api from "../../services/api";
import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";
import UserModal from "../../components/AddUser/UserModal.vue";
import AddModal from "../../components/AddUser/UserAddModal.vue";
import DeleteUser from "../../components/AddUser/DeleteUser.vue";


const appStore = useAppStore();
const users = ref([]);
const searchQuery = ref("");
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });

// Modals
const showUserModal = ref(false);
const showAddModal = ref(false);
const showDeleteUser = ref(false);
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
    meta.value =
      res.data?.meta || {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
      };
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

// ✅ Edit Button Handler
function editUser(user) {
  if (!canEditAddUsers.value) return;
  openUserModal(user);
}



async function openUserModal(user) {
  appStore.showLoading();
  try {
    const res = await api.get(`/users/display_user/${user.user_id}`);
    selectedUser.value = {
      ...res.data?.data,
      modules: res.data?.data?.modules || [],
    };
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

async function deleteUser(user) {
  if (!canDeleteUsers.value) return;
  selectedUser.value = user;
  showDeleteUser.value = true;

  try{
    appStore.showLoading();
    const res = await api.get(`/users/display_user/${user.user_id}`);
    if(res.data?.data){
      selectedUser.value = res.data?.data;
      showDeleteUser.value = true;
    }else{
      console.error("❌ No user data returned from API:", res.data);
    }
  }catch(err){
    console.error("❌ Failed to fetch user for deletion:", err);
  } finally {
    appStore.hideLoading();
  }
}
function closeDeleteUser() {
  showDeleteUser.value = false;
  selectedUser.value = null;
}
async function onDeletedUser(){
  showDeleteUser.value = false;
  selectedUser.value = null;
  await fetchUsers();
}

// Pagination
function goToPage(page) {
  if (page < 1 || page > meta.value.last_page) return;
  fetchUsers(searchQuery.value, page);
}

// Computed array of page numbers
const totalPagesArray = computed(() =>
  Array.from({ length: meta.value.last_page }, (_, i) => i + 1)
);

onMounted(() => fetchUsers());
</script>
<style scoped>
/* ============================================================
   🌟 HEADER – Elegant Admin Design
============================================================ */
.custom-headers {
  background: #ffffff;
  border-radius: 16px;
  padding: 22px 26px;
  margin-bottom: 20px;
  border: 1px solid #e3e6ef;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);

  display: flex;
  justify-content: space-between;
  align-items: center;
}

.custom-headers h1 {
  font-size: 1.7rem;
  font-weight: 800;
  color: #2c364d;
  display: flex;
  align-items: center;
  gap: 8px;
}

.custom-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

/* Search Bar */
.custom-actions .form-control {
  width: 260px;
  padding: 10px 14px;
  border-radius: 10px;
  background: #f5f7fa;
  border: 1px solid #cfd4df;
  font-weight: 500;
  transition: 0.25s ease;
}

.custom-actions .form-control:focus {
  background: #ffffff;
  border-color: #4c82ff;
  box-shadow: 0 0 0 3px rgba(76,130,255,0.28);
}

/* Add User Button */
.btn-primary {
  padding: 10px 16px;
  border-radius: 10px;
  font-weight: 600;
  background: linear-gradient(135deg, #4c82ff, #3a6be0);
  border: none;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #3a6be0, #335dcc);
}


/* ============================================================
   📋 TABLE – Floating modern rows
============================================================ */
.table {
  border-collapse: separate !important;
  border-spacing: 0 10px !important;
}

.table thead th {
  background: #e7eaf3 !important;
  color: #3b465f;
  border: none;
  padding: 14px;
  font-size: 0.9rem;
  font-weight: 700;
  text-transform: uppercase;
}

.table tbody tr {
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  transition: 0.2s ease;
}

.table tbody tr:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 18px rgba(0,0,0,0.13);
}

.table td {
  padding: 15px 10px !important;
  color: #3a4255;
  font-size: 1rem;
  font-weight: 500;
}

/* Status Badge */
.badge {
  padding: 8px 12px;
  font-size: 0.8rem;
  border-radius: 8px;
  font-weight: 700;
}

.bg-primary {
  background: #4c82ff !important;
}

.bg-danger {
  background: #ff5c5c !important;
}

/* ============================================================
   🎯 ACTION BUTTONS – Modern Icons
============================================================ */
.btn-warning {
  background: #ffb84d;
  border: none;
  padding: 6px 10px;
  border-radius: 8px;
}

.btn-warning:hover {
  background: #e6a240;
}

.btn-danger {
  background: #ff5c5c;
  border: none;
  padding: 6px 10px;
  border-radius: 8px;
}

.btn-danger:hover {
  background: #e34d4d;
}

/* Icons inside action buttons */
.btn-sm i {
  font-size: 1rem;
}

/* ============================================================
   🔢 PAGINATION – Rounded & elevated
============================================================ */
.pagination .page-link {
  border-radius: 12px;
  padding: 7px 14px;
  margin: 0 3px;
  border: 1px solid #cfd4df;
  background: white;
  color: #4a5572;
  font-weight: 600;
  transition: 0.2s;
}

.pagination .page-item.active .page-link {
  background: #4c82ff;
  border: none;
  color: white;
  box-shadow: 0 3px 10px rgba(76,130,255,0.35);
}

.pagination .page-link:hover {
  background: #eff2fd;
}

/* Disabled state */
.pagination .page-item.disabled .page-link {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ============================================================
   GENERAL LAYOUT CONSISTENCY
============================================================ */
.table th,
.table td {
  vertical-align: middle !important;
}

</style>