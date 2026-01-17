<script setup>
import { Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import CommandPalette from '../../Components/VortexPanel/CommandPalette.vue'

defineProps({
  brand: { type: String, default: 'VortexPanel' },
  resources: { type: Array, default: () => [] },
  apiBase: { type: String, default: '/admin/api' },
  basePath: { type: String, default: '/admin' },
})

const userMenuOpen = ref(false)
</script>

<template>
  <div class="vp-shell flex flex-col h-screen">
    <CommandPalette :api-base="apiBase" />

    <!-- Top Bar -->
    <header class="border-b" style="border-color: rgb(var(--vp-border));">
      <div class="flex items-center justify-between px-6 py-4">
        <div class="text-sm" style="color: rgb(var(--vp-muted));">{{ brand }} Admin</div>

        <!-- User Menu -->
        <div class="relative">
          <button
            @click="userMenuOpen = !userMenuOpen"
            class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/5 transition-colors"
          >
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center text-white font-semibold text-sm">
              {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
            </div>
            <span class="text-sm" style="color: rgb(var(--vp-text));">{{ $page.props.auth.user.name }}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
          </button>

          <!-- Dropdown Menu -->
          <div
            v-if="userMenuOpen"
            class="absolute right-0 mt-2 w-48 vp-card rounded-lg shadow-lg overflow-hidden z-50"
          >
            <div class="p-3 border-b" style="border-color: rgb(var(--vp-border));">
              <div class="font-medium text-sm">{{ $page.props.auth.user.name }}</div>
              <div class="text-xs" style="color: rgb(var(--vp-muted));">{{ $page.props.auth.user.email }}</div>
            </div>

            <Link
              href="/profile"
              class="block px-4 py-2 hover:bg-white/5 transition-colors text-sm border-b"
              style="border-color: rgb(var(--vp-border)); color: rgb(var(--vp-text));"
            >
              Profile Settings
            </Link>

            <form method="post" action="/logout" class="w-full">
              <input type="hidden" name="_token" :value="$page.props.csrf_token" />
              <button
                type="submit"
                class="w-full text-left px-4 py-2 hover:bg-white/5 transition-colors text-sm text-red-500 hover:text-red-400"
              >
                Logout
              </button>
            </form>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <div class="flex flex-1 overflow-hidden">
      <!-- Sidebar -->
      <aside class="w-72 border-r overflow-y-auto p-4" style="border-color: rgb(var(--vp-border));">
        <div class="vp-card p-4 mb-4">
          <div class="text-lg font-semibold">
            <span class="vp-accent">{{ brand }}</span>
          </div>
          <div class="text-xs mt-1" style="color: rgb(var(--vp-muted));">Fast • Futuristic • Laravel 10+</div>
        </div>

        <nav class="space-y-1">
          <Link class="block px-3 py-2 rounded-xl vp-link" :href="route('vortexpanel.dashboard')">
            Dashboard
          </Link>

          <div class="mt-3 text-xs uppercase tracking-wider px-3" style="color: rgb(var(--vp-muted));">Resources</div>

          <Link
            v-for="r in resources"
            :key="r.slug"
            class="block px-3 py-2 rounded-xl vp-link"
            :href="basePath + '/' + r.slug"
          >
            {{ r.label }}
          </Link>
        </nav>
      </aside>

      <!-- Content -->
      <main class="flex-1 overflow-y-auto p-6">
        <slot />
      </main>
    </div>
  </div>
</template>
