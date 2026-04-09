<script setup>
import {Head, useForm, usePage} from '@inertiajs/vue3'
const form = useForm({
    email: '',
    password: '',
})

const submitLogin = () => {
    form.post('/login', {onFinish: () => form.reset('password')})
}
</script>

<template>
    <Head>
        <title>Login</title>
    </Head>
    <form @submit.prevent="submitLogin">
        <label for="email">Email</label>
        <input id="email" type="email" v-model="form.email" placeholder="lecturer@beats.test" required autofocus></input>
        <p v-if="form.errors.email">{{form.errors.email}}</p>
        <label for="password">Password</label>
        <input id="password" type="password" v-model="form.password" placeholder="password" required></input>

        <button type="submit" :disabled="form.processing">
            <span v-if="form.processing">Signing In...</span>
            <span v-else>Sign In</span>
        </button>
    </form>
</template>

<style scoped>

</style>
