<template>
  <div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
      <h1 class="mb-0">Items</h1>
      <div class="d-flex align-items-center gap-2" style="white-space: nowrap;">
        <input type="text" v-model="search" @input="fetchItems" class="form-control" placeholder="Search Item..."
          style="width: 500px; flex: 0 0 auto;" />
        <button :disabled="!canAddCategory" class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i> Item Code
        </button>
      </div>
    </div>

    <table class="table table-bordered table-hover text-center">
      <thead class="table-secondary">
        <tr>
          <th style="width: 5%;">ID</th>
          <th style="width: 15%;">ItemCode</th>
          <th style="width: 35%;">Description</th>
          <th style="width: 15%;">Action</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in items" :key="item.id" v-if="items.length > 0">
          <td>{{ item.ItemCode_id }}</td>
          <td>{{ item.ItemCode }}</td>
          <td>{{ item.description }}</td>
          <td>
            <button :disabled="!canEditCategory" class="btn btn-warning" title="Edit Supplier">
              <i class="bi bi-pencil"></i>
            </button>
            |
            <button :disabled="!canDeleteCategory" class="btn btn-danger" title="Delete Supplier">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
        <tr v-else>
          <td colspan="4" class="text-center py-3 text-muted">
            No items found.
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Pagination -->
    <nav>
      <ul class="pagination justify-content-center">
        <li class="page-item" :class="{ disabled: meta.current_page === 1 }" @click="changePage(meta.current_page - 1)">
          <a class="page-link">Previous</a>
        </li>

        <li v-for="page in meta.last_page" :key="page" class="page-item" :class="{ active: page === meta.current_page }"
          @click="changePage(page)">
          <a class="page-link">{{ page }}</a>
        </li>

        <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }"
          @click="changePage(meta.current_page + 1)">
          <a class="page-link">Next</a>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script>
import { computed } from "vue";
import api from "../../services/api";
import { userStore } from "../../stores/userStore";

export default {
  data() {
    return {
      items: [],
      meta: {},
      search: "",
      perPage: 10,
      currentPage: 1,
    };
  },
  computed: {
    // ✅ Just read properties directly from userStore
    canAddCategory() {
      return userStore.cadAddCategory;
    },
    canEditCategory() {
      return userStore.canEditCategory;
    },
    canDeleteCategory() {
      return userStore.canDeleteCategory;
    },
  },
  mounted() {
    this.fetchItems();
  },
  methods: {
    async fetchItems() {
      const res = await api.get("/Items/getItemCode", {
        params: {
          search: this.search,
          page: this.currentPage,
          per_page: this.perPage,
        },
      });
      this.items = res.data.data;
      this.meta = res.data.meta;
    },
    changePage(page) {
      if (page < 1 || page > this.meta.last_page) return;
      this.currentPage = page;
      this.fetchItems();
    },
  },
};
</script>
