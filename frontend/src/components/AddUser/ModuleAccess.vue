<template>
  <!-- Table Row -->
  <tr>
    <!-- Description -->
    <td :style="{ paddingLeft: `${level * 20}px` }">
      <strong style="padding-left: 10px;" v-if="!module.parent_module">{{ module.module_name }}</strong>
      <span v-else>— {{ module.module_name }}</span>
    </td>

    <!-- Permissions -->
    <td class="text-center align-middle">
      <div class="form-check d-flex justify-content-center m-0">
        <input class="form-check-input" type="checkbox" v-model="module.can_add" :true-value="1" :false-value="0"
          :disabled="!canEdit" />
      </div>
    </td>

    <td class="text-center align-middle">
      <div class="form-check d-flex justify-content-center m-0">
        <input class="form-check-input" type="checkbox" v-model="module.can_edit" :true-value="1" :false-value="0"
          :disabled="!canEdit" />
      </div>
    </td>

    <td class="text-center align-middle">
      <div class="form-check d-flex justify-content-center m-0">
        <input class="form-check-input" type="checkbox" v-model="module.can_view" :true-value="1" :false-value="0"
          :disabled="!canEdit" />
      </div>
    </td>

    <td class="text-center align-middle">
      <div class="form-check d-flex justify-content-center m-0">
        <input class="form-check-input" type="checkbox" v-model="module.can_delete" :true-value="1" :false-value="0"
          :disabled="!canEdit" />
      </div>
    </td>
  </tr>

  <!-- Recursive Children -->
  <ModuleAccess v-for="child in module.children" :key="child.module_name" :module="child" :level="level + 1"
    :can-edit="canEdit" :can-delete="canDelete" @delete="$emit('delete', $event)" />
</template>

<script setup>
import ModuleAccess from "../AddUser/ModuleAccess.vue";
const emit = defineEmits(["delete"]);

const props = defineProps({
  module: { type: Object, required: true },
  level: { type: Number, default: 0 }, // for indentation
  canEdit: { type: Boolean, default: true },
  canDelete: { type: Boolean, default: false }
});
</script>
<style scoped>
/* Align checkboxes nicely */
.form-check-input {
  cursor: pointer;
  transform: scale(1.1);
}

/* Indentation for nested modules */
td:first-child {
  white-space: nowrap;
}

/* Remove checkbox margin inside cell */
.form-check {
  min-height: auto;
}
</style>
