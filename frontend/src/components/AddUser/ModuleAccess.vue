<template>
  <!-- Row -->
  <tr :class="['module-row', { 'module-parent': !module.parent_module }]">
    
    <!-- Module Description -->
    <td>
      <div class="module-label" :style="{ paddingLeft: `${level * 18}px` }">
        <span v-if="!module.parent_module" class="parent-title">
          {{ module.module_name }}
        </span>

        <span v-else class="child-title">
          <i class="bi bi-caret-right-fill me-1 text-secondary small"></i>
          {{ module.module_name }}
        </span>
      </div>
    </td>

    <!-- Permissions -->
    <td class="perm-col">
      <input type="checkbox" class="perm-check" v-model="module.can_add" 
        :true-value="1" :false-value="0" :disabled="!canEdit">
    </td>

    <td class="perm-col">
      <input type="checkbox" class="perm-check" v-model="module.can_edit" 
        :true-value="1" :false-value="0" :disabled="!canEdit">
    </td>

    <td class="perm-col">
      <input type="checkbox" class="perm-check" v-model="module.can_view" 
        :true-value="1" :false-value="0" :disabled="!canEdit">
    </td>

    <td class="perm-col">
      <input type="checkbox" class="perm-check" v-model="module.can_delete" 
        :true-value="1" :false-value="0" :disabled="!canEdit">
    </td>
  </tr>

  <!-- Recursive Children -->
  <ModuleAccess 
    v-for="child in module.children"
    :key="child.module_name"
    :module="child"
    :level="level + 1"
    :can-edit="canEdit"
    :can-delete="canDelete"
    @delete="$emit('delete', $event)"
  />
</template>


<script setup>

const emit = defineEmits(["delete"]);

const props = defineProps({
  module: { type: Object, required: true },
  level: { type: Number, default: 0 }, // for indentation
  canEdit: { type: Boolean, default: true },
  canDelete: { type: Boolean, default: false }
});
</script>
<style scoped>
/* Parent row highlight */
.module-parent td {
  background: #f8f9fc;
  border-left: 4px solid #0d6efd;
  font-weight: 600;
}

/* Hover effect */
.module-row:hover td {
  background: #eef3ff;
}

/* Description cell */
.module-label {
  display: flex;
  align-items: center;
  padding: 6px 0;
}

.parent-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
}

.child-title {
  font-size: 14px;
  color: #555;
}

/* Permissions column */
.perm-col {
  text-align: center;
  vertical-align: middle;
}

/* Smaller, cleaner checkbox */
.perm-check {
  transform: scale(1.15);
  cursor: pointer;
}

/* Align checkboxes */
td.perm-col {
  padding: 6px 0;
}

tr.module-row td {
  border-color: #e4e7ea;
}

</style>
