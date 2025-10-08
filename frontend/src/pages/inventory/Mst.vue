<template>
    <div class="main-container">
        <div>
            <h1>Material Salvage Ticket</h1>
           <p>This is the Material Salvage Ticket page.</p>
        </div>
        <button :disabled="!canAddSalvageTicket" class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i>  Add
        </button>
        &nbsp;
        <button :disabled="!canEditSalvageTicket" class="btn btn-warning">
          <i class="bi bi-pencil me-1"></i>  Edit
        </button>
        &nbsp;
        <button class="btn btn-danger" :disabled="!canDeleteSalvageTicket">
          <i class="bi bi trash me-1"></i>  Delete
        </button>
    </div>
</template>
<script setup>
import { computed } from "vue";
import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";
import "../../assets/css/Global.css";


//Permissions for material salvage ticket
const canAddSalvageTicket = computed(() => userStore.canAddSalvageTicket);
const canEditSalvageTicket = computed(() => userStore.canEditSalvageTicket);
const canDeleteSalvageTicket = computed(() => userStore.canDeleteSalvageTicket);

</script>
