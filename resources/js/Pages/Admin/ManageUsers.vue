<template>
    <AdminLayout>
        <Head title="User Management" />

        <div class="flex justify-between items-end mb-8">
            <div>
                <p class="text-xs font-bold tracking-widest text-amber-700 uppercase mb-2">System Access</p>
                <h2 class="text-4xl font-black text-slate-900 tracking-tight leading-tight">
                    User <span class="text-amber-600 italic">Management</span>
                </h2>
                <p class="text-slate-600 mt-3">Create, edit, and manage system credentials for all stakeholders.</p>
            </div>
        </div>

        <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-teal-50 text-teal-800 rounded-xl border border-teal-100 font-medium flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ $page.props.flash.success }}
        </div>
        <div v-if="errors && Object.keys(errors).length > 0" class="mb-6 p-4 bg-rose-50 text-rose-800 rounded-xl border border-rose-100 shadow-sm">
            <p class="font-bold flex items-center gap-2"><svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Please check the form for errors.</p>
            <ul class="list-disc pl-8 mt-2 text-sm">
                <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
            </ul>
        </div>

        <div v-if="!isEditing" class="bg-[#fcf8f3] p-6 rounded-2xl shadow-sm border border-amber-100 flex flex-col md:flex-row items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-lg font-black text-amber-900">Bulk Import Students</h2>
                <p class="text-sm text-amber-700 mt-1">Upload a CSV file to create multiple accounts instantly.</p>
                <p class="text-xs text-amber-600 mt-2 font-mono bg-amber-100/50 inline-block px-2 py-1 border border-amber-200 rounded">Format: Name, Email, Student_ID</p>
            </div>

            <form @submit.prevent="submitImport" class="flex items-center gap-3 w-full md:w-auto">
                <input
                    type="file"
                    id="csv-upload"
                    accept=".csv"
                    @input="importForm.file = $event.target.files[0]"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200 cursor-pointer transition"
                    required
                />
                <button type="submit" :disabled="importForm.processing" class="bg-amber-700 hover:bg-amber-800 text-white px-6 py-2.5 rounded-lg font-bold transition disabled:opacity-50 whitespace-nowrap shadow-sm">
                    {{ importForm.processing ? 'Uploading...' : 'Import CSV' }}
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 col-span-1 h-fit">
                <h2 class="text-lg font-black text-slate-800 mb-5 border-b border-gray-100 pb-3">
                    {{ isEditing ? 'Edit User Profile' : 'Create New User' }}
                </h2>

                <form @submit.prevent="submitUser" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Full Name</label>
                        <input type="text" v-model="form.name" placeholder="e.g. Ali Bin Abu" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Username</label>
                        <input type="text" v-model="form.username" placeholder="e.g. ali_abu" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Email Address</label>
                        <input type="email" v-model="form.email" placeholder="e.g. ali@beats.edu" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Student / Staff ID</label>
                        <input type="text" v-model="form.student_id" placeholder="e.g. ST-2024" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">System Role</label>
                        <select v-model="form.role" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5 font-medium" required>
                            <option value="student">Student</option>
                            <option value="lecturer">Lecturer</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-gray-100 mt-2 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
                                Password <span v-if="isEditing" class="text-[10px] font-normal lowercase">(Leave blank to keep current)</span>
                            </label>
                            <input type="password" v-model="form.password" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5" :required="!isEditing">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Confirm Password</label>
                            <input type="password" v-model="form.password_confirmation" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5" :required="!isEditing || form.password.length > 0">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <button v-if="isEditing" type="button" @click="cancelEdit" class="bg-gray-100 hover:bg-gray-200 text-slate-700 px-4 py-2 rounded-lg font-bold transition">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="bg-slate-900 hover:bg-slate-800 text-white px-6 py-2.5 rounded-lg font-bold transition disabled:opacity-50 w-full shadow-sm">
                            {{ isEditing ? 'Update Profile' : 'Save New User' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden col-span-1 lg:col-span-2">
                <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:justify-between md:items-center bg-white gap-4">
                    <h2 class="text-lg font-black text-slate-800">Active Directory</h2>
                    <div class="relative w-full md:w-72">
                        <svg class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" v-model="searchQuery" placeholder="Search by name or ID..." class="w-full bg-[#f8fafc] border-gray-200 rounded-lg py-2 pl-10 pr-4 text-sm focus:ring-amber-600 focus:border-amber-600 shadow-sm">
                    </div>
                </div>

                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100 text-[11px] uppercase tracking-widest text-gray-500 font-bold">
                        <th class="px-6 py-4">User Details</th>
                        <th class="px-6 py-4">Role</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                    <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <img :src="`https://ui-avatars.com/api/?name=${user.name}&background=f1f5f9&color=334155`" class="w-10 h-10 rounded-md border border-gray-200">
                                <div>
                                    <p class="font-bold text-slate-900">{{ user.name }}</p>
                                    <p class="text-[11px] text-gray-500 font-medium">{{ user.student_id || 'N/A' }} &bull; {{ user.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span v-if="user.role === 'admin'" class="px-3 py-1 bg-rose-50 text-rose-700 border border-rose-100 rounded-full text-[10px] font-bold tracking-widest uppercase">Admin</span>
                            <span v-else-if="user.role === 'lecturer'" class="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-100 rounded-full text-[10px] font-bold tracking-widest uppercase">Lecturer</span>
                            <span v-else class="px-3 py-1 bg-[#e8f6f5] text-teal-700 border border-teal-100 rounded-full text-[10px] font-bold tracking-widest uppercase">Student</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-3">
                            <button @click="editUser(user)" class="text-slate-400 hover:text-amber-600 transition"><svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></button>
                            <button @click="deleteUser(user.id)" class="text-slate-400 hover:text-rose-600 transition"><svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    users: Array,
    errors: Object
});

const isEditing = ref(false);
const editingUserId = ref(null);
const searchQuery = ref('');

const form = useForm({
    name: '', username: '', email: '', student_id: '', role: 'student', password: '', password_confirmation: ''
});

const importForm = useForm({ file: null });

const filteredUsers = computed(() => {
    if (!searchQuery.value) return props.users;
    return (props.users || []).filter(user =>
        user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (user.student_id && user.student_id.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
        user.email.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const submitImport = () => {
    importForm.post(route('admin.users.import'), {
        preserveScroll: true,
        onSuccess: () => {
            importForm.reset();
            document.getElementById('csv-upload').value = null;
        }
    });
};

const submitUser = () => {
    if (isEditing.value) {
        form.post(`/admin/users/update/${editingUserId.value}`, {
            preserveScroll: true,
            onSuccess: () => resetForm()
        });
    } else {
        form.post(route('admin.users.store'), {
            preserveScroll: true,
            onSuccess: () => resetForm()
        });
    }
};

const editUser = (user) => {
    isEditing.value = true;
    editingUserId.value = user.id;
    form.name = user.name;
    form.username = user.username;
    form.email = user.email;
    form.student_id = user.student_id;
    form.role = user.role;
    form.password = '';
    form.password_confirmation = '';
    form.clearErrors();
};

const cancelEdit = () => {
    resetForm();
};

const deleteUser = (id) => {
    if (!id) {
        alert("Error: User ID is missing.");
        return;
    }
    if (confirm('Are you sure you want to delete this user? This cannot be undone.')) {
        router.post(`/admin/users/${id}`, { preserveScroll: true });
    }
};

const resetForm = () => {
    isEditing.value = false;
    editingUserId.value = null;
    form.reset();
    form.clearErrors();
};
</script>