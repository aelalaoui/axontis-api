<template>
    <teleport to="body">
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="modelValue"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click="closeOnOverlay && close()"
            >
                <!-- Overlay -->
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>

                <!-- Modal -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <transition
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="modelValue"
                            :class="[
                                'relative w-full transform overflow-hidden rounded-lg bg-dark-900 shadow-xl transition-all',
                                sizeClasses
                            ]"
                            @click.stop
                        >
                            <!-- Header -->
                            <div class="flex items-center justify-between px-6 py-4 border-b border-white/10">
                                <h3 class="text-lg font-semibold text-white">{{ title }}</h3>
                                <button
                                    @click="close"
                                    class="text-white/60 hover:text-white transition-colors"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <!-- Content -->
                            <div class="px-6 py-4">
                                <slot />
                            </div>

                            <!-- Footer -->
                            <div v-if="$slots.footer" class="px-6 py-4 border-t border-white/10 bg-dark-800/30">
                                <slot name="footer" />
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </transition>
    </teleport>
</template>

<script setup>
import { computed, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg', 'xl', '2xl'].includes(value),
    },
    closeOnOverlay: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['update:modelValue', 'close'])

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'max-w-md',
        md: 'max-w-lg',
        lg: 'max-w-2xl',
        xl: 'max-w-4xl',
        '2xl': 'max-w-6xl',
    }
    return sizes[props.size]
})

const close = () => {
    emit('update:modelValue', false)
    emit('close')
}

// Fermer avec la touche Escape
watch(() => props.modelValue, (value) => {
    if (value) {
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                close()
                document.removeEventListener('keydown', handleEscape)
            }
        }
        document.addEventListener('keydown', handleEscape)
    }
})
</script>
