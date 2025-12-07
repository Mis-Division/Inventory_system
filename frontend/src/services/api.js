import axios from "axios";
import { useAppStore } from "../stores/appStore";

//
// =====================================================
//  MAIN API (LOCAL) — ito ang default, pangalan = api
// =====================================================
//
const api = axios.create({
  baseURL: "http://127.0.0.1:8000/api",
});

// Token + Loader for LOCAL API only
api.interceptors.request.use(
  (config) => {
    const token = sessionStorage.getItem("access_token");
    if (token) config.headers.Authorization = `Bearer ${token}`;

    useAppStore().showLoading();
    return config;
  },
  (error) => {
    useAppStore().hideLoading();
    return Promise.reject(error);
  }
);

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

export default api;   // 🔥 IMPORTANT: Hindi nagbago pangalan



//
// =====================================================
//  SECONDARY API (REMOTE INTERNET API)
//  → Hindi apektado ang existing code mo
//  → Tatawagin mo lang pag kailangan
// =====================================================
//
export const apiRemote = axios.create({
  baseURL: "http://26.183.28.177:8000/api",
});

// Optional token kung kailangan ng remote API
// apiRemote.interceptors.request.use((config) => {
//   const token = sessionStorage.getItem("access_token");
//   if (token) config.headers.Authorization = `Bearer ${token}`;
//   return config;
// });

