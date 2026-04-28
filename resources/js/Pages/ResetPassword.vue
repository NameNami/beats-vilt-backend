<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Password" />

    <div class="flex justify-center items-center min-h-screen bg-[#b3cec9]">
        <p class="absolute top-5 left-5 font-bold text-[#d6704d] text-[24px]">BEATS</p>
        <div class="min-h-screen bg-[#b6cdce] flex items-center justify-center w-full">
            <div class="relative z-10 bg-white p-10 rounded-lg shadow-xl border border-gray-100 w-full max-w-md">
                <h1 class="text-3xl font-bold text-gray-900 text-center mb-10">Reset Password</h1>

                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <input
                            class="block w-full border-1 border-gray-200 rounded-lg px-4 py-3 placeholder-gray-400"
                            id="email"
                            type="email"
                            v-model="form.email"
                            placeholder="Email"
                            required
                            autofocus
                            autocomplete="username"
                            disabled
                        />
                        <p class="text-red-500 mt-1" v-if="form.errors.email">{{ form.errors.email }}</p>
                    </div>

                    <div class="mb-4">
                        <input
                            class="block w-full border-1 border-gray-200 rounded-lg px-4 py-3 placeholder-gray-400"
                            id="password"
                            type="password"
                            v-model="form.password"
                            placeholder="New Password"
                            required
                            autocomplete="new-password"
                        />
                        <p class="text-red-500 mt-1" v-if="form.errors.password">{{ form.errors.password }}</p>
                    </div>

                    <div class="mb-4">
                        <input
                            class="block w-full border-1 border-gray-200 rounded-lg px-4 py-3 placeholder-gray-400"
                            id="password_confirmation"
                            type="password"
                            v-model="form.password_confirmation"
                            placeholder="Confirm Password"
                            required
                            autocomplete="new-password"
                        />
                        <p class="text-red-500 mt-1" v-if="form.errors.password_confirmation">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <button :disabled="form.processing" class="block mt-6 w-2/3 max-w-[200px] bg-orange-500 text-white font-semibold py-3 px-6 rounded-lg shadow hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 disabled:opacity-50 mx-auto">
                        Reset Password
                    </button>
                </form>
            </div>
        </div>
    </div>
    <img :src="'/images/Background Visual.png'" alt="island" class="absolute bottom-0 left-0 w-full h-auto">
</template>
