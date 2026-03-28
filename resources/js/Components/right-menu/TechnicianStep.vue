<template>
    <div class="flex flex-col gap-6">

        <!-- Title -->
        <div>
            <h3 class="text-lg font-semibold text-white">Assignation du technicien</h3>
            <p class="text-sm text-white/50 mt-1">
                Choisissez le technicien qui interviendra chez le client.
            </p>
        </div>

        <!-- Technicien -->
        <div>
            <label class="block text-sm font-medium text-white/70 mb-2">
                Technicien assigné <span class="text-error-400">*</span>
            </label>
            <div v-if="loadingStaff" class="flex items-center gap-2 text-white/40 text-sm py-3">
                <i class="fas fa-spinner fa-spin"></i> Chargement du personnel...
            </div>
            <select
                v-else
                v-model="localValue.technician_id"
                class="axontis-input w-full"
            >
                <option :value="null" disabled>Choisir un technicien</option>
                <option
                    v-for="user in staff"
                    :key="user.id"
                    :value="user.id"
                >
                    {{ user.name }}<template v-if="user.role"> — {{ formatRole(user.role) }}</template>
                </option>
            </select>
        </div>

        <!-- Date d'intervention -->
        <div>
            <label class="block text-sm font-medium text-white/70 mb-2">
                Date d'intervention prévue
            </label>
            <input
                v-model="localValue.scheduled_date"
                type="date"
                class="axontis-input w-full"
            />
        </div>

        <!-- Récapitulatif -->
        <div class="p-4 rounded-xl border border-white/10 bg-dark-800/30">
            <p class="text-xs font-semibold text-white/40 uppercase tracking-wider mb-3">Récapitulatif</p>
            <div class="flex items-center gap-2 text-sm">
                <i class="fas fa-tools w-4 text-center text-primary-400"></i>
                <span class="text-white">Installation sur site par technicien</span>
            </div>
            <div v-if="technicianName" class="flex items-center gap-2 text-sm mt-2">
                <i class="fas fa-user w-4 text-center text-white/40"></i>
                <span class="text-white/70">{{ technicianName }}</span>
            </div>
            <div v-if="localValue.scheduled_date" class="flex items-center gap-2 text-sm mt-2">
                <i class="fas fa-calendar w-4 text-center text-white/40"></i>
                <span class="text-white/70">{{ localValue.scheduled_date }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed} from 'vue'

const props = defineProps({
    modelValue:   { type: Object,  required: true },
    staff:        { type: Array,   default: () => [] },
    loadingStaff: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const localValue = new Proxy(props.modelValue, {
    set(target, key, value) {
        target[key] = value
        emit('update:modelValue', { ...target })
        return true
    }
})

const technicianName = computed(() => {
    if (!props.modelValue.technician_id) return null
    return props.staff.find(u => u.id === props.modelValue.technician_id)?.name ?? null
})

const roleLabels = {
    technician:    'Technicien',
    operator:      'Opérateur',
    manager:       'Gestionnaire',
    administrator: 'Administrateur',
}

const formatRole = (role) => roleLabels[role] ?? role
</script>

