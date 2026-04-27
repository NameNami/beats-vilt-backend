<script setup>
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />

    <h1>Forgot Password</h1>
    <div v-if="status">
        {{ status }}
    </div>

    <form @submit.prevent="submit">
        <div>
            <label for="email">Email</label>
            <input
                id="email"
                type="email"
                v-model="form.email"
                required
                autofocus
                autocomplete="username"
            />
            <p v-if="form.errors.email">{{ form.errors.email }}</p>
        </div>

        <button :disabled="form.processing">
            Email Password Reset Link
        </button>
    </form>
</template>
