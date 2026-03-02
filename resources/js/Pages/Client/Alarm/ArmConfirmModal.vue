<script setup>
import {useForm} from '@inertiajs/vue3'
import {ref, watch} from 'vue'

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    device: { type: Object, required: true },
})

const emit = defineEmits(['update:modelValue'])

// ─── Form ────────────────────────────────────────────────
const selectedMode = ref('away')
const forceArm = ref(false)
const openZones = ref([])
const hasZoneWarning = ref(false)

const form = useForm({
    mode: 'away',
    force: false,
})

// ─── Modes ───────────────────────────────────────────────
const armModes = [
    {
        value: 'away',
        label: 'Armement total',
        description: 'Toutes les zones surveillées. Utilisez quand vous quittez le site.',
        icon: 'fas fa-lock',
        color: 'border-green-500/50 bg-green-500/10',
        activeColor: 'border-green-400 bg-green-500/20 ring-2 ring-green-400/30',
    },
    {
        value: 'stay',
        label: 'Armement partiel',
        description: 'Zones périmètre uniquement. Utilisez quand vous restez à l\'intérieur.',
        icon: 'fas fa-home',
        color: 'border-yellow-500/50 bg-yellow-500/10',
        activeColor: 'border-yellow-400 bg-yellow-500/20 ring-2 ring-yellow-400/30',
    },
    {
        value: 'instant',
        label: 'Armement instantané',
        description: 'Armement immédiat sans délai d\'entrée/sortie.',
        icon: 'fas fa-bolt',
        color: 'border-blue-500/50 bg-blue-500/10',
        activeColor: 'border-blue-400 bg-blue-500/20 ring-2 ring-blue-400/30',
    },
]

// ─── Submit ──────────────────────────────────────────────
function submit() {
    form.mode = selectedMode.value
    form.force = forceArm.value

    form.post(route('client.alarm.devices.arm', props.device.uuid), {
        preserveScroll: true,
        onSuccess: () => {
            close()
        },
        onError: (errors) => {
            // Si zones ouvertes retournées par le backend
            if (errors.response?.data?.open_zones) {
                openZones.value = errors.response.data.open_zones
                hasZoneWarning.value = true
            }
        },
    })
}

function close() {
    emit('update:modelValue', false)
    hasZoneWarning.value = false
    openZones.value = []
    forceArm.value = false
    selectedMode.value = 'away'
}

// Reset sur fermeture
watch(() => props.modelValue, (val) => {
    if (!val) {
        hasZoneWarning.value = false
        openZones.value = []
        forceArm.value = false
        selectedMode.value = 'away'
    }
})
</script>

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
            <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>

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
                            class="relative w-full max-w-lg bg-slate-800 rounded-xl shadow-xl border border-slate-700"
                            @click.stop
                        >
                            <!-- Header -->
                            <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Armer la centrale</h3>
                                    <p class="text-sm text-slate-400 mt-0.5">{{ device.brand }} {{ device.model }}</p>
                                </div>
                                <button @click="close" class="text-slate-400 hover:text-white transition-colors">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="px-6 py-5 space-y-5">

                                <!-- Zone Warning -->
                                <div v-if="hasZoneWarning" class="bg-orange-500/20 border border-orange-500/30 rounded-lg p-4">
                                    <div class="flex items-start gap-3">
                                        <i class="fas fa-exclamation-triangle text-orange-400 mt-0.5"></i>
                                        <div>
                                            <p class="text-orange-300 font-medium text-sm">Zones ouvertes détectées</p>
                                            <ul class="mt-2 space-y-1">
                                                <li v-for="zone in openZones" :key="zone.id" class="text-orange-200/80 text-sm">
                                                    • Zone {{ zone.number }} — {{ zone.name || 'Sans nom' }}
                                                </li>
                                            </ul>
                                            <label class="flex items-center gap-2 mt-3 cursor-pointer">
                                                <input
                                                    v-model="forceArm"
                                                    type="checkbox"
                                                    class="rounded border-orange-500/50 text-orange-600 focus:ring-orange-500 bg-transparent"
                                                />
                                                <span class="text-sm text-orange-200">Forcer l'armement malgré les zones ouvertes</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mode Selection -->
                                <div>
                                    <label class="text-sm font-medium text-slate-300 mb-3 block">Mode d'armement</label>
                                    <div class="space-y-3">
                                        <button
                                            v-for="mode in armModes"
                                            :key="mode.value"
                                            @click="selectedMode = mode.value"
                                            class="w-full text-left px-4 py-3 rounded-lg border transition-all"
                                            :class="selectedMode === mode.value ? mode.activeColor : mode.color"
                                        >
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-white/5">
                                                    <i :class="mode.icon" class="text-sm text-white"></i>
                                                </div>
                                                <div>
                                                    <p class="text-white font-medium text-sm">{{ mode.label }}</p>
                                                    <p class="text-slate-400 text-xs mt-0.5">{{ mode.description }}</p>
                                                </div>
                                                <i
                                                    v-if="selectedMode === mode.value"
                                                    class="fas fa-check-circle text-green-400 ml-auto"
                                                ></i>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Error display -->
                                <div v-if="form.errors.device" class="bg-red-500/20 border border-red-500/30 rounded-lg p-3">
                                    <p class="text-red-300 text-sm">{{ form.errors.device }}</p>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="px-6 py-4 border-t border-slate-700/50 flex items-center justify-end gap-3">
                                <button
                                    @click="close"
                                    class="px-4 py-2 text-sm text-slate-300 hover:text-white transition-colors"
                                >
                                    Annuler
                                </button>
                                <button
                                    @click="submit"
                                    :disabled="form.processing || (hasZoneWarning && !forceArm)"
                                    class="px-5 py-2 bg-green-600 hover:bg-green-700 disabled:bg-slate-600 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors"
                                >
                                    <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                    <i v-else class="fas fa-lock mr-2"></i>
                                    Armer
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </transition>
    </teleport>
</template>

