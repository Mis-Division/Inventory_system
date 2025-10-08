<template>
  <div class="main-container">
    <div >
      <h1>Special Hardware Inventory</h1>
      <p>This is a Special Hardware Inventory</p>
    </div>
    <button :disabled="!canAddSpecialHardware" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i> Add
    </button>
    &nbsp;
    <button :disabled="!canEditSpecialHardware" class="btn btn-warning">
      <i class="bi bi-pencil me-1"></i> Edit
    </button>
    &nbsp;
    <button class="btn btn-danger" :disabled="!canDeleteSpecialHardware">
      <i class="bi bi-trash me-1"></i> Delete
    </button>

  </div>
</template>
<script setup>
import { computed } from "vue";
//import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";


//Permissions for Special Hardware
const canAddSpecialHardware = computed(() => userStore.canAddSpecialHardware);
const canEditSpecialHardware = computed(() => userStore.canEditSpecialHardware);
const canDeleteSpecialHardware = computed(() => userStore.canDeleteSpecialHardware);

</script>
<style scoped>
.main-container {
  padding-left: 50px;
  padding-right: 20px;
  padding-top: 20px;
  padding-bottom: 20px;
  width: 100%;
  overflow-y: hidden;
  box-sizing: border-box;
  transition: padding-left 0.3s;
}
</style>
