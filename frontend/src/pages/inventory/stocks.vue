<script setup>
import { computed, ref } from "vue";
import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";



const canDeleteStocks = computed(() => userStore.canDeleteStocks);
const canEditStocks = computed(() => userStore.canEditStocks);
const canAddStocks = computed(() => userStore.canAddStocks);
const appStore = useAppStore();

const loading = ref(true);
const searchStock = ref("");
const error = ref(null);

</script>


<template>
  <div class="main-container">
    <!-- Header -->
    <div class="custom-headers">
      <h1 class="mb-3">Stock Management</h1>

      <div class="custom-actions">
        <input type="text" class="form-control" placeholder="Search Stocks..." />
        <button class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i> Add Stocks
        </button>
      </div>
    </div>


    <div v-if="loading" class="text-center py-4 text-muted">
      Loading Stocks......
    </div>
    <div class="alert alert-danger" v-if="error"> {{ error }}</div>
    <div class="table-responsive" v-if="!loading">
      <table class="table table-hover table-borded align-middle mb-2 text-center"> 
        
      </table>
    </div>

    <!-- <button :disabled="!canAddStocks" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i> Add
    </button>
    &nbsp;
    <button :disabled="!canEditStocks" class="btn btn-warning">
      <i class="bi bi-pencil me-1"></i> Edit
    </button>
    &nbsp;
    <button class="btn btn-danger" :disabled="!canDeleteStocks">
      <i class="bi bi-trash me-1"></i> Delete
    </button> -->
  </div>
</template>
