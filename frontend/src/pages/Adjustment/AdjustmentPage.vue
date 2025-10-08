<template>
  <div class="main-container">
    <div >
      <h1>Adjustment</h1>
      <p>This is a Adjustment</p>
    </div>
    <button :disabled="!canAddAdjustment" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i> Add
    </button>
    &nbsp;
    <button :disabled="!canEditAdjustment" class="btn btn-warning">
      <i class="bi bi-pencil me-1"></i> Edit
    </button>
    &nbsp;
    <button class="btn btn-danger" :disabled="!canDeleteAdjustment">
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
const canAddAdjustment = computed(() => userStore.canAddAdjustment);
const canEditAdjustment = computed(() => userStore.canEditAdjustment);
const canDeleteAdjustment = computed(() => userStore.canDeleteAdjustment);

</script>

