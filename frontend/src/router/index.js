import { createRouter, createWebHistory } from "vue-router";
import Login from "../views/Login.vue";
import Dashboard from "../views/Dashboard.vue";
import DashboardHome from "../pages/DashboardHome.vue";
import AddUserPage from "../pages/users/AddUserPage.vue";
import AddEmployeePage from "../pages/users/AddEmployeePage.vue";



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
  const token = localStorage.getItem("access_token"); // ✅ use the same key everywhere

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
