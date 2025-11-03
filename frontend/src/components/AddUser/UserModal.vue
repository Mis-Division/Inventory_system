<template>
  <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
      <div class="modal-content position-relative">

        <!-- Loading overlay -->
        <div v-if="modalLoading" class="modal-loading-overlay d-flex justify-content-center align-items-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <!-- Header -->
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-bold">User Details</h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <div class="row g-3">
            <!-- Full Name -->
            <div class="col-md-4">
              <label class="form-label">Full Name</label>
              <input v-model="localUser.fullname" type="text" class="form-control" />
            </div>

            <!-- Username -->
            <div class="col-md-4">
              <label class="form-label">Username</label>
              <input v-model="localUser.username" type="text" class="form-control" />
            </div>

            <!-- Email -->
            <div class="col-md-4">
              <label class="form-label">Email Address</label>
              <input v-model="localUser.email" type="email" class="form-control" />
            </div>

            <!-- Department -->
            <div class="col-md-4">
              <label class="form-label">Department</label>
              <select v-model="localUser.department" class="form-select">
                <option disabled value="">Select a Department</option>
                <option value="Internal Service Department">Internal Service Department</option>
                <option value="Technical Support Department">Technical Support Department</option>
                <option value="Area Office Management Department">Area Office Management Department</option>
                <option value="Finance Service Department">Finance Service Department</option>
                <option value="Audit Service Department">Audit Service Department</option>
                <option value="Energy Trading Service Department">Energy Trading Service Department</option>
                <option value="Office of the General Manager">Office of the General Manager</option>
              </select>
            </div>

            <!-- Role -->
            <div class="col-md-4">
              <label class="form-label">Role</label>
              <select v-model="localUser.role" class="form-select">
                <option disabled value="">Select a Role</option>
                <option value="Administrator">Administrator</option>
                <option value="Warehouse Staff">Warehouse Staff</option>
                <option value="Manager">Manager</option>
                <option value="General Manager">General Manager</option>
              </select>
            </div>

            <!-- Status -->
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select v-model="localUser.status" class="form-select">
                <option disabled value="">Select a Status</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Module Access -->
        <div class="mt-4">
          <div class="modal-header bg-light border-top">
            <h5 class="modal-title fw-bold">Module Access</h5>
          </div>

          <div class="table-wrapper">
            <table class="table table-bordered align-middle module-access-table mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 50%;">Description</th>
                  <th class="text-center" style="width: 10%;">Add</th>
                  <th class="text-center" style="width: 10%;">Edit</th>
                  <th class="text-center" style="width: 10%;">View</th>
                  <th class="text-center" style="width: 10%;">Delete</th>
                </tr>
              </thead>
              <tbody>
                <ModuleAccess v-for="module in localUser.modules" :key="module.module_name" :module="module"
                  :can-edit="canEdit" :can-delete="canDelete" @delete="handleModuleDelete" />
              </tbody>
            </table>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          
          <div class="d-flex gap-2">
            <button class="btn btn-warning" @click="confirmSave" :disabled="!canEdit">
              <i class="bi bi-pencil-square"></i> Update
            </button>
            <!-- <button class="btn btn-danger" @click="confirmDelete" :disabled="!canDelete">
              <i class="bi bi-trash"></i> Delete User
            </button> -->

            <button class="btn btn-primary" @click="$emit('close')">
            <i class="bi bi-x-circle"></i> Close
          </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <AlertModal v-if="showConfirm" v-model="showConfirm" :message="confirmMessage" type="confirm"
      @response="handleConfirmResponse" />

    <!-- Alert Modal (Success/Error) -->
    <AlertModal v-if="showAlert" v-model="showAlert" :message="alertMessage" :type="alertType" />
  </div>
</template>
<script setup>
import { ref, watch } from "vue";
import api from "../../services/api";
import { useAppStore } from "../../stores/appStore";
import ModuleAccess from "./ModuleAccess.vue";
import AlertModal from "./AlertModal.vue";

const appStore = useAppStore();

const props = defineProps({
  user: Object,
  canEdit: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false }
});

const emit = defineEmits(["close", "updated", "deleted"]);

const localUser = ref({});
const modalLoading = ref(false);

// Confirm modal
const showConfirm = ref(false);
const confirmMessage = ref("");
let confirmActionType = null;

// Alert modal
const showAlert = ref(false);
const alertMessage = ref("");
const alertType = ref("success");

// Deep copy user
watch(
  () => props.user,
  val => {
    localUser.value = val ? JSON.parse(JSON.stringify(val)) : {};
  },
  { immediate: true }
);

// Recursive delete for modules
function handleModuleDelete(moduleToRemove) {
  function recursiveRemove(modules) {
    return modules
      .filter(m => m.module_name !== moduleToRemove.module_name)
      .map(m => ({
        ...m,
        children: m.children ? recursiveRemove(m.children) : []
      }));
  }
  localUser.value.modules = recursiveRemove(localUser.value.modules);
}

// === CONFIRMATION LOGIC ===
function confirmSave() {
  confirmMessage.value = `Are you sure you want to update ${localUser.value.fullname}?`;
  confirmActionType = "save";
  showConfirm.value = true;
}

function confirmDelete() {
  confirmMessage.value = `Are you sure you want to delete ${localUser.value.fullname}?`;
  confirmActionType = "delete";
  showConfirm.value = true;
}

async function handleConfirmResponse(confirmed) {
  if (!confirmed) return;

  if (confirmActionType === "save") await updateUser();
  else if (confirmActionType === "delete") await deleteUser();

  confirmActionType = null;
  showConfirm.value = false;
}

// === UPDATE USER ===
async function updateUser() {
  try {
    modalLoading.value = true;

    const payload = {
      fullname: localUser.value.fullname,
      email: localUser.value.email,
      username: localUser.value.username,
      department: localUser.value.department,
      role: localUser.value.role,
      status: localUser.value.status,
      modules: flattenModules(localUser.value.modules || [])
    };

    const res = await api.put(`/users/update_user/${localUser.value.user_id}`, payload);
    alertMessage.value = res.data?.message || "User updated successfully.";
    alertType.value = "success";
    showAlert.value = true;
    emit("updated", res.data?.data || localUser.value);

    // ⏳ Wait a bit before closing to show success message
    setTimeout(() => {
      showAlert.value = false;
      emit("close"); // 👈 Close modal after save
    }, 1200);
  } catch (err) {
    alertMessage.value = err.response?.data?.message || "Failed to update user.";
    alertType.value = "error";
    showAlert.value = true;
  } finally {
    modalLoading.value = false;
  }
}

// === DELETE USER ===
async function deleteUser() {
  try {
    modalLoading.value = true;
    const res = await api.delete(`/users/delete_user/${localUser.value.user_id}`);
    alertMessage.value = res.data?.message || "User deleted successfully.";
    alertType.value = "success";
    showAlert.value = true;
    emit("deleted", localUser.value);

    // ⏳ Wait a bit before closing to show success message
    setTimeout(() => {
      showAlert.value = false;
      emit("close");
    }, 1200);
  } catch (err) {
    alertMessage.value = err.response?.data?.message || "Failed to delete user.";
    alertType.value = "error";
    showAlert.value = true;
  } finally {
    modalLoading.value = false;
  }
}

// === UTILITIES ===
function flattenModules(modules, parent = null) {
  return modules.flatMap(m => {
    const current = {
      module_name: m.module_name,
      parent_module: parent,
      can_view: !!m.can_view,
      can_add: !!m.can_add,
      can_edit: !!m.can_edit,
      can_delete: !!m.can_delete
    };
    const children = m.children ? flattenModules(m.children, m.module_name) : [];
    return [current, ...children];
  });
}
</script>


<style scoped>
.table-wrapper {
  max-height: 490px;
  overflow-y: auto;
  border: 1px solid #dee2e6;
}

.module-access-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

.module-access-table thead th {
  position: sticky;
  top: 0;
  background-color: #f8f9fa;
  z-index: 2;
}

.module-access-table th:nth-child(n+2),
.module-access-table td:nth-child(n+2) {
  text-align: center;
  vertical-align: middle;
}

.module-access-table input[type="checkbox"] {
  transform: scale(1.1);
  cursor: pointer;
  margin: 0;
}

.table-wrapper::-webkit-scrollbar {
  width: 8px;
}

.table-wrapper::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 4px;
}

.table-wrapper:hover::-webkit-scrollbar-thumb {
  background: #999;
}

.modal-loading-overlay {
  position: absolute;
  inset: 0;
  background-color: rgba(255, 255, 255, 0.7);
  z-index: 1055;
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
