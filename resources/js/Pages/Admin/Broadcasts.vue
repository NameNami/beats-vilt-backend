<template>
    <Head title="Broadcast Announcements" />
    <AdminLayout>
        <div class="max-w-6xl mx-auto space-y-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-semibold mb-2 text-gray-900">Broadcast Announcements</h1>
                    <p class="text-slate-600 text-sm font-medium">Send urgent push notifications to all users, specific roles, or faculties.</p>
                </div>
            </div>

            <div v-if="$page.props.flash?.success" class="p-4 bg-teal-50 text-teal-800 rounded-xl border border-teal-100 font-bold flex items-center gap-3">
                <CheckCircle class="w-5 h-5 text-teal-600" />
                {{ $page.props.flash.success }}
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Composer Card -->
                <div class="lg:col-span-1">
                    <form @submit.prevent="submitBroadcast" class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-100 bg-orange-50">
                            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <Megaphone class="w-5 h-5 text-orange-600" />
                                New Broadcast
                            </h2>
                        </div>
                        <div class="p-6 space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Target Audience</label>
                                <select v-model="form.target" class="w-full bg-[#f8fafc] border-slate-200 rounded-xl focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5 font-medium cursor-pointer">
                                    <option value="all_users">Everyone (All Users)</option>
                                    <option value="all_students">All Students</option>
                                    <option value="all_lecturers">All Lecturers</option>
                                    <option value="specific_faculty">Specific Faculty</option>
                                </select>
                                <p v-if="form.errors.target" class="text-red-500 text-xs mt-1">{{ form.errors.target }}</p>
                            </div>

                            <div v-if="form.target === 'specific_faculty'">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Select Faculty</label>
                                <select v-model="form.faculty" class="w-full bg-[#f8fafc] border-slate-200 rounded-xl focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5 font-medium cursor-pointer">
                                    <option value="">Choose...</option>
                                    <option v-for="fac in faculties" :key="fac" :value="fac">{{ fac }}</option>
                                </select>
                                <p v-if="form.errors.faculty" class="text-red-500 text-xs mt-1">{{ form.errors.faculty }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Headline</label>
                                <input v-model="form.title" type="text" class="w-full bg-[#f8fafc] border-slate-200 rounded-xl focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5" placeholder="e.g. Campus Closure">
                                <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Message Body</label>
                                <textarea v-model="form.body" rows="4" class="w-full bg-[#f8fafc] border-slate-200 rounded-xl focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5 resize-none" placeholder="Type your announcement here..."></textarea>
                                <p v-if="form.errors.body" class="text-red-500 text-xs mt-1">{{ form.errors.body }}</p>
                            </div>

                            <div class="pt-2">
                                <button type="submit" :disabled="form.processing" class="w-full bg-orange-400 hover:bg-orange-500 text-white px-6 py-3 rounded-xl font-bold transition disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer">
                                    <Send class="w-4 h-4" />
                                    {{ form.processing ? 'Sending...' : 'Send Broadcast Now' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- History Log -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm h-fit">
                        <div class="px-6 py-4 border-b border-slate-100 bg-orange-50 flex justify-between items-center">
                            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <History class="w-5 h-5 text-slate-400" />
                                History
                            </h2>
                        </div>

                        <div class="divide-y divide-slate-100 max-h-[600px] overflow-y-auto custom-scrollbar">
                            <div v-for="log in pastBroadcasts" :key="log.created_at" class="p-4 hover:bg-slate-50/50 transition">
                                <div class="flex justify-between items-start mb-1 gap-2">
                                    <h3 class="text-sm font-bold text-slate-900 truncate flex-1">{{ log.title }}</h3>
                                    <span class="text-[10px] font-bold text-slate-400 whitespace-nowrap">{{ new Date(log.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) }}</span>
                                </div>
                                <p class="text-xs text-slate-600 mb-3 line-clamp-2 leading-relaxed">{{ log.body }}</p>
                                <div class="flex items-center gap-1.5 text-[10px] font-bold text-teal-600 bg-teal-50 w-fit px-2 py-0.5 rounded-lg border border-teal-100">
                                    <Users class="w-3 h-3" />
                                    {{ log.recipient_count }} users
                                </div>
                            </div>

                            <div v-if="pastBroadcasts.length === 0" class="p-12 text-center text-slate-500 font-medium">
                                No records.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Megaphone, CheckCircle, Send, History, Users } from 'lucide-vue-next';

const props = defineProps({
    pastBroadcasts: Array,
    faculties: Array
});

const form = useForm({
    title: '',
    body: '',
    target: 'all_users',
    faculty: ''
});

const submitBroadcast = () => {
    if (!confirm('Are you sure you want to send this broadcast? It will immediately appear in the notifications of ' + form.target.replace('_', ' ') + '.')) {
        return;
    }

    form.post(route('admin.broadcasts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('title', 'body');
        }
    });
};
</script>
