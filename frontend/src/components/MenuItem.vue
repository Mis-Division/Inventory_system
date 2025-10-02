<template>
  <aside class="bg-gray-100 w-64 h-[calc(100vh-56px)] fixed top-14 left-0 shadow-md overflow-y-auto">
    <nav class="p-2 space-y-1">
      <!-- Dashboard -->
      <router-link to="/dashboard"
        class="flex items-center space-x-2 w-full px-4 py-2 rounded transition-colors duration-200 hover:bg-blue-200"
        :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Dashboard') }">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M3 12l9-9 9 9M4 10v10h16V10" />
        </svg>
        <span>Dashboard</span>
      </router-link>

      <!-- Inventory -->
      <div>
        <button
          class="w-full flex justify-between items-center font-medium px-4 py-2 rounded transition-colors duration-200 hover:bg-blue-200"
          :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Inventory') }" @click="toggle('Inventory')">
          <span class="flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M3 7h18M3 12h18M3 17h18" />
            </svg>
            <span>Inventory</span>
          </span>
          <svg v-if="hasAccess('Inventory')"
            :class="['w-4 h-4 transition-transform', expanded.includes('Inventory') ? 'rotate-90' : '']" fill="none"
            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <!-- Submenus -->
        <ul v-show="expanded.includes('Inventory')" class="ml-2 space-y-1">
          <!-- MRV -->
          <li>
            <router-link to="/dashboard/mrv"
              class="flex items-center space-x-2 w-full px-6 py-2 rounded transition-colors duration-200 hover:bg-blue-100 text-sm"
              :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Materials Requisition Voucher') }">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 4v16m8-8H4" />
              </svg>
              <span>Materials Requisition Voucher</span>
            </router-link>
          </li>

          <!-- MCT -->
          <li>
            <button
              class="flex justify-between items-center w-full px-6 py-2 rounded transition-colors duration-200 hover:bg-blue-100 text-sm"
              :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Material Charge Ticket') }"
              @click="toggle('MCT')">
              <span class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M5 13l4 4L19 7" />
                </svg>
                <span>Material Charge Ticket</span>
              </span>
              <svg v-if="hasAccess('Material Charge Ticket')"
                :class="['w-4 h-4 transition-transform', expanded.includes('MCT') ? 'rotate-90' : '']" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 5l7 7-7 7" />
              </svg>
            </button>

            <!-- Sub-submenus -->
            <ul v-show="expanded.includes('MCT')" class="ml-4 space-y-1">
              <li>
                <router-link to="/dashboard/line-hardware"
                  class="flex items-center space-x-2 w-full px-8 py-2 rounded transition-colors duration-200 hover:bg-blue-100 text-xs"
                  :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Line Hardware') }">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                  </svg>
                  <span>Line Hardware</span>
                </router-link>
              </li>
              <li>
                <router-link to="/dashboard/special-hardware"
                  class="flex items-center space-x-2 w-full px-8 py-2 rounded transition-colors duration-200 hover:bg-blue-100 text-xs"
                  :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Special Hardware') }">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 2l9 21H3L12 2z" />
                  </svg>
                  <span>Special Hardware</span>
                </router-link>
              </li>
              <li>
                <router-link to="/dashboard/others"
                  class="flex items-center space-x-2 w-full px-8 py-2 rounded transition-colors duration-200 hover:bg-blue-100 text-xs"
                  :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Others') }">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                  </svg>
                  <span>Others</span>
                </router-link>
              </li>
            </ul>
          </li>

          <!-- MST -->
          <li>
            <router-link to="/dashboard/mst"
              class="flex items-center space-x-2 w-full px-6 py-2 rounded transition-colors duration-200 hover:bg-blue-100 text-sm"
              :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Material Salvage Ticket') }">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M19 21H5a2 2 0 01-2-2V7h18v12a2 2 0 01-2 2zM5 7l7-5 7 5" />
              </svg>
              <span>Material Salvage Ticket</span>
            </router-link>
          </li>

          <!-- MR -->
          <li>
            <router-link to="/dashboard/mr"
              class="flex items-center space-x-2 w-full px-6 py-2 rounded transition-colors duration-200 hover:bg-blue-100 text-sm"
              :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Memorandum Receipt') }">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 20h9M12 4h9M4 9h16M4 15h16" />
              </svg>
              <span>Memorandum Receipt</span>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- Supplier -->
      <router-link to="/dashboard/supplier"
        class="flex items-center space-x-2 w-full px-4 py-2 rounded transition-colors duration-200 hover:bg-blue-200"
        :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Suppliers') }">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M20 13V7a2 2 0 00-2-2h-3V3h-6v2H6a2 2 0 00-2 2v6H2v7h20v-7h-2z" />
        </svg>
        <span>Supplier</span>
      </router-link>

      <!-- Reports -->
      <div>
        <button
          class="w-full flex justify-between items-center font-medium px-4 py-2 rounded transition-colors duration-200 hover:bg-blue-200"
          :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Reports') }" @click="toggle('Reports')">
          <span class="flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M3 4h18M3 12h18M3 20h18" />
            </svg>
            <span>Reports</span>
          </span>
          <svg v-if="hasAccess('Reports')"
            :class="['w-4 h-4 transition-transform', expanded.includes('Reports') ? 'rotate-90' : '']" fill="none"
            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <ul v-show="expanded.includes('Reports')" class="ml-2 space-y-1">
          <li>
            <router-link to="/dashboard/sample-reports"
              class="flex items-center space-x-2 w-full px-6 py-2 rounded transition-colors duration-200 hover:bg-blue-100 text-sm"
              :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Sample Reports') }">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 20h9M12 4h9" />
              </svg>
              <span>Material Requisition Voucher</span>
            </router-link>
          </li>
          <li>
            <router-link to="/dashboard/demo-testing"
              class="flex items-center space-x-2 w-full px-6 py-2 rounded transition-colors duration-200 hover:bg-blue-100 text-sm"
              :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Demo Testing') }">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" />
              </svg>
              <span>Demo Testing</span>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- Users -->
      <div>
        <button
          class="w-full flex justify-between items-center font-medium px-4 py-2 rounded transition-colors duration-200 hover:bg-blue-200"
          :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Users') }" @click="toggle('Users')">
          <span class="flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M17 20h5v-2a4 4 0 00-4-4h-1" />
              <path d="M9 20H4v-2a4 4 0 014-4h1" />
              <circle cx="9" cy="7" r="4" />
              <circle cx="17" cy="7" r="4" />
            </svg>
            <span>Users</span>
          </span>
          <svg v-if="hasAccess('Users')"
            :class="['w-4 h-4 transition-transform', expanded.includes('Users') ? 'rotate-90' : '']" fill="none"
            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <ul v-show="expanded.includes('Users')" class="ml-2 space-y-1">
          <li>
            <router-link to="/dashboard/user"
              class="flex items-center space-x-2 w-full px-6 py-2 rounded transition-colors duration-200 hover:bg-blue-100 text-sm"
              :class="{ 'text-gray-400 pointer-events-none': !hasAccess('Add Users') }">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 4v16m8-8H4" />
              </svg>
              <span>Add Users</span>
            </router-link>
          </li>
          <li>
            <router-link to="/dashboard/employees"
              class="flex items-center space-x-2 w-full px-6 py-2 rounded transition-colors duration-200 hover:bg-blue-100 text-sm"
              :class="{ 'text-gray-400 pointer-events-none': !hasAccess('List of Employee') }">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <span>List of Employee</span>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- Logout -->
      <button @click="userStore.logout"
        class="flex items-center space-x-2 w-full text-left px-4 py-2 rounded transition-colors duration-200 hover:bg-red-200 font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M17 16l4-4-4-4M7 12h14M3 21h6a2 2 0 002-2V5a2 2 0 00-2-2H3z" />
        </svg>
        <span>Logout</span>
      </button>
    </nav>
  </aside>
</template>

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
