<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

interface Props {
  rates: Record<string, number>
  base: string
  date: string
  next_update: string
  error?: string
}

const props = defineProps<Props>()

const form = useForm({
  amount: 1,
  from: props.base,
  to: Object.keys(props.rates)[0] || 'USD'
})

const result = ref<number | null>(null)
const error = ref<string | null>(props.error || null)
const loading = ref(false)
const apiMethod = ref('rest')
const cbRates = ref<Record<string, { name: string, nominal: number }>>({})
const loadingCbRates = ref(false)

// Вычисляемый список доступных валют в зависимости от выбранного API
const availableCurrencies = computed(() => {
  if (apiMethod.value === 'soap') {
    return Object.keys(cbRates.value).sort()
  }
  return Object.keys(props.rates).sort()
})

// Загрузка списка валют ЦБ РФ
const loadCbRates = async () => {
  try {
    loadingCbRates.value = true
    error.value = null
    
    const { data } = await axios.get('/api/currency/supported-currencies', {
      params: { api_method: 'soap' }
    })
    
    cbRates.value = data.currencies
    
    // Обновляем выбранные валюты, если они не поддерживаются ЦБ РФ
    if (!cbRates.value[form.from]) {
      form.from = 'RUB'
    }
    if (!cbRates.value[form.to]) {
      form.to = Object.keys(cbRates.value).find(code => code !== 'RUB') || 'USD'
    }
  } catch (e) {
    if (axios.isAxiosError(e)) {
      error.value = e.response?.data?.message || 'Ошибка загрузки списка валют'
    } else {
      error.value = 'Неизвестная ошибка'
    }
  } finally {
    loadingCbRates.value = false
  }
}

// Следим за изменением метода API
watch(apiMethod, async (newMethod) => {
  if (newMethod === 'soap' && Object.keys(cbRates.value).length === 0) {
    await loadCbRates()
  }
})

const convert = async () => {
  loading.value = true
  error.value = null
  result.value = null
  
  try {
    const { data } = await axios.post('/api/currency/convert', {
      from: form.from,
      to: form.to,
      amount: form.amount,
      api_method: apiMethod.value,
    })
    
    result.value = data.result
  } catch (e) {
    if (axios.isAxiosError(e)) {
      error.value = e.response?.data?.message || 'Ошибка конвертации'
    } else {
      error.value = 'Неизвестная ошибка'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AppLayout title="Конвертер валют">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
          <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
              Конвертер валют
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
              Курсы обновлены: {{ date }}
              <br>
              Следующее обновление: {{ next_update }}
            </p>
          </div>

          <!-- Выбор метода API -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Метод API
            </label>
            <select
              v-model="apiMethod"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              :disabled="loading || loadingCbRates"
            >
              <option value="rest">REST API (ExchangeRate-API)</option>
              <option value="soap">SOAP API (ЦБ РФ)</option>
            </select>
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <!-- From Currency -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Из валюты
              </label>
              <select
                v-model="form.from"
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                :disabled="loading || loadingCbRates"
              >
                <option v-for="code in availableCurrencies" :key="code" :value="code">
                  {{ code }} {{ apiMethod === 'soap' && cbRates[code] ? `- ${cbRates[code].name}` : '' }}
                </option>
              </select>
            </div>

            <!-- To Currency -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                В валюту
              </label>
              <select
                v-model="form.to"
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                :disabled="loading || loadingCbRates"
              >
                <option v-for="code in availableCurrencies" :key="code" :value="code">
                  {{ code }} {{ apiMethod === 'soap' && cbRates[code] ? `- ${cbRates[code].name}` : '' }}
                </option>
              </select>
            </div>
          </div>

          <!-- Amount -->
          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Сумма
            </label>
            <div class="mt-1">
              <input
                type="number"
                v-model="form.amount"
                class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md"
                placeholder="Введите сумму"
                step="0.01"
                min="0"
                :disabled="loading || loadingCbRates"
              >
            </div>
          </div>

          <!-- Convert Button -->
          <div class="mt-6">
            <button
              @click="convert"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              :disabled="loading || loadingCbRates"
            >
              <template v-if="loading">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Конвертация...
              </template>
              <template v-else-if="loadingCbRates">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Загрузка валют...
              </template>
              <template v-else>
                Конвертировать
              </template>
            </button>
          </div>

          <!-- Result -->
          <div v-if="result !== null" class="mt-6">
            <div class="rounded-md bg-green-50 dark:bg-green-900 p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-green-800 dark:text-green-200">
                    {{ form.amount }} {{ form.from }} = {{ result.toFixed(2) }} {{ form.to }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Error -->
          <div v-if="error" class="mt-6">
            <div class="rounded-md bg-red-50 dark:bg-red-900 p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-red-800 dark:text-red-200">
                    {{ error }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template> 