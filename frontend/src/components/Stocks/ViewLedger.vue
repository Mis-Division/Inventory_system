<template>
  <div class="modal fade show" role="dialog" style="display: block; background: rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            Stock Ledger Details
            <span class="text-warning" style="text-align: end;">
              ({{ stock[0]?.product_name || '-' }}: {{ stock[0]?.Current_Stock || 0 }})
            </span>
          </h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>
        <div class="modal-body table-responsive-vertical">
          <table class="table table-hover table-bordered text-center">
            <thead>
              <tr>
                <th>Date</th>
                <th>Movement</th>
                <th>Type</th>
                <th>Transaction Reference</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Running Balance</th>
                <th>Condition</th>
              </tr>
            </thead>
            <tbody ref="ledgerBody">
              <tr v-for="(entry, index) in stock" :key="entry.transaction_id ?? index"
                :class="{ 'table-warning': entry.transaction_type === 'RETURN' }">
                <td>{{ entry.created_at ? (new Date(entry.created_at).toISOString().slice(0, 10)) : '-' }}</td>
                <td>{{ entry.movement_type || '-' }}</td>
                <td>{{ entry.transaction_type || '-' }}</td>
                <td>{{ entry.reference || '-' }}</td>
                <td>{{ entry.Debit || 0 }}</td>
                <td>{{ entry.Credit || 0 }}</td>
                <td>{{ entry.Running_Balance || 0 }}</td>
                <td>
                  <span v-if="entry.transaction_type === 'RETURN'">
                    {{ entry.mcrt_condition || '-' }}
                  </span>
                  <span v-else>-</span>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="stock.length === 0" class="text-center text-muted">
            No ledger data available for this item.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


<script setup>
import { onUpdated, ref, nextTick } from 'vue';

const props = defineProps({
  stock: { type: Array, required: true }
});
const emit = defineEmits(['close']);

function closeModal() {
  emit('close');
}

const ledgerBody = ref(null);

onUpdated(() => {
  nextTick(() => {
    if (ledgerBody.value) {
      ledgerBody.value.scrollTop = ledgerBody.value.scrollHeight;
    }
  });
});
</script>

<style scoped>
.modal-dialog {
  max-width: 60%;
}

.modal-body {
  overflow-x: auto;
}
</style>
