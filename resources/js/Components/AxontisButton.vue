<template>
    <component
        :is="tag"
        :type="tag === 'button' ? type : undefined"
        :href="tag === 'a' ? href : undefined"
        :to="tag === 'router-link' ? to : undefined"
        :disabled="disabled || loading"
        :class="buttonClasses"
        @click="handleClick"
    >
        <!-- Loading Spinner -->
        <div v-if="loading" class="axontis-spinner w-4 h-4"></div>
        
        <!-- Icon -->
        <i v-if="icon && !loading" :class="icon"></i>
        
        <!-- Content -->
        <span v-if="$slots.default || text">
            <slot>{{ text }}</slot>
        </span>
        
        <!-- Right Icon -->
        <i v-if="rightIcon" :class="rightIcon"></i>
    </component>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'ghost', 'icon'].includes(value)
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    tag: {
        type: String,
        default: 'button',
        validator: (value) => ['button', 'a', 'router-link'].includes(value)
    },
    type: {
        type: String,
        default: 'button'
    },
    href: {
        type: String,
        default: null
    },
    to: {
        type: [String, Object],
        default: null
    },
    text: {
        type: String,
        default: null
    },
    icon: {
        type: String,
        default: null
    },
    rightIcon: {
        type: String,
        default: null
    },
    loading: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    fullWidth: {
        type: Boolean,
        default: false
    },
    class: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['click'])

const buttonClasses = computed(() => {
    let classes = 'inline-flex items-center justify-center gap-2 font-semibold transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-400/50 disabled:opacity-50 disabled:cursor-not-allowed '

    // Variant classes
    if (props.variant === 'primary') {
        classes += 'btn-axontis-primary '
    } else if (props.variant === 'secondary') {
        classes += 'btn-axontis-secondary '
    } else if (props.variant === 'ghost') {
        classes += 'btn-axontis-ghost '
    } else if (props.variant === 'icon') {
        classes += 'btn-axontis-icon '
    }

    // Size classes
    if (props.variant !== 'icon') {
        if (props.size === 'xs') {
            classes += 'px-2.5 py-1.5 text-xs '
        } else if (props.size === 'sm') {
            classes += 'px-3 py-2 text-sm '
        } else if (props.size === 'md') {
            classes += 'px-4 py-2.5 text-sm '
        } else if (props.size === 'lg') {
            classes += 'px-6 py-3 text-base '
        } else if (props.size === 'xl') {
            classes += 'px-8 py-4 text-lg '
        }
    } else {
        // Icon button sizes
        if (props.size === 'xs') {
            classes += 'w-6 h-6 text-xs '
        } else if (props.size === 'sm') {
            classes += 'w-8 h-8 text-sm '
        } else if (props.size === 'md') {
            classes += 'w-10 h-10 text-base '
        } else if (props.size === 'lg') {
            classes += 'w-12 h-12 text-lg '
        } else if (props.size === 'xl') {
            classes += 'w-14 h-14 text-xl '
        }
    }

    // Full width
    if (props.fullWidth) {
        classes += 'w-full '
    }

    // Border radius
    if (props.variant !== 'icon') {
        classes += 'rounded-lg '
    } else {
        classes += 'rounded-lg '
    }

    // Custom classes
    classes += props.class

    return classes
})

const handleClick = (event) => {
    if (!props.disabled && !props.loading) {
        emit('click', event)
    }
}
</script>