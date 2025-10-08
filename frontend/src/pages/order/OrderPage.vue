<template>
  <div class="main-container">
    <div >
      <h1>Order Item</h1>
      <p>This is aOrder Items</p>
    </div>
    <button :disabled="!canAddOrder" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i> Add
    </button>
    &nbsp;
    <button :disabled="!canEditOrder" class="btn btn-warning">
      <i class="bi bi-pencil me-1"></i> Edit
    </button>
    &nbsp;
    <button class="btn btn-danger" :disabled="!canDeleteOrder">
      <i class="bi bi-trash me-1"></i> Delete
    </button>

  </div>
</template>
<script setup>
import { computed } from "vue";
//import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";
import "../../assets/css/Global.css";


//Permissions for Special Hardware
const canAddOrder = computed(() => userStore.canAddOrder);
const canEditOrder = computed(() => userStore.canEditOrder);
const canDeleteOrder = computed(() => userStore.canDeleteOrder);

</script>

