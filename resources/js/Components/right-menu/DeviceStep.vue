<template>
    <div class="flex flex-col gap-6">

        <!-- Sub-product info -->
        <div class="flex items-start gap-3 p-4 rounded-xl bg-primary-500/10 border border-primary-500/20">
            <i class="fas fa-box text-primary-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="font-semibold text-white">{{ subProduct.name }}</p>
                <p v-if="subProduct.property_name" class="text-sm text-white/50 mt-0.5">
                    Propriété : <span class="text-white/70">{{ subProduct.property_name }}</span>
                </p>
            </div>
        </div>

        <!-- Device reference (pre-selected) -->
        <div>
            <label class="block text-sm font-medium text-white/70 mb-2">
                Device référence
            </label>

            <div
                v-if="subProduct.device"
                class="flex items-center gap-3 p-3 rounded-xl border transition-all duration-200"
                :class="useReference
                    ? 'border-primary-500 bg-primary-500/10'
                    : 'border-white/10 bg-dark-800/30 opacity-50'"
            >
                <i class="fas fa-microchip text-primary-400 flex-shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-white truncate">{{ subProduct.device.full_name }}</p>
                    <p class="text-xs text-white/40 mt-0.5">
                        {{ subProduct.device.category }}
                        <span class="ml-2" :class="subProduct.device.stock_qty > 0 ? 'text-success-400' : 'text-error-400'">
                            · Stock: {{ subProduct.device.stock_qty }}
                        </span>
                    </p>
                </div>
                <span
                    v-if="subProduct.device.stock_qty <= 0"
                    class="text-xs text-error-400 flex-shrink-0"
                >
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Rupture
                </span>
            </div>

            <p v-else class="text-sm text-white/40 italic p-3 border border-white/10 rounded-xl">
                Aucun device de référence lié à ce sous-produit
            </p>

            <!-- Toggle: use reference or pick another -->
            <div v-if="subProduct.device" class="flex items-center gap-3 mt-3">
                <button
                    class="text-xs px-3 py-1.5 rounded-lg border transition-colors duration-200"
                    :class="useReference
                        ? 'border-primary-500 bg-primary-500/20 text-primary-400'
                        : 'border-white/20 bg-white/5 text-white/50'"
                    @click="selectReference"
                >
                    <i class="fas fa-link mr-1.5"></i>
                    Utiliser le device référence
                </button>
                <button
                    class="text-xs px-3 py-1.5 rounded-lg border transition-colors duration-200"
                    :class="!useReference
                        ? 'border-warning-500 bg-warning-500/20 text-warning-400'
                        : 'border-white/20 bg-white/5 text-white/50'"
                    @click="useReference = false; localValue.device_id = null"
                >
                    <i class="fas fa-exchange-alt mr-1.5"></i>
                    Choisir un autre
                </button>
            </div>
        </div>

        <!-- Manual device picker (if not using reference) -->
        <div v-if="!useReference">
            <label class="block text-sm font-medium text-white/70 mb-2">
                Rechercher un device <span class="text-error-400">*</span>
            </label>
            <div class="relative">
                <input
                    v-model="deviceSearch"
                    type="text"
                    placeholder="Rechercher par marque, modèle..."
                    class="axontis-input w-full pr-10"
                    @input="onSearchDevice"
                />
                <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-white/30"></i>
            </div>
            <div v-if="deviceResults.length > 0" class="mt-2 border border-white/10 rounded-xl overflow-hidden max-h-48 overflow-y-auto">
                <div
                    v-for="device in deviceResults"
                    :key="device.id"
                    class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-white/5 transition-colors border-b border-white/5 last:border-0 cursor-pointer"
                    :class="localValue.device_id === device.id ? 'bg-primary-500/10' : ''"
                    @click="selectDevice(device)"
                >
                    <i class="fas fa-microchip text-white/40 flex-shrink-0"></i>
                    <span class="flex flex-col">
                        <span class="text-white text-sm font-medium">{{ device.brand }} {{ device.model }}</span>
                        <span class="text-xs text-white/40">{{ device.category }} · Stock: {{ device.stock_qty }}</span>
                    </span>
                    <i v-if="localValue.device_id === device.id" class="fas fa-check ml-auto text-primary-400 flex-shrink-0"></i>
                </div>
            </div>
        </div>

        <!-- Serial number -->
        <div>
            <label class="block text-sm font-medium text-white/70 mb-2">
                Numéro de série
            </label>
            <input
                v-model="localValue.serial_number"
                type="text"
                placeholder="Ex: SN-2024-001"
                class="axontis-input w-full"
            />
        </div>

        <!-- Dynamic property from sub-product definition -->
        <div v-if="subProduct.property_name">
            <label class="block text-sm font-medium text-white/70 mb-2">
                {{ subProduct.property_name }}
                <span class="text-white/30 ml-1 text-xs">(propriété)</span>
            </label>
            <input
                v-model="localValue.properties[subProduct.property_name]"
                type="text"
                :placeholder="subProduct.default_value || ''"
                class="axontis-input w-full"
            />
        </div>

        <!-- Additional free properties -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium text-white/70">Propriétés supplémentaires</label>
                <button
                    class="text-xs text-primary-400 hover:text-primary-300 transition-colors"
                    @click="addProperty"
                >
                    <i class="fas fa-plus mr-1"></i>Ajouter
                </button>
            </div>
            <div
                v-for="(prop, idx) in extraProperties"
                :key="idx"
                class="flex gap-2 mb-2"
            >
                <input
                    v-model="prop.key"
                    type="text"
                    placeholder="Clé"
                    class="axontis-input flex-1"
                    @change="syncExtraProperties"
                />
                <input
                    v-model="prop.value"
                    type="text"
                    placeholder="Valeur"
                    class="axontis-input flex-1"
                    @change="syncExtraProperties"
                />
                <button
                    class="text-error-400 hover:text-error-300 px-2"
                    @click="removeProperty(idx)"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Notes -->
        <div>
            <label class="block text-sm font-medium text-white/70 mb-2">Notes</label>
            <textarea
                v-model="localValue.notes"
                rows="3"
                placeholder="Remarques sur cet équipement..."
                class="axontis-input w-full resize-none"
            />
        </div>
    </div>
</template>

<script setup>
import {ref, watch} from 'vue'
import axios from 'axios'

const props = defineProps({
    subProduct: { type: Object, required: true },
    modelValue: { type: Object, required: true },
})

const emit = defineEmits(['update:modelValue'])

// Local copy synced with modelValue
const localValue = ref({ ...props.modelValue })

watch(localValue, (val) => emit('update:modelValue', { ...val }), { deep: true })
watch(() => props.modelValue, (val) => {
    if (JSON.stringify(val) !== JSON.stringify(localValue.value)) {
        localValue.value = { ...val }
    }
}, { deep: true })

// Reference device selection
const useReference = ref(!!props.subProduct.device?.id)

const selectReference = () => {
    useReference.value = true
    localValue.value.device_id = props.subProduct.device?.id ?? null
}

// Manual device search
const deviceSearch = ref('')
const deviceResults = ref([])
let searchTimeout = null

const onSearchDevice = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(async () => {
        if (!deviceSearch.value.trim()) { deviceResults.value = []; return }
        try {
            const { data } = await axios.get(route('crm.api.devices.search'), {
                params: { q: deviceSearch.value, stock: 1 }
            })
            deviceResults.value = data.devices ?? data ?? []
        } catch (e) {
            deviceResults.value = []
        }
    }, 300)
}

const selectDevice = (device) => {
    localValue.value.device_id = device.id
    deviceSearch.value = `${device.brand} ${device.model}`
    deviceResults.value = []
}

// Extra free properties
const extraProperties = ref([])

const addProperty = () => extraProperties.value.push({ key: '', value: '' })

const removeProperty = (idx) => {
    extraProperties.value.splice(idx, 1)
    syncExtraProperties()
}

const syncExtraProperties = () => {
    const extras = {}
    for (const p of extraProperties.value) {
        if (p.key.trim()) extras[p.key.trim()] = p.value
    }
    // Merge with base properties (keep property_name from sub-product)
    const base = props.subProduct.property_name
        ? { [props.subProduct.property_name]: localValue.value.properties[props.subProduct.property_name] ?? '' }
        : {}
    localValue.value.properties = { ...base, ...extras }
}
</script>




