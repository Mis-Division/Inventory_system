<template>
    <!-- Modal -->
    <div class="modal fade show" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header">
                    <h5 class="modal-title">Add MRV Modal</h5>
                    <button type="button" class="btn-close" aria-label="Close" @click="$emit('close')"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <p>
                        This is where the form to add a Material Requisition Voucher (MRV)
                        would be implemented.
                    </p>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" @click="$emit('close')">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>


<script setup>
const emit = defineEmits(["close"]);
</script>
