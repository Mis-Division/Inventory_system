<template>
    <div class="print-section">
        <div v-if="loading" class="loading">Loading Request Materials...</div>

        <div v-else class="header-wrapper">
            <!-- MRV NUMBER -->
            <div class="mrv-number">    {{ mrv.mrv_number }}</div>
            <br>
            <!-- ================= HEADER ================= -->
            <table class="header-table">
                <tr>
                    <td class="logo-col">
                        <img src="../../assets/ISELCO1LOGO.png" class="header-logo" />
                    </td>
                    <td colspan="2" class="text-center">
                        <h3 class="company-name">ISABELA ELECTRIC COOPERATIVE, INC.</h3>
                        <h6 class="report-title">Victoria, Alicia, Isabela</h6>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="text-center">
                        <h4 class="company-name">MATERIAL REQUISITION VOUCHER</h4>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        To: <strong>THE GENERAL MANAGER / CEO</strong><br />
                        Please furnish the following materials for:
                    </td>
                    <td class="text-right">
                      {{ formattedMrvDate }}
                    </td>
                </tr>
            </table>

            <!-- ================= MATERIALS (COLUMN STYLE) ================= -->
            <table class="materials-wrapper">
                <tr>
                    <th class="materials-title">Material Code</th>
                    <th class="materials-description">Description</th>
                    <th class="qty-title">Quantity</th>
                    <th class="qty-remarks">Remarks</th>
                </tr>

                <tr>
                    <!-- MATERIAL CODE -->
                    <td class="materials-box with-divider">
                       
                            <div v-for="(item, i) in mrv.items" :key="i" class="material-line">
                                {{ item.material_code }}
                            </div>
                        

                        <div class="filler-text">
                            ====== Nothing to follows ======
                        </div>
                    </td>

                    <!-- DESCRIPTION -->
                    <td class="materials-box with-divider">
                        <div v-for="(item, i) in mrv.items" :key="i" class="material-line">
                            {{ item.material_description }}
                        </div>
                    </td>

                    <!-- QUANTITY -->
                    <td class="materials-box with-divider text-center">
                        <div v-for="(item, i) in mrv.items" :key="i" class="material-line">
                            {{ item.quantity }}
                        </div>
                    </td>

                    <!-- REMARKS -->
                    <td class="materials-box text-center">
                        <div v-for="(item, i) in mrv.items" :key="i" class="material-line">
                            {{ item.remarks }}
                        </div>
                    </td>
                </tr>
            </table>

            <!-- ================= CERTIFICATION ================= -->
            <p class="filler-text2">
                I hereby certify that the materials / supplies requisitioned above are
                necessary and will be used solely for the purpose stated above.
            </p>

            <!-- ================= SIGNATURES ================= -->
            <table class="signature-table">
                <tr>
                    <td>Prepared by:</td>
                    <td>Recommending Approval:</td>
                    <td>Approved by:</td>
                </tr>
                <tr>
                    <td>
                        <strong>{{ mrv.requested_by }}</strong><br />
                        Requisitioner
                    </td>
                    <td>
                        <strong>{{ mrv.approved_by }}</strong><br />
                        Dept. Head / Branch Manager
                    </td>
                    <td>
                        <strong>{{ mrv.approved_by_Gm }}</strong><br />
                        General Manager
                    </td>
                </tr>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import api from "../../services/api";

const props = defineProps({
    mrv_id: {
        type: Number,
        required: true,
    },
});

const loading = ref(true);
const mrv = ref({
    items: [],
});

const formattedMrvDate = computed(() => {
    if (!mrv.value.mrv_date) return "";
    return new Date(mrv.value.mrv_date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
});


onMounted(async () => {
    try {
        const res = await api.get(`Mrv/printMrv/${props.mrv_id}`);

        if (res.data.success) {
            mrv.value = {
                ...res.data.data.header,
                items: res.data.data.items,
            };

            setTimeout(() => {
                window.print();
            }, 400);
        }
    } catch (err) {
        console.error("Failed to fetch MRV data", err);
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
/* ================= BASE ================= */
.print-section {
    font-family: Arial, sans-serif;
    padding: 20px;
    font-size: 13px;
}

.loading {
    text-align: center;
    font-size: 14px;
}

.header-wrapper {
    position: relative;
}

.mrv-number {
    position: absolute;
    top: 0;
    right: 0;
    font-weight: bold;
    font-size: 24px;
}

/* ================= HEADER ================= */
.header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
    margin-top: 20px;
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

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
    font-size: 15px;
}

/* ================= MATERIALS ================= */
.materials-wrapper {
    width: 100%;
    border-collapse: collapse;
    margin-top: 14px;
}

.materials-wrapper th {
    border: 1px solid #000;
    padding: 6px;
    text-align: center;
    font-weight: bold;
}

.materials-box {
    vertical-align: top;
    height: 260px;
    padding: 6px;
    border-bottom: 1px solid #000;
}
.materials-title,
.qty-title,
.material-description,
 .qty-remarks{
  border: 1px solid #000;
  text-align: center;
  font-weight: bold;
  padding: 6px;
}
.with-divider {
    border-right: 1px solid #000;
}
.materials-title {
  width:30%;
}
.material-description{
     width:50%;
}
.qty-title {
  width: 10%;
}
.qty-remarks{
     width: 10%; 
}
.material-line {
    line-height: 20px;
    min-height: 20px;
    text-align: center;
  
}

.filler-text {
    margin-top: auto;
    text-align: center;
    font-style: italic;
    font-size: 12px;
    opacity: 0.8;
}

.filler-text2 {
    margin: 20px 0;
    font-style: italic;
    font-size: 14px;
}

/* ================= SIGNATURES ================= */
.signature-table {
    width: 100%;
    margin-top: 30px;
    text-align: center;
}

.signature-table td {
    padding-top: 25px;
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
