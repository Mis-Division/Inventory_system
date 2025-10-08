<template>
  <div class="main-container">
    <div >
      <h1>Material requisition Voucher</h1>
     <p>This is the Material Requisition Vouchers page.</p>
    </div>

    <button class="btn btn-primary" :disabled="!canAddMVR" @click="AddMrv">
      <i class="bi bi-plus-circle"></i> ADD
    </button>
    &nbsp;
    <button class="btn btn-warning" :disabled="!canEditMVR" @click="editMrv">
      <i class="bi bi-pencil-square"></i> EDIT
    </button>
      &nbsp;
    <button class="btn btn-danger" :disabled="!canDeleteMVR" @click="deleteMrv">
      <i class="bi bi-trash"></i> DELETE

    </button>
    <AddMrvModal v-if="showAddMrvModal" @close="closeAddMrvModal()" />
    <EditMrvModal v-if="showEditMrvModal" @close="closeEditMrvModal()" />
    <DeleteMrvModal v-if="showDeleteMrvModal" @close="closeDeleteMrvModal()" />
  </div>
</template>
<script setup>
import { computed, ref } from "vue";
import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";
import AddMrvModal from "../../components/MRV/AddMrvModal.vue";
import EditMrvModal from "../../components/MRV/EditMrvModal.vue";
import DeleteMrvModal from "../../components/MRV/DeleteMvrModal.vue";


const showAddMrvModal = ref(false);
const showEditMrvModal = ref(false);
const showDeleteMrvModal = ref(false);
const appStore = useAppStore();


//Permissions for material requisition voucher
const canAddMVR = computed(() => userStore.canAddMRV);
const canEditMVR = computed(() => userStore.canEditMRV);
const canDeleteMVR = computed(() => userStore.canDeleteMRV);

// Add MRV Modal
async function AddMrv() {
  try {
    appStore.showLoading();
    await new Promise((resolve) => setTimeout(resolve, 500)); // Simulate loading
    showAddMrvModal.value = true;
  } catch (error) {
    console.error("Error opening Add MRV modal:", error);
  } finally {
    appStore.hideLoading();
  }
}
function closeAddMrvModal() {
  showAddMrvModal.value = false;
}

// Edit MRV Modal
async function editMrv() {
  try {
    appStore.showLoading();
    await new Promise((resolve) => setTimeout(resolve, 500)); // Simulate loading
    showEditMrvModal.value = true;
  } catch (error) {
    console.error("Error opening Edit MRV modal:", error);
  } finally {
    appStore.hideLoading();
  }
}
function closeEditMrvModal() {
  showEditMrvModal.value = false;
}

// Delete MRV Modal
async function deleteMrv() {
  try {
    appStore.showLoading();
    await new Promise((resolve) => setTimeout(resolve, 500)); // Simulate loading
    showDeleteMrvModal.value = true;
  } catch (error) {
    console.error("Error opening Delete MRV modal:", error);
  } finally {
    appStore.hideLoading();
  }
}
function closeDeleteMrvModal() {
  showDeleteMrvModal.value = false;
}
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