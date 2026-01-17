<script setup>
import Layout from './_Layout.vue'

defineProps({
  brand: String,
  resources: Array,
  apiBase: String,
})
</script>

<template>
  <Layout :brand="brand" :resources="resources">
    <div class="max-w-6xl">
      <h1 class="text-2xl font-semibold">Dashboard</h1>
      <p class="mt-2" style="color: rgb(var(--vp-muted));">
        Welcome to <span class="vp-accent">{{ brand }}</span>. This is the foundation-fast routes, server-side tables, and a clean futuristic shell.
      </p>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div class="vp-card p-4">
          <div class="text-sm" style="color: rgb(var(--vp-muted));">Resources</div>
          <div class="text-3xl font-semibold mt-1">{{ resources?.length ?? 0 }}</div>
        </div>
        <div class="vp-card p-4">
          <div class="text-sm" style="color: rgb(var(--vp-muted));">API Base</div>
          <div class="text-sm mt-1 break-all">{{ apiBase }}</div>
        </div>
        <div class="vp-card p-4">
          <div class="text-sm" style="color: rgb(var(--vp-muted));">Mode</div>
          <div class="text-3xl font-semibold mt-1">Dark</div>
        </div>
      </div>
    </div>
  </Layout>
</template>
