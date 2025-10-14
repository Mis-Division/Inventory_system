<template>
  <aside class="sidebar bg-light border-end vh-100 p-3 mt-5">
    <nav class="nav flex-column gap-1">

      <!-- Dashboard -->
      <router-link to="/dashboard" class="nav-link d-flex align-items-center mb-2"
        :class="{ 'disabled': !hasAccess('Dashboard') }">

        <span><i class="bi bi-house me-2"></i> Dashboard</span>
      </router-link>

      <!-- Inventory -->
      <div class="mb-2">
        <button class="btn btn-toggle w-100 text-start d-flex align-items-center justify-content-between" type="button"
          @click="toggle('Inventory')" :class="{ 'disabled': !hasAccess('Inventory') }">

          <span> <i class="bi bi-list text-black me-2"></i> Inventory</span>
          <i v-if="hasAccess('Inventory')" class="bi bi-chevron-right transition"
            :class="{ 'rotate-90': expanded.includes('Inventory') }"></i>
        </button>

        <!-- Inventory submenu -->
        <ul v-show="expanded.includes('Inventory')" class="list-unstyled ms-3 mt-">
          <li>
            <router-link to="/dashboard/stocks" class="nav-link d-flex align-items-center"
              :class="{ 'disabled': !hasAccess('Stocks') }">

              <span><i class="bi bi-box-seam me-2"></i> Stocks</span>
            </router-link>
          </li>
          <li>
            <router-link to="/dashboard/mrv" class="nav-link d-flex align-items-center"
              :class="{ 'disabled': !hasAccess('Material Requisition Voucher') }">

              <span><i class="bi bi-ticket-perforated me-2"></i> Materials Requisition Voucher</span>
            </router-link>
          </li>

          <!-- MCT -->
          <li class="mt-1">
            <button class="btn btn-toggle w-100 text-start d-flex align-items-center justify-content-between"
              @click="toggle('MCT')" :class="{ 'disabled': !hasAccess('Material Charge Ticket') }">

              <span> <i class="bi bi-ticket me-2"></i>Material Charge Ticket</span>
              <i v-if="hasAccess('Material Charge Ticket')" class="bi bi-chevron-right transition"
                :class="{ 'rotate-90': expanded.includes('MCT') }"></i>
            </button>

            <!-- MCT submenu -->
            <ul v-show="expanded.includes('MCT')" class="list-unstyled ms-4 mt-1">
              <li>
                <router-link to="/dashboard/LineHardware" class="nav-link d-flex align-items-center"
                  :class="{ 'disabled': !hasAccess('Line Hardware') }">

                  <span><i class="bi bi-tools me-2"></i>Line Hardware</span>
                </router-link>
              </li>
              <li>
                <router-link to="/dashboard/specialhardware" class="nav-link d-flex align-items-center"
                  :class="{ 'disabled': !hasAccess('Special Hardware') }">

                  <span> <i class="bi bi-lightning me-2"></i> Special Hardware</span>
                </router-link>
              </li>
              <li>
                <router-link to="/dashboard/others" class="nav-link d-flex align-items-center"
                  :class="{ 'disabled': !hasAccess('Others') }">

                  <span><i class="bi bi-question-circle me-2"></i> Others</span>
                </router-link>
              </li>
            </ul>
          </li>

          <!-- MST -->
          <li class="mt-1">
            <router-link to="/dashboard/mst" class="nav-link d-flex align-items-center"
              :class="{ 'disabled': !hasAccess('Material Salvage Ticket') }">
              <span><i class="bi-exclamation-triangle me-2"></i>Material Salvage Ticket</span>
            </router-link>
          </li>

          <!-- MR -->
          <li class="mt-1">
            <router-link to="/dashboard/mr" class="nav-link d-flex align-items-center"
              :class="{ 'disabled': !hasAccess('Memorandum Receipts') }">

              <span><i class="bi bi-clipboard me-2"></i> Memorandum Receipt</span>
            </router-link>
          </li>
        </ul>
      </div>

      <!--OrderItem-->
      <router-link to="/dashboard/order" class="nav-link d-flex align-items-center mb-2"
        :class="{ 'disabled': !hasAccess('Order') }">
        <span> <i class="bi bi-cart me-2"></i>Purchase Order</span>
      </router-link>
      <!-- Catergory-->
      <router-link to="/dashboard/category" class="nav-link d-flex align-items-center mb-2"
        :class="{ 'disabled': !hasAccess('Categories') }">
       <span> <i class="bi bi-info-lg me-2"></i>Items</span>
      </router-link>
      <!-- Supplier -->
      <router-link to="/dashboard/supplier" class="nav-link d-flex align-items-center mb-2"
        :class="{ 'disabled': !hasAccess('Suppliers') }">
        <span> <i class="bi bi-person-badge me-2"></i>Supplier</span>
      </router-link>
      <!--Adjusment-->
      <router-link to="/dashboard/adjustment" class="nav-link d-flex align-items-center mb-2"
        :class="{ 'disabled': !hasAccess('Adjustment') }">
        <span> <i class="bi bi-arrow-repeat me-2"></i>Adjustments</span>
      </router-link>
      <!-- Reports -->
      <div class="mb-2">
        <button class="btn btn-toggle w-100 text-start d-flex align-items-center justify-content-between" type="button"
          @click="toggle('Reports')" :class="{ 'disabled': !hasAccess('Reports') }">
          <span><i class="bi bi-clipboard-data me-2"></i>Reports</span>

          <i v-if="hasAccess('Reports')" class="bi bi-chevron-right transition"
            :class="{ 'rotate-90': expanded.includes('Reports') }"></i>
        </button>

        <ul v-show="expanded.includes('Reports')" class="list-unstyled ms-4 mt-2">
          <li>
            <router-link to="/dashboard/sample-reports" class="nav-link d-flex align-items-center"
              :class="{ 'disabled': !hasAccess('MRV Reports') }">
              <span><i class="bi bi-file-earmark-text me-2"></i> MRV</span>
            </router-link>
          </li>
          <li>
            <router-link to="/dashboard/reports_line" class="nav-link d-flex align-items-center"
              :class="{ 'disabled': !hasAccess('Line Hardware Reports') }">
              <span><i class="bi bi-tools me-2"></i> Line Hardware</span>
            </router-link>
          </li>
          <li>
            <router-link to="/dashboard/reports_Special" class="nav-link d-flex align-items-center"
              :class="{ 'disabled': !hasAccess('Special Hardware Reports') }">
              <span><i class="bi bi-lightning me-2"></i> Special Hardware</span>
            </router-link>
          </li>
          <li>
            <router-link to="/dashboard/reports_Others" class="nav-link d-flex align-items-center"
              :class="{ 'disabled': !hasAccess('Others Reports') }">
              <span><i class="bi bi-question-circle me-2"></i> Others</span>
            </router-link>
          </li>
          <li>
            <router-link to="/dashboard/reports_salvage" class="nav-link d-flex align-items-center"
              :class="{ 'disabled': !hasAccess('MST Reports') }">
              <span><i class="bi-exclamation-triangle me-2"></i> Salvage Ticket</span>
            </router-link>
          </li>
          <li>
            <router-link to="/dashboard/reports_memo" class="nav-link d-flex align-items-center"
              :class="{ 'disabled': !hasAccess('MR Reports') }">
              <span><i class="bi bi-clipboard me-2"></i> Memorandum Receipt</span>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- Users -->
      <div class="mb-2">
        <button class="btn btn-toggle w-100 text-start d-flex align-items-center justify-content-between"
          @click="toggle('Users')" :class="{ 'disabled': !hasAccess('Users') }">

          <span> <i class="bi bi-people me-2"></i> Users</span>
          <i v-if="hasAccess('Users')" class="bi bi-chevron-right transition"
            :class="{ 'rotate-90': expanded.includes('Users') }"></i>
        </button>

        <ul v-show="expanded.includes('Users')" class="list-unstyled ms-4 mt-2">
          <li>
            <router-link to="/dashboard/user" class="nav-link d-flex align-items-center"
              :class="{ 'disabled': !hasAccess('Add Users') }">
              <span><i class="bi bi-person-plus me-2"></i> Add Users</span>

            </router-link>
          </li>
          <li>
            <router-link to="/dashboard/employees/" class="nav-link d-flex align-items-center"
              :class="{ 'disabled': !hasAccess('List of Employee') }">
              <span><i class="bi bi-person-badge me-2"></i>List of Employee</span>
            </router-link>
          </li>

        </ul>

      </div>
      <!-- <div class="mb-2">
        <button @click="userStore.logout" class="sidebar-item flex  rounded hover:bg-blue-100 text-gray-700">
          <span> <i class="bi bi-power me-2"></i> Logout</span>
        </button>
      </div> -->
      <!-- Logout -->


    </nav>
  </aside>
</template>

<style scoped>
.sidebar-item {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  /* same as sidebar menu */
  color: #374151;
  /* same text color as sidebar links */
  background-color: transparent;
  transition: background-color 0.2s ease;
  cursor: pointer;
}

.sidebar-item:hover {
  background-color: #3a7aba;
  /* match sidebar hover */
}



.nav-link {
  color: black;
  text-decoration: none;

}

.nav-link.disabled {
  color: gray;
  opacity: 0.4;
  cursor: not-allowed;
}

.nav-link:active {
  background-color: #81a00e;
  border-radius: 6px;
  color: black;
}

.active-link i {
  color: rgb(141, 45, 45);
}

.nav-link:hover {
  background-color: #3a7aba;
  border-radius: 6px;
  color: black;
}

.sidebar {
  width: 300px;
  overflow-y: auto;
  position: fixed;
  left: 0;
  top: 0;
  border: 2px;
  font-size: 15px;
}

.icon-lg {
  width: 24px;
  height: 24px;
}

.icon-sm {
  width: 18px;
  height: 18px;
}

.icon-xs {
  width: 16px;
  height: 16px;
}

.btn-toggle {
  background: none;
  border: none;
  color: #3a7aba;

  padding: 0.5rem 0.75rem;
  transition: background-color 0.3s;
}

.btn-toggle:hover {
  background-color: #3a7aba;
  border-radius: 6px;
}

.transition {
  transition: transform 0.3s ease;
}

.rotate-90 {
  transform: rotate(90deg);
}

/* Scrollbar styling (optional for aesthetic) */
.sidebar::-webkit-scrollbar {
  width: 8px;
}

.sidebar::-webkit-scrollbar-thumb {
  background-color: #3b3d3e;
  border-radius: 4px;
}

span {
  font-weight: 600;
  font-size: 17px;
  color: black;
}
</style>



<script setup>
import { ref } from "vue";
import { userStore } from "../stores/userStore";


const expanded = ref([]);

function toggle(name) {
  if (expanded.value.includes(name)) {
    expanded.value = expanded.value.filter((n) => n !== name);
  } else {
    expanded.value.push(name);
  }
}

const hasAccess = (moduleName) => {
  return (
    userStore.user?.modules?.some(
      (m) =>
        m.module_name?.toLowerCase().trim() === moduleName.toLowerCase().trim() &&
        Number(m.can_view) === 1
    ) || false
  );
};
</script>
