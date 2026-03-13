<template>
    <RightMenu
        :show="show"
        :title="installation ? `Assigner — ${formatType(installation.type)}` : 'Assignation'"
        :subtitle="installation ? (installation.address || '') : ''"
        width="680px"
        @close="$emit('close')"
    >
        <div class="flex flex-col h-full">

            <!-- Stepper header -->
            <div class="px-6 pt-5 pb-4 border-b border-white/10 flex-shrink-0">
                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                    <template v-for="(step, index) in steps" :key="index">
                        <button
                            class="flex items-center gap-2 flex-shrink-0 text-sm font-medium transition-colors duration-200"
                            :class="currentStep === index
                                ? 'text-primary-400'
                                : index < currentStep
                                    ? 'text-success-400'
                                    : 'text-white/30'"
                            @click="goToStep(index)"
                            :disabled="index > maxReachedStep"
                        >
                            <span
                                class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 border"
                                :class="currentStep === index
                                    ? 'bg-primary-500/20 border-primary-500 text-primary-400'
                                    : index < currentStep
                                        ? 'bg-success-500/20 border-success-500 text-success-400'
                                        : 'bg-white/5 border-white/20 text-white/30'"
                            >
                                <i v-if="index < currentStep" class="fas fa-check text-[10px]"></i>
                                <span v-else>{{ index + 1 }}</span>
                            </span>
                            <span class="hidden sm:block max-w-[100px] truncate">{{ step.label }}</span>
                        </button>
                        <div
                            v-if="index < steps.length - 1"
                            class="flex-1 min-w-[16px] h-px"
                            :class="index < currentStep ? 'bg-success-500/50' : 'bg-white/10'"
                        />
                    </template>
                </div>
            </div>

            <!-- Step content (scrollable) -->
            <div class="flex-1 overflow-y-auto px-6 py-6">

                <!-- Device Steps -->
                <template v-if="currentStep < subProducts.length">
                    <DeviceStep
                        :key="currentStep"
                        :sub-product="subProducts[currentStep]"
                        v-model="deviceAssignments[currentStep]"
                    />
                </template>

                <!-- Final Step: Delivery Mode -->
                <template v-else>
                    <DeliveryStep
                        v-model="deliveryData"
                        :staff="staff"
                        :loading-staff="loadingStaff"
                    />
                </template>
            </div>

            <!-- Navigation footer -->
            <div class="px-6 py-4 border-t border-white/10 flex items-center justify-between flex-shrink-0">
                <button
                    v-if="currentStep > 0"
                    class="btn-axontis-secondary"
                    @click="prevStep"
                    :disabled="submitting"
                >
                    <i class="fas fa-arrow-left mr-2"></i>
                    Précédent
                </button>
                <div v-else />

                <button
                    v-if="currentStep < steps.length - 1"
                    class="btn-axontis-primary"
                    @click="nextStep"
                    :disabled="!canGoNext"
                >
                    Suivant
                    <i class="fas fa-arrow-right ml-2"></i>
                </button>

                <button
                    v-else
                    class="btn-axontis-primary"
                    @click="submit"
                    :disabled="!canSubmit || submitting"
                >
                    <i v-if="submitting" class="fas fa-spinner fa-spin mr-2"></i>
                    <i v-else class="fas fa-check mr-2"></i>
                    {{ submitting ? 'Assignation...' : 'Valider l\'assignation' }}
                </button>
            </div>
        </div>
    </RightMenu>
</template>

<script setup>
import {computed, ref, watch} from 'vue'
import {router} from '@inertiajs/vue3'
import axios from 'axios'
import RightMenu from '@/Components/right-menu/RightMenu.vue'
import DeviceStep from '@/Components/right-menu/DeviceStep.vue'
import DeliveryStep from '@/Components/right-menu/DeliveryStep.vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    installation: { type: Object, default: null },
    subProducts: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'assigned'])

// ── State ─────────────────────────────────────────────────────────────────────
const currentStep = ref(0)
const maxReachedStep = ref(0)
const submitting = ref(false)

const staff = ref([])
const loadingStaff = ref(false)

// One entry per sub-product + one final step
const steps = computed(() => [
    ...props.subProducts.map((sp) => ({ label: sp.name || `Équipement ${sp.id}` })),
    { label: 'Livraison' },
])

// Device assignments: one object per sub-product
const deviceAssignments = ref([])

const deliveryData = ref({
    delivery_mode: 'on_site',
    technician_id: null,
    scheduled_date: '',
    postal_address: '',
})

// ── Watchers ──────────────────────────────────────────────────────────────────
watch(() => props.subProducts, (val) => {
    deviceAssignments.value = val.map((sp) => ({
        device_id: sp.device?.id ?? null,
        serial_number: '',
        status: 'assigned',
        notes: '',
        properties: sp.property_name
            ? { [sp.property_name]: sp.default_value ?? '' }
            : {},
    }))
    currentStep.value = 0
    maxReachedStep.value = 0
}, { immediate: true })

watch(() => props.show, async (val) => {
    if (val && staff.value.length === 0) {
        await loadStaff()
    }
    if (!val) {
        currentStep.value = 0
        maxReachedStep.value = 0
    }
})

// ── Navigation ────────────────────────────────────────────────────────────────
const canGoNext = computed(() => {
    if (currentStep.value >= props.subProducts.length) return false
    const a = deviceAssignments.value[currentStep.value]
    return a && a.device_id
})

const canSubmit = computed(() => {
    if (deliveryData.value.delivery_mode === 'on_site') {
        return !!deliveryData.value.technician_id
    }
    return !!deliveryData.value.postal_address?.trim()
})

const goToStep = (index) => {
    if (index <= maxReachedStep.value) currentStep.value = index
}

const nextStep = () => {
    if (!canGoNext.value) return
    currentStep.value++
    if (currentStep.value > maxReachedStep.value) {
        maxReachedStep.value = currentStep.value
    }
}

const prevStep = () => {
    if (currentStep.value > 0) currentStep.value--
}

// ── Staff loading ─────────────────────────────────────────────────────────────
const loadStaff = async () => {
    loadingStaff.value = true
    try {
        const { data } = await axios.get(route('crm.api.staff'))
        staff.value = data.users
    } catch (e) {
        console.error('Failed to load staff', e)
    } finally {
        loadingStaff.value = false
    }
}

// ── Submit ────────────────────────────────────────────────────────────────────
const submit = () => {
    if (!canSubmit.value || submitting.value) return
    submitting.value = true

    router.post(
        route('crm.installations.assign', props.installation.uuid),
        {
            delivery_mode: deliveryData.value.delivery_mode,
            technician_id: deliveryData.value.delivery_mode === 'on_site'
                ? deliveryData.value.technician_id
                : null,
            scheduled_date: deliveryData.value.scheduled_date || null,
            postal_address: deliveryData.value.delivery_mode === 'postal'
                ? deliveryData.value.postal_address
                : null,
            devices: deviceAssignments.value.map((a) => ({
                device_id: a.device_id,
                serial_number: a.serial_number || null,
                status: a.status,
                notes: a.notes || null,
                properties: a.properties,
            })),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                emit('assigned')
                emit('close')
            },
            onError: (errors) => console.error(errors),
            onFinish: () => { submitting.value = false },
        }
    )
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const formatType = (type) => {
    const map = {
        first_installation: 'Première installation',
        additional_installation: 'Installation supplémentaire',
        maintenance: 'Maintenance',
    }
    return map[type] || type
}
</script>

