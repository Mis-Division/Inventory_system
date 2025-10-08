<template>
<div class="main-container">
        <div >
        <h1>Memorandum  Receipt</h1>
        <p>This is the Memorandum Receipt page.</p>
    </div>
    <button :disabled="!canAddMemorandumReceipt" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i>  Add
    </button>
    &nbsp;
    <button :disabled="!canEditMemorandumReceipt" class="btn btn-warning">
      <i class="bi bi-pencil me-1"></i>  Edit
    </button>
    &nbsp;
    <button class="btn btn-danger" :disabled="!canDeleteMemorandumReceipt">
      <i class="bi bi-trash me-1"></i>  Delete
    </button>
</div>
</template>

<script setup>
import { computed } from "vue";
import { userStore } from "../../stores/userStore";
import "../../assets/css/Global.css";


//Permissions for material salvage ticket
const canAddMemorandumReceipt = computed(() => userStore.canAddMemorandumReceipt);
const canEditMemorandumReceipt = computed(() => userStore.canEditMemorandumReceipt);
const canDeleteMemorandumReceipt = computed(() => userStore.canDeleteMemorandumReceipt);

</script>

