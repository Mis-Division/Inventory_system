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

                                        <a href="#" @click.stop.prevent="printingRR(rr)" v-if="canViewReceivingReports" class="text-warning"><i
                                                class="bi bi-printer me-2"></i>Print</a>
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

     


  
  
    </div>
</template>

<script setup>
import { computed, onMounted, ref, nextTick, watch, onBeforeUnmount } from "vue";

import { userStore } from "../../stores/userStore";
import api from "../../services/api";


const receives = ref([]);
const loading = ref(true);
const error = ref(null);
const searchQuery = ref("");
const meta = ref({});
const currentPage = ref(1);
const perPage = 30;


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
watch([printingRR], ([printVal]) => {
    if (printVal) activeDropdown.value = null;
});

// Permissions
const canViewReceivingReports = computed(() => userStore.canViewReceivingReports);



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



function printingRR(rr) {
    activeDropdown.value = null;

    nextTick(() => {
        const link = document.createElement('a');
        link.href = `/receiving/DisplayRR/${rr.r_id}`;
        link.target = "_blank";
        link.rel = "noopener"; // SUPER IMPORTANT to prevent tab-lock freezing
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
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


function clearSearch() {
    searchQuery.value = "";
    fetchReceivingOrders();
}
</script>

<style scoped>
/* Dropdown slide + fade animation */



/* Table adjustments */
/* ============================================================
   🌟 HEADER – Premium Navy + Gold Theme
============================================================ */
.custom-headers {
  background: linear-gradient(135deg, #ffffff 0%, #f7f8fb 100%);
  border: 1px solid #e2e5ec;
  padding: 22px 26px;
  border-radius: 16px;
  margin-bottom: 20px;
  box-shadow: 0 6px 14px rgba(0,0,0,0.05);
}

.custom-headers h1 {
  font-size: 1.8rem;
  font-weight: 800;
  color: #2c3a57;
}

/* Search bar */
.custom-headers input {
  padding: 12px 18px;
  border-radius: 12px;
  background: #fcf6d9 !important;
  border: 1px solid #d9cfa1;
  transition: 0.25s ease;
  font-weight: 500;
}

.custom-headers input:focus {
  background: #fff8d1 !important;
  border-color: #d6b54a;
  box-shadow: 0 0 0 3px rgba(223, 185, 71, 0.35);
}

.custom-headers i.bi-x-circle-fill:hover {
  color: #b39533 !important;
}

/* ============================================================
   📋 TABLE – Raised minimalist cards
============================================================ */
.table {
  border-collapse: separate !important;
  border-spacing: 0 12px !important;
}

.table thead th {
  background: #eceef3 !important;
  color: #3e475d !important;
  padding: 16px;
  border: none;
  font-weight: 700;
  font-size: 0.92rem;
}

.table tbody tr {
  background: white;
  border-radius: 14px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.06);
  transition: 0.18s ease;
}

.table tbody tr:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 18px rgba(0,0,0,0.10);
}

.table td {
  padding: 14px 12px !important;
  color: #374155;
  font-weight: 500;
}

/* Status colors (optional) */
.table-success {
  background: #eaf7ea !important;
}

.table-danger {
  background: #fdeaea !important;
}

/* ============================================================
   ⋮ DROPDOWN – Smooth frosted floating menu
============================================================ */
.cursor-pointer {
  padding: 6px 10px;
  border-radius: 10px;
  transition: 0.2s ease;
}

.cursor-pointer:hover {
  background: #eef0ff;
}

.dropdown-menu-teleport {
  position: absolute;
  background: rgba(255,255,255,0.95);
  backdrop-filter: blur(12px);
  border-radius: 12px;
  min-width: 160px;
  padding: 10px 0;
  border: 1px solid #d5dae3;
  box-shadow: 0 12px 30px rgba(50,60,120,0.18);
  animation: dropdownFade 0.18s ease-out;
}

@keyframes dropdownFade {
  from { opacity: 0; transform: translateY(-6px); }
  to   { opacity: 1; transform: translateY(0); }
}

.dropdown-menu-teleport a {
  display: block;
  padding: 10px 16px;
  font-weight: 600;
  color: #3b4565;
}

.dropdown-menu-teleport a:hover {
  background: #f0f2ff;
  border-radius: 8px;
}

/* ============================================================
   🔢 PAGINATION – Circle modern nav
============================================================ */
.pagination .page-link {
  border-radius: 12px !important;
  padding: 7px 14px;
  border: 1px solid #cdd4e1;
  color: #4a5572;
  font-weight: 600;
}

.pagination .active .page-link {
  background: #5b6dff;
  border: none;
  color: white;
  box-shadow: 0 3px 10px rgba(90,110,255,0.35);
}

.page-circle {
  border-radius: 50% !important;
}

/* ============================================================
   GENERAL TABLE ALIGNMENT
============================================================ */
.table th,
.table td {
  vertical-align: middle;
}

</style>
