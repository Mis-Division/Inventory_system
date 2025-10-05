<template>
  <!-- Row -->
  <tr>
    <!-- Description Column -->
    <td :style="{ paddingLeft: level * 20 + 'px' }">
      <strong v-if="!module.parent_module">{{ module.module_name }}</strong>
      <span v-else>— {{ module.module_name }}</span>
    </td>

    <!-- Permissions -->
    <td>
      <input type="checkbox" v-model="module.can_add" :true-value="1" :false-value="0" :disabled="!canEdit" />
    </td>
    <td>
      <input type="checkbox" v-model="module.can_edit" :true-value="1" :false-value="0" :disabled="!canEdit" />
    </td>
    <td>
      <input type="checkbox" v-model="module.can_view" :true-value="1" :false-value="0" :disabled="!canEdit" />
    </td>
    <td>
      <input type="checkbox" v-model="module.can_delete" :true-value="1" :false-value="0" :disabled="!canEdit" />
    </td>
  </tr>

  <!-- Recursive children -->
  <ModuleAccess v-for="child in module.children" :key="child.module_name" :module="child" :level="level + 1"
    :can-edit="canEdit" :can-delete="canDelete" @delete="$emit('delete', $event)" />
</template>

<script setup>
import ModuleAccess from "./ModuleAccess.vue";
const emit = defineEmits(["delete"]);

const props = defineProps({
  module: { type: Object, required: true },
  level: { type: Number, default: 0 }, // for indentation
  canEdit: { type: Boolean, default: true },
  canDelete: { type: Boolean, default: false }
});
</script>
