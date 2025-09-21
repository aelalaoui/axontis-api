<template>
    <div class="axontis-input-group">
        <!-- Label -->
        <label v-if="label" :for="inputId" class="axontis-label">
            {{ label }}
            <span v-if="required" class="text-error-400 ml-1">*</span>
        </label>

        <!-- Input Container -->
        <div class="relative">
            <!-- Left Icon -->
            <div v-if="leftIcon" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i :class="leftIcon" class="text-white/50"></i>
            </div>

            <!-- Input Field -->
            <input
                :id="inputId"
                ref="input"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :readonly="readonly"
                :required="required"
                :autocomplete="autocomplete"
                :class="inputClasses"
                @input="$emit('update:modelValue', $event.target.value)"
                @focus="$emit('focus', $event)"
                @blur="$emit('blur', $event)"
                @keydown="$emit('keydown', $event)"
                @keyup="$emit('keyup', $event)"
            />

            <!-- Right Icon -->
            <div v-if="rightIcon" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <i :class="rightIcon" class="text-white/50"></i>
            </div>

            <!-- Loading Spinner -->
            <div v-if="loading" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <div class="axontis-spinner w-4 h-4"></div>
            </div>
        </div>

        <!-- Help Text -->
        <p v-if="help" class="text-xs text-white/60 mt-1">
            {{ help }}
        </p>

        <!-- Error Message -->
        <p v-if="error" class="axontis-form-error">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    type: {
        type: String,
        default: 'text'
    },
    label: {
        type: String,
        default: null
    },
    placeholder: {
        type: String,
        default: null
    },
    leftIcon: {
        type: String,
        default: null
    },
    rightIcon: {
        type: String,
        default: null
    },
    disabled: {
        type: Boolean,
        default: false
    },
    readonly: {
        type: Boolean,
        default: false
    },
    required: {
        type: Boolean,
        default: false
    },
    loading: {
        type: Boolean,
        default: false
    },
    error: {
        type: String,
        default: null
    },
    help: {
        type: String,
        default: null
    },
    autocomplete: {
        type: String,
        default: null
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
    class: {
        type: String,
        default: ''
    }
})

const emit = defineEmits([
    'update:modelValue',
    'focus',
    'blur',
    'keydown',
    'keyup'
])

const input = ref()
const inputId = computed(() => `axontis-input-${Math.random().toString(36).substr(2, 9)}`)

const inputClasses = computed(() => {
    let classes = 'axontis-input '

    // Size classes
    if (props.size === 'sm') {
        classes += 'py-2 text-sm '
    } else if (props.size === 'lg') {
        classes += 'py-4 text-lg '
    }

    // Icon padding
    if (props.leftIcon) {
        classes += 'pl-10 '
    }
    if (props.rightIcon || props.loading) {
        classes += 'pr-10 '
    }

    // State classes
    if (props.error) {
        classes += 'border-error-500 focus:border-error-400 focus:ring-error-400/20 '
    }
    if (props.disabled) {
        classes += 'opacity-50 cursor-not-allowed '
    }
    if (props.readonly) {
        classes += 'bg-dark-900/30 '
    }

    // Custom classes
    classes += props.class

    return classes
})

// Expose input methods
const focus = () => input.value?.focus()
const blur = () => input.value?.blur()
const select = () => input.value?.select()

defineExpose({
    focus,
    blur,
    select
})
</script>