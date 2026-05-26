<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { Trash2, Camera, X, Check } from "lucide-vue-next";

const page = usePage();
const user = computed(() => page.props.auth?.user || {});

// Profile Form
const profileForm = useForm({
    name: user.value?.name || '',
    username: user.value?.username || '',
});

const updateProfile = () => {
    profileForm.post(route('lecturer.settings.profile'), {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: show toast
        },
    });
};

// Password Form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.post(route('lecturer.settings.password'), {
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
        initCrop();
    };
    reader.readAsDataURL(file);
};

const initCrop = () => {
    // We'll handle drawing in a watcher or nextTick
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

            // We use router.post instead of useForm for file blob
            // Or we can use useForm and set the data
            const photoForm = useForm({
                photo: blob
            });

            photoForm.post(route('lecturer.settings.photo'), {
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
        const deleteForm = useForm({});
        deleteForm.delete(route('lecturer.settings.photo.delete'), {
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
        <title>Settings</title>
    </Head>
    <AppLayout>
        <!-- Header -->
        <header class="mb-10">
            <h1 class="text-2xl font-semibold mb-2 text-gray-900">Settings</h1>
            <p class="text-gray-500">Manage your account settings</p>

            <div v-if="$page.props.flash?.success" class="mt-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center gap-3">
                <Check class="w-5 h-5" />
                {{ $page.props.flash.success }}
            </div>
        </header>

        <!-- Profile Section -->
        <section class="grid grid-cols-1 md:grid-cols-12 gap-8 pb-10 border-b border-gray-200">
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
        <section class="grid grid-cols-1 md:grid-cols-12 gap-8 py-10 border-b border-gray-200">
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
                    <button @click="showCropModal = false" class="p-1 hover:bg-gray-100 rounded-full text-gray-500">
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
    </AppLayout>
</template>

<style scoped>
</style>
