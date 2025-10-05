import { reactive } from "vue";
import api from "../services/api";

export const userStore = reactive({
  user: null,
  async fetchUser() {
    const token = sessionStorage.getItem("access_token");
    if (!token) return;

    api.defaults.headers.common["Authorization"] = `Bearer ${token}`;

    try {
      const res = await api.get("/user");
      this.user = res.data.user;
    } catch (err) {
      console.error("Failed to fetch user:", err);
      this.user = null;
    }
  },
  logout() {
    sessionStorage.removeItem("access_token");
    this.user = null;
  }
});
