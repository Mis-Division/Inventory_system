<template>
  <div class="user-modal-backdrop">
    <div class="user-modal">
      <!-- Header -->
      <div class="modal-header">
        <h2>Add User Account's</h2>
      </div>

      <!-- Form Fields -->
      <div class="modal-body">
        <div class="form-grid">
          <!-- Full Name -->
          <div class="form-group">
            <label for="fullname">Full Name <span class="required">*</span></label>
            <input v-model="form.fullname" type="text" id="fullname" placeholder="Please Input Full Name"
              :class="{ 'error-input': errors.fullname }" required />
            <small v-if="errors.fullname" class="error-text">{{ errors.fullname }}</small>
          </div>

          <!-- Username -->
          <div class="form-group">
            <label for="username">Username <span class="required">*</span></label>
            <input v-model="form.username" type="text" id="username" placeholder="Please Input Username"
              :class="{ 'error-input': errors.username }" required />
            <small v-if="errors.username" class="error-text">{{ errors.username }}</small>
          </div>

          <!-- Email -->
          <div class="form-group">
            <label for="email">Email Address <span class="required">*</span></label>
            <input v-model="form.email" type="email" id="email" placeholder="Please Input Valid Email Address"
              :class="{ 'error-input': errors.email }" required />
            <small v-if="errors.email" class="error-text">{{ errors.email }}</small>
          </div>

          <!-- Department -->
          <div class="form-group">
            <label>Department <span class="required">*</span></label>
            <select v-model="form.department" id="dept" class="select-input"
              :class="{ 'error-input': errors.department }" required>
              <option value="">Please Select Department</option>
              <option value="Internal Service Department">Internal Service Department</option>
              <option value="Technical Support Department">Technical Support Department</option>
              <option value="Area Office Management Department">Area Office Management Department</option>
              <option value="Finance Service Department">Finance Service Department</option>
              <option value="Audit Service Department">Audit Service Department</option>
              <option value="Energy Trading Service Department">Energy Trading Service Department</option>
              <option value="Office of the General Manager">Office of the General Manager</option>
            </select>
            <small v-if="errors.department" class="error-text">{{ errors.department }}</small>
          </div>

          <!-- Role -->
          <div class="form-group">
            <label>Role <span class="required">*</span></label>
            <select v-model="form.role" id="role" class="select-input" :class="{ 'error-input': errors.role }" required>
              <option value="">Please Select Role</option>
              <option value="Administrator">Administrator</option>
              <option value="Warehouse Staff">Warehouse Staff</option>
              <option value="Manager">Manager</option>
              <option value="General Manager">General Manager</option>
            </select>
            <small v-if="errors.role" class="error-text">{{ errors.role }}</small>
          </div>

          <!-- Password -->
          <div class="form-group">
            <label for="password">Password <span class="required">*</span></label>
            <input v-model="form.password" type="password" id="password" :class="{ 'error-input': errors.password }"
              required />
            <small v-if="errors.password" class="error-text">{{ errors.password }}</small>
          </div>
        </div>
      </div>

      <!-- Module Access -->
      <div class="modal-header">
        <h2>Module Access <span class="required">*</span></h2>
      </div>

      <div>
        <table>
          <thead>
            <tr>
              <th style="width: 100%;">Description</th>
              <th style="width: 10%;">Add</th>
              <th style="width: 10%;">Edit</th>
              <th style="width: 10%;">View</th>
              <th style="width: 10%;">Delete</th>
            </tr>
          </thead>
          <tbody>
            <!-- Use flatModules instead of groupModules -->
            <tr v-for="item in flatModules" :key="item.node.module_name">
              <td :style="{ paddingLeft: item.depth * 20 + 'px' }">
                <strong v-if="!item.node.parent_module">{{ item.node.module_name }}</strong>
                <span v-else>— {{ item.node.module_name }}</span>
              </td>

              <td>
                <input type="checkbox" v-model="item.node.can_add"
                  @change="() => onPermissionChange(item.node, 'can_add')" />
              </td>
              <td>
                <input type="checkbox" v-model="item.node.can_edit"
                  @change="() => onPermissionChange(item.node, 'can_edit')" />
              </td>
              <td>
                <input type="checkbox" v-model="item.node.can_view"
                  @change="() => onPermissionChange(item.node, 'can_view')" />
              </td>
              <td>
                <input type="checkbox" v-model="item.node.can_delete"
                  @change="() => onPermissionChange(item.node, 'can_delete')" />
              </td>
            </tr>
          </tbody>
        </table>
        <small v-if="errors.modules" class="error-text">{{ errors.modules }}</small>

        <!-- Footer -->
        <div class="modal-footer">
          <button class="btn btn-gray" @click="$emit('close')">Close</button>
          <button class="btn btn-blue" @click="saveUser">CREATE</button>
        </div>
      </div>
    </div>
  </div>
</template>


<script setup>
import { reactive, computed } from "vue";
import { useAppStore } from "../stores/appStore";
import api from "../services/api";

const appStore = useAppStore();
const emit = defineEmits(["close"]); // ✅ emit close event to parent

// 🔹 Keep a reusable module template
const defaultModules = () => [
  { module_name: "Dashboard", parent_module: null, can_view: false, can_add: false, can_edit: false, can_delete: false },
  { module_name: "Inventory", parent_module: null, can_view: false, can_add: false, can_edit: false, can_delete: false },

  { module_name: "Material Requisition Voucher", parent_module: "Inventory", can_view: false, can_add: false, can_edit: false, can_delete: false },
  { module_name: "Material Charge Ticket", parent_module: "Inventory", can_view: false, can_add: false, can_edit: false, can_delete: false },

  { module_name: "Line Hardware", parent_module: "Material Charge Ticket", can_view: false, can_add: false, can_edit: false, can_delete: false },
  { module_name: "Special Hardware", parent_module: "Material Charge Ticket", can_view: false, can_add: false, can_edit: false, can_delete: false },
  { module_name: "Others", parent_module: "Material Charge Ticket", can_view: false, can_add: false, can_edit: false, can_delete: false },

  { module_name: "Material Salvage Ticket", parent_module: "Inventory", can_view: false, can_add: false, can_edit: false, can_delete: false },
  { module_name: "Memorandum Receipts", parent_module: "Inventory", can_view: false, can_add: false, can_edit: false, can_delete: false },

  { module_name: "Suppliers", parent_module: null, can_view: false, can_add: false, can_edit: false, can_delete: false },

  { module_name: "Reports", parent_module: null, can_view: false, can_add: false, can_edit: false, can_delete: false },
  { module_name: "Sample Reports", parent_module: "Reports", can_view: false, can_add: false, can_edit: false, can_delete: false },
  { module_name: "Demo Testing", parent_module: "Reports", can_view: false, can_add: false, can_edit: false, can_delete: false },

  { module_name: "Users", parent_module: null, can_view: false, can_add: false, can_edit: false, can_delete: false },
  { module_name: "List of Employee", parent_module: "Users", can_view: false, can_add: false, can_edit: false, can_delete: false },
  { module_name: "Add Users", parent_module: "Users", can_view: false, can_add: false, can_edit: false, can_delete: false },
];

// 🔹 Reactive form
const form = reactive({
  fullname: "",
  username: "",
  email: "",
  password: "",
  department: "",
  role: "",
  status: "Active",
  modules: defaultModules(),
});

const errors = reactive({
  fullname: "",
  username: "",
  email: "",
  password: "",
  department: "",
  role: "",
  modules: "",
});

// --------- TREE HELPERS ----------
function buildTree(modules, parent = null) {
  return modules
    .filter(m => m.parent_module === parent)
    .map(m => {
      m.children = buildTree(modules, m.module_name);
      return m;
    });
}

const treeRoot = computed(() => buildTree(form.modules, null));

const flatModules = computed(() => {
  const out = [];
  function walk(nodes, depth = 0) {
    nodes.forEach(n => {
      out.push({ node: n, depth });
      if (n.children && n.children.length) walk(n.children, depth + 1);
    });
  }
  walk(treeRoot.value, 0);
  return out;
});

function toggleChildren(node, permission) {
  if (!node) return;
  const newValue = !!node[permission];
  if (Array.isArray(node.children) && node.children.length) {
    node.children.forEach(child => {
      child[permission] = newValue;
      toggleChildren(child, permission);
    });
  }
}

function syncParentPermission(child, permission) {
  if (!child || !child.parent_module) return;
  const parent = form.modules.find(m => m.module_name === child.parent_module);
  if (!parent) return;

  if (child[permission]) {
    parent[permission] = true;
  } else {
    const siblings = form.modules.filter(m => m.parent_module === child.parent_module);
    const anyChecked = siblings.some(s => s[permission]);
    parent[permission] = anyChecked;
  }
  syncParentPermission(parent, permission);
}

function onPermissionChange(node, permission) {
  toggleChildren(node, permission);
  syncParentPermission(node, permission);
}

// --------- SAVE + RESET ----------
async function saveUser() {
  try {
    const payload = { ...form };
    await api.post("/users/create_user", payload);

    appStore.hideLoading();
    appStore.showAlert("User successfully created!", "success");

    setTimeout(() => {
      emit("close"); // ✅ tell parent to close modal
      resetForm();
    }, 1500);

  } catch (err) {
    appStore.hideLoading();
    console.error("❌ API error:", err.response?.data || err.message);
    appStore.showAlert("Failed to create user", "error");
  }
}

function resetForm() {
  form.fullname = "";
  form.username = "";
  form.email = "";
  form.password = "";
  form.department = "";
  form.role = "";
  form.status = "Active";
  form.modules = defaultModules(); // ✅ restore template instead of []
}
</script>


<style src="../assets/css/UserDetailsModal.css"></style>
