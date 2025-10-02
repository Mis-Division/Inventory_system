<template>
  <div class="flex flex-col h-screen bg-gray-100">
    <!-- Header -->
    <HeaderBar class="flex-shrink-0" />

    <div class="flex flex-1 overflow-hidden">
      <!-- Sidebar -->
      <aside v-if="userStore.user" class="w-64 bg-gray-200 p-4 flex flex-col overflow-y-auto">
        <ul class="flex flex-col h-full">
          <!-- Dashboard -->
          <MenuItem label="Dashboard" module="Dashboard" icon="home" to="/dashboard/home"
            :disabled="!hasAccess('Dashboard')" />

          <!-- Inventory -->
          <!-- <MenuItem
            label="Inventory"
            module="Inventory"
            icon="box"
            :disabled="!hasAccess('Inventory')"
          >
            <ul class="ml-4 mt-1 space-y-1">
              <MenuItem
                label="Materials Requisition Voucher"
                module="Materials Requisition Voucher"
                icon="file-plus"
                to="/dashboard/mrv"
                :disabled="!hasAccess('Materials Requisition Voucher')"
              />
              <MenuItem
                label="Material Charge Ticket"
                module="Material Charge Ticket"
                icon="check-square"
                :disabled="!hasAccess('Material Charge Ticket')"
              >
                <ul class="ml-4 mt-1 space-y-1">
                  <MenuItem
                    label="Line Hardware"
                    module="Line Hardware"
                    icon="settings"
                    to="/dashboard/line-hardware"
                    :disabled="!hasAccess('Line Hardware')"
                  />
                  <MenuItem
                    label="Special Hardware"
                    module="Special Hardware"
                    icon="cpu"
                    to="/dashboard/special-hardware"
                    :disabled="!hasAccess('Special Hardware')"
                  />
                  <MenuItem
                    label="Others"
                    module="Others"
                    icon="circle"
                    to="/dashboard/others"
                    :disabled="!hasAccess('Others')"
                  />
                </ul>
              </MenuItem>
            </ul>
          </MenuItem> -->

          <!-- Supplier -->
          <!-- <MenuItem
            label="Supplier"
            module="Supplier"
            icon="truck"
            to="/dashboard/supplier"
            :disabled="!hasAccess('Supplier')"
          /> -->

          <!-- Reports -->
          <!-- <MenuItem
            label="Reports"
            module="Reports"
            icon="bar-chart-2"
            :disabled="!hasAccess('Reports')"
          >
            <ul class="ml-4 mt-1 space-y-1">
              <MenuItem
                label="Sample Reports"
                module="Sample Reports"
                icon="file-text"
                to="/dashboard/sample-reports"
                :disabled="!hasAccess('Sample Reports')"
              />
              <MenuItem
                label="Demo Testing"
                module="Demo Testing"
                icon="activity"
                to="/dashboard/demo-testing"
                :disabled="!hasAccess('Demo Testing')"
              />
            </ul>
          </MenuItem> -->

          <!-- Users -->
          <!-- <MenuItem
            label="Users"
            module="Users"
            icon="users"
            :disabled="!hasAccess('Users')"
          >
            <ul class="ml-4 mt-1 space-y-1">
              <MenuItem
                label="Add Users"
                module="Add Users"
                icon="user-plus"
                to="/dashboard/user"
                :disabled="!hasAccess('Add Users')"
              />
              <MenuItem
                label="List of Employee"
                module="List of Employee"
                icon="list"
                to="/dashboard/employees"
                :disabled="!hasAccess('List of Employee')"
              />
            </ul>
          </MenuItem> -->

          <!-- Logout
          <li class="mt-auto">
            <button
              class="flex items-center gap-2 px-2 py-1 rounded bg-red-500 text-white hover:bg-red-600 w-full text-left"
              @click="userStore.logout"
            >
              <span>🚪</span>
              Logout
            </button>
          </li> -->
        </ul>
      </aside>

      <!-- Main content -->
      <main class=" content-wrapper">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
import { onMounted } from "vue";
import { userStore } from "../stores/userStore";
import { useAppStore } from "../stores/appStore";
import HeaderBar from "../components/HeaderBar.vue";
import MenuItem from "../components/MenuItem.vue";

export default {
  components: { HeaderBar, MenuItem },
  setup() {
    const appStore = useAppStore();

    function hasAccess(moduleName) {
      return (
        userStore.user?.modules?.some(
          (m) => m.module_name === moduleName && Number(m.can_view) === 1
        ) ?? false
      );
    }

    onMounted(async () => {
      appStore.showLoading();
      if (!userStore.user) {
        await userStore.initUser();
      }
      appStore.hideLoading();
    });

    return {
      userStore,
      hasAccess,
    };
  },
};
</script>
<style scoped>
.content-wrapper {
  flex: 2 2 0%;         /* flex-2 */
  overflow-y: auto;     /* overflow-y-auto */
  padding: 2.5rem;      /* p-6 (24px) */
  margin-top: 3.5rem;   /* mt-14 (56px) */
}
</style>