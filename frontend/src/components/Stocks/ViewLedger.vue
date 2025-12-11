<template>
  <div class="modal fade show" role="dialog" style="display: block; background: rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">

        <!-- HEADER -->
        <div class="modal-header">
          <h5 class="modal-title">
            Stock Ledger Details
            <span class="text-success">
              ({{ stock[0]?.product_name || '-' }}) : Stocks {{ stock[0]?.Current_Stock || 0 }}
            </span>

          </h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <!-- BODY -->
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
                <th>Amount</th>
                <th>Remarks</th>
              </tr>
            </thead>

            <tbody ref="ledgerBody">
              <tr v-for="(entry, index) in stock" :key="entry.transaction_id ?? index" :class="rowHighlight(entry)">
                <td>{{ entry.created_at ? new Date(entry.created_at).toISOString().slice(0, 10) : '-' }}</td>
                <td>{{ entry.movement_type || '-' }}</td>
                <td>{{ entry.transaction_type || '-' }}</td>
                <td>{{ entry.reference || '-' }}</td>
                <td>{{ entry.Debit || 0 }}</td>
                <td>{{ entry.Credit || 0 }}</td>
                <td>{{ entry.Running_Balance || 0 }}</td>
                <td>{{ entry.amount || 0 }}</td>
                <td>
                  <span :class="badgeClass(entry.remarks)">
                    {{ entry.remarks || '-' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="stock.length === 0" class="text-center text-muted">
            No ledger data available for this item.
          </div>
         <div class="mb-2">
  <span style="color:#0d6efd; font-weight:600;"> - Original Stock</span> |
  <span style="color:#28a745; font-weight:600;"> G - Return Good Stock</span> |
  <span style="color:#ffc107; font-weight:600;"> U - Return Usable Stock</span>
</div>

        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { onUpdated, ref, nextTick } from "vue";

const props = defineProps({
  stock: { type: Array, required: true }
});

const emit = defineEmits(["close"]);

function closeModal() {
  emit("close");
}

const ledgerBody = ref(null);

onUpdated(() => {
  nextTick(() => {
    if (ledgerBody.value) {
      ledgerBody.value.scrollTop = ledgerBody.value.scrollHeight;
    }
  });
});

/* BADGE COLOR */
function badgeClass(val) {
  if (val === "G") return "badge badge-green";     // Good Stock - Green
  if (val === "U") return "badge badge-yellow";    // Usable Stock - Yellow
  return "badge badge-blue";                       // Original Stock - Blue
}


/* ROW COLOR BASED ON REMARKS */
function rowHighlight(entry) {
  if (entry.remarks === "G") return "row-green";
  if (entry.remarks === "U") return "row-blue";
  return "row-gray"; // good stock (normal)
}
</script>

<style scoped>
.modal-dialog {
  max-width: 70%;
}

.modal-body {
  overflow-x: auto;
}

.table td,
.table th {
  padding: 4px 6px !important;
  vertical-align: middle !important;
}

/* BADGES */
.badge {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  color: white;
}

.badge-green {
  background-color: #28a745; /* Good Stock */
}

.badge-yellow {
  background-color: #ffc107; /* Usable Stock */
  color: black; /* para readable */
}

.badge-blue {
  background-color: #0d6efd; /* Original Stock */
}


/* ROW HIGHLIGHTING */
.row-green {
  background-color: #e9f7ef !important;
  /* light green */
}

.row-blue {
  background-color: #e8f8fd !important;
  /* light blue */
}

.row-gray {
  background-color: #fafafa !important;
  /* subtle */
}
</style>
