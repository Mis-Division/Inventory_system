<template>
    <div class="main-container">
        <div>
        <h1>Others Inventory Page</h1>
        <p>This is a placeholder for the Others inventory section.</p>
    </div>
    <button :disabled="!canAddOthers" class="btn btn-primary">
       <i class="bi bi-plus-circle me-1"></i> Add
    </button>
    &nbsp;
    <button :disabled="!canEditOthers" class="btn btn-warning">
       <i class="bi bi-pencil me-1"></i> Edit
    </button>
    &nbsp;
    <button class="btn btn-danger "  :disabled="!canDeleteOthers">
      <i class="bi bi-trash me-1"></i>  Delete
    </button>
    </div>
</template>

<script setup>
import { computed } from "vue";
//import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";
import "../../assets/css/Global.css";


//Permissions for others
const canAddOthers = computed(() => userStore.canAddOthers);
const canEditOthers = computed(() => userStore.canEditOthers);
const canDeleteOthers = computed(() => userStore.canDeleteOthers);

</script>

