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
    const token = localStorage.getItem("access_token");
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
    localStorage.setItem("access_token", token);
    api.defaults.headers.common["Authorization"] = `Bearer ${token}`;
  },

  // 🔹 Logout (clear session + redirect)
  logout() {
    localStorage.removeItem("access_token");
    delete api.defaults.headers.common["Authorization"];
    this.user = null;
    window.location.href = "/login";
  },

  // 🔹 Expose modules safely
  get modules() {
    return Array.isArray(this.user?.modules) ? this.user.modules : [];
  },

  //  Generic permission checker
  hasPermission(moduleName, action = "can_view") {
    const mod = findModule(this.modules, moduleName);
    return String(mod?.[action] ?? "0") === "1";
  },

    //  Permission shortcuts
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
});
