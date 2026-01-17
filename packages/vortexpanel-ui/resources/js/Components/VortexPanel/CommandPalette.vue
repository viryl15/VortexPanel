<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  apiBase: String,
})

const open = ref(false)
const query = ref('')
const results = ref([])
const selectedIndex = ref(0)
const loading = ref(false)

const filteredResults = computed(() => results.value.slice(0, 8))

async function search() {
  if (query.value.length < 2) {
    results.value = []
    return
  }

  loading.value = true
  try {
    const res = await fetch(`${props.apiBase}/search?q=${encodeURIComponent(query.value)}`)
    const data = await res.json()
    results.value = data.data ?? []
    selectedIndex.value = 0
  } finally {
    loading.value = false
  }
}

function handleKeyDown(e) {
  if (e.key === 'k' && (e.ctrlKey || e.metaKey)) {
    e.preventDefault()
    open.value = !open.value
    if (open.value) {
      setTimeout(() => document.querySelector('#vp-search-input')?.focus(), 50)
    }
  }

  if (!open.value) return

  if (e.key === 'Escape') {
    open.value = false
  } else if (e.key === 'ArrowDown') {
    e.preventDefault()
    selectedIndex.value = Math.min(selectedIndex.value + 1, filteredResults.value.length - 1)
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    selectedIndex.value = Math.max(selectedIndex.value - 1, 0)
  } else if (e.key === 'Enter' && filteredResults.value[selectedIndex.value]) {
    const item = filteredResults.value[selectedIndex.value]
    window.location.href = `/admin/${item.resource}/${item.id}/edit`
  }
}

function selectItem(item) {
  window.location.href = `/admin/${item.resource}/${item.id}/edit`
}

watch(query, search)

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
})
</script>

<template>
  <div>
    <!-- Command Palette Button -->
    <button
      v-if="!open"
      @click="open = true"
      class="fixed bottom-4 right-4 px-4 py-2 rounded-lg vp-card hover:bg-white/10 transition-colors flex items-center gap-2 text-sm"
      style="color: rgb(var(--vp-muted));"
    >
      <span>⌘K</span>
    </button>

    <!-- Command Palette Modal -->
    <div v-if="open" class="fixed inset-0 z-50 flex items-start justify-center pt-20 bg-black/40">
      <div class="w-full max-w-2xl vp-card rounded-lg shadow-xl overflow-hidden">
        <!-- Input -->
        <div class="p-4 border-b" style="border-color: rgb(var(--vp-border));">
          <input
            id="vp-search-input"
            v-model="query"
            type="text"
            placeholder="Search resources… (Esc to close)"
            class="w-full bg-transparent text-lg outline-none"
            style="color: rgb(var(--vp-text));"
          />
        </div>

        <!-- Results -->
        <div class="max-h-96 overflow-auto">
          <div v-if="loading" class="p-4 text-center" style="color: rgb(var(--vp-muted));">
            Loading…
          </div>
          <div v-else-if="results.length === 0 && query.length >= 2" class="p-4 text-center" style="color: rgb(var(--vp-muted));">
            No results found.
          </div>
          <div v-else-if="filteredResults.length === 0" class="p-4 text-center" style="color: rgb(var(--vp-muted));">
            Type to search (min 2 chars)
          </div>
          <button
            v-for="(item, i) in filteredResults"
            :key="`${item.resource}-${item.id}`"
            @click="selectItem(item)"
            :class="{ 'bg-white/10': i === selectedIndex }"
            class="w-full px-4 py-3 text-left hover:bg-white/10 transition-colors border-b"
            style="border-color: rgb(var(--vp-border));"
          >
            <div class="font-medium">{{ item.title }}</div>
            <div class="text-sm" style="color: rgb(var(--vp-muted));">
              {{ item.resource_label }}
            </div>
          </button>
        </div>

        <!-- Footer -->
        <div class="p-3 border-t text-xs" style="border-color: rgb(var(--vp-border)); color: rgb(var(--vp-muted));">
          <span>↑↓ to navigate • Enter to select • Esc to close</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
button {
  background: transparent;
  border: none;
  cursor: pointer;
}
</style>
