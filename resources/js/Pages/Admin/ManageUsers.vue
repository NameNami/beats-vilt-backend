<template>
    <AdminLayout>
        <Head title="User Management" />

        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-2xl font-semibold mb-2 text-gray-900">User Management</h1>
                <p class="text-slate-600 text-sm font-medium">Create, edit, and manage system credentials for all stakeholders.</p>
            </div>
        </div>

        <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-teal-50 text-teal-800 rounded-xl border border-teal-100 font-medium flex items-center gap-2 ">
            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ $page.props.flash.success }}
        </div>
        <div v-if="errors && Object.keys(errors).length > 0" class="mb-6 p-4 bg-rose-50 text-rose-800 rounded-xl border border-rose-100 ">
            <p class="font-bold flex items-center gap-2"><svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Please check the form for errors.</p>
            <ul class="list-disc pl-8 mt-2 text-sm">
                <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
            </ul>
        </div>

        <div v-if="!isEditing" class="bg-orange-50/30 p-4 rounded-xl border border-orange-100 flex flex-col md:flex-row items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-base font-bold text-orange-900">Bulk Import Students</h2>
                <p class="text-xs text-orange-700 mt-1">Upload a CSV file to create multiple accounts instantly.</p>
                <p class="text-[13px] text-orange-600 mt-1.5 font-mono">Format: Name, Email, Student_ID</p>
            </div>

            <form @submit.prevent="submitImport" class="flex items-center gap-3 w-full md:w-auto">
                <input
                    type="file"
                    id="csv-upload"
                    accept=".csv"
                    @input="importForm.file = $event.target.files[0]"
                    class="px-5 block w-full md:w-auto text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-white hover:file:bg-slate-700 cursor-pointer transition"
                    required
                />
                <button type="submit" :disabled="importForm.processing" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-1.5 rounded-md text-sm font-medium transition disabled:opacity-50 whitespace-nowrap cursor-pointer">
                    {{ importForm.processing ? 'Uploading...' : 'Import CSV' }}
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-xl border border-gray-200 col-span-1 h-fit">
                <h2 class="text-lg font-bold text-slate-900 mb-5 border-b border-slate-100 pb-3">
                    {{ isEditing ? 'Edit User Profile' : 'Create New User' }}
                </h2>

                <form @submit.prevent="submitUser" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Full Name</label>
                        <input type="text" v-model="form.name" placeholder="e.g. Ali Bin Abu" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Username</label>
                        <input type="text" v-model="form.username" placeholder="e.g. ali_abu" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Email Address</label>
                        <input type="email" v-model="form.email" placeholder="e.g. ali@beats.edu" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Student / Staff ID</label>
                        <input type="text" v-model="form.student_id" placeholder="e.g. ST-2024" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">System Role</label>
                        <select v-model="form.role" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5 font-medium cursor-pointer" required>
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
                            <input type="password" v-model="form.password" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5" :required="!isEditing">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Confirm Password</label>
                            <input type="password" v-model="form.password_confirmation" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5" :required="!isEditing || form.password.length > 0">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <button v-if="isEditing" type="button" @click="cancelEdit" class="bg-rose-100 hover:bg-rose-200 text-rose-700 px-4 py-2 rounded-lg font-medium transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="bg-orange-400 hover:bg-orange-500 text-white px-6 py-2.5 rounded-lg font-medium transition disabled:opacity-50 w-full cursor-pointer">
                            {{ isEditing ? 'Update Profile' : 'Save New User' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden col-span-1 lg:col-span-2">
                <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:justify-between md:items-center bg-white gap-4">
                    <h2 class="text-lg font-bold text-slate-900">Active Directory</h2>
                    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                        <div class="relative w-full sm:w-auto" ref="roleDropdownRef">
                            <button
                                @click="isRoleDropdownOpen = !isRoleDropdownOpen"
                                class="w-full sm:w-auto inline-flex items-center justify-between text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 focus:ring-4 focus:ring-slate-100 font-medium rounded-lg text-sm px-4 py-2 transition-all outline-none cursor-pointer"
                                type="button"
                            >
                                <div class="flex items-center gap-2">
                                    <Filter class="w-4 h-4 text-slate-400" />
                                    <span class="truncate max-w-[150px]">{{ selectedRoleLabel }}</span>
                                </div>
                                <ChevronDown class="w-4 h-4 ms-2 -me-1 text-slate-400 transition-transform duration-200" :class="{'rotate-180': isRoleDropdownOpen}" />
                            </button>

                            <!-- Dropdown menu -->
                            <div
                                v-if="isRoleDropdownOpen"
                                class="absolute right-0 top-full mt-2 z-30 bg-white border border-slate-200 rounded-xl shadow-xl w-48 overflow-hidden animate-in fade-in zoom-in-95 duration-100"
                            >
                                <ul class="p-1.5 text-sm text-slate-700 font-medium max-h-60 overflow-y-auto space-y-1">
                                    <li>
                                        <button
                                            @click="roleFilter = 'all'; isRoleDropdownOpen = false"
                                            class="flex items-center w-full p-2 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                            :class="{'text-orange-600 bg-orange-50': roleFilter === 'all'}"
                                        >
                                            All Roles
                                        </button>
                                    </li>
                                    <li>
                                        <button
                                            @click="roleFilter = 'student'; isRoleDropdownOpen = false"
                                            class="flex items-center w-full p-2 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                            :class="{'text-orange-600 bg-orange-50': roleFilter === 'student'}"
                                        >
                                            Student
                                        </button>
                                    </li>
                                    <li>
                                        <button
                                            @click="roleFilter = 'lecturer'; isRoleDropdownOpen = false"
                                            class="flex items-center w-full p-2 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                            :class="{'text-orange-600 bg-orange-50': roleFilter === 'lecturer'}"
                                        >
                                            Lecturer
                                        </button>
                                    </li>
                                    <li>
                                        <button
                                            @click="roleFilter = 'admin'; isRoleDropdownOpen = false"
                                            class="flex items-center w-full p-2 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                            :class="{'text-orange-600 bg-orange-50': roleFilter === 'admin'}"
                                        >
                                            Admin
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="relative w-full sm:w-64">
                            <svg class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <input type="text" v-model="searchQuery" placeholder="Search by name or ID..." class="w-full bg-[#f8fafc] border-gray-200 rounded-lg py-2 pl-10 pr-4 text-sm focus:ring-orange-600 focus:border-orange-600">
                        </div>
                    </div>
                </div>

                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-orange-50/50 border-b border-gray-100 text-[11px] uppercase tracking-widest text-gray-500 font-bold">
                        <th class="px-6 py-3">User Details</th>
                        <th class="px-6 py-3">Role</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                    <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div>
                                    <p class="text-[14px] font-medium text-slate-900 leading-tight ">{{ user.name }}</p>
                                    <p class="text-[11px] text-gray-500 font-medium">{{ user.student_id || 'N/A' }} &bull; {{ user.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span v-if="user.role === 'admin'" class="px-3 py-1 bg-rose-50 text-rose-700 border border-rose-100 rounded-full text-[10px] font-bold tracking-widest uppercase">Admin</span>
                            <span v-else-if="user.role === 'lecturer'" class="px-3 py-1 bg-orange-50 text-orange-700 border border-orange-100 rounded-full text-[10px] font-bold tracking-widest uppercase">Lecturer</span>
                            <span v-else class="px-3 py-1 bg-[#e8f6f5] text-teal-700 border border-teal-100 rounded-full text-[10px] font-bold tracking-widest uppercase">Student</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-3">
                            <button @click="editUser(user)" class="text-slate-400 hover:text-orange-600 transition cursor-pointer"><svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></button>
                            <button @click="deleteUser(user.id)" class="text-slate-400 hover:text-rose-600 transition cursor-pointer"><svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                        </td>
                    </tr>
                    <tr v-if="filteredUsers.length === 0">
                        <td colspan="3" class="px-6 py-8 text-center text-slate-500 font-medium">No users found matching your criteria.</td>
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
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Filter, ChevronDown } from 'lucide-vue-next';

const props = defineProps({
    users: Array,
    errors: Object
});

const isEditing = ref(false);
const editingUserId = ref(null);
const searchQuery = ref('');
const roleFilter = ref('all');

const isRoleDropdownOpen = ref(false);
const roleDropdownRef = ref(null);

const handleClickOutside = (event) => {
    if (roleDropdownRef.value && !roleDropdownRef.value.contains(event.target)) {
        isRoleDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

const selectedRoleLabel = computed(() => {
    if (roleFilter.value === 'student') return 'Student';
    if (roleFilter.value === 'lecturer') return 'Lecturer';
    if (roleFilter.value === 'admin') return 'Admin';
    return 'All Roles';
});

const form = useForm({
    name: '', username: '', email: '', student_id: '', role: 'student', password: '', password_confirmation: ''
});

const importForm = useForm({ file: null });

const filteredUsers = computed(() => {
    let result = props.users || [];

    if (roleFilter.value !== 'all') {
        result = result.filter(user => user.role === roleFilter.value);
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(user =>
            user.name.toLowerCase().includes(query) ||
            (user.student_id && user.student_id.toLowerCase().includes(query)) ||
            user.email.toLowerCase().includes(query)
        );
    }

    return result;
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
