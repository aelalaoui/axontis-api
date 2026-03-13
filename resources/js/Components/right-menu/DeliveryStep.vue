<template>
    <div class="flex flex-col gap-6">

        <!-- Title -->
        <div>
            <h3 class="text-lg font-semibold text-white">Mode de livraison</h3>
            <p class="text-sm text-white/50 mt-1">
                Définissez comment les équipements seront remis au client.
            </p>
        </div>

        <!-- Mode radio -->
        <div class="flex flex-col gap-3">
            <div
                role="button"
                tabindex="0"
                class="flex items-start gap-4 p-4 rounded-xl border transition-all duration-200 cursor-pointer"
                :class="localValue.delivery_mode === 'on_site'
                    ? 'border-primary-500 bg-primary-500/10'
                    : 'border-white/10 bg-dark-800/20 hover:border-white/20'"
                @click="localValue.delivery_mode = 'on_site'"
                @keydown.enter.space.prevent="localValue.delivery_mode = 'on_site'"
            >
                <span
                    class="mt-0.5 w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors"
                    :class="localValue.delivery_mode === 'on_site' ? 'border-primary-500' : 'border-white/30'"
                >
                    <span v-if="localValue.delivery_mode === 'on_site'" class="w-2.5 h-2.5 rounded-full bg-primary-500" />
                </span>
                <div class="text-left">
                    <p class="font-medium text-white">Installation sur site</p>
                    <p class="text-sm text-white/50 mt-0.5">
                        Un technicien se déplace chez le client pour installer les équipements.
                    </p>
                </div>
                <i class="fas fa-tools text-primary-400/60 ml-auto mt-0.5 flex-shrink-0"></i>
            </div>

            <div
                role="button"
                tabindex="0"
                class="flex items-start gap-4 p-4 rounded-xl border transition-all duration-200 cursor-pointer"
                :class="localValue.delivery_mode === 'postal'
                    ? 'border-warning-500 bg-warning-500/10'
                    : 'border-white/10 bg-dark-800/20 hover:border-white/20'"
                @click="localValue.delivery_mode = 'postal'"
                @keydown.enter.space.prevent="localValue.delivery_mode = 'postal'"
            >
                <span
                    class="mt-0.5 w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors"
                    :class="localValue.delivery_mode === 'postal' ? 'border-warning-500' : 'border-white/30'"
                >
                    <span v-if="localValue.delivery_mode === 'postal'" class="w-2.5 h-2.5 rounded-full bg-warning-500" />
                </span>
                <div class="text-left">
                    <p class="font-medium text-white">Envoi postal</p>
                    <p class="text-sm text-white/50 mt-0.5">
                        Les équipements sont envoyés par la poste. Le client les installera lui-même.
                    </p>
                </div>
                <i class="fas fa-box text-warning-400/60 ml-auto mt-0.5 flex-shrink-0"></i>
            </div>
        </div>

        <!-- On-site fields -->
        <template v-if="localValue.delivery_mode === 'on_site'">
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
                        {{ user.name }}
                        <template v-if="user.role"> — {{ formatRole(user.role) }}</template>
                    </option>
                </select>
            </div>

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
        </template>

        <!-- Postal fields -->
        <template v-if="localValue.delivery_mode === 'postal'">
            <div>
                <label class="block text-sm font-medium text-white/70 mb-2">
                    Adresse de livraison <span class="text-error-400">*</span>
                </label>
                <textarea
                    v-model="localValue.postal_address"
                    rows="3"
                    placeholder="Adresse complète de livraison..."
                    class="axontis-input w-full resize-none"
                />
            </div>
        </template>

        <!-- Summary recap -->
        <div class="p-4 rounded-xl border border-white/10 bg-dark-800/30">
            <p class="text-xs font-semibold text-white/40 uppercase tracking-wider mb-3">Récapitulatif</p>
            <div class="flex items-center gap-2 text-sm">
                <i
                    class="w-4 text-center"
                    :class="localValue.delivery_mode === 'on_site' ? 'fas fa-tools text-primary-400' : 'fas fa-box text-warning-400'"
                ></i>
                <span class="text-white">
                    {{ localValue.delivery_mode === 'on_site' ? 'Installation sur site' : 'Envoi postal' }}
                </span>
            </div>
            <div v-if="localValue.delivery_mode === 'on_site' && technicianName" class="flex items-center gap-2 text-sm mt-2">
                <i class="fas fa-user w-4 text-center text-white/40"></i>
                <span class="text-white/70">{{ technicianName }}</span>
            </div>
            <div v-if="localValue.delivery_mode === 'on_site' && localValue.scheduled_date" class="flex items-center gap-2 text-sm mt-2">
                <i class="fas fa-calendar w-4 text-center text-white/40"></i>
                <span class="text-white/70">{{ localValue.scheduled_date }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed} from 'vue'

const props = defineProps({
    modelValue: { type: Object, required: true },
    staff: { type: Array, default: () => [] },
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
    technician: 'Technicien',
    operator: 'Opérateur',
    manager: 'Gestionnaire',
    administrator: 'Administrateur',
}

const formatRole = (role) => roleLabels[role] ?? role
</script>


