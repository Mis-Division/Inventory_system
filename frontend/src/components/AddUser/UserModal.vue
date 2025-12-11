<template>
  <!-- ===================== MODAL BACKDROP ===================== -->
  <div class="modal fade show" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);">

    <!-- ===================== MODAL CONTAINER ===================== -->
    <!-- custom-modal-position → dynamic spacing based on screen size -->
    <div class="modal-dialog modal-dialog-centered modal-l modal-auto-fit" role="document">

      <!-- ===================== MODAL CONTENT CARD ===================== -->
      <div class="modal-content">

        <!-- ===================== LOADING OVERLAY ===================== -->
        <div v-if="modalLoading" class="modal-loading-overlay d-flex justify-content-center align-items-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <!-- ===================== HEADER ===================== -->
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title fw-bold"><i class="bi bi-person-lines-fill me-2"></i> User Details</h5>
          <button type="button" class="btn-close white-close" @click="emit('close')"></button>
        </div>

        <!-- ===================== BODY : USER INFO ===================== -->
        <div class="modal-body">

          <!-- Section title -->
          <div class="section-title mb-3">
            <i class="bi bi-info-circle me-2"></i> Personal Information
          </div>

          <!-- 3 columns per row -->
          <div class="row g-3">

            <!-- Full Name -->
            <div class="col-md-4">
              <label class="form-label ">Full Name</label>
              <input v-model="localUser.fullname" type="text" class="form-control modern-input">
            </div>

            <!-- Username -->
            <div class="col-md-4">
              <label class="form-label">Username</label>
              <input v-model="localUser.username" type="text" class="form-control modern-input">
            </div>

            <!-- Email -->
            <div class="col-md-4">
              <label class="form-label ">Email Address</label>
              <input v-model="localUser.email" type="email" class="form-control modern-input">
            </div>

            <!-- Department -->
            <div class="col-md-4">
              <label class="form-label">Department</label>
              <select v-model="localUser.department" class="form-select modern-input">
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
              <label class="form-label ">Role</label>
              <select v-model="localUser.role" class="form-select modern-input">
                <option disabled value="">Select a Role</option>
                <option value="Administrator">Administrator</option>
                <option value="Warehouse Staff">Warehouse Staff</option>
                <option value="Manager">Manager</option>
                <option value="General Manager">General Manager</option>
              </select>
            </div>

            <!-- Status -->
            <div class="col-md-4">
              <label class="form-label ">Status</label>
              <select v-model="localUser.status" class="form-select modern-input">
                <option disabled value="">Select a Status</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>

          </div>
        </div>

        <!-- ===================== MODULE ACCESS SECTION ===================== -->
        <div>
          <div class="section-title border-top p-3">
            <i class="bi bi-shield-check me-2"></i> Module Access
          </div>

          <!-- Scrollable area for module access -->
          <div class="table_responsive"  ref="tableWrapper" style="max-height: 300px; overflow-y: auto;" >
            <table class="table align-middle text-center">
              <thead class="table-light sticky-header">
                <tr>
                  <th style="width: 50%;">Description</th>
                  <th class="text-center">Add</th>
                  <th class="text-center">Edit</th>
                  <th class="text-center">View</th>
                  <th class="text-center">Delete</th>
                </tr>
              </thead>

              <!-- ModuleAccess.vue renders rows recursively -->
              <tbody>
                <ModuleAccess v-for="module in localUser.modules" :key="module.module_name" :module="module"
                  :can-edit="canEdit" :can-delete="canDelete" @delete="handleModuleDelete" />
              </tbody>
            </table>
          </div>
        </div>

        <!-- ===================== FOOTER ===================== -->
        <div class="modal-footer modern-footer">
            <button class="btn btn-secondary modern-btn" @click="emit('close')">
            <i class="bi bi-x-circle me-1"></i> Close
          </button>
          <button class="btn btn-warning modern-btn" @click="confirmSave" :disabled="!canEdit">
            <i class="bi bi-pencil-square me-1"></i> Update
          </button>

        
        </div>

      </div>
    </div>

    <!-- Confirmation -->
    <AlertModal v-if="showConfirm" v-model="showConfirm" :message="confirmMessage" type="confirm"
      @response="handleConfirmResponse" />

    <!-- Notice -->
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
    if (!val) {
      localUser.value = {};
      return;
    }

    // Deep copy
    localUser.value = JSON.parse(JSON.stringify(val));

    // 🔥 Critical: Convert backend "1" / "0" strings → numeric 1 / 0
    if (localUser.value.modules) {
      normalizeModules(localUser.value.modules);
    }
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

function normalizeModules(modules) {
  modules.forEach(m => {
    m.can_add = Number(m.can_add);
    m.can_edit = Number(m.can_edit);
    m.can_view = Number(m.can_view);
    m.can_delete = Number(m.can_delete);

    if (m.children && m.children.length > 0) {
      normalizeModules(m.children);
    }
  });
}

</script>


<style scoped>
.modal-auto-fit {
  max-width: 70vw;
  width: auto;
  top: 5%;
}

.table-wrapper {
  max-height: 490px;
  overflow-y: auto;
  border: 1px solid #dee2e6;
  top: 15%;
}
</style>
