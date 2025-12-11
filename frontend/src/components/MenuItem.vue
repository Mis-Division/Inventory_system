<template>
  <aside class="sidebar bg-light border-end">
    <nav class="nav flex-column gap-1">

      <!-- Dashboard -->
      <router-link v-if="hasAccess('Dashboard')" 
        to="/dashboard" 
        class="nav-item-link">
        <i class="bi bi-house-door me-1 icon"></i>
        <span>Dashboard</span>
      </router-link>

      <!-- INVENTORY (DROPDOWN) -->
      <div v-if="hasAccess('Inventory')" class="nav-section">
        <div class="nav-header" @click="toggle('inventory')">
          <div class="left">
            <i class="bi bi-list me-1 icon"></i>
            <span>Inventory</span>
          </div>
          <i class="bi caret" 
             :class="expanded.includes('inventory') ? 'bi-caret-up-fill' : 'bi-caret-down-fill'">
          </i>
        </div>

        <ul v-show="expanded.includes('inventory')" class="submenu">

          <li v-if="hasAccess('Stocks')">
            <router-link to="/dashboard/stocks" class="nav-item-link">
              <i class="bi bi-box-seam me-1 icon"></i> Stocks
            </router-link>
          </li>

          <li v-if="hasAccess('Material Requisition Voucher')">
            <router-link to="/dashboard/mrv" class="nav-item-link">
              <i class="bi bi-ticket-perforated me-1 icon"></i> Material Requisition Vouchers
            </router-link>
          </li>

          <!-- MATERIAL CHARGE TICKET (NESTED DROPDOWN) -->
          <li v-if="hasAccess('Material Charge Ticket')" class="nested-section">

            <div class="nav-header nested" @click="toggle('mct')">
              <div class="left">
                <i class="bi bi-ticket me-1 icon"></i>
                <span>Material Charge Ticket</span>
              </div>
              <i class="bi caret"
                 :class="expanded.includes('mct') ? 'bi-caret-up-fill' : 'bi-caret-down-fill'">
              </i>
            </div>

            <ul v-show="expanded.includes('mct')" class="submenu nested">

              <li v-if="hasAccess('Line Hardware')">
                <router-link to="/dashboard/LineHardware" class="nav-item-link">
                  <i class="bi bi-tools me-1 icon"></i> Line Hardware
                </router-link>
              </li>

              <li v-if="hasAccess('Special Hardware')">
                <router-link to="/dashboard/specialhardware" class="nav-item-link">
                  <i class="bi bi-lightning me-1 icon"></i> Special Hardware
                </router-link>
              </li>

              <li v-if="hasAccess('Others')">
                <router-link to="/dashboard/others" class="nav-item-link">
                  <i class="bi bi-car-front me-1 icon"></i> Motor Pool
                </router-link>
              </li>

              <li v-if="hasAccess('Others')">
                <router-link to="/dashboard/others" class="nav-item-link">
                  <i class="bi bi-wrench me-1 icon"></i> Tools
                </router-link>
              </li>

              <li v-if="hasAccess('Others')">
                <router-link to="/dashboard/others" class="nav-item-link">
                  <i class="bi bi-person me-1 icon"></i> PPE
                </router-link>
              </li>

              <li v-if="hasAccess('Others')">
                <router-link to="/dashboard/others" class="nav-item-link">
                  <i class="bi bi-leaf me-1 icon"></i> Gen. Plant
                </router-link>
              </li>

            </ul>
          </li>

          <li v-if="hasAccess('Material Credit Ticket')">
            <router-link to="/dashboard/mcrt" class="nav-item-link">
              <i class="bi bi-credit-card me-1 icon"></i> Material Credit Ticket
            </router-link>
          </li>
          
          <li v-if="hasAccess('Material Salvage Ticket')">
            <router-link to="/dashboard/mst" class="nav-item-link">
              <i class="bi bi-exclamation-triangle me-1 icon"></i>Material Salvage Ticket
            </router-link>
          </li>

          <li v-if="hasAccess('Memorandum Receipts')">
            <router-link to="/dashboard/mr" class="nav-item-link">
              <i class="bi bi-clipboard me-1 icon"></i> Memorandum Receipts
            </router-link>
          </li>
        </ul>
      </div>



      <!-- Purchase Order -->
      <router-link v-if="hasAccess('Order')" to="/dashboard/order" class="nav-item-link">
        <i class="bi bi-cart me-1 icon"></i> Purchase Order
      </router-link>

      <!-- Receiving Order -->
      <router-link v-if="hasAccess('receiving_Order')" to="/dashboard/receiving" class="nav-item-link">
        <i class="bi bi-receipt me-1 icon"></i> Receiving Order
      </router-link>

      <!-- Items -->
      <router-link v-if="hasAccess('Categories')" to="/dashboard/item" class="nav-item-link">
        <i class="bi bi-info-lg me-1 icon"></i> Items
      </router-link>

      <!-- Supplier -->
      <router-link v-if="hasAccess('Suppliers')" to="/dashboard/supplier" class="nav-item-link">
        <i class="bi bi-person-badge me-1 icon"></i> Supplier
      </router-link>

      <!-- Adjustment -->
      <router-link v-if="hasAccess('Adjustment')" to="/dashboard/adjustment" class="nav-item-link">
        <i class="bi bi-arrow-repeat me-1 icon"></i> Adjustments
      </router-link>


      <!-- REPORTS -->
      <div v-if="hasAccess('Reports')" class="nav-section">
        <div class="nav-header" @click="toggle('Reports')">
          <div class="left">
            <i class="bi bi-clipboard-data me-1 icon"></i>
            <span>Reports</span>
          </div>

          <i class="bi caret" 
             :class="expanded.includes('Reports') ? 'bi-caret-up-fill' : 'bi-caret-down-fill'">
          </i>
        </div>

        <ul v-show="expanded.includes('Reports')" class="submenu">

          <li v-if="hasAccess('MRV Reports')">
            <router-link to="/dashboard/sample-reports" class="nav-item-link">
              <i class="bi bi-file-earmark-text me-1 icon"></i> MRV
            </router-link>
          </li>

          <li v-if="hasAccess('Line Hardware Reports')">
            <router-link to="/dashboard/reports_line" class="nav-item-link">
              <i class="bi bi-tools me-1 icon"></i> Line Hardware
            </router-link>
          </li>

          <li v-if="hasAccess('Special Hardware Reports')">
            <router-link to="/dashboard/reports_Special" class="nav-item-link">
              <i class="bi bi-lightning me-1 icon"></i> Special Hardware
            </router-link>
          </li>

          <li v-if="hasAccess('Others Reports')">
            <router-link to="/dashboard/reports_Others" class="nav-item-link">
              <i class="bi bi-question-circle me-1 icon"></i> Others
            </router-link>
          </li>

            <li v-if="hasAccess('MCRT Reports')">
            <router-link to="/dashboard/reports_Others" class="nav-item-link">
              <i class="bi bi-credit-card me-1 icon"></i> MCRT
            </router-link>
          </li>

          <li v-if="hasAccess('MST Reports')">
            <router-link to="/dashboard/reports_salvage" class="nav-item-link">
              <i class="bi bi-exclamation-triangle me-1 icon"></i> Salvage Ticket
            </router-link>
          </li>

          <li v-if="hasAccess('MR Reports')">
            <router-link to="/dashboard/reports_memo" class="nav-item-link">
              <i class="bi bi-clipboard me-1 icon"></i> Memorandum Receipt
            </router-link>
          </li>

          <li v-if="hasAccess('Receiving Reports')">
            <router-link to="/dashboard/rr_reports" class="nav-item-link">
              <i class="bi bi-receipt me-1 icon"></i> Receiving Reports
            </router-link>
          </li>

        </ul>
      </div>


      <!-- ADMINISTRATION -->
      <div v-if="hasAccess('Users')" class="nav-section">
        <div class="nav-header" @click="toggle('administration')">
          <div class="left">
            <i class="bi bi-people me-1 icon"></i>
            <span>Administration</span>
          </div>

          <i class="bi caret"
             :class="expanded.includes('administration') ? 'bi-caret-up-fill' : 'bi-caret-down-fill'">
          </i>
        </div>

        <ul v-show="expanded.includes('administration')" class="submenu">
          <li v-if="hasAccess('Add Users')">
            <router-link to="/dashboard/user" class="nav-item-link">
              <i class="bi bi-person-plus me-1 icon"></i> Add Users
            </router-link>
          </li>
        </ul>

      </div>


    </nav>
  </aside>
</template>

<script setup>
import { ref } from "vue";
import { userStore } from "../stores/userStore";

const expanded = ref([]);

function toggle(name) {
  if (expanded.value.includes(name)) {
    expanded.value = expanded.value.filter(n => n !== name);
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

<style scoped>
.sidebar {
  width: 300px;
  height: 97vh;
  position: fixed;
  top: 45px;
  left: 0;
  padding: 20px;
  background: #f8f9fa;

  overflow-y: auto;
  scrollbar-width: none;
}
.sidebar::-webkit-scrollbar {
  display: none;
}

/* UNIFORM ICON + TEXT ALIGNMENT */
.nav-item-link {
  display: flex;
  align-items: center;
  gap: 4px;
  color: black;
  font-weight: 600;
  padding: 8px 10px;
  border-radius: 6px;
  text-decoration: none;
}

.nav-item-link:hover {
  background: radial-gradient(circle at 30% 30%, #4ade80, #22c55e, #16a34a);
  color: white;
}

.nav-section {
  margin-bottom: 0px;
}

.nav-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 5px;
  font-weight: 700;
  cursor: pointer;
}

.nav-header .left {
  display: flex;
  align-items: center;
  gap: 5px;
}

.caret {
  font-size: 16px;
}

.submenu {
  list-style: none;
  padding-left: 20px;
  border-left: 2px solid #ddd;
  margin-top: 0px;
}

.submenu.nested {
  padding-left: 25px;
}
</style>
