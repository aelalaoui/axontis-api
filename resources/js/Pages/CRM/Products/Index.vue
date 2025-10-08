<template>
    <AxontisDashboardLayout title="Products" subtitle="Manage your products and sub-products">
        <div class="space-y-6">
            <!-- Header with Create Button -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">Products</h1>
                    <p class="text-gray-400 mt-1">Manage your products, sub-products and device configurations</p>
                </div>
                <Link :href="route('crm.products.create')" class="btn-axontis">
                    <i class="fas fa-plus mr-2"></i>
                    New Product
                </Link>
            </div>

            <!-- Filters -->
            <AxontisCard>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Search</label>
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Search products..."
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        />
                    </div>

                    <!-- Product Type Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Product Type</label>
                        <select
                            v-model="typeFilter"
                            @change="handleFilter"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                            <option value="">Parent Products Only</option>
                            <option value="all">All Products</option>
                            <option value="parent">Parent Products</option>
                            <option value="child">Sub-Products</option>
                        </select>
                    </div>

                    <!-- Device Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Device Status</label>
                        <select
                            v-model="deviceStatusFilter"
                            @change="handleFilter"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                            <option value="">All</option>
                            <option value="with_device">With Device</option>
                            <option value="without_device">Without Device</option>
                        </select>
                    </div>
                </div>
            </AxontisCard>

            <!-- Products Table -->
            <AxontisCard>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-300">
                        <thead class="text-xs text-gray-400 uppercase bg-gray-800">
                            <tr>
                                <th scope="col" class="px-6 py-3">Product</th>
                                <th scope="col" class="px-6 py-3">Type</th>
                                <th scope="col" class="px-6 py-3">Property Name</th>
                                <th scope="col" class="px-6 py-3">Default Value</th>
                                <th scope="col" class="px-6 py-3">Device</th>
                                <th scope="col" class="px-6 py-3">Sub-Products</th>
                                <th scope="col" class="px-6 py-3">Formulas</th>
                                <th scope="col" class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="product in products.data"
                                :key="product.id"
                                class="bg-gray-900 border-b border-gray-700 hover:bg-gray-800 transition-colors duration-200"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-box text-white text-sm"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-white">{{ product.name }}</div>
                                            <div v-if="product.parent" class="text-xs text-gray-400">
                                                Parent: {{ product.parent.name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="{
                                            'bg-blue-900 text-blue-300': !product.id_parent,
                                            'bg-green-900 text-green-300': product.id_parent
                                        }"
                                        class="px-2 py-1 text-xs font-medium rounded-full"
                                    >
                                        {{ product.id_parent ? 'Sub-Product' : 'Parent' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-white">
                                        {{ product.property_name || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-white">
                                        {{ product.default_value || '-' }}
                                    </div>
                                    <div v-if="product.default_value" class="text-xs text-gray-400">
                                        Default set
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="product.device" class="flex items-center space-x-2">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                        <span class="text-sm text-gray-300">{{ product.device.full_name }}</span>
                                    </div>
                                    <div v-else class="flex items-center space-x-2">
                                        <div class="w-2 h-2 bg-gray-500 rounded-full"></div>
                                        <span class="text-sm text-gray-500">No device</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-if="!product.id_parent"
                                        class="px-2 py-1 text-xs font-medium bg-gray-800 text-gray-300 rounded-full"
                                    >
                                        {{ product.children_count || 0 }} sub-products
                                    </span>
                                    <span v-else class="text-gray-500 text-sm">-</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="text-xs text-gray-400">
                                            Caution: <span class="text-gray-300">{{ product.caution_price ? `€${product.caution_price.toFixed(2)}` : 'Not set' }}</span>
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            Subscription: <span class="text-gray-300">{{ product.subscription_price ? `€${product.subscription_price.toFixed(2)}` : 'Not set' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <Link
                                            :href="route('crm.products.show', product.id)"
                                            class="text-blue-400 hover:text-blue-300 transition-colors duration-200"
                                            title="View"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </Link>
                                        <Link
                                            :href="route('crm.products.edit', product.id)"
                                            class="text-yellow-400 hover:text-yellow-300 transition-colors duration-200"
                                            title="Edit"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button
                                            @click="confirmDelete(product)"
                                            class="text-red-400 hover:text-red-300 transition-colors duration-200"
                                            title="Delete"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="products.links" class="flex justify-between items-center mt-6 pt-6 border-t border-gray-700">
                    <div class="text-sm text-gray-400">
                        Showing {{ products.from }} to {{ products.to }} of {{ products.total }} results
                    </div>
                    <div class="flex space-x-1">
                        <Link
                            v-for="link in products.links"
                            :key="link.label"
                            :href="link.url"
                            v-html="link.label"
                            :class="{
                                'bg-primary-600 text-white': link.active,
                                'bg-gray-800 text-gray-300 hover:bg-gray-700': !link.active && link.url,
                                'bg-gray-800 text-gray-500 cursor-not-allowed': !link.url
                            }"
                            class="px-3 py-1 text-sm rounded transition-colors duration-200"
                        />
                    </div>
                </div>
            </AxontisCard>
        </div>

        <!-- Delete Confirmation Modal -->
        <AxontisModal :show="showDeleteModal" @close="showDeleteModal = false">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-red-900 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-triangle text-red-300 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-white">Delete Product</h3>
                        <p class="text-gray-400">This action cannot be undone.</p>
                    </div>
                </div>

                <p class="text-gray-300 mb-6">
                    Are you sure you want to delete "<strong>{{ productToDelete?.name }}</strong>"?
                    <span v-if="!productToDelete?.id_parent && productToDelete?.children_count > 0" class="text-yellow-400">
                        This will also delete {{ productToDelete.children_count }} sub-products.
                    </span>
                </p>

                <div class="flex justify-end space-x-3">
                    <button
                        @click="showDeleteModal = false"
                        class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition-colors duration-200"
                    >
                        Cancel
                    </button>
                    <button
                        @click="deleteProduct"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </AxontisModal>
    </AxontisDashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import AxontisModal from '@/Components/AxontisModal.vue'

const props = defineProps({
    products: Object,
    filters: Object
})

// Reactive data
const searchQuery = ref(props.filters.search || '')
const typeFilter = ref(props.filters.type || '')
const deviceStatusFilter = ref(props.filters.device_status || '')
const showDeleteModal = ref(false)
const productToDelete = ref(null)

// Methods
const handleSearch = () => {
    router.get(route('crm.products.index'), {
        search: searchQuery.value,
        type: typeFilter.value,
        device_status: deviceStatusFilter.value
    }, {
        preserveState: true,
        replace: true
    })
}

const handleFilter = () => {
    router.get(route('crm.products.index'), {
        search: searchQuery.value,
        type: typeFilter.value,
        device_status: deviceStatusFilter.value
    }, {
        preserveState: true,
        replace: true
    })
}

const confirmDelete = (product) => {
    productToDelete.value = product
    showDeleteModal.value = true
}

const deleteProduct = () => {
    router.delete(route('crm.products.destroy', productToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false
            productToDelete.value = null
        }
    })
}
</script>
