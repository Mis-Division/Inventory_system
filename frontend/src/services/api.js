import axios from "axios";
import { useAppStore } from "../stores/appStore";

//
// =====================================================
//  MAIN API (LOCAL)
// =====================================================
//
const api = axios.create({
  baseURL: "http://127.0.0.1:8000/api",
});

// ===============================
// REQUEST INTERCEPTOR
// ===============================
api.interceptors.request.use(
  (config) => {
    const token = sessionStorage.getItem("access_token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    // 🔥 SKIP GLOBAL LOADING IF FLAG EXISTS
    if (!config.headers["X-Skip-Loading"]) {
      useAppStore().showLoading();
    }

    return config;
  },
  (error) => {
    useAppStore().hideLoading();
    return Promise.reject(error);
  }
);

// ===============================
// RESPONSE INTERCEPTOR
// ===============================
api.interceptors.response.use(
  (response) => {
    useAppStore().hideLoading();
    return response;
  },
  (error) => {
    useAppStore().hideLoading();
    return Promise.reject(error);
  }
);

export default api;


//
// =====================================================
//  SECONDARY API (REMOTE)
// =====================================================
//
export const apiRemote = axios.create({
  baseURL: "http://26.183.28.177:8000/api",
});
