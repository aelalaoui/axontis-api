<template>
    <div class="axontis-card" :class="cardClass">
        <!-- Card Header -->
        <div v-if="title || subtitle || $slots.header" class="axontis-card-header">
            <div v-if="$slots.header">
                <slot name="header" />
            </div>
            <div v-else>
                <h3 class="axontis-card-title">{{ title }}</h3>
                <p v-if="subtitle" class="axontis-card-subtitle">{{ subtitle }}</p>
            </div>
            <div v-if="$slots.actions" class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>

        <!-- Card Content -->
        <div class="axontis-card-content">
            <slot />
        </div>

        <!-- Card Footer -->
        <div v-if="$slots.footer" class="axontis-card-footer">
            <slot name="footer" />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    title: {
        type: String,
        default: null
    },
    subtitle: {
        type: String,
        default: null
    },
    variant: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'feature', 'pricing', 'stat'].includes(value)
    },
    hover: {
        type: Boolean,
        default: true
    },
    class: {
        type: String,
        default: ''
    }
})

const cardClass = computed(() => {
    let classes = props.class

    if (props.variant === 'feature') {
        classes += ' axontis-feature-card'
    } else if (props.variant === 'pricing') {
        classes += ' axontis-pricing-card'
    } else if (props.variant === 'stat') {
        classes += ' axontis-stat-card'
    }

    if (props.hover) {
        classes += ' hover-lift'
    }

    return classes
})
</script>