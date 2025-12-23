<template>
    <div class="main-container">

        <!-- HEADER -->
        <div class="custom-headers mb-3">
            <div class="w-100">
                <h1 class="m-0 mb-3">
                    <i class="bi bi-info-circle text-primary"></i>
                    Material Charge Ticket - Line Hardware
                </h1>
            </div>

            <div class="d-flex justify-content-between align-items-center w-100">

                <!-- Search -->
                <div class="position-relative" style="width:35%; min-width:300px;">
                    <input type="text" v-model="searchQuery" @keyup.enter="handleSearchEnter" class="form-control pe-5"
                        placeholder="Search Line Hardware..." style="background-color:#FCF6D9;" />

                    <i v-if="searchQuery" class="bi bi-x-circle-fill text-muted" @click="clearSearch"
                        style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.2rem;"></i>
                </div>

                <!-- Release -->
                <button v-if="canAddLineHardware" @click="addMct" class="btn btn-success">
                    <i class="bi bi-check2-circle me-1"></i>
                    Release Line Hardware
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-4 text-muted">
            Loading Material Charge Ticket - Line Hardware...
        </div>

        <!-- Error -->
        <div v-if="error" class="alert alert-danger">
            {{ error }}
        </div>
        <!-- TABLE -->
        <div v-if="!loading">
            <table class="table table-hover align-middle text-center">
                <thead class="table-secondary">
                    <tr>
                        <th>MCT #</th>
                        <th>MRV #</th>
                        <th>Requisitioner</th>
                        <th>Received By</th>
                        <th>Total Amount</th>
                        <th>Date Released</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody v-if="request.length">
                    <tr v-for="mct in request" :key="mct.mct_id">
                        <td>{{ mct.mct_number }}</td>
                        <td>{{ mct.mrv_number }}</td>
                        <td>{{ mct.requested_by }}</td>
                        <td>{{ mct.received_by ?? '—' }}</td>
                        <td>₱ {{ Number(mct.grand_total).toFixed(2) }}</td>
                        <td>{{ formatDate(mct.created_at) }}</td>
                        <td>
                            <span class="badge" :class="statusClass(mct.status)">
                                {{ mct.status }}
                            </span>
                        </td>
                        <!-- ACTION -->
                        <td>
                            <div class="cursor-pointer" @click="toggleDropdown(mct.mct_id, $event)">
                                <i class="bi bi-three-dots"></i>
                            </div>
                            <teleport to="body">
                                <transition name="fade">
                                    <div v-if="activeDropdown === mct.mct_id" :ref="el => dropdownRefs[mct.mct_id] = el"
                                        class="dropdown-menu-teleport" :style="getDropdownStyle(mct.mct_id)"
                                        @click.stop>
                                        <a v-if="canEditLineHardware" href="#" class="dropdown-item text-success"
                                            @click.prevent="editMrv(mct)">
                                            <i class="bi bi-pencil me-2"></i>Edit
                                        </a>
                                        <a v-if="canDeleteLineHardware" href="#" class="dropdown-item text-danger"
                                            @click.prevent="deleteMrv(mct)">
                                            <i class="bi bi-trash me-2"></i>Delete
                                        </a>
                                        <div v-if="!canEditLineHardware && !canDeleteLineHardware"
                                            class="dropdown-item text-muted">
                                            <i class="bi bi-lock me-2"></i>No permission
                                        </div>
                                    </div>
                                </transition>
                            </teleport>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr>
                        <td colspan="8" class="text-muted">
                            No Line Hardware found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <nav v-if="meta.last_page > 1" class="mt-3">
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
<addHardware v-if="showAddHardwareModal" @close="closeAddHardwareModal()" />
</template>

<script setup>
import { ref, computed, onMounted, nextTick, onBeforeUnmount } from "vue";
import { userStore } from "../../stores/userStore";
import api from "../../services/api";
import addHardware from "../../components/LineHardware/AddLineHardware.vue"
import { useAppStore } from "../../stores/appStore";


/*========Modals========*/
const showAddHardwareModal = ref(false);
const showEditHardwareModal = ref(false);
const showDeleteHardwareModal = ref(false);

/* ===== APP STORE ===== */
const appStore = useAppStore();

/* ===== PERMISSIONS ===== */
const canAddLineHardware = computed(() => userStore.canAddLineHardware);
const canEditLineHardware = computed(() => userStore.canEditLineHardware);
const canDeleteLineHardware = computed(() => userStore.canDeleteLineHardware);

/* ===== STATE ===== */
const searchQuery = ref("");
const request = ref([]);
const loading = ref(false);
const error = ref(null);

const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
});

const currentPage = ref(1);
const perPage = 10;

/* ===== DROPDOWN ===== */
const activeDropdown = ref(null);
const dropdownPositions = ref({});
const dropdownRefs = ref({});

/* ===== FETCH ===== */
async function displayMct() {
    loading.value = true;
    error.value = null;

    try {
        const res = await api.get("Mct/displayMct", {
            params: {
                product_type: "Line Hardware",
                search: searchQuery.value,
                page: currentPage.value,
                per_page: perPage
            }
        });

        request.value = res.data.data;
        meta.value = {
            
            current_page: res.data.current_page,
            last_page: res.data.last_page,
            per_page: res.data.per_page,
            total: res.data.total
        };

    } catch {
        error.value = "Failed to load Line Hardware data.";
    } finally {
        loading.value = false;
    }
}

/* ===== ACTIONS ===== */
function handleSearchEnter() {
    currentPage.value = 1;
    displayMct();
}

function clearSearch() {
    searchQuery.value = "";
    currentPage.value = 1;
    displayMct();
}

function changePage(page) {
    if (page < 1 || page > meta.value.last_page) return;
    currentPage.value = page;
    displayMct();
}




/* ===== DROPDOWN LOGIC ===== */
function toggleDropdown(id, event) {
    event.stopPropagation();
    activeDropdown.value = activeDropdown.value === id ? null : id;

    nextTick(() => {
        const rect = event.currentTarget.getBoundingClientRect();
        dropdownPositions.value[id] = {
            top: rect.bottom + window.scrollY,
            left: rect.left + window.scrollX
        };
    });
}

function getDropdownStyle(id) {
    const pos = dropdownPositions.value[id] || { top: 0, left: 0 };
    const width = 180;

    let left = pos.left;
    if (left + width > window.innerWidth) left -= width - 24;

    return {
        position: "absolute",
        top: pos.top + "px",
        left: left + "px",
        zIndex: 3000,
        minWidth: width + "px",
        background: "#fff",
        border: "1px solid #ddd",
        borderRadius: "6px",
        boxShadow: "0 2px 8px rgba(0,0,0,.15)"
    };
}

function handleClickOutside(e) {
    const el = dropdownRefs.value[activeDropdown.value];
    if (!el || !el.contains(e.target)) activeDropdown.value = null;
}

/* ===== HELPERS ===== */
function formatDate(date) {
    return new Date(date).toLocaleDateString();
}

function statusClass(status) {
    if (status === "PENDING") return "bg-warning text-dark";
    if (status === "RELEASED") return "bg-success";
    if (status === "CANCELLED") return "bg-danger";
    return "bg-secondary";
}

/* ===== INIT ===== */
onMounted(() => {
    document.addEventListener("click", handleClickOutside);
    displayMct();
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
});

/* ===== Add MODAL LOGIC ===== */
async function addMct(){
 
    showAddHardwareModal.value = true;
}

/* ===== MODAL HANDLERS ===== */
function closeAddHardwareModal() {
    showAddHardwareModal.value = false;
    //displayMct();
}
</script>




<style>
/* ============================================================
   🌟 HEADER – Executive Clean Look
============================================================ */
.custom-headers {
    background: #ffffff;
    border: 1px solid #e3e7ee;
    padding: 20px 25px;
    border-radius: 14px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.custom-headers h1 {
    font-weight: 700;
    font-size: 1.7rem;
    color: #2e3b54;
}

/* ============================================================
   🔍 SEARCH BAR – Sleek pill style
============================================================ */
.custom-headers input {
    border-radius: 50px !important;
    padding: 12px 18px;
    padding-right: 45px !important;
    background: #f7f9fc !important;
    border: 1px solid #cdd6e4;
    transition: 0.25s ease;
}

.custom-headers input:focus {
    background: white !important;
    border-color: #6f9bff;
    box-shadow: 0 0 0 3px rgba(111, 155, 255, 0.25);
}

/* Clear icon */
.custom-headers i.bi-x-circle-fill {
    color: #8b96af !important;
    cursor: pointer;
}

/* Add MRV Button */
.custom-headers .btn-success {
    background: #4caf50;
    border-radius: 10px;
    padding: 10px 16px;
    font-weight: 600;
    transition: 0.2s ease;
}

.custom-headers .btn-success:hover {
    background: #43a047;
}

/* ============================================================
   📋 TABLE – Premium corporate style
============================================================ */
.table {
    border-collapse: separate !important;
    border-spacing: 0 10px !important;
}

.table thead tr td {
    background: #e9edf5;
    padding: 15px;
    font-weight: 700;
    color: #435067;
    border: none;
}

.table tbody tr {
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06);
    transition: 0.15s ease;
}

.table tbody tr:hover {
    transform: scale(1.01);
    box-shadow: 0 5px 14px rgba(0, 0, 0, 0.10);
}

.table td {
    padding: 14px 10px !important;
    color: #435067;
    font-weight: 500;
}

/* Status Highlighting */
.table-success {
    background-color: #e8f5e9 !important;
}

.table-danger {
    background-color: #fdecea !important;
}

/* ============================================================
   ⋮ DROPDOWN – Soft elegant floating menu
============================================================ */
.cursor-pointer {
    padding: 6px 10px;
    border-radius: 8px;
}

.cursor-pointer:hover {
    background: #eef1ff;
    transition: 0.2s;
}

.dropdown-menu-teleport {
    position: absolute;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    min-width: 170px;
    padding: 8px 0;
    border: 1px solid #d5dbea;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
    backdrop-filter: blur(10px);
    animation: fadeDropdown 0.15s ease-out;
}

@keyframes fadeDropdown {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-menu-teleport a {
    display: block;
    padding: 10px 15px;
    font-weight: 600;
    color: #3b4a6b;
}

.dropdown-menu-teleport a:hover {
    background: #f1f4ff;
    border-radius: 8px;
}

/* ============================================================
   🔢 PAGINATION – minimal rounded
============================================================ */
.pagination {
    align-items: center;
    /* Centers the pagination items vertically */
    display: flex !important;
    /* Force flex to apply alignment */
    margin-top: 10px;
    /* Adjust spacing above */
}

.pagination .page-item {
    display: flex;
    align-items: center;
    /* Center each button vertically */
}

.pagination .page-link {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}
</style>
