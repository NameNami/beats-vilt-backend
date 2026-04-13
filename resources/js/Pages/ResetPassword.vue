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

        <div>
            <label for="password">Password</label>
            <input
                id="password"
                type="password"
                v-model="form.password"
                required
                autocomplete="new-password"
            />
            <p v-if="form.errors.password">{{ form.errors.password }}</p>
        </div>

        <div>
            <label for="password_confirmation">Confirm Password</label>
            <input
                id="password_confirmation"
                type="password"
                v-model="form.password_confirmation"
                required
                autocomplete="new-password"
            />
            <p v-if="form.errors.password_confirmation">{{ form.errors.password_confirmation }}</p>
        </div>

        <button :disabled="form.processing">
            Reset Password
        </button>
    </form>
</template>
