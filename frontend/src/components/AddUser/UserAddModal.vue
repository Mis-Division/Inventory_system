<template>
  <!-- Main Add User Modal -->
  <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
      <div class="modal-content">

        <!-- Header -->
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Add User Account</h5>
          <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
          <form class="row g-3">
            <!-- Full Name -->
            <div class="col-md-6">
              <label for="fullname" class="form-label">Full Name <span class="text-danger">*</span></label>
              <input v-model="form.fullname" type="text" id="fullname" class="form-control"
                placeholder="Please Input Full Name" :class="{ 'is-invalid': errors.fullname }" />
              <div v-if="errors.fullname" class="invalid-feedback">{{ errors.fullname }}</div>
            </div>

            <!-- Username -->
            <div class="col-md-6">
              <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
              <input v-model="form.username" type="text" id="username" class="form-control"
                placeholder="Please Input Username" :class="{ 'is-invalid': errors.username }" />
              <div v-if="errors.username" class="invalid-feedback">{{ errors.username }}</div>
            </div>

            <!-- Email -->
            <div class="col-md-6">
              <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
              <input v-model="form.email" type="email" id="email" class="form-control"
                placeholder="Please Input Valid Email Address" :class="{ 'is-invalid': errors.email }" />
              <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
            </div>

            <!-- Department -->
            <div class="col-md-6">
              <label for="dept" class="form-label">Department <span class="text-danger">*</span></label>
              <select v-model="form.department" id="dept" class="form-select"
                :class="{ 'is-invalid': errors.department }">
                <option value="">Please Select Department</option>
                <option value="Internal Service Department">Internal Service Department</option>
                <option value="Technical Support Department">Technical Support Department</option>
                <option value="Area Office Management Department">Area Office Management Department</option>
                <option value="Finance Service Department">Finance Service Department</option>
                <option value="Audit Service Department">Audit Service Department</option>
                <option value="Energy Trading Service Department">Energy Trading Service Department</option>
                <option value="Office of the General Manager">Office of the General Manager</option>
              </select>
              <div v-if="errors.department" class="invalid-feedback">{{ errors.department }}</div>
            </div>

            <!-- Role -->
            <div class="col-md-6">
              <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
              <select v-model="form.role" id="role" class="form-select" :class="{ 'is-invalid': errors.role }">
                <option value="">Please Select Role</option>
                <option value="Administrator">Administrator</option>
                <option value="Staff">Staff</option>
                <option value="Manager">Manager</option>
                <option value="General Manager">General Manager</option>
              </select>
              <div v-if="errors.role" class="invalid-feedback">{{ errors.role }}</div>
            </div>

            <!-- Password -->
            <div class="col-md-6">
              <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
              <input v-model="form.password" type="password" id="password" class="form-control"
                :class="{ 'is-invalid': errors.password }" />
              <div v-if="errors.password" class="invalid-feedback">{{ errors.password }}</div>
            </div>
          </form>

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
                  <tr v-for="item in flatModules" :key="item.node.module_name">
                    <td :style="{ paddingLeft: item.depth * 25 + 'px' }">
                      <strong style="padding-left: 10px;" v-if="!item.node.parent_module">{{ item.node.module_name
                      }}</strong>
                      <span v-else>— {{ item.node.module_name }}</span>
                    </td>
                    <td class="text-center">
                      <input type="checkbox" v-model="item.node.can_add"
                        @change="toggleChildren(item.node, 'can_add')" />
                    </td>
                    <td class="text-center">
                      <input type="checkbox" v-model="item.node.can_edit"
                        @change="toggleChildren(item.node, 'can_edit')" />
                    </td>
                    <td class="text-center">
                      <input type="checkbox" v-model="item.node.can_view"
                        @change="toggleChildren(item.node, 'can_view')" />
                    </td>
                    <td class="text-center">
                      <input type="checkbox" v-model="item.node.can_delete"
                        @change="toggleChildren(item.node, 'can_delete')" />
                    </td>
                  </tr>
                </tbody>
              </table>
              <div v-if="errors.modules" class="text-danger small mt-1">{{ errors.modules }}</div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button type="button" class="btn btn-warning" @click="closeModal">
            <i class="bi bi-x-circle me-1"></i> Close
          </button>
          <button type="button" class="btn btn-success" @click="handleCreateClick">
            <i class="bi bi-plus-circle me-1"></i> Save
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Confirmation Modal -->
  <div v-if="showConfirm" class="custom-modal-backdrop">
    <div class="custom-modal">
      <div class="custom-header bg-primary text-white">
        <h5 class="mb-0">Confirm Action</h5>
      </div>
      <div class="custom-body">
        <p>Are you sure you want to create this user?</p>
      </div>
      <div class="custom-footer">
        <button class="btn btn-secondary" @click="showConfirm = false">Cancel</button>
        <button class="btn btn-success" @click="confirmSave">Yes, Create</button>
      </div>
    </div>
  </div>

  <!-- Success Modal -->
  <div v-if="showSuccess" class="custom-modal-backdrop">
    <div class="custom-modal">
      <div class="custom-header bg-success text-white">
        <h5 class="mb-0">Success</h5>
      </div>
      <div class="custom-body text-center">
        ✅ User successfully created!
      </div>
      <div class="custom-footer text-center">
        <button class="btn btn-success" @click="closeSuccess">OK</button>
      </div>
    </div>
  </div>

  <!-- Error Modal -->
  <div v-if="showError" class="custom-modal-backdrop">
    <div class="custom-modal">
      <div class="custom-header bg-danger text-white">
        <h5 class="mb-0">Error</h5>
      </div>
      <div class="custom-body text-center">
        ❌ Failed to create user. Please try again.
      </div>
      <div class="custom-footer text-center">
        <button class="btn btn-danger" @click="showError = false">Close</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed } from "vue";
import api from "../../services/api";

const emit = defineEmits(["close"]);

const modalLoading = ref(false);
const showConfirm = ref(false);
const showSuccess = ref(false);
const showError = ref(false);

const form = reactive({
  fullname: "",
  username: "",
  email: "",
  password: "",
  department: "",
  role: "",
  status: "Active",
  modules: [],
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


function defaultModules() {
  return [
    { module_name: "Dashboard", parent_module: null, can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Inventory", parent_module: null, can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Stocks", parent_module: "Inventory", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Material Requisition Voucher", parent_module: "Inventory", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Material Charge Ticket", parent_module: "Inventory", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Line Hardware", parent_module: "Material Charge Ticket", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Special Hardware", parent_module: "Material Charge Ticket", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Others", parent_module: "Material Charge Ticket", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Material Salvage Ticket", parent_module: "Inventory", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Memorandum Receipts", parent_module: "Inventory", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Order", parent_module: null, can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Categories", parent_module: null, can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Suppliers", parent_module: null, can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Adjustment", parent_module: null, can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Reports", parent_module: null, can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "MRV Reports", parent_module: "Reports", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Line Hardware Reports", parent_module: "Reports", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Special Hardware Reports", parent_module: "Reports", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Others Reports", parent_module: "Reports", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "MST Reports", parent_module: "Reports", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "MR Reports", parent_module: "Reports", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Users", parent_module: null, can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "List of Employee", parent_module: "Users", can_view: false, can_add: false, can_edit: false, can_delete: false },
    { module_name: "Add Users", parent_module: "Users", can_view: false, can_add: false, can_edit: false, can_delete: false },
  ];
}

// Initialize modules
form.modules = defaultModules();

// --- TREE HELPERS ---
function buildTree(modules, parent = null) {
  return modules.filter(m => m.parent_module === parent).map(m => {
    m.children = buildTree(modules, m.module_name);
    return m;
  });
}

const treeRoot = computed(() => buildTree(form.modules));

const flatModules = computed(() => {
  const out = [];
  function walk(nodes, depth = 0) {
    nodes.forEach(n => {
      out.push({ node: n, depth });
      if (n.children?.length) walk(n.children, depth + 1);
    });
  }
  walk(treeRoot.value);
  return out;
});

function toggleChildren(node, permission) {
  const newValue = !!node[permission];
  if (node.children?.length) {
    node.children.forEach(child => {
      child[permission] = newValue;
      toggleChildren(child, permission);
    });
  }
}

// --- VALIDATION ---
function validateForm() {
  errors.fullname = form.fullname ? "" : "Full name is required";
  errors.username = form.username ? "" : "Username is required";
  errors.email = form.email ? "" : "Email is required";
  errors.password = form.password ? "" : "Password is required";
  errors.department = form.department ? "" : "Department is required";
  errors.role = form.role ? "" : "Role is required";
  const anyPermission = form.modules.some(m => m.can_add || m.can_edit || m.can_view || m.can_delete);
  errors.modules = anyPermission ? "" : "At least one module permission must be selected";
  return Object.values(errors).every(e => !e);
}

// --- CONFIRMATION LOGIC ---
function handleCreateClick() {
  if (!validateForm()) return;
  showConfirm.value = true;
}

async function confirmSave() {
  showConfirm.value = false;
  if (!validateForm()) return;
  try {
    modalLoading.value = true;
    const payload = { ...form };
    await api.post("/users/create_user", payload);
    showSuccess.value = true;
  } catch (err) {
    showError.value = true;
  } finally {
    modalLoading.value = false;
  }
}

function closeSuccess() {
  showSuccess.value = false;
  resetForm();
  emit("close");
}

function resetForm() {
  Object.assign(form, {
    fullname: "",
    username: "",
    email: "",
    password: "",
    department: "",
    role: "",
    status: "Active",
    modules: defaultModules(),
  });
}

function closeModal() {
  resetForm();
  emit("close");
}
</script>

<style scoped>
.table-wrapper {
  max-height: 390px;
  overflow-y: auto;
  border: 1px solid #dee2e6;
}

/* --- SHARED MODAL STYLE --- */
.custom-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  animation: fadeIn 0.2s ease;
}

.custom-modal {
  background: #fff;
  border-radius: 10px;
  width: 380px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  overflow: hidden;
  animation: scaleUp 0.25s ease;
}

.custom-header {
  padding: 12px 16px;
  font-weight: 600;
}

.custom-body {
  padding: 20px;
  font-size: 15px;
}

.custom-footer {
  padding: 12px 16px;
  display: flex;
  justify-content: center;
  gap: 10px;
}

/* --- ANIMATIONS --- */
@keyframes fadeIn {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}

@keyframes scaleUp {
  from {
    transform: scale(0.9);
    opacity: 0;
  }

  to {
    transform: scale(1);
    opacity: 1;
  }
}
</style>
