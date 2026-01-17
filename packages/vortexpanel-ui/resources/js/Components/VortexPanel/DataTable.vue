<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

const props = defineProps({
  columns: { type: Array, required: true },
  url: { type: String, required: true },
  q: { type: String, default: '' },
  sort: { type: String, default: 'id' },
  dir: { type: String, default: 'desc' },
  resourceSlug: { type: String, default: '' },
})

const emit = defineEmits(['update:sort', 'update:dir'])

const page = ref(1)
const perPage = ref(25)
const loading = ref(false)
const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 25 })

// Debounce timer for search
let searchDebounce = null
const DEBOUNCE_MS = 150

// Abort controller for cancelling stale requests
let abortController = null

const params = computed(() => {
  const p = new URLSearchParams()
  p.set('page', String(page.value))
  p.set('perPage', String(perPage.value))
  if (props.q) p.set('q', props.q)
  if (props.sort) p.set('sort', props.sort)
  if (props.dir) p.set('dir', props.dir)
  return p
})

async function load() {
  // Cancel any pending request
  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()

  loading.value = true
  try {
    const res = await fetch(`${props.url}?${params.value.toString()}`, {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
      signal: abortController.signal,
    })
    const json = await res.json()
    rows.value = json.data ?? []
    meta.value = json.meta ?? meta.value
  } catch (e) {
    if (e.name !== 'AbortError') throw e
  } finally {
    loading.value = false
  }
}

function toggleSort(col) {
  if (!col.sortable) return
  if (props.sort !== col.key) {
    emit('update:sort', col.key)
    emit('update:dir', 'asc')
  } else {
    emit('update:dir', props.dir === 'asc' ? 'desc' : 'asc')
  }
}

// Debounced search watcher
watch(() => props.q, () => {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    page.value = 1
    load()
  }, DEBOUNCE_MS)
})

// Immediate reload for sort/perPage changes
watch(() => [props.sort, props.dir, perPage.value], () => {
  page.value = 1
  load()
})

onMounted(load)

onUnmounted(() => {
  clearTimeout(searchDebounce)
  if (abortController) abortController.abort()
})
</script>

<template>
  <div class="vp-card overflow-hidden">
    <div class="flex items-center justify-between p-3 border-b" style="border-color: rgb(var(--vp-border));">
      <div class="text-sm" style="color: rgb(var(--vp-muted));">Total: {{ meta.total }}</div>
      <div class="flex items-center gap-2">
        <label class="text-sm" style="color: rgb(var(--vp-muted));">Per page</label>
        <select v-model.number="perPage" class="vp-input py-2">
          <option :value="10">10</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>
      </div>
    </div>

    <div class="overflow-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left" style="color: rgb(var(--vp-muted));">
            <th
              v-for="c in columns"
              :key="c.key"
              class="px-4 py-3 border-b select-none"
              style="border-color: rgb(var(--vp-border));"
              :class="c.sortable ? 'cursor-pointer' : ''"
              @click="toggleSort(c)"
            >
              <span class="inline-flex items-center gap-2">
                {{ c.label }}
                <span v-if="c.sortable && c.key === sort" class="vp-accent">
                  {{ dir === 'asc' ? '▲' : '▼' }}
                </span>
              </span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td :colspan="columns.length" class="px-4 py-6" style="color: rgb(var(--vp-muted));">Loading…</td>
          </tr>
          <tr v-else-if="rows.length === 0">
            <td :colspan="columns.length" class="px-4 py-6" style="color: rgb(var(--vp-muted));">No results.</td>
          </tr>
          <tr v-else v-for="(r, i) in rows" :key="i" class="hover:bg-white/5">
            <td v-for="c in columns" :key="c.key" class="px-4 py-3 border-b" style="border-color: rgb(var(--vp-border));">
              <a v-if="c.key === 'id' && resourceSlug" :href="`/admin/${resourceSlug}/${r.id}/edit`" class="vp-accent hover:underline">
                {{ r[c.key] }}
              </a>
              <span v-else>{{ r[c.key] }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between p-3">
      <div class="flex gap-1">
        <button class="vp-input px-3" :disabled="page <= 1" @click="page = 1; load()" title="First">«</button>
        <button class="vp-input px-3" :disabled="page <= 1" @click="page--; load()">Prev</button>
      </div>
      <div class="text-sm" style="color: rgb(var(--vp-muted));">
        Page {{ meta.current_page }} / {{ meta.last_page }} ({{ meta.total }} total)
      </div>
      <div class="flex gap-1">
        <button class="vp-input px-3" :disabled="page >= meta.last_page" @click="page++; load()">Next</button>
        <button class="vp-input px-3" :disabled="page >= meta.last_page" @click="page = meta.last_page; load()" title="Last">»</button>
      </div>
    </div>
  </div>
</template>
