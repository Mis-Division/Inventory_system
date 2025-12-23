<template>
  <div class="print-section">
    <div v-if="loading" class="loading">Loading Materials...</div>

    <div v-else>
      <!-- ================= RFM NUMBER (PAGE TOP-RIGHT) ================= -->
      <div class="rfm-number">
        {{ rfm.rfm_date }}<br />
        <strong>{{ rfm.rfm_number }}</strong>

      </div>

      <!-- ================= HEADER ================= -->
      <div class="header-wrapper">
        <table class="header-table">
          <tr>
            <td class="logo-col">
              <img src="../../assets/ISELCO1LOGO.png" class="header-logo" />
            </td>
            <td style="text-align: center;">
              <h3 class="company-name">ISABELA ELECTRIC COOPERATIVE, INC.</h3>
              <h6 class="report-title">REQUEST FOR MATERIALS</h6>
            </td>
          </tr>
        </table>
      </div>

      <!-- ================= BASIC DETAILS ================= -->
      <table class="details-table">
        <tr>
          <td class="label">Work Description: <label class="value">{{ rfm.work_description }}</label></td>

        </tr>
        <tr>
          <td class="label">Location/s: <label class="value">{{ rfm.location }}</label></td>

        </tr>
        <tr>
          <td class="label">Retirement / Replacement: <label class="value">{{ rfm.work_type }}</label></td>

        </tr>
      </table>

      <!-- ================= TECHNICAL DETAILS ================= -->
      <table class="details-table two-col">
        <tr>
          <td class="label">Primary Lines Retired:</td>
          <td class="segmented">{{ rfm.primary_lines_retired }}</td>
          <td class="label">Cut Out Assembly:</td>
          <td class="segmented">{{ rfm.cut_of_assembly }}</td>
        </tr>
        <tr>
          <td class="label">Sec & Serv Lines Retired:</td>
          <td class="segmented">{{ rfm.secondary_lines_retired }}</td>
          <td class="label">Meter/s:</td>
          <td class="segmented">{{ rfm.meters }}</td>
        </tr>
        <tr>
          <td class="label">Busted Transformer:</td>
          <td class="segmented">{{ rfm.busted_transformer }}</td>
          <td class="label">Pole/s:</td>
          <td class="segmented">{{ rfm.poles }}</td>
        </tr>
        <tr>
          <td class="label">Service Drop Wire:</td>
          <td class="segmented">{{ rfm.service_drop_wire }}</td>
        </tr>
      </table>

      <!-- ================= MATERIALS + QUANTITY ================= -->
      <table class="materials-wrapper">
  <tr>
    <th class="materials-title">Materials</th>
    <th class="qty-title">Quantity</th>
    <th v-if="hasRemarks" class="qty-remarks">Remarks</th>
  </tr>

  <tr>
    <!-- MATERIALS -->
    <td class="materials-box with-divider">
      <div v-for="(item, i) in rfm.items" :key="i" class="material-line">
        <strong>{{ item.material_description }}</strong>
      </div>

      <div class="filler-text">
        ============== Nothing to follows =================
      </div>
    </td>

    <!-- QUANTITY -->
    <td class="qty-box">
      <div v-for="(item, i) in rfm.items" :key="i" class="qty-line">
        ( {{ item.requested_qty }} {{ displayUnit(item.units, item.requested_qty) }} )
      </div>

      <div class="filler-text">&nbsp;</div>
    </td>

    <!-- REMARKS -->
<td v-if="hasRemarks" class="qty-box">
  <div
    v-for="(item, i) in rfm.items"
    :key="i"
    class="qty-line"
  >
    <span v-if="item.remarks && item.remarks.trim() !== ''">
      • <strong>{{ item.material_description }}</strong> – {{ item.remarks }}
    </span>
  </div>
</td>

  </tr>
</table>



      <!-- ================= OTHER DETAILS ================= -->
      <table class="details-table">
        <tr>
          <td class="label">MCO’s Details:</td>
        </tr>
        <tr>
          <td class="value">{{ rfm.mco_details }}</td>
        </tr>

        <tr>
          <td class="label">Other Important Reports / Disclosures:</td>
        </tr>
        <tr>
          <td class="value">{{ rfm.remarks }}</td>
        </tr>

        <tr>
          <td class="label">Requested By: <label class="value">{{ rfm.requested_by }}</label></td>


          <td class="label">Department: <label class="value">{{ rfm.department }}</label></td>


        </tr>

        <tr>
          <td class="label">Area Engr / Mngr / BS: <label class="value">{{ rfm.area_engineering }}</label></td>

        </tr>
        <tr>
          <td class="label">Warehouse Initial: <label class="value">{{ rfm.warehouse_initial }} </label></td>
          <td class="label">Date: <label class="value"> {{ rfm.warehouse_date }} </label></td>
        </tr>
      </table>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted,computed } from "vue";
import api from "../../services/api";

const props = defineProps({
  rfm_id: {
    type: Number,
    required: true,
  },
});

const loading = ref(true);
const rfm = ref({
  items: [],
});

function displayUnit(unit, qty) {
  if (!unit) return '';

  // basic pluralization (pc → pcs)
  if (qty > 1) {
    return unit.endsWith('s') ? unit : unit + 's';
  }

  return unit;
}


/* ✅ FIX: correct ref access */
const hasRemarks = computed(() => {
  return rfm.value.items.some(
    item => item.remarks && item.remarks.trim() !== ""
  );
});
onMounted(async () => {
  try {
    const res = await api.get(`/Rfm/print/${props.rfm_id}`);

    if (res.data.success) {
      rfm.value = res.data.data;

      // auto print after render
      setTimeout(() => {
        window.print();
      }, 400);
    }
  } catch (error) {
    console.error("Failed to fetch RFM:", error);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
/* ================= BASE ================= */
.print-section {
  position: relative;
  /* IMPORTANT for absolute positioning */
  font-family: Arial, sans-serif;
  padding: 20px;
  font-size: 13px;
}

.filler-text {
  margin-top: auto;
  text-align: center;
  font-style: italic;
  font-size: 12px;
  opacity: 0.8;
}


.loading {
  text-align: center;
  font-size: 14px;
}

/* ================= RFM NUMBER ================= */
.rfm-number {
  position: absolute;
  top: 15px;
  /* exact page margin */
  right: 20px;
  /* exact page margin */
  font-size: 14px;
  font-weight: bold;
}

/* ================= HEADER ================= */
.header-wrapper {
  margin-top: 30px;
  /* spacing below RFM No. */
}

.header-table {
  width: 100%;
  margin-bottom: 10px;
}

.logo-col {
  width: 90px;
}

.header-logo {
  width: 70px;
}

.company-name {
  margin: 0;
  font-weight: bold;
}

.report-title {
  margin: 0;
}

/* ================= DETAILS ================= */
.details-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 10px;
}

.details-table.two-col td {
  width: 25%;
}

.label {
  font-weight: bold;
  padding: 4px 6px;
  vertical-align: top;
}

.value {
  padding: 4px 6px;
  text-decoration: underline;
  cursor: pointer
}

/* PUTOL-PUTOL LINE */
.segmented {
  height: 18px;
  position: relative;
}

.segmented::after {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  bottom: 2px;
  height: 1px;
  background: repeating-linear-gradient(to right,
      #000 0px,
      #000 12px,
      transparent 12px,
      transparent 20px);
}

/* ================= MATERIALS ================= */
.materials-wrapper {
  width: 100%;
  border-collapse: collapse;
  margin-top: 14px;
}

.materials-title,
.qty-title,
.qty-remarks {
  border: 1px solid #000;
  text-align: center;
  font-weight: bold;
  padding: 6px;
}

.materials-title {
  width: 55%;
}

.qty-title {
  width: 15%;
}
.qty-remarks {
  width: 25%;
}

.materials-box {
  height: 260px;
  border-left: 1px solid #000;
  border-bottom: 1px solid #000;
  padding: 6px;
}

.qty-box {
  height: 260px;
  border-right: 1px solid #000;
  border-bottom: 1px solid #000;
  padding: 6px;
  text-align: center;
}

.with-divider {
  border-right: 1px solid #000;
}

.material-line,
.qty-line {
  line-height: 20px;

}

/* ================= PRINT ================= */
@media print {
  body * {
    visibility: hidden;
  }

  .print-section,
  .print-section * {
    visibility: visible;
  }

  .print-section {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
  }
}
</style>
