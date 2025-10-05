import { createRouter, createWebHistory } from "vue-router";
import Login from "../views/Login.vue";
import Dashboard from "../views/Dashboard.vue";
import DashboardHome from "../pages/DashboardHome.vue";
import AddUserPage from "../pages/users/AddUserPage.vue";
import AddEmployeePage from "../pages/users/AddEmployeePage.vue";
import Mrv from "../pages/inventory/Mrv.vue";
import LineHardware from "../pages/inventory/LineHardware.vue";
import SpecialHardware from "../pages/inventory/SpecialHardware.vue";
import Others from "../pages/inventory/Others.vue";
import Mst from "../pages/inventory/Mst.vue";
import Mr from "../pages/inventory/Mr.vue";
import Supplier from "../pages/suppliers/Supplier.vue";



const routes = [
  {
    path: "/login",
    name: "Login",
    component: Login,
    meta: { guestOnly: true }, // prevent access if already logged in
  },

  {
    path: "/dashboard",
    component: Dashboard,
    meta: { requiresAuth: true },
    children: [
      {path: "/dashboard/employees",
      component: AddEmployeePage,},
      {path: "/dashboard/user",
      component: AddUserPage,},
      {path: "/dashboard",
        name: "Dashboard",
      component: DashboardHome,},
      {path: "/dashboard/mrv",
      component: Mrv,},
      {path: "/dashboard/linehardware",
      component: LineHardware,},
      {path: "/dashboard/specialhardware",
      component: SpecialHardware,},
      {path: "/dashboard/others",
      component: Others,},
      {path: "/dashboard/mst",
      component: Mst,},
      {path: "/dashboard/mr",
      component: Mr,},
      {path: "/dashboard/supplier",
      component: Supplier,},

    ] // protect all children
  },

  // Redirect top-level paths to /dashboard


  { path: "/", redirect: "/login" },
  { path: "/:catchAll(.*)", redirect: "/login" }, // fallback
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Global route guard
router.beforeEach((to, from, next) => {
  const token = sessionStorage.getItem("access_token"); // ✅ use the same key everywhere

  if (to.meta.requiresAuth && !token) {
    // not logged in → force login
    next("/login");
  } else if (to.meta.guestOnly && token) {
    // logged in but trying to access login → redirect dashboard
    next("/dashboard");
  } else {
    next();
  }
});

export default router;
