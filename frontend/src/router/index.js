import { createRouter, createWebHistory } from "vue-router";
import Login from "../views/Login.vue";
import Dashboard from "../views/Dashboard.vue";
import DashboardHome from "../pages/DashboardHome.vue";
import UserManagement from "../pages/users/UserManagement.vue";
import AddEmployeePage from "../pages/users/AddEmployeePage.vue";
import Mrv from "../pages/inventory/Mrv.vue";
import LineHardware from "../pages/inventory/LineHardware.vue";
import SpecialHardware from "../pages/inventory/SpecialHardware.vue";
import Others from "../pages/inventory/Others.vue";
import Mst from "../pages/inventory/Mst.vue";
import Mr from "../pages/inventory/Mr.vue";
import Supplier from "../pages/suppliers/Supplier.vue";
import { useAppStore } from "../stores/appStore";
import Stocks from "../pages/inventory/stocks.vue";
import OrderItem from "../pages/order/OrderPage.vue";
import CategoryItem from "../pages/categories/CategoryPages.vue";
import Adjustment from "../pages/Adjustment/AdjustmentPage.vue";
import ReceivingPage from "../pages/receiving_order/Recevingpage.vue";
import PrintRR from "../components/Receiving/PrintRR.vue";

const routes = [
  {
    path: "/login",
    name: "Login",
    component: Login,
    meta: { guestOnly: true },
  },
  {
    path: '/receiving/DisplayRR/:r_id',
    name: 'PrintRR',
    component: PrintRR,
    props: true // para ma-access mo ang :id as prop
  },

  {
    path: "/dashboard",
    component: Dashboard,
    meta: { requiresAuth: true },
    children: [
      {path: "/dashboard/employees/",
      component: AddEmployeePage,},
      {path: "/dashboard/user",
      component: UserManagement,},
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
      {path: "/dashboard/stocks",
      component: Stocks,},
      {path: "/dashboard/order",
      component: OrderItem,},
      {path: "/dashboard/item",
      component: CategoryItem,},
      {path: "/dashboard/adjustment",
      component: Adjustment,},
      {path: "/dashboard/receiving",
      component: ReceivingPage,},

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
  const appStore = useAppStore();

  // ✅ show loader at the start of navigation
  appStore.showLoading();

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
router.afterEach(() => {
  const appStore = useAppStore();
  // ✅ hide loader after navigation
  setTimeout(() => {
    appStore.hideLoading();
  }, 700);
});
export default router;