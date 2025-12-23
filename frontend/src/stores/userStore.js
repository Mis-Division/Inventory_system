import { reactive } from "vue";
import api from "../services/api";

/**
 * 🔍 Helper: Find access permission by module name
 */
function findAccess(accessList, name) {
  if (!Array.isArray(accessList)) return null;
  const target = String(name ?? "").trim().toLowerCase();

  return accessList.find((a) => {
    const moduleName = String(a.module_name ?? "").trim().toLowerCase();
    return moduleName === target;
  }) || null;
}

/**
 * 🔹 Reactive user store
 */
export const userStore = reactive({
  user: null,
  loading: false,

  /**
   * Initialize user from stored token
   */
  async initUser() {
  const token = sessionStorage.getItem("access_token");
  if (!token) return null;

  this.loading = true;
  api.defaults.headers.common["Authorization"] = `Bearer ${token}`;

  try {
    const res = await api.get("/user");
    this.user = {
      ...res?.data?.user,
      accessModules: res?.data?.user?.modules ?? []
    };
    return this.user;
  } catch (err) {
    console.error("❌ Failed to fetch user:", err);
    this.user = null;
    return null;
  } finally {
    this.loading = false;
  }
},


  /**
   * Login — save token + user info and setup Authorization header
   */
  login(token, user) {
    if (!token) return;
    sessionStorage.setItem("access_token", token);
    api.defaults.headers.common["Authorization"] = `Bearer ${token}`;
    this.user = user;
  },

  /**
   * Logout — clear session + redirect to login
   */
  logout() {
    sessionStorage.removeItem("access_token");
    delete api.defaults.headers.common["Authorization"];
    this.user = null;
    window.location.href = "/login";
  },

  /**
   * Getter for accessModules
   */
  get accessModules() {
    return Array.isArray(this.user?.accessModules)
      ? this.user.accessModules
      : [];
  },

  /**
   * Check permission for a specific module and action
   */
  hasPermission(moduleName, action = "can_view") {
    const mod = findAccess(this.accessModules, moduleName);
    return Boolean(mod?.[action]);
  },
  // ──────────────────────────────
  // 🔹 Permission Shortcuts
  // ──────────────────────────────

  // 🧑 Users
  get canViewUsers() { return this.hasPermission("Users", "can_view"); },
  get canEditUsers() { return this.hasPermission("Add Users", "can_edit"); },
  get canDeleteUsers() { return this.hasPermission("Add Users", "can_delete"); },
  get canAddUsers() { return this.hasPermission("Add Users", "can_add"); },

  // 📄 Material Requisition Voucher
  get canViewMRV() { return this.hasPermission("Material Requisition Voucher", "can_view"); },
  get canEditMRV() { return this.hasPermission("Material Requisition Voucher", "can_edit"); },
  get canDeleteMRV() { return this.hasPermission("Material Requisition Voucher", "can_delete"); },
  get canAddMRV() { return this.hasPermission("Material Requisition Voucher", "can_add"); },

  // 🧰 Line Hardware
  get canViewLineHardware() { return this.hasPermission("Line Hardware", "can_view"); },
  get canEditLineHardware() { return this.hasPermission("Line Hardware", "can_edit"); },
  get canDeleteLineHardware() { return this.hasPermission("Line Hardware", "can_delete"); },
  get canAddLineHardware() { return this.hasPermission("Line Hardware", "can_add"); },

  // ⚙️ Special Hardware
  get canViewSpecialHardware() { return this.hasPermission("Special Hardware", "can_view"); },
  get canEditSpecialHardware() { return this.hasPermission("Special Hardware", "can_edit"); },
  get canDeleteSpecialHardware() { return this.hasPermission("Special Hardware", "can_delete"); },
  get canAddSpecialHardware() { return this.hasPermission("Special Hardware", "can_add"); },

  // 📦 Others
  get canViewOthers() { return this.hasPermission("Others", "can_view"); },
  get canEditOthers() { return this.hasPermission("Others", "can_edit"); },
  get canDeleteOthers() { return this.hasPermission("Others", "can_delete"); },
  get canAddOthers() { return this.hasPermission("Others", "can_add"); },

  // ♻️ Material Credit Ticket
  get canViewCreditTicket() { return this.hasPermission("Material Credit Ticket", "can_view"); },
  get canEditCreditTicket() { return this.hasPermission("Material Credit Ticket", "can_edit"); },
  get canDeleteCreditTicket() { return this.hasPermission("Material Credit Ticket", "can_delete"); },
  get canAddCreditTicket() { return this.hasPermission("Material Credit Ticket", "can_add"); }, 
  
  // ♻️ Material Salvage Ticket
  get canViewSalvageTicket() { return this.hasPermission("Material Salvage Ticket", "can_view"); },
  get canEditSalvageTicket() { return this.hasPermission("Material Salvage Ticket", "can_edit"); },
  get canDeleteSalvageTicket() { return this.hasPermission("Material Salvage Ticket", "can_delete"); },
  get canAddSalvageTicket() { return this.hasPermission("Material Salvage Ticket", "can_add"); },

  // 🧾 Memorandum Receipts
  get canViewMemorandumReceipt() { return this.hasPermission("Memorandum Receipts", "can_view"); },
  get canEditMemorandumReceipt() { return this.hasPermission("Memorandum Receipts", "can_edit"); },
  get canDeleteMemorandumReceipt() { return this.hasPermission("Memorandum Receipts", "can_delete"); },
  get canAddMemorandumReceipt() { return this.hasPermission("Memorandum Receipts", "can_add"); },

  // 🏷️ Stocks
  get canViewStocks() { return this.hasPermission("Stocks", "can_view"); },
  get canEditStocks() { return this.hasPermission("Stocks", "can_edit"); },
  get canDeleteStocks() { return this.hasPermission("Stocks", "can_delete"); },
  get canAddStocks() { return this.hasPermission("Stocks", "can_add"); },

  // 🛒 Orders
  get canViewOrder() { return this.hasPermission("Order", "can_view"); },
  get canEditOrder() { return this.hasPermission("Order", "can_edit"); },
  get canDeleteOrder() { return this.hasPermission("Order", "can_delete"); },
  get canAddOrder() { return this.hasPermission("Order", "can_add"); },

  // 🗂️ Categories
  get canViewCategory() { return this.hasPermission("Categories", "can_view"); },
  get canEditCategory() { return this.hasPermission("Categories", "can_edit"); },
  get canDeleteCategory() { return this.hasPermission("Categories", "can_delete"); },
  get canAddCategory() { return this.hasPermission("Categories", "can_add"); },

  // 🚚 Suppliers
  get canViewSupplier() { return this.hasPermission("Suppliers", "can_view"); },
  get canEditSupplier() { return this.hasPermission("Suppliers", "can_edit"); },
  get canDeleteSupplier() { return this.hasPermission("Suppliers", "can_delete"); },
  get canAddSupplier() { return this.hasPermission("Suppliers", "can_add"); },

  // ⚖️ Adjustments
  get canViewAdjustment() { return this.hasPermission("Adjustment", "can_view"); },
  get canEditAdjustment() { return this.hasPermission("Adjustment", "can_edit"); },
  get canDeleteAdjustment() { return this.hasPermission("Adjustment", "can_delete"); },
  get canAddAdjustment() { return this.hasPermission("Adjustment", "can_add"); },

  get canViewReceivingOrder() { return this.hasPermission("receiving_Order", "can_view"); },
  get canEditReceivingOrder() { return this.hasPermission("receiving_Order", "can_edit"); },
  get canDeleteReceivingOrder() { return this.hasPermission("receiving_Order", "can_delete"); },
  get canAddReceivingOrder() { return this.hasPermission("receiving_Order", "can_add"); },


  //Receiving Reports
  get canViewReceivingReports() { return this.hasPermission("Receiving Reports", "can_view"); },
  get canEditReceivingReports() { return this.hasPermission("Receiving Reports", "can_edit"); },

  //Request for Materials
  get canViewRFM() { return this.hasPermission("Request Materials", "can_view"); },
  get canEditRFM() { return this.hasPermission("Request Materials", "can_edit"); },
  get canAddRFM() { return this.hasPermission("Request Materials", "can_add"); },
  get canDeleteRFM() { return this.hasPermission("Request Materials", "can_delete"); }, 

});
