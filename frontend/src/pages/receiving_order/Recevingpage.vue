<template>
    <div class="main-container">
        <div class="custom-headers">
            <!-- Title -->
            <div class="w-100">
                <h1 class="m-0 mb-3">
                    <i class="bi bi-info-circle text-primary"></i> Receiving Order
                </h1>
            </div>
            <!-- Search + Add Button -->
            <div class="d-flex justify-content-between align-items-center w-100">
                <div class="position-relative" style="width: 35%; min-width: 300px;">
                    <input type="text" v-model="searchQuery" @keyup.enter="handleSearchEnter" class="form-control pe-5"
                        placeholder="Search Order..."  style="background-color: #FCF6D9;"/>

                    <!-- Clear Button (X) -->
                    <i v-if="searchQuery" class="bi bi-x-circle-fill text-muted" @click="clearSearch"
                        style="position: absolute;right: 10px;top: 50%; transform: translateY(-50%);cursor: pointer; font-size: 1.2rem;"></i>
                </div>
                <button class="btn btn-success ms-2" v-if="canAddReceivingOrder" @click="addRR">
                    <i class="bi bi-plus-circle me-1"></i> Receiving Order
                </button>
            </div>
        </div>

        <!-- Loading / Error -->
        <div v-if="loading" class="text-center py-4 text-muted">Loading receiving orders...</div>
        <div v-if="error" class="alert alert-danger">{{ error }}</div>

        <!-- Table -->
        <div v-if="!loading" class="table-responsive">
            <table class="table table-hover align-middle mb-2 text-center">
                <thead class="table-secondary">
                    <tr>
                        <th style="width: 15%;">RR#</th>
                        <th style="width: 10%;">PO#</th>
                        <th style="width: 10%;">Invoice#</th>
                        <th style="width: 10%;">DR#</th>
                        <th style="width: 30%;">Supplier</th>
                        <th style="width: 10%;">Remarks</th>
                        <th style="width: 15%;">Received Date</th>
                        <th style="width: 20%;">Action</th>
                    </tr>
                </thead>
                <tbody v-if="receives.length > 0">
                    <tr v-for="rr in receives" :key="rr.r_id"
                     :class="{
            'table-success': rr.remarks?.toLowerCase() === 'complete',
            'table-danger': rr.remarks?.toLowerCase() === 'partial'}">
                        <td>{{ rr.rr_number }}</td>
                        <td>{{ rr.po_number }}</td>
                        <td>{{ rr.invoice_number }}</td>
                        <td>{{ rr.dr_number }}</td>
                        <td>{{ rr.supplier_name }}</td>
                        <td>{{ rr.remarks }}</td>
                        <td>{{ rr.receive_date }}</td>
                        <td>
                            <!-- Dropdown toggle -->
                            <div @click="toggleDropdown(rr.r_id, $event)" class="cursor-pointer">
                                <i class="bi bi-three-dots"></i>
                            </div>

                            <!-- Teleport + Transition -->
                            <teleport to="body">
                                <Transition name="fade">
                                    <div v-if="activeDropdown === rr.r_id"
                                        ref="el => (dropdownRefs.value ??= {})[rr.r_id] = el"
                                        class="dropdown-menu-teleport" :style="getDropdownStyle(rr.r_id)" @click.stop>
                                        <a href="#" @click.stop.prevent="updatePerRR(rr)" v-if="canEditReceivingOrder"
                                            class="text-success"><i class="bi bi-pencil me-2"></i>Edit</a>
                                        <a href="#" @click.stop.prevent="deleteRR(rr)" class="text-danger"
                                            v-if="canDeleteReceivingOrder">
                                            <i class="bi bi-trash me-2"></i>
                                            Delete</a>
                                       
                                    </div>
                                </Transition>
                            </teleport>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No receiving orders found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav v-if="meta && meta.last_page > 1" class="mt-3">
            <ul class="pagination justify-content-end">
                <li class="page-item" :class="{ disabled: meta.current_page === 1 }"
                    @click="changePage(meta.current_page - 1)">
                    <a class="page-link rounded-pill px-3" href="#">Previous</a>
                </li>
                <li v-for="page in meta.last_page" :key="page" class="page-item"
                    :class="{ active: page === meta.current_page }" @click="changePage(page)">
                    <a class="page-link page-circle" href="#">{{ page }}</a>
                </li>
                <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }"
                    @click="changePage(meta.current_page + 1)">
                    <a class="page-link rounded-pill px-3" href="#">Next</a>
                </li>
            </ul>
        </nav>

        <!-- Modals -->
        <div v-if="showError" class="custom-modal-backdrop">
            <div class="custom-modal">
                <div class="custom-header bg-danger text-white">
                    <h5 class="mb-0">Error</h5>
                </div>
                <div class="custom-body text-center">
                    ❌ {{ errorPO }} is already complete. This RR cannot be edited.
                </div>
                <div class="custom-footer text-center">
                    <button class="btn btn-danger" @click="showError = false">Close</button>
                </div>
            </div>
        </div>

        <div v-if="showDeleteRR" class="custom-modal-backdrop">
            <div class="custom-modal">
                <div class="custom-header bg-danger text-white">
                    <h5 class="mb-0">Confirm Action</h5>
                </div>
                <div class="custom-body">
                    <p>Are you sure you want to delete {{ rrToDelete?.rr_number }}?</p>
                </div>
                <div class="custom-footer">
                    <button class="btn btn-warning" @click="showDeleteRR = false">Cancel</button>
                    <button class="btn btn-danger" @click="confirmDeelteRR">Yes, Delete</button>
                </div>
            </div>
        </div>

        <AddReceiving v-if="showAddReceiving" @close="closeAddReceiving" />
        <updateRR v-if="showUpdateRR" :rr="selectedRR" @close="closeUpdateRR" @updated="fetchReceivingOrders" />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, nextTick, watch, onBeforeUnmount } from "vue";
import { useAppStore } from "../../stores/appStore";
import { userStore } from "../../stores/userStore";
import api from "../../services/api";
import AddReceiving from "../../components/Receiving/AddReceiving.vue";
import UpdateRR from "../../components/Receiving/UpdateRR.vue";

const appStore = useAppStore();
const receives = ref([]);
const loading = ref(true);
const error = ref(null);
const searchQuery = ref("");
const meta = ref({});
const currentPage = ref(1);
const perPage = 30;

const showDeleteRR = ref(false);
const rrToDelete = ref(null);
const showAddReceiving = ref(false);
const showUpdateRR = ref(false);
const selectedRR = ref(null);
const showError = ref(false);
const errorPO = ref("");

const activeDropdown = ref(null);
const dropdownPositions = ref({});
const dropdownRefs = ref({});

// Dropdown toggle
function toggleDropdown(id, event) {
    event.stopPropagation();
    activeDropdown.value = activeDropdown.value === id ? null : id;
    nextTick(() => {
        const rect = event.currentTarget.getBoundingClientRect();
        dropdownPositions.value[id] = { top: rect.bottom + window.scrollY, left: rect.left + window.scrollX };
    });
}

// Click outside closes dropdown
function handleClickOutside(event) {
    if (!activeDropdown.value) return;
    const dropdownEl = dropdownRefs.value?.[activeDropdown.value];
    if (!dropdownEl || !dropdownEl.contains(event.target)) activeDropdown.value = null;
}

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
    fetchReceivingOrders();
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
});

// Auto-close dropdown when modal opens
watch([showUpdateRR, showDeleteRR], ([updateVal, deleteVal]) => {
    if (updateVal || deleteVal) activeDropdown.value = null;
});

// Permissions
const canAddReceivingOrder = computed(() => userStore.canAddReceivingOrder);
const canEditReceivingOrder = computed(() => userStore.canEditReceivingOrder);
const canDeleteReceivingOrder = computed(() => userStore.canDeleteReceivingOrder);

// Add RR
async function addRR() {
    appStore.showLoading();
    await new Promise(r => setTimeout(r, 300));
    showAddReceiving.value = true;
    appStore.hideLoading();
}

async function closeAddReceiving() {
    showAddReceiving.value = false;
    fetchReceivingOrders();
}

// Update RR
async function updatePerRR(rr) {
    activeDropdown.value = null;
    try {
        appStore.showLoading();
        const poStatusRes = await api.get(`/receiving/checkPOComplete/${rr.po_number}`);
        if (poStatusRes.data.success && poStatusRes.data.po_complete) {
            errorPO.value = rr.po_number;
            showError.value = true;
            return;
        }
        const res = await api.get(`/receiving/DisplayRR/${rr.r_id}`);
        selectedRR.value = res.data.data;
        showUpdateRR.value = true;
    } catch (err) {
        console.error("Failed to fetch RR details:", err);
        alert("Failed to fetch RR details.");
    } finally {
        appStore.hideLoading();
    }
}

async function closeUpdateRR() {
    showUpdateRR.value = false;
    fetchReceivingOrders();
}

// Fetch RR
async function fetchReceivingOrders() {
    loading.value = true;
    try {
        const res = await api.get("/receiving/DisplayRR",
            {
                params:
                {
                    query: searchQuery.value,
                    page: currentPage.value,
                    per_page: perPage
                }
            });
        receives.value = res.data.data;
        meta.value = res.data.meta;
    } catch {
        error.value = "Failed to load receiving orders.";
    } finally {
        loading.value = false;
    }
}

// Delete RR
function deleteRR(rr) {
    rrToDelete.value = rr;
    showDeleteRR.value = true;
}

async function confirmDeelteRR() {
    if (!rrToDelete.value) return;
    try {
        appStore.showLoading();
        await api.delete(`/receiving/delete_rr/${rrToDelete.value.r_id}`);
        showDeleteRR.value = false;
        rrToDelete.value = null;
        fetchReceivingOrders();
    } catch {
        alert("Failed to delete receiving order.");
    } finally {
        appStore.hideLoading();
    }
}




// Pagination
function changePage(page) {
    if (page < 1 || page > meta.value.last_page) return;
    currentPage.value = page;
    fetchReceivingOrders();
}

// Dropdown style
function getDropdownStyle(id) {
    const pos = dropdownPositions.value[id] || { top: 0, left: 0 };
    const dropdownWidth = 180;
    let left = pos.left;
    if (pos.left + dropdownWidth > window.innerWidth) left = pos.left - dropdownWidth + 24;
    return {
        position: "absolute",
        top: pos.top + "px",
        left: left + "px",
        zIndex: 3000,
        backgroundColor: "white",
        border: "1px solid #ddd",
        borderRadius: "4px",
        minWidth: dropdownWidth + "px",
        boxShadow: "0 2px 8px rgba(0,0,0,0.15)"
    };
}

// Search handlers
function handleSearchEnter() {
    if (!searchQuery.value.trim()) {
        showErrors("Please enter a search term!");
        return; // Stop search
    }

    error.value = "";
    currentPage.value = 1;
    fetchReceivingOrders();
}

function showErrors(message) {
    error.value = message;
    setTimeout(() => {
        error.value = "";
    }, 5000);
}

function clearSearch() {
    searchQuery.value = "";
    fetchReceivingOrders();
}
</script>

<style scoped>
/* Dropdown slide + fade animation */



/* Table adjustments */
.table th,
.table td {
    vertical-align: middle;
}
</style>
