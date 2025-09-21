<template>
    <AxontisDashboardLayout title="Edit Supplier" :subtitle="`Update ${supplier.name} information`">
        <div class="max-w-4xl mx-auto">
            <!-- Back Button -->
            <div class="mb-6">
                <Link :href="route('crm.suppliers.index')" class="btn-axontis-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Suppliers
                </Link>
            </div>

            <!-- Form Card -->
            <AxontisCard title="Supplier Information" subtitle="Update the supplier details">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <InputLabel for="name" value="Supplier Name *" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <!-- Code -->
                        <div>
                            <InputLabel for="code" value="Supplier Code *" />
                            <TextInput
                                id="code"
                                v-model="form.code"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                placeholder="e.g., SUP001"
                            />
                            <InputError class="mt-2" :message="form.errors.code" />
                        </div>

                        <!-- Email -->
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <!-- Phone -->
                        <div>
                            <InputLabel for="phone" value="Phone" />
                            <TextInput
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError class="mt-2" :message="form.errors.phone" />
                        </div>

                        <!-- Contact Person -->
                        <div>
                            <InputLabel for="contact_person" value="Contact Person" />
                            <TextInput
                                id="contact_person"
                                v-model="form.contact_person"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError class="mt-2" :message="form.errors.contact_person" />
                        </div>

                        <!-- Website -->
                        <div>
                            <InputLabel for="website" value="Website" />
                            <TextInput
                                id="website"
                                v-model="form.website"
                                type="url"
                                class="mt-1 block w-full"
                                placeholder="https://example.com"
                            />
                            <InputError class="mt-2" :message="form.errors.website" />
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="border-t border-primary-500/20 pt-6">
                        <h3 class="text-lg font-medium text-white mb-4">Address Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Address -->
                            <div class="md:col-span-2">
                                <InputLabel for="address" value="Address" />
                                <TextInput
                                    id="address"
                                    v-model="form.address"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.address" />
                            </div>

                            <!-- City -->
                            <div>
                                <InputLabel for="city" value="City" />
                                <TextInput
                                    id="city"
                                    v-model="form.city"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.city" />
                            </div>

                            <!-- Postal Code -->
                            <div>
                                <InputLabel for="postal_code" value="Postal Code" />
                                <TextInput
                                    id="postal_code"
                                    v-model="form.postal_code"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.postal_code" />
                            </div>

                            <!-- Country -->
                            <div class="md:col-span-2">
                                <InputLabel for="country" value="Country" />
                                <TextInput
                                    id="country"
                                    v-model="form.country"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.country" />
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="border-t border-primary-500/20 pt-6">
                        <h3 class="text-lg font-medium text-white mb-4">Additional Information</h3>
                        
                        <!-- Notes -->
                        <div>
                            <InputLabel for="notes" value="Notes" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="axontis-input mt-1 block w-full"
                                placeholder="Any additional notes about this supplier..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>

                        <!-- Status -->
                        <div class="mt-6">
                            <label class="flex items-center">
                                <Checkbox
                                    v-model:checked="form.is_active"
                                    name="is_active"
                                />
                                <span class="ml-2 text-sm text-white">Active Supplier</span>
                            </label>
                            <InputError class="mt-2" :message="form.errors.is_active" />
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-primary-500/20">
                        <Link :href="route('crm.suppliers.index')" class="btn-axontis-secondary">
                            Cancel
                        </Link>
                        <PrimaryButton
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            <i class="fas fa-save mr-2"></i>
                            Update Supplier
                        </PrimaryButton>
                    </div>
                </form>
            </AxontisCard>
        </div>
    </AxontisDashboardLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import AxontisCard from '@/Components/AxontisCard.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import Checkbox from '@/Components/Checkbox.vue'

const props = defineProps({
    supplier: Object,
})

// Form
const form = useForm({
    name: props.supplier.name,
    code: props.supplier.code,
    email: props.supplier.email || '',
    phone: props.supplier.phone || '',
    address: props.supplier.address || '',
    city: props.supplier.city || '',
    postal_code: props.supplier.postal_code || '',
    country: props.supplier.country || '',
    contact_person: props.supplier.contact_person || '',
    website: props.supplier.website || '',
    notes: props.supplier.notes || '',
    is_active: props.supplier.is_active,
})

// Methods
const submit = () => {
    form.put(route('crm.suppliers.update', props.supplier.id))
}
</script>