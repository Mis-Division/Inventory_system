<template>
    <div class="main-container">
        <div>
            <h1>Line Hardware Inventory</h1>
            <p>This is the LineHardware page.</p>
        </div>

        <button :disabled="!canAddLineHardware" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add
        </button>
        &nbsp;
        <button :disabled="!canEditLineHardware" class="btn btn-warning">
          <i class="bi bi-pencil me-1"></i>  Edit
        </button>
        &nbsp;
        <button class="btn btn-danger" :disabled="!canDeleteLineHardware">
            <i class="bi bi-trash me-1"></i>Delete
        </button>
    </div>
</template>
<script setup>
import { computed } from "vue";
//import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";
import "../../assets/css/Global.css";


//Permissions for Line hardware
const canAddLineHardware = computed(() => userStore.canAddLineHardware);
const canEditLineHardware = computed(() => userStore.canEditLineHardware);
const canDeleteLineHardware = computed(() => userStore.canDeleteLineHardware);

</script>

