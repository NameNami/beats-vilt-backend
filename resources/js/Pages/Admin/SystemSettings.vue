<template>
    <Head title="Settings" />
    <AdminLayout>
        <div class="max-w-6xl space-y-12">
            <div class="mb-8">
                <h1 class="text-2xl font-semibold mb-2 text-gray-900">Settings</h1>
                <p class="text-slate-600 text-sm font-medium">Manage your personal profile and global system configurations.</p>
            </div>

            <div v-if="$page.props.flash?.success" class="p-4 bg-teal-50 text-teal-800 rounded-xl shadow-sm border border-teal-100 font-medium">
                {{ $page.props.flash.success }}
            </div>

            <!-- Profile & Personal Settings -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1">
                    <h2 class="text-lg font-bold text-slate-900 mb-1">Personal Profile</h2>
                    <p class="text-sm text-slate-500">Update your account information and profile picture.</p>
                </div>
                
                <div class="lg:col-span-2 space-y-6">
                    <!-- Profile Info Card -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row gap-10">
                        <div class="flex-1 space-y-6">
                            <form @submit.prevent="updateProfile" class="space-y-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Username</label>
                                    <input v-model="profileForm.username" type="text" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm py-2.5" :class="{ 'border-red-500': profileForm.errors.username }">
                                    <p v-if="profileForm.errors.username" class="text-red-500 text-xs mt-1">{{ profileForm.errors.username }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Full Name</label>
                                    <input v-model="profileForm.name" type="text" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm py-2.5" :class="{ 'border-red-500': profileForm.errors.name }">
                                    <p v-if="profileForm.errors.name" class="text-red-500 text-xs mt-1">{{ profileForm.errors.name }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Email Address</label>
                                    <p class="text-sm font-medium text-slate-800">{{ user.email }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1 italic">Email cannot be changed.</p>
                                </div>

                                <div class="pt-2">
                                    <button type="submit" :disabled="profileForm.processing" class="bg-slate-900 hover:bg-black text-white px-6 py-2.5 rounded-lg font-bold transition disabled:opacity-50 text-sm shadow-sm">
                                        {{ profileForm.processing ? 'Saving...' : 'Update Profile' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Profile Picture -->
                        <div class="flex flex-col items-center space-y-4">
                            <div class="w-32 h-32 rounded-[2rem] border-4 border-slate-50 shadow-inner overflow-hidden flex items-center justify-center bg-slate-100 relative">
                                <img :src="profilePhotoUrl" alt="Profile" class="w-full h-full object-cover">
                                <div v-if="isUploading" class="absolute inset-0 bg-black/20 flex items-center justify-center">
                                    <div class="w-8 h-8 border-4 border-white border-t-transparent rounded-full animate-spin"></div>
                                </div>
                            </div>
                            <div class="flex flex-col items-center gap-2">
                                <div class="flex items-center gap-2">
                                    <input type="file" ref="photoInput" class="hidden" accept="image/*" @change="onPhotoChange">
                                    <button @click="triggerPhotoInput" class="px-4 py-1.5 bg-white border border-gray-200 rounded-full text-xs font-bold text-slate-700 hover:bg-slate-50 transition flex items-center gap-2 shadow-sm">
                                        <Camera class="w-3.5 h-3.5" />
                                        Change
                                    </button>
                                    <button v-if="user.profile_photo_path" @click="deletePhoto" class="p-1.5 bg-white border border-gray-200 rounded-full text-slate-400 hover:text-rose-600 transition shadow-sm">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                                <p v-if="$page.props.errors.photo" class="text-red-500 text-[10px] font-bold mt-1">{{ $page.props.errors.photo }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Password Card -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                        <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            Security & Password
                        </h3>
                        <form @submit.prevent="updatePassword" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Current Password</label>
                                    <input v-model="passwordForm.current_password" type="password" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm py-2.5" :class="{ 'border-red-500': passwordForm.errors.current_password }">
                                    <p v-if="passwordForm.errors.current_password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.current_password }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">New Password</label>
                                    <input v-model="passwordForm.password" type="password" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm py-2.5" :class="{ 'border-red-500': passwordForm.errors.password }">
                                    <p v-if="passwordForm.errors.password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.password }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Confirm New Password</label>
                                    <input v-model="passwordForm.password_confirmation" type="password" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm py-2.5">
                                </div>
                            </div>
                            <div class="pt-2">
                                <button type="submit" :disabled="passwordForm.processing" class="bg-white border border-slate-200 hover:border-slate-300 text-slate-700 px-6 py-2.5 rounded-lg font-bold transition disabled:opacity-50 text-sm shadow-sm">
                                    {{ passwordForm.processing ? 'Updating...' : 'Change Password' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="h-px bg-slate-100"></div>

            <!-- Global System Settings -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1">
                    <h2 class="text-lg font-bold text-slate-900 mb-1">Global Configuration</h2>
                    <p class="text-sm text-slate-500">System-wide parameters for attendance, security, and hardware.</p>
                </div>

                <div class="lg:col-span-2">
                    <form @submit.prevent="submitSettings" class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 space-y-8">
                        <div>
                            <h3 class="text-md font-bold text-slate-800 border-b border-gray-50 pb-3 mb-5 flex items-center gap-2">
                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Attendance Parameters
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Early Check-In Window</label>
                                    <p class="text-[10px] text-gray-400 mb-2 font-medium italic">Minutes before class a student can scan in.</p>
                                    <div class="relative">
                                        <input type="number" v-model="form.early_window_minutes" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-orange-600 focus:border-orange-600 text-sm pr-12">
                                        <span class="absolute right-3 top-2.5 text-[10px] font-bold text-slate-400">MINS</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Late Cutoff</label>
                                    <p class="text-[10px] text-gray-400 mb-2 font-medium italic">Minutes after start time to be marked Late.</p>
                                    <div class="relative">
                                        <input type="number" v-model="form.late_cutoff_minutes" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-orange-600 focus:border-orange-600 text-sm pr-12">
                                        <span class="absolute right-3 top-2.5 text-[10px] font-bold text-slate-400">MINS</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Attendance Threshold</label>
                                    <p class="text-[10px] text-gray-400 mb-2 font-medium italic">Triggers At-Risk intervention warnings.</p>
                                    <div class="relative">
                                        <input type="number" v-model="form.min_attendance_threshold" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-orange-600 focus:border-orange-600 text-sm pr-12">
                                        <span class="absolute right-3 top-2.5 text-[10px] font-bold text-slate-400">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Dynamic QR Rate</label>
                                    <p class="text-[10px] text-gray-400 mb-2 font-medium italic">Token regeneration frequency.</p>
                                    <div class="relative">
                                        <input type="number" v-model="form.qr_refresh_seconds" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-orange-600 focus:border-orange-600 text-sm pr-12">
                                        <span class="absolute right-3 top-2.5 text-[10px] font-bold text-slate-400">SECS</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-gray-100">
                            <button type="submit" :disabled="form.processing" class="bg-orange-600 hover:bg-orange-700 text-white px-8 py-3 rounded-xl font-bold transition disabled:opacity-50 shadow-sm text-sm">
                                {{ form.processing ? 'Saving...' : 'Save Configuration' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Crop Modal -->
        <div v-if="showCropModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-2xl">
                <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-slate-900">Crop Profile Photo</h3>
                    <button @click="showCropModal = false" class="p-1 hover:bg-gray-100 rounded-full text-gray-400">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-8 flex flex-col items-center">
                    <div class="w-64 h-64 border-2 border-dashed border-gray-200 rounded-[2rem] overflow-hidden bg-slate-50 mb-6 flex items-center justify-center shadow-inner relative">
                        <img :src="photoPreview" class="w-full h-full object-cover" />
                        <div class="absolute inset-0 border-4 border-white/50 rounded-[2rem] pointer-events-none"></div>
                    </div>

                    <p class="text-xs text-slate-500 text-center mb-8 font-medium">
                        Your photo will be cropped to a square automatically.
                    </p>

                    <div class="flex gap-4 w-full">
                        <button @click="showCropModal = false" class="flex-1 px-4 py-3 border border-gray-200 rounded-xl font-bold text-slate-600 hover:bg-slate-50 transition text-sm">
                            Cancel
                        </button>
                        <button @click="uploadPhoto" :disabled="isUploading" class="flex-1 px-4 py-3 bg-orange-600 text-white rounded-xl font-bold hover:bg-orange-700 transition flex items-center justify-center gap-2 text-sm shadow-sm">
                            <Check class="w-4 h-4" />
                            {{ isUploading ? 'Saving...' : 'Confirm' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useForm, usePage, router, Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Camera, Trash2, X, Check } from 'lucide-vue-next';

const props = defineProps({
    settings: Object,
    user: Object
});

const isUploading = ref(false);

// System Settings Form
const form = useForm({
    early_window_minutes: props.settings.early_window_minutes,
    late_cutoff_minutes: props.settings.late_cutoff_minutes,
    min_attendance_threshold: props.settings.min_attendance_threshold,
    qr_refresh_seconds: props.settings.qr_refresh_seconds,
});

const submitSettings = () => {
    form.post(route('admin.settings.update'), { preserveScroll: true });
};

// Profile Form
const profileForm = useForm({
    name: props.user?.name || '',
    username: props.user?.username || '',
});

const updateProfile = () => {
    profileForm.post(route('admin.settings.profile'), {
        preserveScroll: true
    });
};

// Password Form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.post(route('admin.settings.password'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};

// Photo Management
const photoInput = ref(null);
const photoPreview = ref(null);
const showCropModal = ref(false);
const selectedFile = ref(null);

const triggerPhotoInput = () => {
    photoInput.value.click();
};

const onPhotoChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    selectedFile.value = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target.result;
        showCropModal.value = true;
    };
    reader.readAsDataURL(file);
};

const uploadPhoto = () => {
    if (!selectedFile.value) return;
    
    // Create a fresh form data to ensure clean state
    const data = new FormData();
    data.append('photo', selectedFile.value);

    isUploading.value = true;
    router.post(route('admin.settings.photo'), data, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showCropModal.value = false;
            photoPreview.value = null;
            selectedFile.value = null;
        },
        onError: (errors) => {
            console.error('Photo upload failed:', errors);
        },
        onFinish: () => {
            isUploading.value = false;
        }
    });
};

const deletePhoto = () => {
    if (confirm('Delete profile photo?')) {
        router.delete(route('admin.settings.photo.delete'), {
            preserveScroll: true
        });
    }
};

const profilePhotoUrl = computed(() => {
    if (props.user?.profile_photo_path) {
        return `/storage/${props.user.profile_photo_path}`;
    }
    return '/images/default-avatar.png';
});
</script>
