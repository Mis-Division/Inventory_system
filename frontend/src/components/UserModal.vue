<template>
  <div class="user-modal-backdrop">
    <div class="user-modal">
      <!-- Header -->
      <div class="modal-header">
        <h2>User Details</h2>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <div class="form-grid">
          <!-- Full Name -->
          <div class="form-group">
            <label>Full Name</label>
            <input v-model="localUser.fullname" type="text" />
          </div>

          <!-- Username -->
          <div class="form-group">
            <label>Username</label>
            <input v-model="localUser.username" type="text" />
          </div>

          <!-- Email -->
          <div class="form-group">
            <label>Email Address</label>
            <input v-model="localUser.email" type="text" />
          </div>

          <!-- Department Dropdown -->
          <div class="form-group">
            <label>Department</label>
            <select v-model="localUser.department" class="select-input">
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
          <!-- Role Dropdown -->
          <div class="form-group">
            <label>Role</label>
            <select v-model="localUser.role" class="select-input">
              <option disabled value="">Select a role</option>
              <option value="Administrator">Administrator</option>
              <option value="Warehouse Staff">Warehouse Staff</option>
              <option value="Manager">Manager</option>
              <option value="General Manager">General Manager</option>
            </select>
          </div>
          <!-- Status Dropdown -->
          <div class="form-group">
            <label>Status</label>
            <select v-model="localUser.status" class="select-input">
              <option disabled value="">Select a status</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Module Access -->
      <div class="modal-header">
        <h2>Module Access</h2>
      </div>
      <div>
        <!-- <div class="module-access">
             
              <div class="module-list">
                <ModuleAccess
                  v-for="module in localUser.modules"
                  :key="module.module_name"
                  :module="module"
                  :can-edit="canEdit"
                  :can-delete="canDelete"
                  @delete="handleModuleDelete"
                />
              </div>
            </div> -->
        <table >
          <thead>
            <tr>
              <th >Description</th>
              <th >Add</th>
              <th >Edit</th>
              <th >View</th>
              <th>Delete</th>
            </tr>
          </thead>
          <tbody>
            <ModuleAccess v-for="module in localUser.modules" :key="module.module_name" :module="module"
              :can-edit="canEdit" :can-delete="canDelete" @delete="handleModuleDelete" />
          </tbody>
        </table>

      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button class="btn btn-gray" @click="$emit('close')">Close</button>
        <button class="btn btn-blue" @click="$emit('save', localUser)" :disabled="!canEdit">Update</button>
        <button class="btn btn-red" @click="$emit('delete', localUser)" :disabled="!canDelete">Delete User</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from "vue";
import ModuleAccess from "./ModuleAccess.vue";
import "../assets/css/UserDetailsModal.css";

const props = defineProps({
  user: Object,
  canEdit: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false }
});

const emit = defineEmits(["close", "save", "delete"]);
const localUser = ref({});

// Deep copy user
watch(() => props.user, (val) => {
  localUser.value = val ? JSON.parse(JSON.stringify(val)) : {};
}, { immediate: true });

// Dropdown data


const roleOpen = ref(false);
const statusOpen = ref(false);
const deptOpen = ref(false);

const roleWrapper = ref(null);
const statusWrapper = ref(null);
const deptWrapper = ref(null);

function selectRole(r) { localUser.value.role = r; roleOpen.value = false; }
function selectStatus(s) { localUser.value.status = s; statusOpen.value = false; }
function selectDepartment(d) { localUser.value.department = d; deptOpen.value = false; }

// Close dropdown on outside click
function onWindowClick(e) {
  if (roleWrapper.value && !roleWrapper.value.contains(e.target)) roleOpen.value = false;
  if (statusWrapper.value && !statusWrapper.value.contains(e.target)) statusOpen.value = false;
  if (deptWrapper.value && !deptWrapper.value.contains(e.target)) deptOpen.value = false;
}

// Recursive delete for modules
function handleModuleDelete(moduleToRemove) {
  function recursiveRemove(modules) {
    return modules
      .filter(m => m.module_name !== moduleToRemove.module_name)
      .map(m => ({ ...m, children: m.children ? recursiveRemove(m.children) : [] }));
  }
  localUser.value.modules = recursiveRemove(localUser.value.modules);
}


</script>
