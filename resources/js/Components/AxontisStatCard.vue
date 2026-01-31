<template>
    <div class="axontis-stat-card animate-fade-in">
        <!-- Skeleton Loading State -->
        <template v-if="loading">
            <div class="space-y-4">
                <!-- Icon Skeleton -->
                <div class="axontis-stat-icon opacity-50">
                    <div class="w-12 h-12 bg-gradient-to-r from-dark-700 via-dark-600 to-dark-700 rounded-lg animate-pulse"></div>
                </div>

                <!-- Value Skeleton -->
                <div class="h-8 bg-gradient-to-r from-dark-700 via-dark-600 to-dark-700 rounded-lg w-3/4 animate-pulse"></div>

                <!-- Label Skeleton -->
                <div class="h-4 bg-gradient-to-r from-dark-700 via-dark-600 to-dark-700 rounded w-1/2 animate-pulse"></div>

                <!-- Change Indicator Skeleton -->
                <div class="h-4 bg-gradient-to-r from-dark-700 via-dark-600 to-dark-700 rounded w-1/3 animate-pulse"></div>
            </div>
        </template>

        <!-- Loaded State -->
        <template v-else>
            <!-- Icon -->
            <div class="axontis-stat-icon">
                <i :class="icon"></i>
            </div>

            <!-- Value -->
            <div class="axontis-stat-value">{{ formattedValue }}</div>

            <!-- Label -->
            <div class="axontis-stat-label">{{ label }}</div>

            <!-- Change Indicator -->
            <div v-if="change !== null" :class="changeClasses">
                <i :class="changeIcon"></i>
                <span>{{ change }}</span>
            </div>

            <!-- Additional Info -->
            <div v-if="info" class="text-xs text-white/50 mt-2">
                {{ info }}
            </div>
        </template>
    </div>
</template>

<script setup>
import {computed} from 'vue'

const props = defineProps({
    label: {
        type: String,
        required: true
    },
    value: {
        type: [String, Number],
        required: true
    },
    icon: {
        type: String,
        default: 'fas fa-chart-line'
    },
    change: {
        type: [String, Number],
        default: null
    },
    changeType: {
        type: String,
        default: 'neutral',
        validator: (value) => ['positive', 'negative', 'neutral'].includes(value)
    },
    format: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'currency', 'percentage', 'compact'].includes(value)
    },
    currency: {
        type: String,
        default: 'EUR'
    },
    info: {
        type: String,
        default: null
    },
    loading: {
        type: Boolean,
        default: false
    }
})

const formattedValue = computed(() => {
    const value = props.value

    switch (props.format) {
        case 'currency':
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: props.currency
            }).format(value)
        case 'percentage':
            return `${value}%`
        case 'compact':
            return new Intl.NumberFormat('fr-FR', {
                notation: 'compact',
                maximumFractionDigits: 1
            }).format(value)
        default:
            return typeof value === 'number'
                ? new Intl.NumberFormat('fr-FR').format(value)
                : value
    }
})

const changeClasses = computed(() => {
    let classes = 'axontis-stat-change '

    if (props.changeType === 'positive') {
        classes += 'positive'
    } else if (props.changeType === 'negative') {
        classes += 'negative'
    } else {
        classes += 'neutral'
    }

    return classes
})

const changeIcon = computed(() => {
    if (props.changeType === 'positive') {
        return 'fas fa-arrow-up'
    } else if (props.changeType === 'negative') {
        return 'fas fa-arrow-down'
    } else {
        return 'fas fa-minus'
    }
})
</script>
