<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Trash2, Camera, X, Check } from 'lucide-vue-next';

const props = defineProps({
    settings: Object,
});

const page = usePage();
const user = computed(() => page.props.auth?.user || {});

const activeTab = ref('system');
const scrollTo = (id) => {
    activeTab.value = id;
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

// System Settings Form
const sysForm = useForm({
    app_name: props.settings.app_name,
    semester: props.settings.semester,
    semester_start_date: props.settings.semester_start_date,
    semester_total_weeks: props.settings.semester_total_weeks,
    non_teaching_weeks_raw: (props.settings.non_teaching_weeks || []).join(', '),
    attendance_late_minutes: props.settings.attendance_late_minutes,
    ble_scan_timeout_seconds: props.settings.ble_scan_timeout_seconds,
    qr_rotation_seconds: props.settings.qr_rotation_seconds,
    xp_on_time: props.settings.xp_on_time,
    xp_late: props.settings.xp_late,
    points_on_time: props.settings.points_on_time,
    points_late: props.settings.points_late,
    min_attendance_threshold: props.settings.min_attendance_threshold,
});

const submitSettings = () => {
    // Process comma-separated string back to array of ints
    const ntw = sysForm.non_teaching_weeks_raw
        .split(',')
        .map(s => parseInt(s.trim()))
        .filter(n => !isNaN(n));
        
    sysForm.transform((data) => ({
        ...data,
        non_teaching_weeks: ntw
    })).post(route('admin.settings.update'), { 
        preserveScroll: true 
    });
};

// Profile Form
const profileForm = useForm({
    name: user.value?.name || '',
    username: user.value?.username || '',
});

const updateProfile = () => {
    profileForm.post(route('admin.settings.profile'), {
        preserveScroll: true,
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
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};

// Photo management
const photoInput = ref(null);
const photoPreview = ref(null);
const showCropModal = ref(false);
const selectedFile = ref(null);
const canvasRef = ref(null);

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

const cropImage = () => {
    const canvas = canvasRef.value;
    const ctx = canvas.getContext('2d');
    const img = new Image();
    img.onload = () => {
        const size = Math.min(img.width, img.height);
        const x = (img.width - size) / 2;
        const y = (img.height - size) / 2;

        canvas.width = 400;
        canvas.height = 400;
        ctx.drawImage(img, x, y, size, size, 0, 0, 400, 400);

        canvas.toBlob((blob) => {
            const formData = new FormData();
            formData.append('photo', blob, 'profile.jpg');

            router.post(route('admin.settings.photo'), formData, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    showCropModal.value = false;
                    photoPreview.value = null;
                    selectedFile.value = null;
                },
            });
        }, 'image/jpeg', 0.9);
    };
    img.src = photoPreview.value;
};

const deletePhoto = () => {
    if (confirm('Are you sure you want to delete your profile photo?')) {
        router.delete(route('admin.settings.photo.delete'), {
            preserveScroll: true,
        });
    }
};

const profilePhotoUrl = computed(() => {
    if (user.value?.profile_photo_path) {
        return `/storage/${user.value.profile_photo_path}`;
    }
    return '/images/default-avatar.png';
});
</script>

<template>
    <Head>
        <title>System Settings</title>
    </Head>
    <AdminLayout>
        <!-- Header -->
        <header class="mb-10">
            <h1 class="text-2xl font-semibold mb-2 text-gray-900">System Settings</h1>
            <p class="text-gray-500">Configure global application settings, profile, and security.</p>

            <!-- Navigation buttons -->
            <div class="flex gap-4 mt-6 border-b border-gray-200 pb-4 overflow-x-auto">
                <button @click="scrollTo('system')" :class="['px-4 py-2 rounded-full font-medium text-sm transition-colors border cursor-pointer', activeTab === 'system' ? 'border-orange-500 text-orange-600 bg-orange-50' : 'border-transparent text-gray-700 hover:border-orange-300 hover:text-orange-600']">Global Configuration</button>
                <button @click="scrollTo('profile')" :class="['px-4 py-2 rounded-full font-medium text-sm transition-colors border cursor-pointer', activeTab === 'profile' ? 'border-orange-500 text-orange-600 bg-orange-50' : 'border-transparent text-gray-700 hover:border-orange-300 hover:text-orange-600']">Profile</button>
                <button @click="scrollTo('password')" :class="['px-4 py-2 rounded-full font-medium text-sm transition-colors border cursor-pointer', activeTab === 'password' ? 'border-orange-500 text-orange-600 bg-orange-50' : 'border-transparent text-gray-700 hover:border-orange-300 hover:text-orange-600']">Password</button>
            </div>

            <div v-if="$page.props.flash?.success" class="mt-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center gap-3">
                <Check class="w-5 h-5" />
                {{ $page.props.flash.success }}
            </div>
        </header>

        <!-- System Settings Section -->
        <section id="system" class="grid grid-cols-1 md:grid-cols-12 gap-8 pb-10 mb-10 border-b border-gray-200 scroll-mt-24">
            <div class="md:col-span-4 lg:col-span-3 flex flex-col justify-between">
                <div>
                    <h2 class="text-lg font-medium mb-1 text-gray-900">Global Configuration</h2>
                    <p class="text-sm text-gray-500">System-wide parameters for attendance, security, and hardware.</p>
                </div>
                <div class="mt-6 md:mt-4">
                    <button
                        @click="submitSettings"
                        :disabled="sysForm.processing"
                        class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-full font-medium transition-all active:scale-95 disabled:opacity-50 cursor-pointer w-full md:w-auto"
                    >
                        {{ sysForm.processing ? 'Saving...' : 'Save All Settings' }}
                    </button>
                </div>
            </div>

            <div class="md:col-span-8 lg:col-span-9 space-y-10">
                <!-- Academic Configuration -->
                <div>
                    <h3 class="text-md font-medium text-gray-800 mb-4 border-b border-gray-100 pb-2">Academic Configuration</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm text-gray-700 font-medium mb-1.5">App Name</label>
                            <input v-model="sysForm.app_name" type="text" class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900" :class="{ 'border-red-500': sysForm.errors.app_name }">
                            <p v-if="sysForm.errors.app_name" class="text-red-500 text-xs mt-1">{{ sysForm.errors.app_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 font-medium mb-1.5">Current Semester</label>
                            <input v-model="sysForm.semester" type="text" class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900" placeholder="e.g. 2025/2026-1" :class="{ 'border-red-500': sysForm.errors.semester }">
                            <p v-if="sysForm.errors.semester" class="text-red-500 text-xs mt-1">{{ sysForm.errors.semester }}</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 font-medium mb-1.5">Semester Start Date</label>
                            <input v-model="sysForm.semester_start_date" type="date" class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900" :class="{ 'border-red-500': sysForm.errors.semester_start_date }">
                            <p v-if="sysForm.errors.semester_start_date" class="text-red-500 text-xs mt-1">{{ sysForm.errors.semester_start_date }}</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 font-medium mb-1.5">Total Weeks</label>
                            <input v-model="sysForm.semester_total_weeks" type="number" min="1" class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900" :class="{ 'border-red-500': sysForm.errors.semester_total_weeks }">
                            <p v-if="sysForm.errors.semester_total_weeks" class="text-red-500 text-xs mt-1">{{ sysForm.errors.semester_total_weeks }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm text-gray-700 font-medium mb-1.5">Non-Teaching Weeks <span class="text-xs text-gray-400 font-normal ml-2">(Comma-separated, e.g. 8, 9)</span></label>
                            <input v-model="sysForm.non_teaching_weeks_raw" type="text" class="w-full max-w-2xl border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900" :class="{ 'border-red-500': sysForm.errors.non_teaching_weeks }">
                            <p v-if="sysForm.errors.non_teaching_weeks" class="text-red-500 text-xs mt-1">{{ sysForm.errors.non_teaching_weeks }}</p>
                        </div>
                    </div>
                </div>

                <!-- Hardware & Attendance -->
                <div>
                    <h3 class="text-md font-medium text-gray-800 mb-4 border-b border-gray-100 pb-2">Hardware & Attendance</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm text-gray-700 font-medium mb-1.5">Late Threshold (Mins)</label>
                            <input v-model="sysForm.attendance_late_minutes" type="number" class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900" :class="{ 'border-red-500': sysForm.errors.attendance_late_minutes }">
                            <p v-if="sysForm.errors.attendance_late_minutes" class="text-red-500 text-xs mt-1">{{ sysForm.errors.attendance_late_minutes }}</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 font-medium mb-1.5">Min Attendance Threshold (%)</label>
                            <input v-model="sysForm.min_attendance_threshold" type="number" min="0" max="100" class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900" :class="{ 'border-red-500': sysForm.errors.min_attendance_threshold }">
                            <p v-if="sysForm.errors.min_attendance_threshold" class="text-red-500 text-xs mt-1">{{ sysForm.errors.min_attendance_threshold }}</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 font-medium mb-1.5">BLE Scan Timeout (Secs)</label>
                            <input v-model="sysForm.ble_scan_timeout_seconds" type="number" class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900" :class="{ 'border-red-500': sysForm.errors.ble_scan_timeout_seconds }">
                            <p v-if="sysForm.errors.ble_scan_timeout_seconds" class="text-red-500 text-xs mt-1">{{ sysForm.errors.ble_scan_timeout_seconds }}</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 font-medium mb-1.5">QR Rotation Rate (Secs)</label>
                            <input v-model="sysForm.qr_rotation_seconds" type="number" class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900" :class="{ 'border-red-500': sysForm.errors.qr_rotation_seconds }">
                            <p v-if="sysForm.errors.qr_rotation_seconds" class="text-red-500 text-xs mt-1">{{ sysForm.errors.qr_rotation_seconds }}</p>
                        </div>
                    </div>
                </div>

                <!-- Gamification -->
                <div>
                    <h3 class="text-md font-medium text-gray-800 mb-4 border-b border-gray-100 pb-2">Gamification Engine</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- On-Time -->
                        <div class="p-4 border border-green-200 bg-green-50/50 rounded-xl">
                            <h4 class="text-sm font-semibold text-green-700 mb-4">On-Time Rewards</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">XP Granted</label>
                                    <input v-model="sysForm.xp_on_time" type="number" class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900" :class="{ 'border-red-500': sysForm.errors.xp_on_time }">
                                    <p v-if="sysForm.errors.xp_on_time" class="text-red-500 text-xs mt-1">{{ sysForm.errors.xp_on_time }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Points Granted</label>
                                    <input v-model="sysForm.points_on_time" type="number" class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900" :class="{ 'border-red-500': sysForm.errors.points_on_time }">
                                    <p v-if="sysForm.errors.points_on_time" class="text-red-500 text-xs mt-1">{{ sysForm.errors.points_on_time }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Late -->
                        <div class="p-4 border border-amber-200 bg-amber-50/50 rounded-xl">
                            <h4 class="text-sm font-semibold text-amber-700 mb-4">Late Rewards</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">XP Granted</label>
                                    <input v-model="sysForm.xp_late" type="number" class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900" :class="{ 'border-red-500': sysForm.errors.xp_late }">
                                    <p v-if="sysForm.errors.xp_late" class="text-red-500 text-xs mt-1">{{ sysForm.errors.xp_late }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Points Granted</label>
                                    <input v-model="sysForm.points_late" type="number" class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900" :class="{ 'border-red-500': sysForm.errors.points_late }">
                                    <p v-if="sysForm.errors.points_late" class="text-red-500 text-xs mt-1">{{ sysForm.errors.points_late }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Profile Section -->
        <section id="profile" class="grid grid-cols-1 md:grid-cols-12 gap-8 pb-10 mb-10 border-b border-gray-200 scroll-mt-24">
            <div class="md:col-span-4 lg:col-span-3">
                <h2 class="text-lg font-medium mb-1 text-gray-900">Profile</h2>
                <p class="text-sm text-gray-500">Set your account details</p>
            </div>

            <div class="md:col-span-8 lg:col-span-9 flex flex-col md:flex-row gap-10">
                <!-- Form Fields -->
                <div class="flex-1 space-y-6">
                    <form @submit.prevent="updateProfile" class="space-y-6">
                        <div>
                            <label class="block text-sm text-gray-700 font-medium mb-1.5">Username</label>
                            <input
                                v-model="profileForm.username"
                                type="text"
                                class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900"
                                :class="{ 'border-red-500': profileForm.errors.username }"
                            />
                            <p v-if="profileForm.errors.username" class="text-red-500 text-xs mt-1">{{ profileForm.errors.username }}</p>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-700 font-medium mb-1.5">Full Name</label>
                            <input
                                v-model="profileForm.name"
                                type="text"
                                class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900"
                                :class="{ 'border-red-500': profileForm.errors.name }"
                            />
                            <p v-if="profileForm.errors.name" class="text-red-500 text-xs mt-1">{{ profileForm.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-700 font-medium mb-1.5">Email</label>
                            <p class="text-gray-800">{{ user.email }}</p>
                        </div>

                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="profileForm.processing"
                                class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-full font-medium transition-all active:scale-95 disabled:opacity-50 cursor-pointer"
                            >
                                {{ profileForm.processing ? 'Saving...' : 'Update Profile' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Profile Picture Actions -->
                <div class="flex flex-col items-center md:items-start space-y-4 pt-6">
                    <div class="w-32 h-32 border-2 border-gray-200 rounded-[2rem] bg-gray-50 shadow-inner overflow-hidden flex items-center justify-center">
                        <img :src="profilePhotoUrl" alt="Profile Photo" class="w-full h-full object-cover">
                    </div>
                    <div class="flex items-center space-x-3">
                        <input
                            type="file"
                            ref="photoInput"
                            class="hidden"
                            accept="image/*"
                            @change="onPhotoChange"
                        />
                        <button
                            @click="triggerPhotoInput"
                            class="px-4 py-1.5 border border-gray-300 bg-white rounded-full text-sm font-medium text-gray-700 hover:text-orange-600 hover:border-orange-300 hover:bg-orange-50 transition-colors cursor-pointer flex items-center gap-2"
                        >
                            <Camera class="w-4 h-4" />
                            Edit Photo
                        </button>
                        <button
                            v-if="user.profile_photo_path"
                            @click="deletePhoto"
                            class="p-2 border border-gray-300 bg-white rounded-full text-gray-500 hover:text-red-600 hover:bg-red-50 hover:border-red-200 transition-colors cursor-pointer"
                            title="Delete Photo"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Password Section -->
        <section id="password" class="grid grid-cols-1 md:grid-cols-12 gap-8 py-10">
            <div class="md:col-span-4 lg:col-span-3">
                <h2 class="text-lg font-medium mb-1 text-gray-900">Password</h2>
                <p class="text-sm text-gray-500">Update your security details</p>
            </div>

            <div class="md:col-span-8 lg:col-span-9 space-y-6">
                <form @submit.prevent="updatePassword" class="space-y-6">
                    <div>
                        <label class="block text-sm text-gray-700 font-medium mb-1.5">Current Password</label>
                        <input
                            v-model="passwordForm.current_password"
                            type="password"
                            class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900"
                            :class="{ 'border-red-500': passwordForm.errors.current_password }"
                        />
                        <p v-if="passwordForm.errors.current_password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.current_password }}</p>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700 font-medium mb-1.5">New Password</label>
                        <input
                            v-model="passwordForm.password"
                            type="password"
                            class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900"
                            :class="{ 'border-red-500': passwordForm.errors.password }"
                        />
                        <p v-if="passwordForm.errors.password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700 font-medium mb-1.5">Confirm Password</label>
                        <input
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            class="w-full max-w-lg border border-gray-300 bg-white rounded-lg p-2.5 outline-none focus:border-orange-300 focus:ring-1 focus:ring-orange-300 transition-colors text-gray-900"
                        />
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-full font-medium transition-all active:scale-95 disabled:opacity-50 cursor-pointer"
                        >
                            {{ passwordForm.processing ? 'Updating...' : 'Change Password' }}
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Crop Modal -->
        <div v-if="showCropModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-2xl">
                <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-900">Crop Profile Photo</h3>
                    <button @click="showCropModal = false" class="p-1 hover:bg-gray-100 rounded-full text-gray-500 cursor-pointer">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 flex flex-col items-center">
                    <div class="w-64 h-64 border-2 border-dashed border-gray-300 rounded-[2rem] overflow-hidden bg-gray-50 mb-6 flex items-center justify-center">
                        <canvas ref="canvasRef" class="w-full h-full object-cover hidden"></canvas>
                        <img :src="photoPreview" class="w-full h-full object-cover" />
                    </div>

                    <p class="text-sm text-gray-500 text-center mb-6">
                        The photo will be automatically cropped to a square.
                    </p>

                    <div class="flex gap-4 w-full">
                        <button
                            @click="showCropModal = false"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-full font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            @click="cropImage"
                            class="flex-1 px-4 py-2 bg-orange-500 text-white rounded-full font-medium hover:bg-orange-600 transition-colors cursor-pointer flex items-center justify-center gap-2"
                        >
                            <Check class="w-4 h-4" />
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
