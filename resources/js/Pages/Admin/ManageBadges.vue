<template>
    <AdminLayout>
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-2xl font-semibold mb-2 text-gray-900">Badge Management</h1>
                <p class="text-slate-600 text-sm font-medium">Create and manage achievement badges for students.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Create/Edit Form -->
            <div class="bg-white p-6 rounded-2xl  border border-gray-100 col-span-1 h-fit">
                <h2 class="text-lg font-bold text-slate-900 mb-5 border-b border-slate-100 pb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                    </svg>
                    {{ isEditing ? 'Edit Badge' : 'Create New Badge' }}
                </h2>

                <form @submit.prevent="submitBadge" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Badge Name</label>
                        <input type="text" v-model="form.name" placeholder="e.g. Present Student" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg  focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5" required>
                        <p v-if="form.errors.name" class="text-rose-500 text-[10px] mt-1 font-bold">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Description</label>
                        <textarea v-model="form.description" placeholder="Awarded for 10 present check-ins" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg  focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5" rows="3" required></textarea>
                        <p v-if="form.errors.description" class="text-rose-500 text-[10px] mt-1 font-bold">{{ form.errors.description }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Badge Type</label>
                        <select v-model="form.type" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg  focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5 font-medium" required>
                            <option value="attendance">Attendance</option>
                            <option value="streak">Streak</option>
                            <option value="xp">XP</option>
                        </select>
                        <p v-if="form.errors.type" class="text-rose-500 text-[10px] mt-1 font-bold">{{ form.errors.type }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Requirement Type</label>
                            <select v-model="form.requirement_type" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg  focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5 font-medium cursor-pointer" required>
                                <option value="present_checkins">Present Check-ins</option>
                                <option value="on_time_checkins">On-time Check-ins</option>
                                <option value="streak_count">Streak Count</option>
                                <option value="total_xp">Total XP</option>
                            </select>
                            <p v-if="form.errors.requirement_type" class="text-rose-500 text-[10px] mt-1 font-bold">{{ form.errors.requirement_type }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Requirement Value</label>
                            <input type="number" v-model="form.requirement_value" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg  focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5" required min="1">
                            <p v-if="form.errors.requirement_value" class="text-rose-500 text-[10px] mt-1 font-bold">{{ form.errors.requirement_value }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 mt-4">
                        <button v-if="isEditing" type="button" @click="cancelEdit" class="bg-gray-100 hover:bg-gray-200 text-slate-700 px-4 py-2 rounded-lg font-bold transition text-sm cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2.5 rounded-lg font-bold transition disabled:opacity-50 w-full  text-sm cursor-pointer">
                            {{ isEditing ? 'Update Badge' : 'Save Badge' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Badges List -->
            <div class="bg-white rounded-xl  border border-gray-200 overflow-hidden col-span-1 lg:col-span-2">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
                    <h2 class="text-lg font-bold text-slate-900">Existing Badges</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100 text-[11px] uppercase tracking-widest text-gray-500 font-bold">
                                <th class="px-6 py-4">Badge</th>
                                <th class="px-6 py-4">Requirement</th>
                                <th class="px-6 py-4">Type</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="badge in badges" :key="badge.id" class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900">{{ badge.name }}</p>
                                            <p class="text-[11px] text-gray-500 font-medium truncate max-w-xs">{{ badge.description }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-medium text-slate-700">
                                        {{ formatRequirement(badge.requirement_type) }}: {{ badge.requirement_value }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-[10px] font-bold tracking-widest uppercase">{{ badge.type }}</span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <button @click="editBadge(badge)" class="text-slate-400 hover:text-amber-600 transition cursor-pointer">
                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>
                                    <button @click="deleteBadge(badge.id)" class="text-slate-400 hover:text-rose-600 transition cursor-pointer">
                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="badges.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500 font-medium">No badges found. Create your first badge!</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    badges: Array,
});

const isEditing = ref(false);

const form = useForm({
    id: null,
    name: '',
    description: '',
    type: 'achievement',
    requirement_type: 'present_checkins',
    requirement_value: 1,
    icon_path: null,
});

const submitBadge = () => {
    if (isEditing.value) {
        form.post(`/admin/badges/update/${form.id}`, {
            preserveScroll: true,
            onSuccess: () => resetForm()
        });
    } else {
        form.post('/admin/badges', {
            preserveScroll: true,
            onSuccess: () => resetForm()
        });
    }
};

const editBadge = (badge) => {
    isEditing.value = true;
    form.id = badge.id;
    form.name = badge.name;
    form.description = badge.description;
    form.type = badge.type;
    form.requirement_type = badge.requirement_type;
    form.requirement_value = badge.requirement_value;
    form.icon_path = badge.icon_path;
    form.clearErrors();
};

const deleteBadge = (id) => {
    if (confirm('Are you sure you want to delete this badge?')) {
        router.post(`/admin/badges/delete/${id}`, { preserveScroll: true });
    }
};

const cancelEdit = () => {
    resetForm();
};

const resetForm = () => {
    isEditing.value = false;
    form.id = null;
    form.reset();
    form.clearErrors();
};

const formatRequirement = (type) => {
    const types = {
        present_checkins: 'Present Check-ins',
        on_time_checkins: 'On-time Check-ins',
        streak_count: 'Streak Count',
        total_xp: 'Total XP'
    };
    return types[type] || type;
};
</script>

