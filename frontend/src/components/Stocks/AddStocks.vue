<template>
    <div class="modal fade show" tabindex="1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-l modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5>Add Stocks</h5>
                    <button type="button" class=" btn btn-close btn-close-white" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <form class="row">
                        <div class="col-md-12">
                            <label class="form-label" for="Item Code">Product Name <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="itemCode" v-model="selectItemCode"
                                @change="handleStockChange">
                                <option value="" disabled>Select Product Name</option>
                                <option v-for="item in items" :key="item.ItemCode_id" :value="item.ItemCode_id">
                                    {{ item.product_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="product_name">Item Code </label>
                                   
                            <input type="text" id="product_name" v-model="stockDetails.ItemCode"
                                class="form-control" readonly />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="Descriptions">Descriptions </label>
                            <input type="text" id="description" class="form-control" v-model="stockDetails.description"
                                readonly />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="qty_onhand">Quantity Onhand <span
                                    class="text-danger">*</span></label>
                            <input type="number" id="qty_onhand" class="form-control" />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="qty_inStock">Quantity in Stock <span
                                    class="text-danger">*</span></label>
                            <input type="number" id="qty_inStock" class="form-control" />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="unit_cost">Unit Cost <span
                                    class="text-danger">*</span></label>
                            <input type="number" id="unit_cost" class="form-control" />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="product_type">Product Type <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="product_type">
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
                ✅ Item Code successfully updated!
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-success" @click="closeSuccess">OK</button>
            </div>
        </div>
    </div>
</template>
<script setup>
import { reactive, ref, onMounted } from "vue";
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
})

async function handleStockChange() {
    if (!selectItemCode.value) return;

    try {
        const res = await api.get(`/Items/getItemCode/${selectItemCode.value}`);
        const data = res.data.data;

        stockDetails.value.ItemCode = data.ItemCode;
        stockDetails.value.description = data.description;
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Failed to fetch Item details";
    }
}
//Confirmation first muna sir then pag confirn diretso na sa save
async function createStocks() {
    showConfirm.value = true;
}


//after Confirmation save na agad



async function fetchItemCode() {
    try {
        const res = await api.get(`/Items/getItemCode`);
        items.value = res.data.data || [];
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "failed to fetch ItemCode";
    }
}
function closeModal() {
    emit("close");
}
onMounted(() => {
    fetchItemCode();
})
</script>