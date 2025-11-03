<template>
    <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5) ;">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Update stocks</h5>
                    <button type="button" class="btn-close" aria-label="Close" @click="closeModal" ></button>                   
                </div>
                <div class="modal-body">
                    <form>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="Product_name">Product Name</label>
                            <input type="text" v-model="form.product_name" class="form-control" id="product_name"/>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="ItemCode">Item Code</label>
                            <input type="text" v-model="form.item_code" class="form-control" id="ItemCode" readonly/>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="descriptions">Descriptions</label>
                            <input type="text" v-model="form.descriptions" class="form-control" id="descriptions" readonly/>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="quantity_onhand">Quantity On Hand</label>
                            <input type="number" v-model="form.quantity_onhand" class="form-control" id="quantity_onhand" />
                        </div>
                         <div class="col-md-12 mb-3">
                            <label class="form-label" for="quantity_in_stock">Quantity  in Stock</label>
                            <input type="number" v-model="form.quantity_in_stock" class="form-control" id="quantity_in_stock" />
                        </div>
                         <div class="col-md-12 mb-3">
                            <label class="form-label" for="unit_cost">Unit Cost</label>
                            <input type="number" v-model="form.unit_cost" class="form-control" id="unit_cost" />
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="product_type">Product Type</label>
                            <select v-model="form.product_type" class="form-select" id="product_type" >
                                <option value="" disabled selected> Select product Type</option>
                                <option value="Line_Hardware">Line Hardware</option>
                                <option value="Special_Hardware">Special Hardware</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, watch } from "vue";
import api from "../../services/api";
import { useAppStore } from "../../stores/appStore";
const props = defineProps({
        stock: {
                type: Object,
                required: true,
        },
});
const appStore = useAppStore();
const emit = defineEmits(["close","updated"]);
// ✅ Close modals
function closeModal() {
        emit("close");
}
const form = ref({
    product_name: "",
    item_code: "",
    descriptions: "",
    quantity_onhand: "",
    quantity_in_stock: "",
    unit_cost: "",
    product_type:"",
})
watch(
    () =>props.stock,
    (newStock) => {
       if(newStock){
         form.value.product_name = newStock.product_name || "";
        form.value.item_code = newStock.item_code || "";
        form.value.descriptions = newStock.descriptions || "";
        form.value.quantity_onhand = newStock.quantity_onhand || "";
        form.value.quantity_in_stock = newStock.quantity_in_stock || "";
        form.value.unit_cost = newStock.unit_cost || "";
        form.value.product_type = newStock.product_type || "";
       }
    },{
        immediate: true
    }
)
</script>