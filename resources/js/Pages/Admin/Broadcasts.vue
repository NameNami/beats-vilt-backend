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
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ $page.props.flash.success }}
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Composer Card -->
                <div class="lg:col-span-1">
                    <form @submit.prevent="submitBroadcast" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm sticky top-6">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                                New Broadcast
                            </h2>
                        </div>
                        <div class="p-6 space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Target Audience</label>
                                <select v-model="form.target" class="w-full bg-[#f8fafc] border-slate-200 rounded-xl focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5 font-medium">
                                    <option value="all_users">Everyone (All Users)</option>
                                    <option value="all_students">All Students</option>
                                    <option value="all_lecturers">All Lecturers</option>
                                    <option value="specific_faculty">Specific Faculty</option>
                                </select>
                                <p v-if="form.errors.target" class="text-red-500 text-xs mt-1">{{ form.errors.target }}</p>
                            </div>

                            <div v-if="form.target === 'specific_faculty'">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Select Faculty</label>
                                <select v-model="form.faculty" class="w-full bg-[#f8fafc] border-slate-200 rounded-xl focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5 font-medium">
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
                                <button type="submit" :disabled="form.processing" class="w-full bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-xl font-bold transition disabled:opacity-50 flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                    {{ form.processing ? 'Sending...' : 'Send Broadcast Now' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- History Log -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Broadcast History
                            </h2>
                        </div>
                        
                        <div class="divide-y divide-slate-100">
                            <div v-for="log in pastBroadcasts" :key="log.created_at" class="p-6 hover:bg-slate-50/50 transition">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-bold text-slate-900">{{ log.title }}</h3>
                                    <span class="text-xs font-bold text-slate-400 whitespace-nowrap ml-4">{{ new Date(log.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</span>
                                </div>
                                <p class="text-sm text-slate-600 mb-4 leading-relaxed">{{ log.body }}</p>
                                <div class="flex items-center gap-2 text-xs font-bold text-teal-600 bg-teal-50 w-fit px-3 py-1 rounded-lg border border-teal-100">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    Delivered to {{ log.recipient_count }} users
                                </div>
                            </div>
                            
                            <div v-if="pastBroadcasts.length === 0" class="p-12 text-center text-slate-500 font-medium">
                                No broadcasts have been sent yet.
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