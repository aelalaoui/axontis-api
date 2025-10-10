<template>
    <div class="relative">
        <div class="space-y-2">
            <label v-if="label" class="block text-sm font-medium text-white">
                {{ label }}
                <span v-if="required" class="text-error-400">*</span>
            </label>

            <div class="relative">
                <input
                    ref="searchInput"
                    v-model="searchQuery"
                    type="text"
                    :class="[
                        'axontis-input pr-10',
                        { 'border-error-500': hasError }
                    ]"
                    :placeholder="placeholder"
                    :disabled="disabled || !entityType"
                    @input="handleSearch"
                    @focus="showDropdown = true"
                    @blur="handleBlur"
                    @keydown="handleKeydown"
                    autocomplete="off"
                />

                <!-- Loading spinner -->
                <div v-if="loading" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                    <i class="fas fa-spinner fa-spin text-primary-400"></i>
                </div>

                <!-- Search icon -->
                <div v-else class="absolute right-3 top-1/2 transform -translate-y-1/2">
                    <i class="fas fa-search text-white/40"></i>
                </div>
            </div>

            <!-- Error message -->
            <div v-if="error" class="text-error-400 text-sm">
                {{ error }}
            </div>
        </div>

        <!-- Suggestions dropdown -->
        <div
            v-if="showDropdown && (suggestions.length > 0 || loading || noResults)"
            class="absolute z-50 w-full mt-1 bg-dark-800 border border-white/10 rounded-lg shadow-lg max-h-60 overflow-y-auto"
        >
            <!-- Loading state -->
            <div v-if="loading" class="p-4 text-center">
                <i class="fas fa-spinner fa-spin text-primary-400 mr-2"></i>
                <span class="text-white/60">Recherche en cours...</span>
            </div>

            <!-- No results -->
            <div v-else-if="noResults" class="p-4 text-center text-white/60">
                Aucun résultat trouvé
            </div>

            <!-- Suggestions list -->
            <div v-else>
                <div
                    v-for="(suggestion, index) in suggestions"
                    :key="suggestion.id"
                    :class="[
                        'p-3 cursor-pointer border-b border-white/5 last:border-b-0 transition-colors',
                        {
                            'bg-primary-500/20': index === selectedIndex,
                            'hover:bg-dark-700': index !== selectedIndex
                        }
                    ]"
                    @mousedown.prevent="selectSuggestion(suggestion)"
                    @mouseenter="selectedIndex = index"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-primary-500/20 rounded flex items-center justify-center flex-shrink-0">
                            <i :class="getEntityIcon(entityType)" class="text-primary-400 text-sm"></i>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-white truncate">
                                {{ suggestion.display_name }}
                            </div>
                            <div v-if="suggestion.additional_info" class="text-xs text-white/60 mt-1">
                                <span
                                    v-for="(value, key) in suggestion.additional_info"
                                    :key="key"
                                    class="mr-3"
                                >
                                    {{ formatAdditionalInfo(key, value) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, nextTick, computed } from 'vue'

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    entityType: {
        type: String,
        required: true
    },
    label: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Rechercher...'
    },
    required: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    error: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue', 'selected'])

// État local
const searchQuery = ref('')
const suggestions = ref([])
const selectedIndex = ref(-1)
const showDropdown = ref(false)
const loading = ref(false)
const noResults = ref(false)
const searchInput = ref(null)
const selectedEntity = ref(null)

// Computed
const hasError = computed(() => !!props.error)

// Fonction debounce native
const debounce = (func, wait) => {
    let timeout
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout)
            func(...args)
        }
        clearTimeout(timeout)
        timeout = setTimeout(later, wait)
    }
}

// Watchers
watch(() => props.entityType, () => {
    // Reset when entity type changes
    resetSearch()
})

watch(() => props.modelValue, (newValue) => {
    if (!newValue) {
        resetSearch()
    }
})

// Méthodes
const resetSearch = () => {
    searchQuery.value = ''
    suggestions.value = []
    selectedIndex.value = -1
    showDropdown.value = false
    selectedEntity.value = null
    emit('update:modelValue', '')
}

const handleSearch = debounce(async () => {
    if (!props.entityType || searchQuery.value.length < 2) {
        suggestions.value = []
        noResults.value = false
        return
    }

    loading.value = true
    noResults.value = false

    try {
        // Récupération sécurisée du token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

        const headers = {
            'Accept': 'application/json',
        }

        // Ajouter le token CSRF seulement s'il existe
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken
        }

        const response = await fetch(`/api/entities/search?type=${encodeURIComponent(props.entityType)}&query=${encodeURIComponent(searchQuery.value)}`, {
            headers
        })

        if (response.ok) {
            const data = await response.json()
            suggestions.value = data.data || []
            noResults.value = suggestions.value.length === 0
        } else {
            console.error('Erreur lors de la recherche')
            suggestions.value = []
            noResults.value = true
        }
    } catch (error) {
        console.error('Erreur réseau:', error)
        suggestions.value = []
        noResults.value = true
    } finally {
        loading.value = false
    }
}, 300)

const selectSuggestion = (suggestion) => {
    selectedEntity.value = suggestion
    searchQuery.value = suggestion.display_name
    emit('update:modelValue', suggestion.id)
    emit('selected', suggestion)
    showDropdown.value = false
    selectedIndex.value = -1
}

const handleBlur = () => {
    // Delay hiding dropdown to allow click events
    setTimeout(() => {
        showDropdown.value = false
    }, 150)
}

const handleKeydown = (event) => {
    if (!showDropdown.value || suggestions.value.length === 0) return

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault()
            selectedIndex.value = Math.min(selectedIndex.value + 1, suggestions.value.length - 1)
            break

        case 'ArrowUp':
            event.preventDefault()
            selectedIndex.value = Math.max(selectedIndex.value - 1, -1)
            break

        case 'Enter':
            event.preventDefault()
            if (selectedIndex.value >= 0) {
                selectSuggestion(suggestions.value[selectedIndex.value])
            }
            break

        case 'Escape':
            showDropdown.value = false
            selectedIndex.value = -1
            searchInput.value?.blur()
            break
    }
}

const getEntityIcon = (entityType) => {
    const icons = {
        'App\\Models\\Client': 'fas fa-user',
        'App\\Models\\Supplier': 'fas fa-truck',
        'App\\Models\\Order': 'fas fa-shopping-cart',
        'App\\Models\\Device': 'fas fa-microchip',
        'App\\Models\\Product': 'fas fa-box',
        'App\\Models\\Contract': 'fas fa-file-contract',
    }
    return icons[entityType] || 'fas fa-link'
}

const formatAdditionalInfo = (key, value) => {
    const labels = {
        email: 'Email',
        phone: 'Tél',
        sku: 'SKU',
        status: 'Statut',
        brand: 'Marque',
        model: 'Modèle',
        serial_number: 'N° série',
        contract_number: 'N° contrat',
        contact_person: 'Contact',
        total_amount: 'Montant'
    }

    const label = labels[key] || key
    return `${label}: ${value}`
}

// Expose methods for parent component
defineExpose({
    focus: () => searchInput.value?.focus(),
    reset: resetSearch
})
</script>
