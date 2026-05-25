<script setup>
import {Head, Link, useForm} from '@inertiajs/vue3'

defineProps({
    status: {
        type: String,
    },
});

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

    <div class="flex justify-center items-center min-h-screen bg-[#b3cec9]">

        <p class="absolute top-5 left-5 font-bold text-[#d6704d] text-[24px]">BEATS</p>
        <div class="min-h-screen bg-[#b6cdce] flex items-center justify-center w-full">
            <div class="realtive z-10 bg-white p-10 rounded-2xl shadow-xl border border-gray-100 w-full max-w-md">
                <h1 class="text-3xl font-bold text-gray-900 text-center mb-10">Welcome Back</h1>

                <div v-if="status" class="mb-4 text-center text-sm text-green-600">{{ status }}</div>

                <form @submit.prevent="submitLogin" class="space-y-6">
                    <div>
                        <input class="block w-full border-1 border-gray-200 rounded-lg px-4 py-3 placeholder-gray-400" id="email" type="email" v-model="form.email" placeholder="email" required autofocus />
                        <p v-if="form.errors.email" class="text-sm text-red-600 mt-1">{{form.errors.email}}</p>
                    </div>

                    <div>
                        <input class="block w-full border border-gray-200 rounded-lg px-4 py-3 placeholder-gray-400 focus:ring-orange-500 focus:border-orange-500" id="password" type="password" v-model="form.password" placeholder="password" required />
                        <p v-if="form.errors.password" class="text-sm text-red-600 mt-1">{{form.errors.password}}</p>
                    </div>

                    <div class="flex justify-end mt-[-1rem] mb-6">
                        <Link :href="route('password.request')" class="text-sm text-orange-500 font-medium hover:text-orange-600">
                            forgot password
                        </Link>
                    </div>

                    <div class="flex justify-center mt-12 mb-6">
                        <button type="submit" :disabled="form.processing" class="w-2/3 max-w-[200px] bg-orange-500 text-white font-semibold py-3 px-6 rounded-lg shadow hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 disabled:opacity-50 mx-auto cursor-pointer">
                            <span v-if="form.processing">Signing In...</span>
                            <span v-else>Sign in</span>
                        </button>
                    </div>

                    <p class="text-center text-sm mt-10">
                        <span class="font-semibold text-gray-900">having problem signing in?</span>
                        <a href="mailto:support@beats.com" class="text-orange-500 font-medium hover:text-orange-600"> contact support</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
    <img :src="'/images/Background Visual.png'" alt="island" class="absolute bottom-0 left-0 w-full h-auto">
    // TODO: make sure convert semua png jadi webp nanti
</template>

<style scoped>

</style>
