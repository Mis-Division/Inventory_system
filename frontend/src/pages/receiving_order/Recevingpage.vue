<template>
    <div class="main-container">

        <!-- HEADER WRAPPER -->
        <div class="header-wrapper shadow-sm p-4 rounded-3 mb-3">

            <!-- TITLE -->
            <div class="d-flex align-items-center mb-3">
                <div class="icon-box">
                    <i class="bi bi-info-circle text-primary fs-1"></i>
                </div>
                <h1 class="page-title">Receiving Order</h1>
            </div>

            <!-- SEARCH + BUTTONS -->
            <div class="d-flex justify-content-between align-items-center">

                <!-- Search Box -->
                <div class="search-box position-relative">
                    <input type="text" v-model="searchQuery" @keyup.enter="handleSearchEnter"
                        class="form-control search-input shadow-sm" placeholder="Search Order..." />

                    <i v-if="searchQuery" class="bi bi-x-circle-fill clear-search" @click="clearSearch">
                    </i>
                </div>

                <!-- Add Button -->
                <button class="btn btn-primary add-btn ms-3 shadow-sm" v-if="canAddReceivingOrder" @click="addRR">
                    <i class="bi bi-plus-circle me-1"></i> Receiving Order
                </button>

            </div>
        </div>

        <!-- Loading / Error -->
        <div v-if="loading" class="loading-text">Loading receiving orders...</div>
        <div v-if="error" class="alert alert-danger text-center">{{ error }}</div>

        <!-- TABLE CARD -->
        <div class="card table-card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle stylish-table text-center">

                    <thead>
                        <tr>
                            <th>RR#</th>
                            <th>PO#</th>
                            <th>Invoice#</th>
                            <th>DR#</th>
                            <th>Supplier</th>
                            <th>Remarks</th>
                            <th>Received Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody v-if="receives.length > 0">
                        <tr v-for="rr in receives" :key="rr.r_id" :class="{
                            'row-complete': rr.remarks?.toLowerCase() === 'complete',
                            'row-partial': rr.remarks?.toLowerCase() === 'partial'
                        }">

                            <td>{{ rr.rr_number }}</td>
                            <td>{{ rr.po_number }}</td>
                            <td>{{ rr.invoice_number }}</td>
                            <td>{{ rr.dr_number }}</td>
                            <td>{{ rr.supplier_name }}</td>
                            <td>{{ rr.remarks }}</td>
                            <td>{{ rr.receive_date }}</td>

                            <!-- ACTION DROPDOWN -->
                            <td class="position-relative">
                                <div @click="toggleDropdown(rr.r_id, $event)" class="dots-btn">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </div>

                                <teleport to="body">
                                    <Transition name="fade">
                                        <div v-if="activeDropdown === rr.r_id" class="dropdown-menu-modern shadow-lg"
                                            :style="getDropdownStyle(rr.r_id)" @click.stop>
                                            <a v-if="canEditReceivingOrder" @click.prevent="updatePerRR(rr)"
                                                class="dropdown-item-mod text-primary">
                                                <i class="bi bi-pencil-square me-2"></i>Edit
                                            </a>

                                            <a v-if="canDeleteReceivingOrder" @click.prevent="deleteRR(rr)"
                                                class="dropdown-item-mod text-danger">
                                                <i class="bi bi-trash me-2"></i>Delete
                                            </a>

                                            <div v-if="!canEditReceivingOrder && !canDeleteReceivingOrder"
                                                class="text-muted no-permission">
                                                <i class="bi bi-lock me-2"></i>No permission
                                            </div>
                                        </div>
                                    </Transition>
                                </teleport>
                            </td>
                        </tr>
                    </tbody>

                    <tbody v-else>
                        <tr>
                            <td colspan="8" class="no-data">No receiving orders found.</td>
                        </tr>
                    </tbody>

                </table>
            </div>
        </div>

        <!-- PAGINATION -->
        <nav v-if="meta && meta.last_page > 1" class="mt-3">
            <ul class="pagination justify-content-end">
                <li class="page-item" :class="{ disabled: meta.current_page === 1 }"
                    @click="changePage(meta.current_page - 1)">
                    <a class="page-link rounded-pill px-3">Previous</a>
                </li>

                <li v-for="page in meta.last_page" :key="page" class="page-item"
                    :class="{ active: page === meta.current_page }" @click="changePage(page)">
                    <a class="page-link page-circle">{{ page }}</a>
                </li>

                <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }"
                    @click="changePage(meta.current_page + 1)">
                    <a class="page-link rounded-pill px-3">Next</a>
                </li>
            </ul>
        </nav>

        <!-- MODALS – SAME LOGIC -->
        <div v-if="showError" class="custom-modal-backdrop">
            <div class="custom-modal modern-modal border-0 shadow">
                <div class="modal-header-modern bg-danger text-white">
                    <h5>Error</h5>
                </div>
                <div class="modal-body-modern text-center">
                    ❌ {{ errorPO }} is already complete. Cannot edit this RR.
                </div>
                <div class="modal-footer-modern text-center">
                    <button class="btn btn-danger btn-modern" @click="showError = false">
                        Close
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showDeleteRR" class="custom-modal-backdrop">
            <div class="custom-modal modern-modal border-0 shadow">
                <div class="modal-header-modern bg-danger text-white">
                    <h5>Confirm Action</h5>
                </div>
                <div class="modal-body-modern">
                    <p>Are you sure you want to delete <b>{{ rrToDelete?.rr_number }}</b>?</p>
                </div>
                <div class="modal-footer-modern">
                    <button class="btn btn-light btn-modern" @click="showDeleteRR = false">Cancel</button>
                    <button class="btn btn-danger btn-modern" @click="confirmDeelteRR">Yes, Delete</button>
                </div>
            </div>
        </div>

        <!-- COMPONENTS -->
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

/* =========================================
   🔥 MAIN LAYOUT
========================================= */
.header-wrapper {
    background: #ffffff;
    border-radius: 16px;
    padding: 20px 25px;
    border: 1px solid #eee;
}

.page-title {
    font-size: 1.9rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

.icon-box {

    padding: 12px;
    border-radius: 12px;
}

/* =========================================
   🔍 SEARCH BAR
========================================= */
.search-box {
    width: 35%;
    min-width: 300px;
}

.search-input {
    padding: 12px 40px 12px 15px;
    border-radius: 12px !important;
    border: 1px solid #d3d3d3;
    background: #fafafa;
    transition: 0.2s ease-in-out;
}

.search-input:focus {
    border-color: #0d6efd;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
}

.clear-search {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.2rem;
    cursor: pointer;
    color: #999;
}

/* =========================================
   ➕ ADD BUTTON
========================================= */
.add-btn {
    display: flex;
    align-items: center;
    background: #0d6efd;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 1rem;
    transition: 0.2s ease;
}

.add-btn:hover {
    background: #0b5ed7;
}

/* =========================================
   📋 TABLE CARD
========================================= */
.table-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 10px 15px;
    border: 1px solid #eee;
}

.stylish-table thead {
    background: #f1f3f5;
    border-radius: 12px;
}

.stylish-table th {
    font-weight: 600;
    padding: 14px;
}

.stylish-table td {
    padding: 12px 10px;
    color: #444;
}

/* Highlighted rows */
.row-complete {
    background-color: #e9fbe9 !important;
}

.row-partial {
    background-color: #fdeaea !important;
}

/* No data */
.no-data {
    padding: 25px;
    color: #888;
    font-size: 1rem;
}

/* =========================================
   ⋮ ACTION DROPDOWN
========================================= */
.dots-btn {
    padding: 6px 10px;
    cursor: pointer;
    border-radius: 8px;
}

.dots-btn:hover {
    background: #f0f0f0;
}

.dropdown-menu-modern {
    position: absolute;
    min-width: 170px;
    background: #ffffff;
    border-radius: 12px;
    padding: 8px 0;
    border: 1px solid #ddd;
    animation: dropdownFade 0.15s ease-out;
}

@keyframes dropdownFade {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-item-mod {
    display: block;
    padding: 10px 16px;
    cursor: pointer;
    font-weight: 500;
    transition: 0.2s ease;
}

.dropdown-item-mod:hover {
    background: #f7f7f7;
}

/* =========================================
   📄 PAGINATION
========================================= */
.pagination .page-link {
    border-radius: 10px;
    margin: 0 3px;
    padding: 8px 14px;
    color: #0d6efd;
    font-weight: 500;
}

.pagination .active .page-link {
    background: #0d6efd;
    color: white;
    border: none;
}

.page-circle {
    border-radius: 50% !important;
}

/* =========================================
   ⚠ MODALS
========================================= */
.custom-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 4000;
}

.modern-modal {
    background: #ffffff;
    width: 420px;
    border-radius: 16px;
    overflow: hidden;
    animation: modalPop 0.2s ease-out;
}

@keyframes modalPop {
    from {
        transform: scale(0.94);
        opacity: 0;
    }

    to {
        transform: scale(1);
        opacity: 1;
    }
}

.modal-header-modern {
    padding: 15px 20px;
    font-size: 1.2rem;
    font-weight: 600;
}

.modal-body-modern {
    padding: 20px;
    font-size: 1rem;
    color: #444;
}

.modal-footer-modern {
    padding: 15px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Buttons */
.btn-modern {
    padding: 8px 18px;
    border-radius: 8px;
}

/* =========================================
   ⏳ LOADING TEXT
========================================= */
.loading-text {
    text-align: center;
    padding: 30px;
    font-style: italic;
    color: #777;
}
</style>
