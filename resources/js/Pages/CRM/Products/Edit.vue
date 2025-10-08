<template>
    <AxontisDashboardLayout title="Edit Product" subtitle="Modify product configuration and sub-products">
        <Create :product="product" :devices="devices" />
    </AxontisDashboardLayout>
</template>

<script setup>
import AxontisDashboardLayout from '@/Layouts/AxontisDashboardLayout.vue'
import Create from './Create.vue'

const props = defineProps({
    product: Object,
    devices: Array
})
</script>
