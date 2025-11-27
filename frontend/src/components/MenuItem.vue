<template>
  <aside class="sidebar bg-light border-end vh-100 p-3 mt-5">
    <nav class="nav flex-column gap-1">

      <!-- Dashboard -->
      <router-link 
        v-if="hasAccess('Dashboard')" 
        to="/dashboard" 
        class="nav-link d-flex align-items-center mb-2">
        <span><i class="bi bi-house me-2"></i> Dashboard</span>
      </router-link>

      <!-- Inventory -->
      <div v-if="hasAccess('Inventory')" class="mb-2">
        <button class="btn btn-toggle w-100 text-start d-flex align-items-center justify-content-between" type="button"
          @click="toggle('Inventory')">

          <span> <i class="bi bi-list text-black me-2"></i> Inventory</span>
          <i class="bi bi-chevron-right transition"
            :class="{ 'rotate-90': expanded.includes('Inventory') }"></i>
        </button>

        <!-- Inventory submenu -->
        <ul v-show="expanded.includes('Inventory')" class="list-unstyled ms-3 mt-1">
          <li v-if="hasAccess('Stocks')">
            <router-link to="/dashboard/stocks" class="nav-link d-flex align-items-center">
              <span><i class="bi bi-box-seam me-2"></i> Stocks</span>
            </router-link>
          </li>

          <li v-if="hasAccess('Material Requisition Voucher')">
            <router-link to="/dashboard/mrv" class="nav-link d-flex align-items-center">
              <span><i class="bi bi-ticket-perforated me-2"></i> Materials Requisition Voucher</span>
            </router-link>
          </li>

          <!-- MCT -->
          <li v-if="hasAccess('Material Charge Ticket')" class="mt-1">
            <button class="btn btn-toggle w-100 text-start d-flex align-items-center justify-content-between"
              @click="toggle('MCT')">
              <span><i class="bi bi-ticket me-2"></i>Material Charge Ticket</span>
              <i class="bi bi-chevron-right transition"
                :class="{ 'rotate-90': expanded.includes('MCT') }"></i>
            </button>

            <!-- MCT submenu -->
            <ul v-show="expanded.includes('MCT')" class="list-unstyled ms-4 mt-1">
              <li v-if="hasAccess('Line Hardware')">
                <router-link to="/dashboard/LineHardware" class="nav-link d-flex align-items-center">
                  <span><i class="bi bi-tools me-2"></i>Line Hardware</span>
                </router-link>
              </li>
              <li v-if="hasAccess('Special Hardware')">
                <router-link to="/dashboard/specialhardware" class="nav-link d-flex align-items-center">
                  <span><i class="bi bi-lightning me-2"></i>Special Hardware</span>
                </router-link>
              </li>
              <li v-if="hasAccess('Others')">
                <router-link to="/dashboard/others" class="nav-link d-flex align-items-center">
                  <span><i class="bi bi-question-circle me-2"></i>Others</span>
                </router-link>
              </li>
            </ul>
          </li>

          <li v-if="hasAccess('Material Salvage Ticket')" class="mt-1">
            <router-link to="/dashboard/mst" class="nav-link d-flex align-items-center">
              <span><i class="bi-exclamation-triangle me-2"></i>Material Salvage Ticket</span>
            </router-link>
          </li>

          <li v-if="hasAccess('Memorandum Receipts')" class="mt-1">
            <router-link to="/dashboard/mr" class="nav-link d-flex align-items-center">
              <span><i class="bi bi-clipboard me-2"></i>Memorandum Receipt</span>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- Purchase Order -->
      <router-link v-if="hasAccess('Order')" to="/dashboard/order" class="nav-link d-flex align-items-center mb-2">
        <span><i class="bi bi-cart me-2"></i>Purchase Order</span>
      </router-link>

      <!-- Receiving Order -->
      <router-link v-if="hasAccess('receiving_Order')" to="/dashboard/receiving" class="nav-link d-flex align-items-center mb-2">
        <span><i class="bi bi-receipt me-2"></i>Receiving Order</span>
      </router-link>

      <!-- Category -->
      <router-link v-if="hasAccess('Categories')" to="/dashboard/item" class="nav-link d-flex align-items-center mb-2">
        <span><i class="bi bi-info-lg me-2"></i>Items</span>
      </router-link>

      <!-- Supplier -->
      <router-link v-if="hasAccess('Suppliers')" to="/dashboard/supplier" class="nav-link d-flex align-items-center mb-2">
        <span><i class="bi bi-person-badge me-2"></i>Supplier</span>
      </router-link>

      <!-- Adjustment -->
      <router-link v-if="hasAccess('Adjustment')" to="/dashboard/adjustment" class="nav-link d-flex align-items-center mb-2">
        <span><i class="bi bi-arrow-repeat me-2"></i>Adjustments</span>
      </router-link>

      <!-- Reports -->
      <div v-if="hasAccess('Reports')" class="mb-2">
        <button class="btn btn-toggle w-100 text-start d-flex align-items-center justify-content-between" type="button"
          @click="toggle('Reports')">
          <span><i class="bi bi-clipboard-data me-2"></i>Reports</span>
          <i class="bi bi-chevron-right transition"
            :class="{ 'rotate-90': expanded.includes('Reports') }"></i>
        </button>

        <ul v-show="expanded.includes('Reports')" class="list-unstyled ms-4 mt-2">
          <li v-if="hasAccess('MRV Reports')">
            <router-link to="/dashboard/sample-reports" class="nav-link d-flex align-items-center">
              <span><i class="bi bi-file-earmark-text me-2"></i> MRV</span>
            </router-link>
          </li>
          <li v-if="hasAccess('Line Hardware Reports')">
            <router-link to="/dashboard/reports_line" class="nav-link d-flex align-items-center">
              <span><i class="bi bi-tools me-2"></i> Line Hardware</span>
            </router-link>
          </li>
          <li v-if="hasAccess('Special Hardware Reports')">
            <router-link to="/dashboard/reports_Special" class="nav-link d-flex align-items-center">
              <span><i class="bi bi-lightning me-2"></i> Special Hardware</span>
            </router-link>
          </li>
          <li v-if="hasAccess('Others Reports')">
            <router-link to="/dashboard/reports_Others" class="nav-link d-flex align-items-center">
              <span><i class="bi bi-question-circle me-2"></i> Others</span>
            </router-link>
          </li>
          <li v-if="hasAccess('MST Reports')">
            <router-link to="/dashboard/reports_salvage" class="nav-link d-flex align-items-center">
              <span><i class="bi-exclamation-triangle me-2"></i> Salvage Ticket</span>
            </router-link>
          </li>
          <li v-if="hasAccess('MR Reports')">
            <router-link to="/dashboard/reports_memo" class="nav-link d-flex align-items-center">
              <span><i class="bi bi-clipboard me-2"></i> Memorandum Receipt</span>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- Administration -->
      <div v-if="hasAccess('Users')" class="mb-2">
        <button class="btn btn-toggle w-100 text-start d-flex align-items-center justify-content-between"
          @click="toggle('Users')">
          <span><i class="bi bi-people me-2"></i> ADMINISTRATION</span>
          <i class="bi bi-chevron-right transition"
            :class="{ 'rotate-90': expanded.includes('Users') }"></i>
        </button>

        <ul v-show="expanded.includes('Users')" class="list-unstyled ms-4 mt-2">
          <li v-if="hasAccess('Add Users')">
            <router-link to="/dashboard/user" class="nav-link d-flex align-items-center">
              <span><i class="bi bi-person-plus me-2"></i> Add Users</span>
            </router-link>
          </li>
        </ul>

        <!-- <ul v-show="expanded.includes('Users')" class="list-unstyled ms-4 mt-2">
          <li v-if="hasAccess('Add Users')">
            <router-link to="/dashboard/user" class="nav-link d-flex align-items-center">
              <span><i class="bi bi-person-plus me-2"></i> Employee</span>
            </router-link>
          </li>
        </ul> -->
      </div>

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
