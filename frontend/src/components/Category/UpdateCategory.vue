<template>
        <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0, 0, 0, 0.5);">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                        <div class="modal-content">
                                <div class="modal-header bg-warning text-white">
                                        <h5 class="modal-title">Update Item Code</h5>
                                        <button type="button" class="btn-close" aria-label="Close"
                                                @click="closeModal"></button>
                                </div>
                                <!-- Body -->
                                <div class="modal-body">
                                        <form @submit.prevent="showConfirm = true">
                                               
                                                <div class="mb-3">
                                                        <label class="form-label">Product Name</label>
                                                        <input v-model="form.product_name" type="text"
                                                                class="form-control" />
                                                </div>
                                                <div class="mb-3">
                                                        <label class="form-label">Description</label>
                                                        <textarea v-model="form.description" class="form-control"
                                                                rows="3"></textarea>
                                                </div>
                                                <div class="mb-3">
                                                        <label class="form-label">Acct. Code</label>
                                                        <input v-model="form.accounting_code" type="text"
                                                                class="form-control" />
                                                </div>
                                                <div class="mb-3">
                                                        <label class="form-label">Item Category</label>
                                                        <select class="form-select" v-model="form.item_category">
                                                                <option value="">Select Category</option>
                                                                <option value="Line Hardware">Line Hardware</option>
                                                                <option value="Special Hardware">Special Hardware</option>
                                                                <option value="Motorpool">Motorpool</option>
                                                                <option value="Tools">Tools</option>
                                                        </select>
                                                </div>
                                                <div class="mb-3">
                                                        <label class=" form-label" for="units">Units</label>
                                                        <select class="form-select" id="unitsName" v-model="form.units"
                                                                style="width: 100%;">
                                                                <option value="">Select Unit</option>
                                                                <option v-for="unit in units" :key="unit.id"
                                                                        :value="unit.unit_name">
                                                                        {{ unit.unit_name }}
                                                                </option>
                                                        </select>

                                                </div>
                                                <div v-if="errorMessage" class="alert alert-danger py-2">
                                                        {{ errorMessage }}
                                                </div>
                                                <div class="text-end">
                                                        <button type="button" class="btn btn-secondary me-2"
                                                                @click="closeModal">Cancel</button>
                                                        <button type="submit" class="btn btn-success"
                                                                :disabled="loading">
                                                                <span v-if="loading"
                                                                        class="spinner-border spinner-border-sm me-2"></span>
                                                                Save Changes
                                                        </button>
                                                </div>
                                        </form>
                                </div>
                        </div>
                </div>

                <!-- Confirm Modal -->
                <div v-if="showConfirm" class="custom-modal-backdrop">
                        <div class="custom-modal">
                                <div class="custom-header bg-primary text-white">
                                        <h5 class="mb-0">Confirm Action</h5>
                                </div>
                                <div class="custom-body">
                                        <p>Are you sure you want to update this Item Code?</p>
                                </div>
                                <div class="custom-footer">
                                        <button class="btn btn-warning" @click="showConfirm = false">Cancel</button>
                                        <button class="btn btn-success" @click="confirmSave">Yes, Update</button>
                                </div>
                        </div>
                </div>

                <!-- Success Modal -->
                <div v-if="showSuccess" class="custom-modal-backdrop">
                        <div class="custom-modal">
                                <div class="custom-header bg-success text-white">
                                        <h5 class="mb-0">Success</h5>
                                </div>
                                <div class="custom-body text-center">
                                        ✅ Item Code successfully updated!
                                </div>
                                <div class="custom-footer text-center">
                                        <button class="btn btn-success" @click="closeSuccess">OK</button>
                                </div>
                        </div>
                </div>

                <!-- Error Modal -->
                <div v-if="showError" class="custom-modal-backdrop">
                        <div class="custom-modal">
                                <div class="custom-header bg-danger text-white">
                                        <h5 class="mb-0">Error</h5>
                                </div>
                                <div class="custom-body text-center">
                                        ❌ Failed to update Item Code. Please try again.
                                </div>
                                <div class="custom-footer text-center">
                                        <button class="btn btn-danger" @click="showError = false">Close</button>
                                </div>
                        </div>
                </div>
        </div>
</template>

<script setup>
import { watch, reactive, ref, onMounted, nextTick, computed } from "vue";
import api from "../../services/api";
import { useAppStore } from "../../stores/appStore";

// Props
const props = defineProps({
        item: {
                type: Object,
                required: true,
        },
});

// Emits
const emit = defineEmits(["close", "updated"]);

const appStore = useAppStore();
const form = ref({
        product_name: "",
        description: "",
        item_category: "",
        accounting_code: "",
        units: "",
});

const loading = ref(false);
const errorMessage = ref("");
const showConfirm = ref(false);
const showSuccess = ref(false);
const showError = ref(false);
const units = ref([]);
const selectUnits = ref("");

// Watch for prop changes
watch(
        () => props.item,
        (newItem) => {
                if (newItem) {
                        
                        form.value.product_name = newItem.product_name || "";
                        form.value.description = newItem.description || "";
                        form.value.accounting_code = newItem.accounting_code || "";
                        form.value.item_category = newItem.item_category || "";
                        form.value.units = newItem.units || "";
                }
        },
        { immediate: true }
);

// ✅ Confirm Update Action
async function confirmSave() {
        showConfirm.value = false;
        await updateItemCode();
}


async function fetchUnits() {
        try {
                const res = await api.get("Units/display");
                units.value = res.data.data || [];
                await nextTick(initUnitSelect)
        } catch (err) {
                console.error("Failed to fetch units", err);
        }
}
// ---------------- UNIT SELECT2 (PER ROW) ----------------
function initUnitSelect() {
        // Destroy previous select2 instance
        if ($("#unitsName").data("select2")) {
                $("#unitsName").select2("destroy");
        }

        // Initialize Select2 properly
        $("#unitsName").select2({
                theme: "bootstrap-5",
                placeholder: "Select Unit",
                allowClear: true,
                dropdownParent: $(".modal.show"), // spelling fix: dropdownParent
        }).on("change", function () {
                const val = $(this).val();
                selectUnits.value = val;
                form.value.units = val; // <-- THIS IS IMPORTANT
        });
}

// ✅ Update Function
async function updateItemCode() {
        if (!props.item?.ItemCode_id) {
                errorMessage.value = "Invalid item selected.";
                return;
        }

        try {
                loading.value = true;
                appStore.showLoading();
                const payload = {
                        
                        product_name: form.value.product_name,
                        description: form.value.description,
                        accounting_code: form.value.accounting_code,
                        item_category: form.value.item_category,
                        units: form.value.units,
                };
                await api.put(`/Items/updateItemCode/${props.item.ItemCode_id}`, payload);
                showSuccess.value = true;
                emit("updated"); // Refresh parent list
        } catch (err) {
                console.error("❌ Update failed:", err);
                showError.value = true;
                errorMessage.value = err.response?.data?.message || "Failed to update item code.";
        } finally {
                loading.value = false;
                appStore.hideLoading();
        }
}

// ✅ Close modals
function closeModal() {
        emit("close");
}
function closeSuccess() {
        showSuccess.value = false;
        closeModal();
}
onMounted(() => {
        fetchUnits().then(() => nextTick(initUnitSelect));
});
</script>
