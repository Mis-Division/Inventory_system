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
                <th>Remarks</th>
              </tr>
            </thead>

            <tbody ref="ledgerBody">
              <tr 
                v-for="(entry, index) in stock" 
                :key="entry.transaction_id ?? index"
                :class="rowHighlight(entry)"
              >
                <td>{{ entry.created_at ? new Date(entry.created_at).toISOString().slice(0,10) : '-' }}</td>
                <td>{{ entry.movement_type || '-' }}</td>
                <td>{{ entry.transaction_type || '-' }}</td>
                <td>{{ entry.reference || '-' }}</td>
                <td>{{ entry.Debit || 0 }}</td>
                <td>{{ entry.Credit || 0 }}</td>
                <td>{{ entry.Running_Balance || 0 }}</td>

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
  if (val === "G") return "badge badge-green";
  if (val === "U") return "badge badge-blue";
  return "badge badge-gray";
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
  max-width: 60%;
}

.modal-body {
  overflow-x: auto;
}

.table td, .table th {
  padding: 4px 6px !important;
  vertical-align: middle !important;
}

/* BADGES */
.badge {
  display: inline-block;
  padding: 2px 6px;
  font-size: 11px;
  border-radius: 4px;
  font-weight: 600;
  line-height: 1;
}

.badge-green {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #b2d8b9;
}

.badge-blue {
  background-color: #d1ecf1;
  color: #0c5460;
  border: 1px solid #9bd2d8;
}

.badge-gray {
  background-color: #f0f0f0;
  color: #555;
  border: 1px solid #d0d0d0;
}

/* ROW HIGHLIGHTING */
.row-green {
  background-color: #e9f7ef !important; /* light green */
}

.row-blue {
  background-color: #e8f8fd !important; /* light blue */
}

.row-gray {
  background-color: #fafafa !important; /* subtle */
}
</style>
