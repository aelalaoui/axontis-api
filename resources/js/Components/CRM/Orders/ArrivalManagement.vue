<template>
    <div class="space-y-6">
        <!-- Arrival Management Header -->
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-white">Arrival Management</h3>
                <p class="text-gray-400 text-sm mt-1">Process arrivals for ordered items</p>
            </div>
            <button
                v-if="canProcessArrivals"
                @click="showArrivalForm = true"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center"
            >
                <i class="fas fa-truck-loading mr-2"></i>
                Process Arrival
            </button>
        </div>

        <!-- Order Summary for Arrivals -->
        <div v-if="canProcessArrivals" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center">
                    <div class="bg-blue-100 rounded-lg p-2 mr-3">
                        <i class="fas fa-box text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-white font-medium">{{ totalDevices }}</p>
                        <p class="text-gray-400 text-sm">Total Devices</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center">
                    <div class="bg-yellow-100 rounded-lg p-2 mr-3">
                        <i class="fas fa-clock text-yellow-600"></i>
                    </div>
                    <div>
                        <p class="text-white font-medium">{{ totalPendingQty }}</p>
                        <p class="text-gray-400 text-sm">Items Pending</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center">
                    <div class="bg-green-100 rounded-lg p-2 mr-3">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-white font-medium">{{ totalReceivedQty }}</p>
                        <p class="text-gray-400 text-sm">Items Received</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Device Status Table -->
        <div v-if="devices.length > 0" class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-700">
                <h4 class="text-white font-medium">Device Status</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Device</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Ordered</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Received</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Pending</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Progress</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <tr v-for="device in devices" :key="device.uuid" class="hover:bg-gray-750">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-white font-medium">{{ device.brand }} - {{ device.model }}</div>
                                    <div class="text-gray-400 text-sm">{{ device.category }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-white">{{ device.pivot.qty_ordered }}</td>
                            <td class="px-6 py-4 text-green-400 font-medium">{{ device.pivot.qty_received }}</td>
                            <td class="px-6 py-4 text-yellow-400 font-medium">{{ device.pivot.qty_ordered - device.pivot.qty_received }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-700 rounded-full h-2">
                                        <div
                                            class="h-2 rounded-full"
                                            :class="getProgressColor(device.pivot.qty_received, device.pivot.qty_ordered)"
                                            :style="{ width: getProgressPercentage(device.pivot.qty_received, device.pivot.qty_ordered) + '%' }"
                                        ></div>
                                    </div>
                                    <span class="ml-2 text-gray-400 text-sm">
                                        {{ Math.round(getProgressPercentage(device.pivot.qty_received, device.pivot.qty_ordered)) }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="getDeviceStatusClass(device.pivot)"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{ getDeviceStatusLabel(device.pivot) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Arrivals History -->
        <div v-if="arrivals.length > 0" class="bg-gray-800 rounded-lg border border-gray-700">
            <div class="px-6 py-4 border-b border-gray-700">
                <h4 class="text-white font-medium">Arrivals History</h4>
            </div>
            <div class="p-6 space-y-4">
                <div
                    v-for="arrival in arrivals"
                    :key="arrival.uuid"
                    class="bg-gray-900 rounded-lg p-4 border border-gray-600"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <h5 class="text-white font-medium">{{ getDeviceName(arrival.device_id) }}</h5>
                                <span
                                    :class="getArrivalStatusClass(arrival.status)"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{ arrival.status }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-400">Quantity</span>
                                    <p class="text-white font-medium">{{ arrival.qty }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-400">Arrival Date</span>
                                    <p class="text-white">{{ formatDate(arrival.arrival_date) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-400">Received By</span>
                                    <p class="text-white">{{ getCurrentUser() }}</p>
                                </div>
                                <div v-if="arrival.invoice_number">
                                    <span class="text-gray-400">Invoice</span>
                                    <p class="text-white">{{ arrival.invoice_number }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-400">Total Value</span>
                                    <p class="text-white font-medium">{{ formatCurrency(arrival.total_value) }}</p>
                                </div>
                            </div>
                            <div v-if="arrival.notes" class="mt-3 p-3 bg-gray-800 rounded-lg">
                                <span class="text-gray-400 text-sm">Notes: </span>
                                <span class="text-gray-300 text-sm">{{ arrival.notes }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Arrival Form Modal -->
        <Teleport to="body">
            <div v-if="showArrivalForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-gray-800 rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-white">Process Arrival</h3>
                        <button @click="closeArrivalForm" class="text-gray-400 hover:text-white">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitArrival">
                        <div class="space-y-4">
                            <div
                                v-for="(arrival, index) in arrivalForm.arrivals"
                                :key="index"
                                class="bg-gray-900 rounded-lg p-4 border border-gray-700"
                            >
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-white font-medium">Arrival {{ index + 1 }}</h4>
                                    <button
                                        v-if="arrivalForm.arrivals.length > 1"
                                        @click="removeArrival(index)"
                                        type="button"
                                        class="text-red-400 hover:text-red-300"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-400 mb-2">Device</label>
                                        <select
                                            v-model="arrival.device_id"
                                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            required
                                        >
                                            <option value="">Select Device</option>
                                            <option
                                                v-for="device in availableDevices"
                                                :key="device.uuid"
                                                :value="device.uuid"
                                            >
                                                {{ device.brand }} - {{ device.model }}
                                                (Pending: {{ device.pivot.qty_ordered - device.pivot.qty_received }})
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-400 mb-2">Quantity</label>
                                        <input
                                            v-model.number="arrival.qty"
                                            type="number"
                                            min="1"
                                            :max="getMaxQuantity(arrival.device_id)"
                                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-400 mb-2">Arrival Date</label>
                                        <input
                                            v-model="arrival.arrival_date"
                                            type="date"
                                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-400 mb-2">Invoice Number</label>
                                        <input
                                            v-model="arrival.invoice_number"
                                            type="text"
                                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Optional"
                                        />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-400 mb-2">Notes</label>
                                        <textarea
                                            v-model="arrival.notes"
                                            rows="3"
                                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Optional notes..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <button
                                @click="addArrival"
                                type="button"
                                class="w-full border-2 border-dashed border-gray-600 rounded-lg py-4 text-gray-400 hover:text-white hover:border-gray-500 transition-colors"
                            >
                                <i class="fas fa-plus mr-2"></i>
                                Add Another Arrival
                            </button>
                        </div>

                        <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-gray-700">
                            <button
                                @click="closeArrivalForm"
                                type="button"
                                class="px-4 py-2 text-gray-400 hover:text-white transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="processing"
                                class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-800 disabled:cursor-not-allowed text-white px-6 py-2 rounded-lg transition-colors"
                            >
                                <i v-if="processing" class="fas fa-spinner fa-spin mr-2"></i>
                                {{ processing ? 'Processing...' : 'Process Arrival' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script>
import { router } from '@inertiajs/vue3'

export default {
    name: 'ArrivalManagement',
    props: {
        order: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            showArrivalForm: false,
            processing: false,
            arrivalForm: {
                arrivals: [
                    {
                        device_id: '',
                        qty: 1,
                        arrival_date: new Date().toISOString().split('T')[0],
                        invoice_number: '',
                        notes: ''
                    }
                ]
            }
        }
    },
    computed: {
        canProcessArrivals() {
            // Permettre le traitement des arrivées pour les commandes 'ordered' et 'partially_received'
            return ['ordered', 'partially_received'].includes(this.order.status)
        },
        devices() {
            return this.order.devices || []
        },
        arrivals() {
            return this.order.arrivals || []
        },
        availableDevices() {
            return this.devices.filter(device => {
                const pending = device.pivot.qty_ordered - device.pivot.qty_received
                return pending > 0
            })
        },
        totalDevices() {
            return this.devices.length
        },
        totalPendingQty() {
            return this.devices.reduce((total, device) => {
                return total + (device.pivot.qty_ordered - device.pivot.qty_received)
            }, 0)
        },
        totalReceivedQty() {
            return this.devices.reduce((total, device) => {
                return total + device.pivot.qty_received
            }, 0)
        }
    },
    methods: {
        addArrival() {
            this.arrivalForm.arrivals.push({
                device_id: '',
                qty: 1,
                arrival_date: new Date().toISOString().split('T')[0],
                invoice_number: '',
                notes: ''
            })
        },
        removeArrival(index) {
            this.arrivalForm.arrivals.splice(index, 1)
        },
        getMaxQuantity(deviceId) {
            if (!deviceId) return 1
            const device = this.devices.find(d => d.uuid === deviceId)
            return device ? device.pivot.qty_ordered - device.pivot.qty_received : 1
        },
        submitArrival() {
            this.processing = true

            router.post(route('crm.orders.arrivals.process', this.order.uuid), {
                arrivals: this.arrivalForm.arrivals
            }, {
                onSuccess: () => {
                    this.closeArrivalForm()
                },
                onError: (errors) => {
                    console.error('Arrival processing failed:', errors)
                },
                onFinish: () => {
                    this.processing = false
                }
            })
        },
        closeArrivalForm() {
            this.showArrivalForm = false
            this.arrivalForm.arrivals = [
                {
                    device_id: '',
                    qty: 1,
                    arrival_date: new Date().toISOString().split('T')[0],
                    invoice_number: '',
                    notes: ''
                }
            ]
        },
        getProgressPercentage(received, ordered) {
            return ordered > 0 ? (received / ordered) * 100 : 0
        },
        getProgressColor(received, ordered) {
            const percentage = this.getProgressPercentage(received, ordered)
            if (percentage === 100) return 'bg-green-500'
            if (percentage >= 50) return 'bg-yellow-500'
            return 'bg-blue-500'
        },
        getDeviceStatusClass(pivot) {
            if (pivot.qty_received >= pivot.qty_ordered) return 'bg-green-100 text-green-800'
            if (pivot.qty_received > 0) return 'bg-yellow-100 text-yellow-800'
            return 'bg-gray-100 text-gray-800'
        },
        getDeviceStatusLabel(pivot) {
            if (pivot.qty_received >= pivot.qty_ordered) return 'Completed'
            if (pivot.qty_received > 0) return 'Partial'
            return 'Pending'
        },
        getArrivalStatusClass(status) {
            const classes = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'received': 'bg-blue-100 text-blue-800',
                'verified': 'bg-purple-100 text-purple-800',
                'stocked': 'bg-green-100 text-green-800'
            }
            return classes[status] || 'bg-gray-100 text-gray-800'
        },
        formatDate(date) {
            if (!date) return 'N/A'
            return new Date(date).toLocaleDateString()
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(amount || 0)
        },
        getDeviceName(deviceId) {
            // Trouver le device dans la liste des devices de la commande
            const device = this.devices.find(d => d.uuid === deviceId)
            if (device) {
                return `${device.brand} - ${device.model}`
            }
            return 'Unknown Device'
        },
        getCurrentUser() {
            // Accéder à l'utilisateur connecté via Inertia
            return this.$page.props.auth?.user?.name || 'Unknown User'
        }
    }
}
</script>
