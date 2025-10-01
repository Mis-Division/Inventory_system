<template>
  <div class="add-user-page">
    <!-- Header -->
    <div class="header">
      <h2>User Management</h2>
      <div class="actions">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search users..."
          class="search-input"
        />
        <button @click="addUser" :disabled="!canAddUsers" class="add-btn">
          Add User
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Username</th>
            <th>Role</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="user in paginatedUsers"
            :key="user.id"
            :class="canEditAddUsers ? 'row-clickable' : 'row-disabled'"
            @click="handleRowClick(user)"
            :title="canEditAddUsers ? 'Click to edit user' : 'No permission to edit'"
          >
            <td>{{ user.id }}</td>
            <td>{{ user.fullname }}</td>
            <td>{{ user.username }}</td>
            <td>{{ user.role }}</td>
            <td>{{ user.status }}</td>
          </tr>

          <tr v-if="filteredUsers.length === 0">
            <td colspan="5" class="no-users">No users found</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="pagination">
      <button @click="prevPage" :disabled="currentPage === 1">Prev</button>
      <button
        v-for="page in totalPages"
        :key="page"
        @click="goToPage(page)"
        :class="{ active: page === currentPage }"
      >
        {{ page }}
      </button>
      <button @click="nextPage" :disabled="currentPage === totalPages">
        Next
      </button>
    </div>

    <!-- User Modal -->
    <UserModal
      v-if="showModal"
      :user="selectedUser"
      :can-edit="canEditAddUsers"
      :can-delete="canDeleteUsers"
      @close="closeModal"
      @save="saveUser"
      @delete="deleteUser"
    />
    <AddModal 
      v-if="showAddModal"
      @close="closeAddModal"
    />
    <!-- Alert / Confirm Modal -->
    <AlertModal
      v-model="showAlert"
      :message="alertMessage"
      :type="alertType"
      @response="handleAlertResponse"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import api from "../../services/api";
import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";
import UserModal from "../../components/UserModal.vue";
import AlertModal from "../../components/AlertModal.vue";
import AddModal  from "../../components/UserAddModal.vue";
import "../../assets/css/AddUserPage.css";

const appStore = useAppStore();
const users = ref([]);
const searchQuery = ref("");
const currentPage = ref(1);
const pageSize = 10;

const showModal = ref(false);
const showAddModal = ref(false);
const selectedUser = ref(null);
const showAlert = ref(false);
const alertMessage = ref("");
const alertType = ref("alert"); 
let alertResolve = null;

// Permissions
const canEditAddUsers = computed(() => userStore.canEditUsers);
const canDeleteUsers = computed(() => userStore.canDeleteUsers);
const canAddUsers = computed(() => userStore.canAddUsers);

// add user function
async function addUser() {
  try {
    appStore.showLoading();
    showAddModal.value = true; // open modal
    
    // keep loader visible briefly
    await new Promise(resolve => setTimeout(resolve, 500));
  } catch (err) {
    console.error("Error preparing modal:", err);
  } finally {
    appStore.hideLoading();
  }
}

function closeAddModal() {
  showAddModal.value = false;
}


// Modal functions
function handleRowClick(user) {
  if (!canEditAddUsers.value) return;
  openModal(user);
}
async function openModal(user) {
  try {
    appStore.showLoading();
    const res = await api.get(`/users/display_user/${user.id}`);
    selectedUser.value = res.data.data;
    showModal.value = true;
  } catch (err) {
    console.error(err);
  } finally {
    appStore.hideLoading();
  }
}
function closeModal() {
  showModal.value = false;
  selectedUser.value = null;
}

// Reusable confirmation function
async function confirmAction(message) {
  alertMessage.value = message;
  alertType.value = "confirm";
  showAlert.value = true;

  const confirmed = await new Promise((resolve) => {
    alertResolve = resolve;
  });

  return confirmed;
}

// Save user
async function saveUser(updatedUser) {
  if (!canEditAddUsers.value) return;

  const index = users.value.findIndex((u) => u.id === updatedUser.id);
  if (index === -1) return;

  const originalUser = { ...users.value[index] };

  function flattenModules(modules, parent = null) {
    return modules.flatMap((m) => {
      const current = {
        module_name: m.module_name,
        parent_module: parent,
        can_view: Boolean(m.can_view),
        can_add: Boolean(m.can_add),
        can_edit: Boolean(m.can_edit),
        can_delete: Boolean(m.can_delete),
      };
      const children = m.children ? flattenModules(m.children, m.module_name) : [];
      return [current, ...children];
    });
  }

  const payload = {
    fullname: updatedUser.fullname,
    email: updatedUser.email,
    username: updatedUser.username,
    department: updatedUser.department,
    role: updatedUser.role,
    status: updatedUser.status,
    modules: flattenModules(updatedUser.modules || []),
  };

  const confirmed = await confirmAction(
    `Are you sure you want to update ${updatedUser.fullname}?`
  );
  if (!confirmed) return;

  try {
    appStore.showLoading();
    const res = await api.put(`/users/update_user/${updatedUser.id}`, payload);

    users.value[index] = res.data.data;
    alertMessage.value = res.data.message || "User updated successfully.";
    alertType.value = "alert";
    showAlert.value = true;

    closeModal();
  } catch (err) {
    users.value[index] = originalUser;
    alertMessage.value = err.response?.data?.message || "Failed to update user.";
    alertType.value = "alert";
    showAlert.value = true;
    closeModal();
    console.error(err);
  } finally {
    appStore.hideLoading();
  }
}

// Delete user
async function deleteUser(user) {
  if (!canDeleteUsers.value) return;

  const confirmed = await confirmAction(
    `Are you sure you want to delete ${user.fullname}?`
  );
  if (!confirmed) return;

  try {
    const res = await api.delete(`/users/delete_user/${user.id}`);
    users.value = users.value.filter((u) => u.id !== user.id);

    alertMessage.value = res.data?.message || "User deleted successfully.";
    alertType.value = "alert";
    showAlert.value = true;
  } catch (err) {
    alertMessage.value = "Failed to delete user.";
    alertType.value = "alert";
    showAlert.value = true;
    console.error(err);
  } finally {
    closeModal();
  }
}

// Handle responses from AlertModal
function handleAlertResponse(answer) {
  if (alertResolve) {
    alertResolve(answer);
    alertResolve = null;
  }
}

// Fetch users
async function fetchUsers() {
  appStore.showLoading();
  try {
    const res = await api.get("/users/display_user/");
    users.value = res.data.data;
  } catch (err) {
    console.error(err);
  } finally {
    appStore.hideLoading();
  }
}
// Filtering & pagination
const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value;
  const q = searchQuery.value.toLowerCase();
  return users.value.filter(
    (u) =>
      u.fullname.toLowerCase().includes(q) ||
      u.username.toLowerCase().includes(q) ||
      u.role.toLowerCase().includes(q) ||
      u.status.toLowerCase().includes(q) ||
      String(u.id).includes(q)
  );
});
const totalPages = computed(() =>
  Math.ceil(filteredUsers.value.length / pageSize)
);
const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  return filteredUsers.value.slice(start, start + pageSize);
});

// Pagination functions
function goToPage(page) {
  currentPage.value = page;
}
function nextPage() {
  if (currentPage.value < totalPages.value) currentPage.value++;
}
function prevPage() {
  if (currentPage.value > 1) currentPage.value--;
}
onMounted(() => fetchUsers());
</script>
