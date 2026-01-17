<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Layout from './_Layout.vue'

const props = defineProps({
  brand: String,
  resources: Array,
  resource: Object,
  item: Object,
  apiBase: String,
})

const form = ref({ ...props.item })
const errors = ref({})
const submitting = ref(false)

const handleSubmit = async () => {
  submitting.value = true
  errors.value = {}

  try {
    const response = await fetch(`${props.apiBase}/resources/${props.resource.slug}/${props.item.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify(form.value),
    })

    if (!response.ok) {
      const data = await response.json()
      if (data.errors) {
        errors.value = data.errors
      }
      return
    }

    router.get(`/admin/${props.resource.slug}`)
  } finally {
    submitting.value = false
  }
}

const handleDelete = async () => {
  if (!confirm('Are you sure you want to delete this item?')) return

  try {
    const response = await fetch(`${props.apiBase}/resources/${props.resource.slug}/${props.item.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
    })

    if (response.ok) {
      router.get(`/admin/${props.resource.slug}`)
    }
  } catch (e) {
    console.error('Delete failed:', e)
  }
}
</script>

<template>
  <Layout :brand="brand" :resources="resources">
    <div class="max-w-2xl">
      <div class="mb-6">
        <h1 class="text-2xl font-semibold">Edit {{ resource.label }}</h1>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div v-for="field in resource.form" :key="field.key" class="form-group">
          <label :for="field.key" class="block text-sm font-medium mb-1">
            {{ field.label }}
          </label>
          <input
            :id="field.key"
            v-model="form[field.key]"
            :type="field.type || 'text'"
            :required="field.required"
            :placeholder="field.placeholder"
            class="vp-input w-full"
          />
          <span v-if="errors[field.key]" class="text-red-500 text-sm mt-1">
            {{ errors[field.key][0] }}
          </span>
        </div>

        <div class="flex gap-2">
          <button
            type="submit"
            :disabled="submitting"
            class="vp-btn vp-btn-primary"
          >
            {{ submitting ? 'Saving…' : 'Save' }}
          </button>
          <button
            type="button"
            @click="handleDelete"
            class="vp-btn vp-btn-danger"
          >
            Delete
          </button>
          <a :href="`/admin/${resource.slug}`" class="vp-btn">Cancel</a>
        </div>
      </form>
    </div>
  </Layout>
</template>

<style scoped>
.vp-btn {
  @apply px-4 py-2 rounded transition-colors cursor-pointer;
}

.vp-btn-primary {
  @apply bg-indigo-600 text-white hover:bg-indigo-700;
}

.vp-btn-danger {
  @apply bg-red-600 text-white hover:bg-red-700;
}

.vp-btn:disabled {
  @apply opacity-50 cursor-not-allowed;
}
</style>
