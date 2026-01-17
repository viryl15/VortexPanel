<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  brand: { type: String, default: 'VortexPanel' },
  resources: { type: Array, default: () => [] },
  basePath: { type: String, default: '/admin' },
})
</script>

<template>
  <div class="vp-shell">
    <div class="flex min-h-screen">
      <aside class="w-72 p-4 border-r" style="border-color: rgb(var(--vp-border));">
        <div class="vp-card p-4">
          <div class="text-lg font-semibold">
            <span class="vp-accent">{{ brand }}</span>
          </div>
          <div class="text-xs mt-1" style="color: rgb(var(--vp-muted));">Fast • Futuristic • Laravel 10+</div>
        </div>

        <div class="mt-4 space-y-1">
          <Link class="block px-3 py-2 rounded-xl vp-link" :href="route('vortexpanel.dashboard')">
            Dashboard
          </Link>

          <div class="mt-3 text-xs uppercase tracking-wider" style="color: rgb(var(--vp-muted));">Resources</div>

          <Link
            v-for="r in resources"
            :key="r.slug"
            class="block px-3 py-2 rounded-xl vp-link"
            :href="basePath + '/' + r.slug"
          >
            {{ r.label }}
          </Link>
        </div>
      </aside>

      <main class="flex-1 p-6">
        <slot />
      </main>
    </div>
  </div>
</template>
