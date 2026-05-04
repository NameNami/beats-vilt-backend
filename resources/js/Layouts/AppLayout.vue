<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex h-screen bg-gray-50">

        <aside class="w-64 bg-white border-r">
            <div class="p-4">
                <h1 class="text-xl font-bold text-red-800">BEATS</h1>
            </div>

            <nav class="mt-4">
                <Link href="/dashboard" class="block p-4 hover:bg-gray-100">Dashboard</Link>
                <Link href="/attendance" class="block p-4 hover:bg-gray-100">Attendance</Link>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col">
            <main class="flex-1 p-6 overflow-y-auto">

                <slot />

            </main>
        </div>

    </div>
</template>

<style scoped>

</style>
