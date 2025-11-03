<template>
    <div class="modal fade show" tabindex="1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-l modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5>Add Stocks</h5>
                    <button type="button" class="btn btn-close btn-close-white" @click="closeModal"></button>
                </div>

                <div class="modal-body">
                    <form class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="itemCode">Product Name
                                <span class="text-danger">*</span>
                            </label>
                            <select id="itemCode" class="form-select" v-model="selectItemCode"
                                @change="handleStockChange">
                                <option value="">Select a product</option>
                                <option v-for="item in items" :key="item.ItemCode_id" :value="item.ItemCode_id">
                                    {{ item.product_name }}
                                </option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="ItemCode">Item Code </label>
                            <input type="text" id="ItemCode" v-model="stockDetails.ItemCode" class="form-control"
                                readonly />
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="description">Descriptions</label>
                            <input type="text" id="description" class="form-control" v-model="stockDetails.description"
                                readonly />
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="qty_onhand">Quantity Onhand
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" v-model="form.qty_onhand" id="qty_onhand" class="form-control" />
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="qty_inStock">Quantity in Stock
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" v-model="form.qty_inStock" id="qty_inStock" class="form-control" />
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="unit_cost">Unit Cost
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" v-model="form.unit_cost" id="unit_cost" class="form-control" />
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="product_type">Product Type
                                <span class="text-danger">*</span>
                            </label>
                            <select v-model="form.product_type" class="form-select" id="product_type" required>
                                <option value="">Select Product Type</option>
                                <option value="Line_Hardware">Line Hardware</option>
                                <option value="Special_Hardware">Special Hardware</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </form>

                    <div v-if="errorMessage" class="alert alert-danger py-2">
                        {{ errorMessage }}
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-warning" @click="closeModal">
                            <i class="bi bi-x-circle me-1"></i>Close
                        </button>
                        <button type="button" class="btn btn-success" @click="createStocks">
                            <i class="bi bi-plus-circle me-1"></i>Create
                        </button>
                    </div>
                </div>
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
                <p>Are you sure you want to Create this Stocks?</p>
            </div>
            <div class="custom-footer">
                <button class="btn btn-warning" @click="showConfirm = false">Cancel</button>
                <button class="btn btn-success" @click="confirmSave">Yes, Create</button>
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
                ✅ Stocks successfully Created!
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-success" @click="closeSuccess">OK</button>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted, nextTick } from "vue";
import api from "../../services/api";

const emit = defineEmits(["close"]);
const errorMessage = ref("");
const showConfirm = ref(false);
const showSuccess = ref(false);
const selectItemCode = ref("");
const items = ref([]);

const stockDetails = ref({
    ItemCode: "",
    description: "",
    product_name: "",
});

const form = ref({
    qty_onhand: "",
    qty_inStock: "",
    unit_cost: "",
    product_type: "",
});

async function handleStockChange() {
    if (!selectItemCode.value) return;
    try {
        const res = await api.get(`/Items/getItemCode/${selectItemCode.value}`);
        const data = res.data.data;
        stockDetails.value.ItemCode = data.ItemCode;
        stockDetails.value.product_name = data.product_name;
        stockDetails.value.description = data.description;
    } catch (err) {
        errorMessage.value =
            err.response?.data?.message || "Failed to fetch Item details";
    }
}

function createStocks() {
    // show confirmation modal first
    showConfirm.value = true;
}

/* ✅ MAIN FUNCTION — Save Stock */
async function confirmSave() {
    errorMessage.value = ""; // clear old errors

    // ✅ Validation: ensure qty_onhand and qty_inStock are equal
    if (Number(form.value.qty_onhand) !== Number(form.value.qty_inStock)) {
        errorMessage.value =
            "Quantity Onhand and Quantity In Stock must be equal before saving.";
        showConfirm.value = false;
        return;
    }

    try {
        // Send POST request to backend
        const res = await api.post("/stocks/createStock/", {
            item_code: stockDetails.value.ItemCode,
            product_name: stockDetails.value.product_name,
            descriptions: stockDetails.value.description,
            quantity_onhand: form.value.qty_onhand,
            quantity_in_stock: form.value.qty_inStock,
            unit_cost: form.value.unit_cost,
            product_type: form.value.product_type,
        });

        // ✅ Success — hide confirm, show success
        showConfirm.value = false;
        showSuccess.value = true;
    } catch (err) {
        // ❌ Error — show backend message
        showConfirm.value = false;
        errorMessage.value =
            err.response?.data?.message || "Failed to create stock.";
    }
}

function closeSuccess() {
    showSuccess.value = false;
    emit("close");
}

async function fetchItemCode() {
    try {
        const res = await api.get(`/Items/getItemCode`);
        items.value = res.data.data || [];

        await nextTick();
        initSelect2();
    } catch (err) {
        errorMessage.value =
            err.response?.data?.message || "Failed to fetch ItemCode";
    }
}

function initSelect2() {
    // Destroy existing select2 instance if any
    if ($("#itemCode").data("select2")) {
        $("#itemCode").select2("destroy");
    }

    $("#itemCode").select2({
        theme: "bootstrap-5",
        placeholder: "Search or select a product",
        allowClear: true,
        dropdownParent: $(".modal.show"),
    });

    $("#itemCode").on("change", function () {
        selectItemCode.value = $(this).val();
        handleStockChange();
    });
}

function closeModal() {
    emit("close");
}

onMounted(() => {
    fetchItemCode();
});
</script>
