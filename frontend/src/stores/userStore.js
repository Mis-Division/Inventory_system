// stores/userStore.js
import { reactive } from "vue";
import api from "../services/api";

// 🔎 Recursive search for a module by name (handles nested children)
function findModule(modules, name) {
  if (!Array.isArray(modules)) return null;

  const target = String(name ?? "").trim().toLowerCase();

  for (const m of modules) {
    const moduleName = String(m.module_name ?? "").trim().toLowerCase();

    if (moduleName === target) return m;

    const fromChildren = findModule(m.children, name);
    if (fromChildren) return fromChildren;
  }

  return null;
}

// Reactive user store
export const userStore = reactive({
  user: null,
  loading: false,

  // 🔹 Initialize user from stored token
  async initUser() {
    const token = sessionStorage.getItem("access_token");
    if (!token) return null;

    this.loading = true;
    try {
      const res = await api.get("/user"); 
      // API should return: { user: { id, fullname, username, role, modules } }
      this.user = res.data.user ?? null;
      return this.user;
    } catch (err) {
      console.error("❌ Failed to fetch user:", err);
      this.user = null;
      return null;
    } finally {
      this.loading = false;
    }
  },

  // 🔹 Login (store token + set header)
  login(token) {
    sessionStorage.setItem("access_token", token);
    api.defaults.headers.common["Authorization"] = `Bearer ${token}`;
  },

  // 🔹 Logout (clear session + redirect)
  logout() {
    sessionStorage.removeItem("access_token");
    delete api.defaults.headers.common["Authorization"];
    this.user = null;
    window.location.href = "/login";
  },

  // 🔹 Expose modules safely
  get modules() {
    return Array.isArray(this.user?.modules) ? this.user.modules : [];
  },
  // 🔹 Check permission for a module and action
  hasPermission(moduleName, action = "can_view") {
    const mod = findModule(this.modules, moduleName);
    return String(mod?.[action] ?? "0") === "1";
  },

    //  Permission shortcuts for user actions
    get canViewUsers() {
      return this.hasPermission("Users", "can_view");
    },
    get canEditUsers() {
      return this.hasPermission("Add Users", "can_edit");
    },
    get canDeleteUsers() {
      return this.hasPermission("Add Users", "can_delete");
    },
    get canAddUsers() {
      return this.hasPermission("Add Users", "can_add");
    },

    // Permission shortcuts for Materials Requisition Voucher actions
    get canViewMVR() {
      return this.hasPermission("Material Requisition Voucher", "can_view");
    },
    get canEditMRV() {
      return this.hasPermission("Material Requisition Voucher", "can_edit");
    },
    get canDeleteMRV() {
      return this.hasPermission("Material Requisition Voucher", "can_delete");
    },
    get canAddMRV() {
      return this.hasPermission("Material Requisition Voucher", "can_add");
    },

    // Permission shortcut for line Hardware actions
    get canViewLineHardware(){
      return this.hasPermission("Line Hardware", "can_view");
    },
    get canEditLineHardware(){
      return this.hasPermission("LIne Hardware","can_edit");
    },
    get canDeleteLineHardware(){
      return this.hasPermission("Line Hardware", "can_delete");
    },
    get canAddLineHardware(){
      return this.hasPermission("Line Hardware", "can_add");
    },

    // Permission shortcut for Special Hardware actions
    get canViewSpecialHardware(){
      return this.hasPermission("Special Hardware", "can_view")
    },
     get canEditSpecialHardware(){
      return this.hasPermission("Special Hardware","can_edit");
     },
     get canDeleteSpecialHardware(){
      return this.hasPermission("Special Hardware", "can_delete");
     },
     get canAddSpecialHardware(){
      return this.hasPermission("Special Hardware", "can_add");
     },

    // Permission shourcut for Others actions
    get canViewOthers(){
      return this.hasPermission("Others", "can_view");
    },
    get canEditOthers(){
      return this.hasPermission("Others", "can_edit");
    },
    get canDeleteOthers(){
      return this.hasPermission("Others", "can_delete");
    },
    get canAddOthers(){
      return this.hasPermission("Others", "can_add");
    },

    //Permission Shorcut for Material Salavage ticket
    get canAddSalvageTicket(){
      return this.hasPermission("Material Salvage Ticket","can_add");
    },
    get canEditSalvageTicket(){
      return this.hasPermission("Material salvage Ticket", "can_edit");
    },
    get canViewSalvageTicket(){
      return this.hasPermission("Material Salvage Ticket", "can_view");
    },
    get canDeleteSalvageTicket(){
      return this.hasPermission("Material Salvage Ticket", "can_delete");
    },

    //Permission shortcut for Memorandum Receipt
    get canAddMemorandumReceipt(){
      return this.hasPermission("Memorandum Receipts", "can_add");
    },
    get canEditMemorandumReceipt(){
      return this.hasPermission("Memorandum Receipts", "can_edit");
    },
    get canViewMemorandumReceipt(){
      return this.hasPermission("Memorandum Receipts", "can_view");
    },
    get canDeleteMemorandumReceipt(){
      return this.hasPermission("Memorandum Receipts", "can_delete");
    },
    get canAddStocks(){
      return this.hasPermission("Stocks", "can_add");
    },
    get canEditStocks(){
      return this.hasPermission("Stocks", "can_edit");
    },
    get canViewStocks(){
      return this.hasPermission("Stocks", "can_view");
    },
    get canDeleteStocks(){
      return this.hasPermission("Stocks", "can_delete");
    },
});
