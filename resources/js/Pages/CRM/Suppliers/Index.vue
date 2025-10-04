<template>
    <AxontisDashboardLayout title="Suppliers" subtitle="Manage your suppliers and vendor relationships">
        <div class="space-y-6">
            <!-- Header with Create Button -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">Suppliers</h1>
                    <p class="text-gray-400 mt-1">Manage your suppliers and vendor relationships</p>
                </div>
                <Link :href="route('crm.suppliers.create')" class="btn-axontis">
                    <i class="fas fa-plus mr-2"></i>
                    New Supplier
                </Link>
            </div>

            <!-- Filters -->
            <AxontisCard>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Search</label>
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Search suppliers..."
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                        <select
                            v-model="statusFilter"
                            @change="handleFilter"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </AxontisCard>

            <!-- Suppliers Table -->
            <AxontisCard>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="border-b border-gray-700">
                            <th class="text-left py-3 px-4 font-medium text-gray-300">
                                <button @click="sort('name')" class="flex items-center space-x-1 hover:text-white">
                                    <span>Name</span>
                                    <i class="fas fa-sort text-xs"></i>
                                </button>
                            </th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">
                                <button @click="sort('code')" class="flex items-center space-x-1 hover:text-white">
                                    <span>Code</span>
                                    <i class="fas fa-sort text-xs"></i>
                                </button>
                            </th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Contact</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Location</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Status</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-300">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            v-for="supplier in suppliers.data"
                            :key="supplier.id"
                            class="border-b border-gray-800 hover:bg-gray-800/50"
                        >
                            <td class="py-3 px-4">
                                <div>
                                    <div class="font-medium text-white">{{ supplier.name }}</div>
                                    <div v-if="supplier.contact_person" class="text-sm text-gray-400">
                                        {{ supplier.contact_person }}
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-mono text-sm text-primary-400">{{ supplier.code }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-sm">
                                    <div v-if="supplier.email" class="text-white">{{ supplier.email }}</div>
                                    <div v-if="supplier.phone" class="text-gray-400">{{ supplier.phone }}</div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-sm text-gray-300">
                                    {{ supplier.full_address || 'No address' }}
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                    <span
                                        :class="[
                                            'px-2 py-1 rounded-full text-xs font-medium',
                                            supplier.is_active
                                                ? 'bg-green-600 text-green-100'
                                                : 'bg-red-600 text-red-100'
                                        ]"
                                    >
                                        {{ supplier.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-2">
                                    <!-- View Button -->
                                    <Link
                                        :href="route('crm.suppliers.show', supplier.uuid)"
                                        class="text-blue-400 hover:text-blue-300"
                                        title="View Supplier"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </Link>

                                    <!-- Edit Button -->
                                    <Link
                                        :href="route('crm.suppliers.edit', supplier.uuid)"
                                        class="text-yellow-400 hover:text-yellow-300"
                                        title="Edit Supplier"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </Link>

                                    <!-- Toggle Status Button -->
                                    <button
                                        @click="toggleStatus(supplier)"
                                        :class="[
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
                                        class="text-red-400 hover:text-red-300"
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
                        <i class="fas fa-truck text-4xl text-gray-600 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-400 mb-2">No suppliers found</h3>
                        <p class="text-gray-500 mb-4">
                            {{ searchQuery || statusFilter ? 'Try adjusting your filters' : 'Get started by adding your first supplier' }}
                        </p>
                        <Link
                            v-if="!searchQuery && !statusFilter"
                            :href="route('crm.suppliers.create')"
                            class="btn-axontis"
                        >
                            <i class="fas fa-plus mr-2"></i>
                            Add First Supplier
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="suppliers.data.length > 0" class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-400">
                        Showing {{ suppliers.from }} to {{ suppliers.to }} of {{ suppliers.total }} results
                    </div>
                    <div class="flex items-center space-x-2">
                        <Link
                            v-for="link in suppliers.links"
                            :key="link.label"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-2 text-sm rounded-lg',
                                link.active
                                    ? 'bg-primary-600 text-white'
                                    : link.url
                                    ? 'bg-gray-800 text-gray-300 hover:bg-gray-700'
                                    : 'bg-gray-900 text-gray-600 cursor-not-allowed'
                            ]"
                        />
                    </div>
                </div>
            </AxontisCard>
        </div>

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
import { ref } from 'vue'
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
