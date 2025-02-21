<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

interface Props {
  rates: Record<string, number>
  base: string
  date: string
  next_update: string
}

const props = defineProps<Props>()

const form = useForm({
  amount: 1,
  from: 'USD',
  to: 'EUR'
})

const result = ref<number | null>(null)
const error = ref<string | null>(null)

const availableCurrencies = Object.keys(props.rates).sort()

const convert = async () => {
  error.value = null
  
  try {
    const response = await fetch('/api/currency/convert', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(form.data())
    })
    
    const data = await response.json()
    
    if (!response.ok) {
      error.value = data.message || 'Произошла ошибка при конвертации'
      result.value = null
      return
    }
    
    result.value = data.result
  } catch (e) {
    error.value = 'Произошла ошибка при выполнении запроса'
    result.value = null
  }
}
</script>

<template>
  <AppLayout title="Конвертер валют">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
        <h1 class="text-2xl font-semibold mb-6">Конвертер валют</h1>
        
        <form @submit.prevent="convert" class="space-y-4">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Сумма</label>
              <input
                v-model="form.amount"
                type="number"
                min="0.01"
                step="0.01"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Из</label>
              <select
                v-model="form.from"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">В</label>
              <select
                v-model="form.to"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>
          </div>
          
          <div class="flex justify-end">
            <button
              type="submit"
              class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
              Конвертировать
            </button>
          </div>
        </form>
        
        <div v-if="error" class="mt-6 p-4 bg-red-50 text-red-700 rounded-md">
          {{ error }}
        </div>
        
        <div v-if="result !== null" class="mt-6 p-4 bg-gray-50 rounded-md">
          <p class="text-lg">
            {{ form.amount }} {{ form.from }} = <strong>{{ result.toFixed(2) }} {{ form.to }}</strong>
          </p>
          <p class="text-sm text-gray-500 mt-2">
            Курс на: {{ date }}
          </p>
          <p class="text-sm text-gray-500">
            Следующее обновление: {{ next_update }}
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template> 