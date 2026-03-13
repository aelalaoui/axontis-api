<template>
    <!-- Overlay -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40"
                @click="close"
            />
        </Transition>

        <!-- Panel -->
        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div
                v-if="show"
                class="fixed top-0 right-0 h-full z-50 flex flex-col shadow-2xl"
                :style="{ width: width }"
                :class="panelClasses"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 flex-shrink-0">
                    <div>
                        <slot name="title">
                            <h2 class="text-lg font-semibold text-white">{{ title }}</h2>
                        </slot>
                        <p v-if="subtitle" class="text-sm text-white/50 mt-0.5">{{ subtitle }}</p>
                    </div>
                    <button
                        @click="close"
                        class="text-white/40 hover:text-white transition-colors duration-200 p-1 rounded-lg hover:bg-white/10"
                    >
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <!-- Content (scrollable) -->
                <div class="flex-1 overflow-y-auto">
                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import {onMounted, onUnmounted, watch} from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: null,
    },
    width: {
        type: String,
        default: '600px',
    },
    panelClasses: {
        type: String,
        default: 'bg-dark-900 border-l border-white/10',
    },
})

const emit = defineEmits(['close'])

const close = () => emit('close')

const onKeydown = (e) => {
    if (e.key === 'Escape' && props.show) close()
}

// Lock body scroll when open
watch(() => props.show, (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
})

onMounted(() => document.addEventListener('keydown', onKeydown))
onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
})
</script>

