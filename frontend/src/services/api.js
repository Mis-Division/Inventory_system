import axios from "axios";
import { useAppStore } from "../stores/appStore"; // ✅ import global store

const api = axios.create({
  baseURL: "http://127.0.0.1:8000/api",
});

// ✅ Attach access_token automatically
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("access_token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    // ✅ show loader globally
    const appStore = useAppStore();
    appStore.showLoading();

    return config;
  },
  (error) => {
    const appStore = useAppStore();
    appStore.hideLoading();
    return Promise.reject(error);
  }
);

// ✅ Hide loader when response arrives
api.interceptors.response.use(
  (response) => {
    const appStore = useAppStore();
    appStore.hideLoading();
    return response;
  },
  (error) => {
    const appStore = useAppStore();
    appStore.hideLoading();
    return Promise.reject(error);
  }
);

export default api;
