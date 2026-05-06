<script setup>
// Import the layout we just created
import AppLayout from '../Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head>
        <title>Dashboard</title>
    </Head>
    <AppLayout>

        <div>
            <h2 class="text-2xl font-bold">Attendance Module</h2>
            <p>Your QR code scanner will go here.</p>
        </div>

    </AppLayout>
</template>

<style scoped>

</style>
