<template>
    <AxontisCard :title="title" :subtitle="subtitle">
        <div class="flex justify-end gap-2 mb-4">
            <button
                @click="selectedView = 'day'"
                :disabled="loading"
                :class="['px-3 py-1 rounded text-sm transition-all', selectedView === 'day' ? 'bg-primary-500 text-white' : 'bg-dark-700 text-white/70', loading && 'opacity-50 cursor-not-allowed']"
            >
                Daily
            </button>
            <button
                @click="selectedView = 'month'"
                :disabled="loading"
                :class="['px-3 py-1 rounded text-sm transition-all', selectedView === 'month' ? 'bg-primary-500 text-white' : 'bg-dark-700 text-white/70', loading && 'opacity-50 cursor-not-allowed']"
            >
                Monthly
            </button>
        </div>
        <div class="h-80">
            <!-- Skeleton Loading -->
            <template v-if="loading">
                <div class="space-y-3">
                    <div class="h-64 bg-gradient-to-r from-dark-700 via-dark-600 to-dark-700 rounded-lg animate-pulse"></div>
                    <div class="flex gap-2">
                        <div class="flex-1 h-4 bg-gradient-to-r from-dark-700 via-dark-600 to-dark-700 rounded animate-pulse"></div>
                        <div class="flex-1 h-4 bg-gradient-to-r from-dark-700 via-dark-600 to-dark-700 rounded animate-pulse"></div>
                    </div>
                </div>
            </template>
            <!-- Loaded Chart -->
            <template v-else>
                <canvas ref="chartElement" :key="chartKey"></canvas>
            </template>
        </div>
        <template #footer>
            <slot name="footer">
                <div class="flex items-center justify-between text-sm gap-4">
                    <span class="text-white/70 flex-1 truncate">{{ footerText }}</span>
                    <span class="text-success-400 flex-shrink-0">{{ footerStatus }}</span>
                </div>
            </slot>
        </template>
    </AxontisCard>
</template>

<script setup>
import {nextTick, onBeforeUnmount, ref, watch} from 'vue'
import AxontisCard from '@/Components/AxontisCard.vue'

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    subtitle: {
        type: String,
        default: ''
    },
    chartType: {
        type: String,
        enum: ['line', 'bar'],
        default: 'line'
    },
    loading: {
        type: Boolean,
        default: false
    },
    dayData: {
        type: Array,
        default: () => []
    },
    monthData: {
        type: Array,
        default: () => []
    },
    dataLabelKey: {
        type: String,
        default: 'label'
    },
    dataValueKey: {
        type: String,
        default: 'value'
    },
    chartColor: {
        type: String,
        default: '#f59e0b'
    },
    chartColorDark: {
        type: String,
        default: '#d97706'
    },
    footerText: {
        type: String,
        default: 'Loading...'
    },
    footerStatus: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['view-changed'])

const chartElement = ref(null)
const selectedView = ref('month')
const chartKey = ref(0)
let chartInstance = null
let Chart = null

// Get current data based on selected view
const getCurrentData = () => {
    return selectedView.value === 'day' ? props.dayData : props.monthData
}

// Initialize chart
const initializeChart = async () => {
    // Ensure DOM is fully updated
    await nextTick()

    // Add delay to ensure canvas elements are fully mounted in DOM
    await new Promise(resolve => setTimeout(resolve, 300))

    // Check if Chart.js is available
    if (!Chart) {
        Chart = window.Chart
    }

    if (!Chart) {
        console.warn('Chart.js is not available')
        return
    }

    try {
        const data = getCurrentData()

        if (!data || data.length === 0) {
            console.warn('No data available for chart')
            return
        }

        if (chartElement.value) {
            // Destroy previous chart if it exists
            if (chartInstance) {
                chartInstance.destroy()
            }

            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: { color: 'rgba(255, 255, 255, 0.7)' }
                    }
                },
                scales: {
                    x: {
                        grid: { color: `rgba(245, 158, 11, 0.1)` },
                        ticks: { color: 'rgba(255, 255, 255, 0.7)' }
                    },
                    y: {
                        grid: { color: `rgba(245, 158, 11, 0.1)` },
                        ticks: { color: 'rgba(255, 255, 255, 0.7)' }
                    }
                }
            }

            const chartConfig = {
                type: props.chartType,
                data: {
                    labels: data.map(d => d[props.dataLabelKey]),
                    datasets: [{
                        label: props.subtitle || 'Data',
                        data: data.map(d => d[props.dataValueKey]),
                        borderColor: props.chartColor,
                        backgroundColor: props.chartType === 'line'
                            ? `rgba(245, 158, 11, 0.1)`
                            : props.chartColor,
                        ...(props.chartType === 'line' && {
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointBackgroundColor: props.chartColor
                        }),
                        ...(props.chartType === 'bar' && {
                            borderColor: props.chartColorDark,
                            borderWidth: 2
                        })
                    }]
                },
                options: commonOptions
            }

            chartInstance = new Chart(chartElement.value, chartConfig)
        }
    } catch (error) {
        console.error('Error during chart initialization:', error)
    }
}

// Watch for changes in selected view
watch(() => selectedView.value, async () => {
    emit('view-changed', selectedView.value)
    await initializeChart()
})

// Watch for changes in data
watch(() => [props.dayData, props.monthData], async () => {
    if (!props.loading) {
        await initializeChart()
    }
}, { deep: true })

// Watch for changes in loading state
watch(() => props.loading, async (newLoading) => {
    if (!newLoading) {
        await initializeChart()
    }
})

// Cleanup on unmount
onBeforeUnmount(() => {
    if (chartInstance) {
        chartInstance.destroy()
    }
})

defineExpose({
    selectedView
})
</script>
