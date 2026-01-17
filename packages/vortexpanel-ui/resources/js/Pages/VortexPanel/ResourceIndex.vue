<script setup>
import { computed, ref, watch } from 'vue'
import Layout from './_Layout.vue'
import DataTable from '../../Components/VortexPanel/DataTable.vue'

const props = defineProps({
  brand: String,
  resources: Array,
  resource: Object,
  apiBase: String,
})

const q = ref('')
const sort = ref('id')
const dir = ref('desc')

const dataUrl = computed(() => `${props.apiBase}/resources/${props.resource.slug}/data`)
</script>

<template>
  <Layout :brand="brand" :resources="resources">
    <div class="max-w-6xl">
      <div class="flex items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold">{{ resource.label }}</h1>
          <p class="mt-1 text-sm" style="color: rgb(var(--vp-muted));">Server-side pagination, sorting, and prefix search.</p>
        </div>

        <div class="flex gap-2">
          <input v-model="q" class="vp-input flex-1" placeholder="Search… (prefix)" />
          <a :href="`/admin/${resource.slug}/create`" class="vp-btn vp-btn-primary px-4 py-2 rounded">
            Create
          </a>
        </div>
      </div>

      <div class="mt-6">
        <DataTable
          :columns="resource.columns"
          :url="dataUrl"
          :q="q"
          :sort="sort"
          :dir="dir"
          :resource-slug="resource.slug"
          @update:sort="sort = $event"
          @update:dir="dir = $event"
        />
      </div>
    </div>
  </Layout>
</template>

<style scoped>
.vp-btn {
  @apply transition-colors cursor-pointer text-center;
}

.vp-btn-primary {
  @apply bg-indigo-600 text-white hover:bg-indigo-700;
}
</style>

