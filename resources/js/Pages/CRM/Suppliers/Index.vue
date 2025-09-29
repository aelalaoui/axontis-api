<template>
    <AxontisDashboardLayout title="Suppliers" subtitle="Manage your suppliers and vendor relationships">
        <!-- Header Actions -->
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-4">
                <!-- Search -->
                <div class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search suppliers..."
                        class="axontis-input pl-10 w-64"
                        @input="handleSearch"
                    />
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-white/40"></i>
                </div>

                <!-- Status Filter -->
                <select
                    v-model="statusFilter"
                    class="axontis-input"
                    @change="handleFilter"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <!-- Add Supplier Button -->
            <Link :href="route('crm.suppliers.create')" class="btn-axontis-primary">
                <i class="fas fa-plus mr-2"></i>
                Add Supplier
            </Link>
        </div>

        <!-- Suppliers Table -->
        <AxontisCard>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-primary-500/20">
                            <th class="text-left py-4 px-6 font-semibold text-white">
                                <button @click="sort('name')" class="flex items-center gap-2 hover:text-primary-400">
                                    Name
                                    <i :class="getSortIcon('name')" class="text-xs"></i>
                                </button>
                            </th>
                            <th class="text-left py-4 px-6 font-semibold text-white">
                                <button @click="sort('code')" class="flex items-center gap-2 hover:text-primary-400">
                                    Code
                                    <i :class="getSortIcon('code')" class="text-xs"></i>
                                </button>
                            </th>
                            <th class="text-left py-4 px-6 font-semibold text-white">Contact</th>
                            <th class="text-left py-4 px-6 font-semibold text-white">Location</th>
                            <th class="text-left py-4 px-6 font-semibold text-white">Status</th>
                            <th class="text-right py-4 px-6 font-semibold text-white">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="supplier in suppliers.data"
                            :key="supplier.id"
                            class="border-b border-primary-500/10 hover:bg-primary-500/5 transition-colors duration-200"
                        >
                            <td class="py-4 px-6">
                                <div>
                                    <div class="font-medium text-white">{{ supplier.name }}</div>
                                    <div v-if="supplier.contact_person" class="text-sm text-white/60">
                                        {{ supplier.contact_person }}
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="font-mono text-sm text-primary-400">{{ supplier.code }}</span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="text-sm">
                                    <div v-if="supplier.email" class="text-white/80">{{ supplier.email }}</div>
                                    <div v-if="supplier.phone" class="text-white/60">{{ supplier.phone }}</div>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="text-sm text-white/80">
                                    {{ supplier.full_address || 'No address' }}
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        supplier.is_active
                                            ? 'bg-success-500/20 text-success-300'
                                            : 'bg-error-500/20 text-error-300'
                                    ]"
                                >
                                    {{ supplier.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- View Button -->
                                    <Link
                                        :href="route('crm.suppliers.show', supplier.uuid)"
                                        class="btn-axontis-icon text-primary-400 hover:text-primary-300"
                                        title="View Supplier"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </Link>

                                    <!-- Edit Button -->
                                    <Link
                                        :href="route('crm.suppliers.edit', supplier.uuid)"
                                        class="btn-axontis-icon text-warning-400 hover:text-warning-300"
                                        title="Edit Supplier"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </Link>

                                    <!-- Toggle Status Button -->
                                    <button
                                        @click="toggleStatus(supplier)"
                                        :class="[
                                            'btn-axontis-icon',
                                            supplier.is_active
                                                ? 'text-orange-400 hover:text-orange-300'
                                                : 'text-green-400 hover:text-green-300'
                                        ]"
                                        :title="supplier.is_active ? 'Deactivate' : 'Activate'"
                                    >
                                        <i :class="supplier.is_active ? 'fas fa-pause' : 'fas fa-play'"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    <button
                                        @click="confirmDelete(supplier)"
                                        class="btn-axontis-icon text-error-400 hover:text-error-300"
                                        title="Delete Supplier"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div v-if="suppliers.data.length === 0" class="text-center py-12">
                    <i class="fas fa-truck text-4xl text-white/20 mb-4"></i>
                    <h3 class="text-lg font-medium text-white mb-2">No suppliers found</h3>
                    <p class="text-white/60 mb-4">
                        {{ searchQuery || statusFilter ? 'Try adjusting your filters' : 'Get started by adding your first supplier' }}
                    </p>
                    <Link
                        v-if="!searchQuery && !statusFilter"
                        :href="route('crm.suppliers.create')"
                        class="btn-axontis-primary"
                    >
                        <i class="fas fa-plus mr-2"></i>
                        Add First Supplier
                    </Link>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="suppliers.data.length > 0" class="border-t border-primary-500/20 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-white/60">
                        Showing {{ suppliers.from }} to {{ suppliers.to }} of {{ suppliers.total }} results
                    </div>
                    <div class="flex items-center gap-2">
                        <Link
                            v-for="link in suppliers.links"
                            :key="link.label"
                            :href="link.url"
                            :class="[
                                'px-3 py-2 text-sm rounded-lg transition-colors duration-200',
                                link.active
                                    ? 'bg-primary-500 text-white'
                                    : link.url
                                    ? 'text-white/70 hover:text-white hover:bg-primary-500/20'
                                    : 'text-white/40 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </AxontisCard>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="showDeleteModal" @close="showDeleteModal = false">
            <template #title>
                Delete Supplier
            </template>

            <template #content>
                Are you sure you want to delete <strong>{{ supplierToDelete?.name }}</strong>?
                This action cannot be undone.
            </template>

            <template #footer>
                <SecondaryButton @click="showDeleteModal = false">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': deleteForm.processing }"
                    :disabled="deleteForm.processing"
                    @click="deleteSupplier"
                >
                    Delete Supplier
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AxontisDashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'

const props = defineProps({
    suppliers: Object,
    filters: Object,
})

// Reactive state
const searchQuery = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')
const showDeleteModal = ref(false)
const supplierToDelete = ref(null)

// Forms
const deleteForm = useForm({})

// Methods
const handleSearch = () => {
    router.get(route('crm.suppliers.index'), {
        search: searchQuery.value,
        status: statusFilter.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

const handleFilter = () => {
    router.get(route('crm.suppliers.index'), {
        search: searchQuery.value,
        status: statusFilter.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

const sort = (field) => {
    const direction = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc'
    
    router.get(route('crm.suppliers.index'), {
        search: searchQuery.value,
        status: statusFilter.value,
        sort: field,
        direction: direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

const getSortIcon = (field) => {
    if (props.filters.sort !== field) {
        return 'fas fa-sort text-white/40'
    }
    return props.filters.direction === 'asc' ? 'fas fa-sort-up text-primary-400' : 'fas fa-sort-down text-primary-400'
}

const toggleStatus = (supplier) => {
    router.patch(route('crm.suppliers.toggle-status', supplier.uuid), {}, {
        preserveScroll: true,
    })
}

const confirmDelete = (supplier) => {
    supplierToDelete.value = supplier
    showDeleteModal.value = true
}

const deleteSupplier = () => {
    deleteForm.delete(route('crm.suppliers.destroy', supplierToDelete.value.uuid), {
        onSuccess: () => {
            showDeleteModal.value = false
            supplierToDelete.value = null
        },
    })
}
</script>