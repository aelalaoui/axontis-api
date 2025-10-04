<template>
    <AxontisDashboardLayout title="Create Device" subtitle="Add a new device to your inventory">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">Create Device</h1>
                    <p class="text-gray-400 mt-1">Add a new device to your inventory</p>
                </div>
                <Link :href="route('crm.devices.index')" class="btn-axontis-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Devices
                </Link>
            </div>

            <!-- Form -->
            <AxontisCard>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Brand -->
                        <div>
                            <label for="brand" class="block text-sm font-medium text-gray-300 mb-2">
                                Brand <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="brand"
                                v-model="form.brand"
                                type="text"
                                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.brand }"
                                placeholder="Enter device brand"
                                required
                            />
                            <div v-if="form.errors.brand" class="mt-1 text-sm text-red-400">
                                {{ form.errors.brand }}
                            </div>
                        </div>

                        <!-- Model -->
                        <div>
                            <label for="model" class="block text-sm font-medium text-gray-300 mb-2">
                                Model <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="model"
                                v-model="form.model"
                                type="text"
                                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.model }"
                                placeholder="Enter device model"
                                required
                            />
                            <div v-if="form.errors.model" class="mt-1 text-sm text-red-400">
                                {{ form.errors.model }}
                            </div>
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-300 mb-2">
                                Category <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="category"
                                    v-model="form.category"
                                    type="text"
                                    list="categories"
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.category }"
                                    placeholder="Enter or select category"
                                    required
                                />
                                <datalist id="categories">
                                    <option v-for="category in categories" :key="category" :value="category">
                                        {{ category }}
                                    </option>
                                </datalist>
                            </div>
                            <div v-if="form.errors.category" class="mt-1 text-sm text-red-400">
                                {{ form.errors.category }}
                            </div>
                        </div>

                        <!-- Stock Quantity -->
                        <div>
                            <label for="stock_qty" class="block text-sm font-medium text-gray-300 mb-2">
                                Stock Quantity <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="stock_qty"
                                v-model="form.stock_qty"
                                type="number"
                                min="0"
                                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.stock_qty }"
                                placeholder="0"
                                required
                            />
                            <div v-if="form.errors.stock_qty" class="mt-1 text-sm text-red-400">
                                {{ form.errors.stock_qty }}
                            </div>
                        </div>

                        <!-- Minimum Stock Level -->
                        <div>
                            <label for="min_stock_level" class="block text-sm font-medium text-gray-300 mb-2">
                                Minimum Stock Level <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="min_stock_level"
                                v-model="form.min_stock_level"
                                type="number"
                                min="0"
                                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.min_stock_level }"
                                placeholder="0"
                                required
                            />
                            <div v-if="form.errors.min_stock_level" class="mt-1 text-sm text-red-400">
                                {{ form.errors.min_stock_level }}
                            </div>
                            <p class="mt-1 text-sm text-gray-400">
                                Alert when stock falls below this level
                            </p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.description }"
                            placeholder="Enter device description (optional)"
                        ></textarea>
                        <div v-if="form.errors.description" class="mt-1 text-sm text-red-400">
                            {{ form.errors.description }}
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-700">
                        <Link
                            :href="route('crm.devices.index')"
                            class="btn-axontis-secondary"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="btn-axontis"
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        >
                            <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                            <i v-else class="fas fa-save mr-2"></i>
                            {{ form.processing ? 'Creating...' : 'Create Device' }}
                        </button>
                    </div>
                </form>
            </AxontisCard>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'

const props = defineProps({
    categories: Array,
})

const form = useForm({
    brand: '',
    model: '',
    category: '',
    description: '',
    stock_qty: 0,
    min_stock_level: 0,
})

const submit = () => {
    form.post(route('crm.devices.store'))
}
</script>
