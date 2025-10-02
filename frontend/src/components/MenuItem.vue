<template>
  <aside class="sidebar">
    <nav class="nav">

      <!-- Dashboard -->
      <router-link to="/dashboard" class="nav-link" 
        :class="{ 'disabled': !hasAccess('Dashboard') }">
         <img :src="DashboardIcon" alt="Dashboard" class="icon-md" />
        <span>Dashboard</span>
      </router-link>

      <!-- Inventory -->
      <div>
        <button class="nav-btn" :class="{ 'disabled': !hasAccess('Inventory') }" @click="toggle('Inventory')">
          <span class="nav-label">
            <img :src="InventoryIcon" alt="Dashboard" class="icon-md" />
            <span>Inventory</span>
          </span>
          <svg v-if="hasAccess('Inventory')" 
            :class="['icon-sm rotate-toggle', expanded.includes('Inventory') ? 'rotate-90' : '']"
            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <!-- Submenus -->
        <ul v-show="expanded.includes('Inventory')" class="submenu">
          <!-- MRV -->
          <li>
            <router-link to="/dashboard/mrv" class="submenu-link" 
              :class="{ 'disabled': !hasAccess('Materials Requisition Voucher') }">
              <img :src="Mrv2" alt="MRV" class="icon-sm" />
              <span>Materials Requisition Voucher</span>
            </router-link>
          </li>

          <!-- MCT -->
          <li>
            <button class="submenu-btn" :class="{ 'disabled': !hasAccess('Material Charge Ticket') }" @click="toggle('MCT')">
              <span class="nav-label">
                <img :src="Mct" alt="MCT" class="icon-sm" />
                <span>Material Charge Ticket</span>
              </span>
              <svg v-if="hasAccess('Material Charge Ticket')" 
                :class="['icon-sm rotate-toggle', expanded.includes('MCT') ? 'rotate-90' : '']"
                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 5l7 7-7 7" />
              </svg>
            </button>

            <!-- Sub-submenus -->
            <ul v-show="expanded.includes('MCT')" class="sub-submenu">
              <li>
                <router-link to="/dashboard/line-hardware" class="sub-submenu-link" 
                  :class="{ 'disabled': !hasAccess('Line Hardware') }">
                 <img :src="lineHarware" alt="Line Hardware" class="icon-xs" />
                  <span>Line Hardware</span>
                </router-link>
              </li>
              <li>
                <router-link to="/dashboard/special-hardware" class="sub-submenu-link" 
                  :class="{ 'disabled': !hasAccess('Special Hardware') }">
                 <img :src="specialhardware" alt="Special Hardware" class="icon-xs" />
                  <span>Special Hardware</span>
                </router-link>
              </li>
              <li>
                <router-link to="/dashboard/others" class="sub-submenu-link" 
                  :class="{ 'disabled': !hasAccess('Others') }">
                  <img :src="Others" alt="Others" class="icon-xs" />
                  <span>Others</span>
                </router-link>
              </li>
            </ul>
          </li>

          <!-- MST -->
          <li>
            <router-link to="/dashboard/mst" class="submenu-link"
              :class="{ 'disabled': !hasAccess('Material Salvage Ticket') }">
              <img :src="salaveTicket" alt="MST" class="icon-sm" />
              <span>Material Salvage Ticket</span>
            </router-link>
          </li>

          <!-- MR -->
          <li>
            <router-link to="/dashboard/mr" class="submenu-link"
              :class="{ 'disabled': !hasAccess('Memorandum Receipt') }">
              <img :src="memoReceipt" alt="MR" class="icon-sm" />
              <span>Memorandum Receipt</span>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- Supplier -->
      <router-link to="/dashboard/supplier" class="nav-link" 
        :class="{ 'disabled': !hasAccess('Suppliers') }">
        <img :src="supplierIcon" alt="Supplier" class="icon-md" />
        <span>Supplier</span>
      </router-link>

      <!-- Reports -->
      <div>
        <button class="nav-btn" :class="{ 'disabled': !hasAccess('Reports') }" @click="toggle('Reports')">
          <span class="nav-label">
           
            <span>Reports</span>
          </span>
          <svg v-if="hasAccess('Reports')" 
            :class="['icon-sm rotate-toggle', expanded.includes('Reports') ? 'rotate-90' : '']"
            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <ul v-show="expanded.includes('Reports')" class="submenu">
          <li>
            <router-link to="/dashboard/sample-reports" class="submenu-link"
              :class="{ 'disabled': !hasAccess('Sample Reports') }">
              
              <span>Material Requisition Voucher</span>
            </router-link>
          </li>
          <li>
            <router-link to="/dashboard/demo-testing" class="submenu-link"
              :class="{ 'disabled': !hasAccess('Demo Testing') }">
             
              <span>Demo Testing</span>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- Users -->
      <div>
        <button class="nav-btn" :class="{ 'disabled': !hasAccess('Users') }" @click="toggle('Users')">
          <span class="nav-label">
            
            <span>Users</span>
          </span>
          <svg v-if="hasAccess('Users')" 
            :class="['icon-sm rotate-toggle', expanded.includes('Users') ? 'rotate-90' : '']"
            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <ul v-show="expanded.includes('Users')" class="submenu">
          <li>
            <router-link to="/dashboard/user" class="submenu-link"
              :class="{ 'disabled': !hasAccess('Add Users') }">
              
              <span>Add Users</span>
            </router-link>
          </li>
          <li>
            <router-link to="/dashboard/employees" class="submenu-link"
              :class="{ 'disabled': !hasAccess('List of Employee') }">
              
              <span>List of Employee</span>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- Logout -->
      <button @click="userStore.logout" class="logout-btn">
       
        <span>Logout</span>
      </button>
    </nav>
  </aside>
</template>


<script setup>
import { ref } from "vue";
import { userStore } from "../stores/userStore";
import DashboardIcon from "../assets/icons/dashboard.svg";
import InventoryIcon from "../assets/icons/inventory.svg";
import Mrv2 from "../assets/icons/voucher.png";
import Mct from "../assets/icons/ticket.svg";
import lineHarware from "../assets/icons/horzontal.svg";
import specialhardware from "../assets/icons/special.svg";
import Others from "../assets/icons/others.svg";
import salaveTicket from "../assets/icons/restore.svg";
import memoReceipt from "../assets/icons/memo.svg";
import supplierIcon from "../assets/icons/supplier.png"

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
<style scoped>
/* Sidebar */
.sidebar {
  background: #f7fafc;
  width: 16.5rem;
  height: calc(100vh - 56px);
  position: fixed;
  top: 3.5rem;
  font-size: 16px;
  left: 0;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -2px rgba(0,0,0,.1);
  overflow-y: auto;
}

.nav {
  padding: 0.5rem;
}

/* General link */
.nav-link {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
  transition: background-color 0.2s;
}

.nav-link > * + * {
  margin-left: 0.5rem;
}

.nav-link:hover {
  background: #bfdbfe;
}

/* Buttons for collapsible menus */
.nav-btn, .submenu-btn {
  display: flex;
  justify-content: space-between;
  align-items: left;
  width: 100%;
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
  transition: background-color 0.2s;
}

.nav-btn:hover, .submenu-btn:hover {
  background: #bfdbfe;
}

.nav-label {
  display: flex;
  align-items: center;
}

.nav-label > * + * {
  margin-left: 0.5rem;
}

/* Submenus */
.submenu {
  margin-left: 0.5rem;
}

.submenu > * + * {
  margin-top: 0.25rem;
}

.submenu-link {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 0.5rem 1.5rem;
  border-radius: 0.25rem;
  transition: background-color 0.2s;
}

.submenu-link > * + * {
  margin-left: 0.5rem;
}

.submenu-link:hover {
  background: #dbeafe;
}

/* Sub-submenus */
.sub-submenu {
  margin-left: 1rem;
}

.sub-submenu > * + * {
  margin-top: 0.25rem;
}

.sub-submenu-link {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 0.5rem 2rem;
  border-radius: 0.25rem;
  transition: background-color 0.2s;
}

.sub-submenu-link > * + * {
  margin-left: 0.5rem;
}

.sub-submenu-link:hover {
  background: #dbeafe;
}

/* Disabled */
.disabled {
  color: #9ca3af !important;
  pointer-events: none !important;
}

/* Logout button */
.logout-btn {
  display: flex;
  align-items: center;
  width: 100%;
  text-align: left;
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
  transition: background-color 0.2s;
}

.logout-btn > * + * {
  margin-left: 0.5rem;
}

.logout-btn:hover {
  background: #fecaca;
}

/* Icons */
.icon-md {
  width: 1.25rem;
  height: 1.25rem;
}

.icon-sm {
  width: 1rem;

}

.icon-xs {
  width: 0.875rem;
  height: 0.875rem;
}

/* Rotation animation */
.rotate-toggle {
  transition: transform 0.2s;
}

.rotate-90 {
  transform: rotate(90deg);
}
</style>