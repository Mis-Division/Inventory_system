<template>
    <!-- ================= MAIN UPDATE MODAL ================= -->
    <div class="modal fade show" tabindex="-1" :style="{
        display: 'block',
        background: 'rgba(0,0,0,0.5)',
        pointerEvents: showSuccess ? 'none' : 'auto'
    }">

        <div class="modal-dialog modal-dialog-centered modal-xl modal-auto-fit">
            <div class="modal-content d-flex flex-column mrv_modal rounded-4 overflow-hidden">

                <!-- ================= HEADER ================= -->
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle me-1"></i>
                        Updated Material Requisition Voucher
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
                </div>

                <!-- ================= BODY ================= -->
                <div class="modal-body">
                    <div class="row g-3">

                        <!-- ================= COLUMN 1 : HEADER INFO ================= -->
                        <div class="col-md-4">
                            <div class="mb-2">
                                <label class="form-label fw-semibold">RFM #</label>
                                <input type="text" class="form-control" v-model="rfmNumber" disabled>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-semibold">Requested By</label>
                                <input type="text" class="form-control" v-model="requested_by" disabled>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-semibold">Department</label>
                                <input type="text" class="form-control" v-model="department" disabled>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-semibold">Department Head</label>
                                <input type="text" class="form-control" v-model="approved_by" disabled>
                            </div>
                        </div>

                        <!-- ================= COLUMN 2 : ITEMS ================= -->
                        <div class="col-md-8">
                            <div class="table-responsive mrv-table-wrapper">
                                <table class="table table-sm align-middle text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start">Material</th>
                                            <th>Units</th>
                                            <th>Requested</th>
                                            <th>Issued</th>
                                            <th>Stocks</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>

                                    <!-- ITEMS -->
                                    <tbody v-if="items.length">
                                        <tr v-for="(item, i) in items" :key="i">
                                            <td class="text-start">
                                                <strong>{{ item.material_code }}</strong><br>
                                                <small class="text-muted">{{ item.material_description }}</small>
                                            </td>

                                            <td>{{ item.units }}</td>
                                            <td class="fw-semibold">{{ item.requested_qty }}</td>

                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center"
                                                    v-model.number="item.issued_qty"
                                                    :max="Math.min(item.requested_qty, item.stocks)" min="0"
                                                    :disabled="isLocked" />
                                            </td>

                                            <td>{{ item.stocks }}</td>

                                            <td>
                                                <input type="text" class="form-control form-control-sm"
                                                    v-model="item.remarks" placeholder="Remarks" :disabled="isLocked" />
                                            </td>
                                        </tr>
                                    </tbody>

                                    <!-- EMPTY -->
                                    <tbody v-else>
                                        <tr>
                                            <td colspan="6" class="text-muted py-3">
                                                No items found for this MRV
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ================= FOOTER ================= -->
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeModal">
                        Close
                    </button>

                    <button class="btn btn-warning" @click="UpdateMrv" :disabled="isLocked">
                        <i class="bi bi-save me-1"></i>
                        Update MRV
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- ================= SUCCESS MODAL ================= -->
    <div v-if="showSuccess" class="custom-modal-backdrop">
        <div class="custom-modal">
            <div class="custom-header bg-success text-white">
                <h5 class="mb-0">Success</h5>
            </div>
            <div class="custom-body text-center">
                ✅ MRV successfully updated!
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-success" @click="closeSuccess">OK</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import api from "../../services/api";

const props = defineProps({
    mrv: { type: Object, required: true }
});

const emit = defineEmits(["close", "updated"]);

// ================= HEADER =================
const rfmNumber = ref("");
const requested_by = ref("");
const department = ref("");
const approved_by = ref("");
const status = ref("");

// ================= ITEMS =================
const items = ref([]);

// ================= LOCK =================
const isLocked = ref(false);

// ================= SUCCESS MODAL =================
const showSuccess = ref(false);

const closeSuccess = () => {
    showSuccess.value = false;

    // slight delay para smooth
    setTimeout(() => {
        emit("updated"); // refresh list
        emit("close");   // close update modal
    }, 50);
};
function closeModal() {
    emit("close");
}

// ================= FETCH MRV =================
const fetchMrv = async () => {
    try {
        const res = await api.get(`Mrv/showById/${props.mrv.mrv_id}`);

        if (res.data.success) {
            const header = res.data.data.header;

            rfmNumber.value = header.rfm_number;
            requested_by.value = header.requested_by;
            department.value = header.department;
            approved_by.value = header.approved_by;
            status.value = header.status;

            items.value = res.data.data.items.map(item => ({
                ...item,
                remarks: item.remarks ?? ""
            }));

            // lock kapag COMPLETE
            isLocked.value = header.status?.toUpperCase() === "COMPLETE";
        }
    } catch (error) {
        console.error("Failed to load MRV:", error);
    }
};

onMounted(fetchMrv);

// ================= UPDATE MRV =================
const UpdateMrv = async () => {
    if (isLocked.value) return;

    try {
        const payload = {
            items: items.value.map(item => ({
                mrv_item_id: item.mrv_item_id,
                issued_qty: item.issued_qty,
                remarks: item.remarks
            }))
        };

        const res = await api.put(
            `Mrv/updateMrv/${props.mrv.mrv_id}`,
            payload
        );

        if (res.data.success) {
            showSuccess.value = true;
        }

    } catch (error) {
        console.error("Failed to update MRV:", error);
        alert(error.response?.data?.message || "Failed to update MRV");
    }
};
</script>

<style scoped>
.mrv-table-wrapper {
    max-height: 60vh;
}

/* ================= SUCCESS MODAL ================= */
.custom-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
}

.custom-modal {
    width: 360px;
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
}

.custom-header,
.custom-footer {
    padding: 12px;
}

.custom-body {
    padding: 22px;
    font-size: 1rem;
}
</style>
