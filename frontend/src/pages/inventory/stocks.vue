<script setup>
import { computed } from "vue";
//import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";

const canDeleteStocks = computed(() => userStore.canDeleteStocks);
const canEditStocks = computed(() => userStore.canEditStocks);
const canAddStocks = computed(() => userStore.canAddStocks);
</script>
<template>
  <div class="main-container">
    <div>
      <h1>Stocks Inventory</h1>
      <p>This is a Stocks Inventory</p>
    </div>
    <button :disabled="!canAddStocks" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i> Add
    </button>
    &nbsp;
    <button :disabled="!canEditStocks" class="btn btn-warning">
      <i class="bi bi-pencil me-1"></i> Edit
    </button>
    &nbsp;
    <button class="btn btn-danger" :disabled="!canDeleteStocks">
      <i class="bi bi-trash me-1"></i> Delete
    </button>
  </div>
</template>
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