<template>
    <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5) ;">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Update stocks</h5>
                    <button type="button" class="btn-close" aria-label="Close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <form class="row g-3">
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="Product_name">Product Name</label>

                            <input type="text" v-model="form.product_name" class="form-control" disabled />
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="ItemCode">Item Code</label>
                            <input type="text" v-model="form.item_code" class="form-control" id="ItemCode"
                                disabled />
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="descriptions">Descriptions</label>
                            <input type="text" v-model="form.descriptions" class="form-control" id="descriptions"
                                disabled />
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="quantity_onhand">Quantity On Hand</label>
                            <input type="number" v-model="form.quantity_onhand" class="form-control"
                                id="quantity_onhand" />
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="quantity_in_stock">Quantity in Stock</label>
                            <input type="number" v-model="form.quantity_in_stock" class="form-control"
                                id="quantity_in_stock" />
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="unit_cost">Unit Cost</label>
                            <input type="number" v-model="form.unit_cost" class="form-control" id="unit_cost" />
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="product_type">Product Type</label>
                            <select v-model="form.product_type" class="form-select" id="product_type">
                                <option value="" disabled selected> Select product Type</option>
                                <option value="Line_Hardware">Line Hardware</option>
                                <option value="Special_Hardware">Special Hardware</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>

                        <div v-if="errorMessage" class="alert alert-danger py-2">
                            {{ errorMessage }}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" @click="closeModal"><i class="bi bi-x"></i>
                                Close</button>
                            <button type="button" class="btn btn-success" @click="updateStocks"><i
                                    class="bi bi-pencil"></i> Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div v-if="showConfirm" class="custom-modal-backdrop">
        <div class="custom-modal">
            <div class="custom-header bg-primary text-white">
                <h5 class="mb-0">Confirm Action</h5>
            </div>
            <div class="custom-body">
                <p>Are you sure you want to update this Stocks?</p>
            </div>
            <div class="custom-footer">
                <button class="btn btn-warning" @click="showConfirm = false">Cancel</button>
                <button class="btn btn-success" @click="confirmSave">Yes, Update</button>
            </div>
        </div>
    </div>

    <!-- ✅ Success Modal -->
    <div v-if="showSuccess" class="custom-modal-backdrop">
        <div class="custom-modal">
            <div class="custom-header bg-success text-white">
                <h5 class="mb-0">Success</h5>
            </div>
            <div class="custom-body text-center">
                ✅ Stocks successfully updated!
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-success" @click="closeSuccess">OK</button>
            </div>
        </div>
    </div>

    <!-- ❌ Error Modal -->
    <div v-if="showError" class="custom-modal-backdrop">
        <div class="custom-modal">
            <div class="custom-header bg-danger text-white">
                <h5 class="mb-0">Error</h5>
            </div>
            <div class="custom-body text-center">
                ❌ Failed to update Stocks. Please try again.
            </div>
            <div class="custom-footer text-center">
                <button class="btn btn-danger" @click="showError = false">Close</button>
            </div>
        </div>
    </div>
</template>
<script setup>
import { reactive, ref, onMounted, nextTick, watch } from "vue";
import api from "../../services/api";
import { useAppStore } from "../../stores/appStore";

const props = defineProps({
    stock: { type: Object, required: true },
});
const localStocks = reactive({ ...props.stock });
const emit = defineEmits(["close", "updated"]);
const appStore = useAppStore();
const showConfirm = ref(false);
const showSuccess = ref(false);
const showError = ref(false);
const loading = ref(false);
const errorMessage = ref("");

const form = ref({
    product_name: "",
    item_code: "",
    descriptions: "",
    quantity_onhand: 0,
    quantity_in_stock: 0,
    unit_cost: 0,
    product_type: "",
})

watch(
    () => props.stock,
    (newStock) => {
        Object.assign(localStocks, newStock);
        form.value.product_name = newStock.product_name;
        form.value.item_code = newStock.item_code;
        form.value.descriptions = newStock.descriptions;
        form.value.quantity_onhand = newStock.quantity_onhand;
        form.value.quantity_in_stock = newStock.quantity_in_stock;
        form.value.unit_cost = newStock.unit_cost;
        form.value.product_type = newStock.product_type;
    },
    { immediate: true }
);

async function updateStocks(){
    showConfirm.value = true;
}
async function confirmSave(){
    if(!props.stock?.id){
        errorMessage.value = "Invalid stock ID.";
        return;
    }
    try{
        appStore.showLoading();
        const payload = {
            quantity_onhand: form.value.quantity_onhand,
            quantity_in_stock: form.value.quantity_in_stock,
            unit_cost: form.value.unit_cost,
            product_type: form.value.product_type,
        };
         await api.put(`/stocks/updateStocks/${props.stock.id}`, payload);
         showSuccess.value = true;
         emit("updated");
    } catch(err){
            console.error("Error updating stocks:", err);
            showError.value = true;
            errorMessage.value = err.response?.data?.message || "An error occurred while updating stocks.";
    }finally{
        appStore.hideLoading();
    }
}


function closeModal() {
    emit("close");
}
function closeSuccess() {
    showSuccess.value = false;
   closeModal();
}
</script>
