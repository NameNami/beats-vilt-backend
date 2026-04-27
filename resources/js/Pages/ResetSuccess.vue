<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Password Reset Successful" />

    <div class="flex justify-center items-center min-h-screen bg-[#b3cec9]">
        <p class="absolute top-5 left-5 font-bold text-[#d6704d] text-[24px]">BEATS</p>
        <div class="min-h-screen bg-[#b6cdce] flex items-center justify-center w-full">
            <div class="relative z-10 bg-white p-10 rounded-lg shadow-xl border border-gray-100 w-full max-w-md">
                <h1 class="text-3xl font-bold text-gray-900 text-center mb-6">Success!</h1>

                <p class="text-gray-600 text-center mb-8">
                    Your password has been reset successfully. You can now log in with your new password.
                </p>

                <Link :href="route('login')" class="block w-2/3 max-w-[200px] bg-orange-500 text-white text-center font-semibold py-3 px-6 rounded-lg shadow hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 mx-auto">
                    Log In
                </Link>
            </div>
        </div>
    </div>
    <img :src="'/images/Background Visual.png'" alt="island" class="absolute bottom-0 left-0 w-full h-auto">
</template>

<style scoped>
</style>
