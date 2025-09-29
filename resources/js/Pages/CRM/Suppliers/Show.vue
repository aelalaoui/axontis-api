<template>
    <AxontisDashboardLayout :title="supplier.name" subtitle="Supplier details and related information">
        <div class="max-w-6xl mx-auto">
            <!-- Header Actions -->
            <div class="flex justify-between items-center mb-6">
                <Link :href="route('crm.suppliers.index')" class="btn-axontis-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Suppliers
                </Link>

                <div class="flex items-center gap-3">
                    <!-- Status Badge -->
                    <span
                        :class="[
                            'px-3 py-1 rounded-full text-sm font-medium',
                            supplier.is_active
                                ? 'bg-success-500/20 text-success-300'
                                : 'bg-error-500/20 text-error-300'
                        ]"
                    >
                        {{ supplier.is_active ? 'Active' : 'Inactive' }}
                    </span>

                    <!-- Action Buttons -->
                    <Link :href="route('crm.suppliers.edit', supplier.uuid)" class="btn-axontis-primary">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Supplier
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <AxontisCard title="Basic Information">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Supplier Name</label>
                                <p class="text-white font-medium">{{ supplier.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-1">Supplier Code</label>
                                <p class="text-primary-400 font-mono">{{ supplier.code }}</p>
                            </div>

                            <div v-if="supplier.contact_person">
                                <label class="block text-sm font-medium text-white/70 mb-1">Contact Person</label>
                                <p class="text-white">{{ supplier.contact_person }}</p>
                            </div>

                            <div v-if="supplier.website">
                                <label class="block text-sm font-medium text-white/70 mb-1">Website</label>
                                <a
                                    :href="supplier.website"
                                    target="_blank"
                                    class="text-primary-400 hover:text-primary-300 underline"
                                >
                                    {{ supplier.website }}
                                    <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Contact Information -->
                    <AxontisCard title="Contact Information">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div v-if="supplier.email">
                                <label class="block text-sm font-medium text-white/70 mb-1">Email</label>
                                <a
                                    :href="`mailto:${supplier.email}`"
                                    class="text-primary-400 hover:text-primary-300"
                                >
                                    {{ supplier.email }}
                                </a>
                            </div>

                            <div v-if="supplier.phone">
                                <label class="block text-sm font-medium text-white/70 mb-1">Phone</label>
                                <a
                                    :href="`tel:${supplier.phone}`"
                                    class="text-primary-400 hover:text-primary-300"
                                >
                                    {{ supplier.phone }}
                                </a>
                            </div>
                        </div>

                        <!-- Address -->
                        <div v-if="supplier.full_address" class="mt-6 pt-6 border-t border-primary-500/20">
                            <label class="block text-sm font-medium text-white/70 mb-1">Address</label>
                            <p class="text-white">{{ supplier.full_address }}</p>
                        </div>
                    </AxontisCard>

                    <!-- Notes -->
                    <AxontisCard v-if="supplier.notes" title="Notes">
                        <p class="text-white whitespace-pre-wrap">{{ supplier.notes }}</p>
                    </AxontisCard>

                    <!-- Related Orders -->
                    <AxontisCard title="Related Orders" :subtitle="`${supplier.orders.length} orders found`">
                        <div v-if="supplier.orders.length > 0" class="space-y-3">
                            <div
                                v-for="order in supplier.orders"
                                :key="order.id"
                                class="flex items-center justify-between p-4 rounded-lg bg-dark-800/30 hover:bg-dark-800/50 transition-colors duration-200"
                            >
                                <div>
                                    <p class="font-medium text-white">Order #{{ order.id }}</p>
                                    <p class="text-sm text-white/60">{{ formatDate(order.created_at) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-primary-400">€{{ order.total_amount }}</p>
                                    <span
                                        :class="[
                                            'px-2 py-1 rounded-full text-xs font-medium',
                                            getOrderStatusClass(order.status)
                                        ]"
                                    >
                                        {{ order.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <i class="fas fa-shopping-cart text-3xl text-white/20 mb-3"></i>
                            <p class="text-white/60">No orders found for this supplier</p>
                        </div>
                    </AxontisCard>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <AxontisCard title="Quick Stats">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-white/70">Total Orders</span>
                                <span class="font-semibold text-white">{{ supplier.orders.length }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-white/70">Order Devices</span>
                                <span class="font-semibold text-white">{{ supplier.order_devices.length }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-white/70">Status</span>
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
                            </div>
                        </div>
                    </AxontisCard>

                    <!-- Quick Actions -->
                    <AxontisCard title="Quick Actions">
                        <div class="space-y-3">
                            <Link :href="route('crm.suppliers.edit', supplier.uuid)" class="btn-axontis-primary w-full">
                                <i class="fas fa-edit mr-2"></i>
                                Edit Supplier
                            </Link>

                            <button
                                @click="toggleStatus"
                                :class="[
                                    'btn-axontis-secondary w-full',
                                    supplier.is_active ? 'text-orange-400' : 'text-green-400'
                                ]"
                            >
                                <i :class="supplier.is_active ? 'fas fa-pause' : 'fas fa-play'" class="mr-2"></i>
                                {{ supplier.is_active ? 'Deactivate' : 'Activate' }}
                            </button>

                            <button
                                @click="confirmDelete"
                                class="btn-axontis-secondary w-full text-error-400 hover:text-error-300"
                            >
                                <i class="fas fa-trash mr-2"></i>
                                Delete Supplier
                            </button>
                        </div>
                    </AxontisCard>

                    <!-- Timestamps -->
                    <AxontisCard title="Record Information">
                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-white/70">Created:</span>
                                <p class="text-white">{{ formatDate(supplier.created_at) }}</p>
                            </div>
                            <div>
                                <span class="text-white/70">Last Updated:</span>
                                <p class="text-white">{{ formatDate(supplier.updated_at) }}</p>
                            </div>
                        </div>
                    </AxontisCard>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="showDeleteModal" @close="showDeleteModal = false">
            <template #title>
                Delete Supplier
            </template>

            <template #content>
                Are you sure you want to delete <strong>{{ supplier.name }}</strong>?
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
    supplier: Object,
})

// Reactive state
const showDeleteModal = ref(false)

// Forms
const deleteForm = useForm({})

// Methods
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getOrderStatusClass = (status) => {
    const statusClasses = {
        pending: 'bg-warning-500/20 text-warning-300',
        processing: 'bg-info-500/20 text-info-300',
        completed: 'bg-success-500/20 text-success-300',
        cancelled: 'bg-error-500/20 text-error-300',
    }
    return statusClasses[status] || 'bg-gray-500/20 text-gray-300'
}

const toggleStatus = () => {
    router.patch(route('crm.suppliers.toggle-status', props.supplier.uuid), {}, {
        preserveScroll: true,
    })
}

const confirmDelete = () => {
    showDeleteModal.value = true
}

const deleteSupplier = () => {
    deleteForm.delete(route('crm.suppliers.destroy', props.supplier.uuid), {
        onSuccess: () => {
            router.visit(route('crm.suppliers.index'))
        },
    })
}
</script>