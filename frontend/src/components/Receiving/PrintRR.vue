<template>
    <div class="print-section">
        <div v-if="loading" class="loading">Loading RR...</div>

        <div v-else>
            <!-- HEADER -->
            <table class="header-table">
                <tr class="header-row">
                    <td>
                        <img src="../../assets/ISELCO1LOGO.png" alt="Company Logo" class="header-logo">
                    </td>
                    <td>
                        <h1 class="company-name">ISABELA ELECTRIC COOPERATIVE, INC.</h1>
                        <h6 class="report-title">Victoria, Alicia, Isabela</h6>
                    </td>
                </tr>
            </table>

            <table class="rr-table">

                <tr>
                    <th colspan="2" rowspan="2">
                        <h1 style="font-weight: bold;">RECEIVING REPORT</h1>
                    </th>
                    <th colspan="2" style="text-align: left;">Purchase Order No.<br />
                        <h6 class="report-title">
                            {{ rrData.po_number }}</h6>
                    </th>
                    <th colspan="2" style="text-align: left;"> Invoice Number<br />
                        <h6 class="report-title">
                            {{ rrData.invoice_number }}</h6>
                    </th>
                    <th colspan="2" style="text-align: left;">Date<br />
                        <h6 class="report-title">
                            {{ rrData.receive_date }}</h6>
                    </th>
                </tr>
                <tr>

                    
                    <th colspan="2" style="text-align: left;">Receiving reports<br />
                        <h6 class="report-title">
                            {{ rrData.rr_number }}</h6>
                    </th>
                    <th colspan="3" style="text-align: left;"> Delivery Receipt No.<br />
                        <h6 class="report-title">
                            {{ rrData.dr_number }}</h6>
                    </th>

                </tr>
                <tr>
                    <th colspan="2" style="text-align: left;">SUPPLIER: <strong style="font-size: 15px;">
                        {{ rrData.supplier_name }}</strong></th>
                    <th colspan="5" style="text-align: left;">ADDRESS: <strong style="font-size: 15px;">
                        {{ rrData.address }}</strong></th>
                </tr>
                <tr style="font-weight: bold;">
                    <th style="width: 15%;"  rowspan="2"><strong style="font-size: 15px;">Material Code</strong></th>
                    <th style="width: 40%" rowspan="2"><strong style="font-size: 15px;">Description</strong></th>
                    <th style="width: 10%" rowspan="2"><strong style="font-size: 15px;">Unit</strong></th>
                    <th style="width: 15%" colspan="2"><strong style="font-size: 15px;">Quantity</strong></th>
                    <th style="width: 10%" rowspan="2"><strong style="font-size: 15px;">Unit Cost</strong></th>
                    <th style="width: 10%" rowspan="2"><strong style="font-size: 15px;">Total Cost</strong></th>
                </tr>
                <tr style="font-weight: bold;">
                    <th>Order</th>
                    <th>Received</th>
                </tr>

                <tbody>
                    <tr v-for="item in rrData.items" :key="item.ItemCode">
                        <td>{{ item.ItemCode }}</td>
                        <td>{{ item.description }}</td>
                        <td>{{ item.units }}</td>
                        <td>{{ item.quantity_order }}</td>
                        <td>{{ item.quantity_received }}</td>
                        <td>{{ formatCurrency(item.unit_cost) }}</td>
                        <td>{{ formatCurrency(item.total_cost) }}</td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align: right;"><strong>Grand Total:</strong></td>
                        <td>{{ formatCurrency(rrData.grand_total) }}</td>
                    </tr>
                </tbody>
            </table>


            <!-- SIGNATURE BOXES -->
            <div class="signature-container">
                <div class="signature-box">
                    Received By:<br />

                    <div class="signature-line">{{ rrData.received_by }}</div>
                    Warehouseman
                </div>
                <div class="signature-box">
                    Noted by:
                    <div class="signature-line">{{userStore.user?.depthead_name }}</div>
                    ISD Manager
                </div>

            </div>
        </div>
    </div>


</template>
<script setup>
import { ref, onMounted, computed } from "vue";
import api from "../../services/api";
import { userStore } from "../../stores/userStore";

const props = defineProps({
    r_id: { type: Number, required: true },
});


const loading = ref(true);
const rrData = ref({
    r_id: 0,
    po_number: '',
    invoice_number: '',
    rr_number: '',
    receive_date: '',
    dr_number: '',
    supplier_name: '',
    address: '',
    grand_total: 0,
    items: []
});



const formatCurrency = (value) => {
    if (!value) return '₱0.00';
    return '₱' + Number(value).toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

onMounted(async () => {
    try {
        // Load user info once
        await userStore.initUser();

        // Fetch RR data
        const response = await api.get(`/receiving/DisplayRR/${props.r_id}`);
        if (response.data.success) {
            rrData.value = response.data.data;

            // Trigger print after slight delay
            setTimeout(() => {
                window.print();
            }, 300);
        } else {
            console.error("Failed to fetch RR:", response.data.message);
        }
    } catch (error) {
        console.error("API call failed:", error);
    } finally {
        loading.value = false;
    }
});
</script>




<style scoped>
.print-section {
    font-family: Arial, sans-serif;
    padding: 20px;
}

.loading {
    font-size: 16px;
    text-align: center;
}

.header-section {
    text-align: center;
    margin-bottom: 20px;
}

.header-table {
    width: 100%;
    margin-bottom: 20px;
    border-collapse: collapse;
}

.header-table .header-logo {
    width: 70px;
}

.company-name {
    margin: 0;
    font-weight: bold;
}

.report-title {
    margin: 5px 0 0 0;
}

.header-row {
    display: flex;
    align-items: center;
    /* vertical alignment */
    justify-content: center;
    /* horizontal alignment */
}

.header-row td {
    border: none;
    /* optional */
    text-align: center;
}

.header-logo {
    width: 70px;
    margin-right: 15px;
    /* space between logo and name */
}






.rr-info {
    margin-bottom: 15px;
    font-size: 14px;
}

.rr-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    font-size: 13px;

}

.rr-table th,
.rr-table td {
    border: 1px solid #000;
    padding: 4px;
    text-align: center;
    padding: 5px 12px;
}

.grand-total-label {
    text-align: right;
}

.signature-container {
    display: flex;
    justify-content: space-between;
    margin-top: 50px;
    text-align: center;
    font-style: italic;
}

.signature-box {
    width: 32%;
}

.signature-line {
    border-bottom: 1px solid #000;
    margin-top: 40px;
    font-weight: bold;
    font-size: 20px;
}

/* PRINT STYLES */
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