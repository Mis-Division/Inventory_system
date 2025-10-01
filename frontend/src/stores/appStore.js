import { defineStore } from "pinia";
import { ref } from "vue";

export const useAppStore = defineStore("app", () => {
  const loading = ref(false);
  const error = ref(null);
  const alert = ref({
    visible: false,
    message: "",
    type: "success", // success | error | warning
  });

  function showLoading() {
    loading.value = true;
  }

  function hideLoading() {
    loading.value = false;
  }

  function setError(message) {
    error.value = message;
  }

  function clearError() {
    error.value = null;
  }

  function showAlert(message, type = "success", duration = 1500) {
    alert.value = {
      visible: true,
      message,
      type,
    };

    if (duration) {
      setTimeout(() => {
        alert.value.visible = false;
      }, duration);
    }
  }

  return {
    loading,
    error,
    alert,
    showLoading,
    hideLoading,
    setError,
    clearError,
    showAlert, // ✅ now available
  };
});
