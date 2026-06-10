<template>
    <AdminLayout>
        <Head title="Lecturer Management" />

        <!-- Assignment Modal -->
        <div v-if="showAddForm" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-xl font-black text-slate-900">Assign New Course</h3>
                    <button @click="showAddForm = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form @submit.prevent="submitLecturer" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[10px] font-medium text-gray-400 uppercase tracking-widest mb-1">Select Lecturer</label>
                        <select v-model="form.user_id" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-sm cursor-pointer" required>
                            <option value="">Select a lecturer...</option>
                            <option v-for="lecturer in lecturers" :key="lecturer.id" :value="lecturer.id">
                                {{ lecturer.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-medium text-gray-400 uppercase tracking-widest mb-1">Select Course</label>
                        <select v-model="form.course_id" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-sm cursor-pointer" required>
                            <option value="">Select a course...</option>
                            <option v-for="course in availableCourses" :key="course.id" :value="course.id">
                                {{ course.code }} - {{ course.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-medium text-gray-400 uppercase tracking-widest mb-1">Select Lab / Group (Optional)</label>
                        <select v-model="form.lab_id" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-sm cursor-pointer" :class="{ 'border-rose-300 focus:border-rose-500 focus:ring-rose-500': form.errors.lab_id }">
                            <option value="">None / Lecture</option>
                            <option v-for="lab in filteredFormLabs" :key="lab.id" :value="lab.id" :disabled="(lab.enrollments?.length || 0) >= lab.capacity">
                                {{ lab.name }} (Enrolled: {{ lab.enrollments?.length || 0 }} / {{ lab.capacity }}) {{ (lab.enrollments?.length || 0) >= lab.capacity ? '- FULL' : '' }}
                            </option>
                        </select>
                        <p v-if="form.errors.lab_id" class="text-rose-500 text-xs mt-1 font-medium">{{ form.errors.lab_id }}</p>
                    </div>
                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="showAddForm = false" class="px-6 py-2 text-slate-600 font-medium hover:text-slate-800 transition cursor-pointer">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-orange-400 text-white rounded-xl font-medium hover:bg-orange-500 transition cursor-pointer">Assign Course</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-semibold mb-2 text-gray-900">Lecturer Management</h1>
                <p class="text-slate-600 text-sm font-medium">Manage faculty members, track assigned courses, and view performance metrics.</p>
            </div>
            <div class="flex gap-3 w-full md:w-auto">
                <button @click="openAssignModal()" class="bg-orange-400 hover:bg-orange-500 text-white px-6 py-2.5 rounded-xl font-medium flex items-center justify-center gap-2 transition text-sm w-full md:w-auto cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Assign Course
                </button>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 bg-white p-2 border border-slate-300 rounded-xl">

            <!-- Search -->
            <div class="relative w-full sm:flex-1 md:max-w-md ml-1">
                <svg class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" v-model="searchQuery" placeholder="Search name, email, course..." class="w-full bg-[#f8fafc] border-gray-200 rounded-lg py-2 pl-10 pr-4 text-sm focus:ring-orange-600 focus:border-orange-600 outline-none">
            </div>

            <!-- Dropdown Filters -->
            <div class="flex flex-wrap gap-2 w-full sm:w-auto pr-1 pb-1 sm:pb-0">

                <!-- Course Filter Dropdown -->
                <div class="relative w-full sm:w-auto" ref="courseDropdownRef">
                    <button
                        @click="isCourseDropdownOpen = !isCourseDropdownOpen"
                        class="w-full sm:w-auto inline-flex items-center justify-between text-white bg-orange-400 hover:bg-orange-500 focus:ring-4 focus:ring-slate-100 font-medium rounded-xl text-sm px-4 py-2 transition-all outline-none cursor-pointer"
                        type="button"
                    >
                        <div class="flex items-center gap-2">
                            <Filter class="w-4 h-4 text-orange-100" />
                            <span class="truncate max-w-[130px]">{{ selectedCourseLabel }}</span>
                        </div>
                        <ChevronDown class="w-4 h-4 ms-2 -me-1 text-white transition-transform duration-200" :class="{'rotate-180': isCourseDropdownOpen}" />
                    </button>

                    <div
                        v-if="isCourseDropdownOpen"
                        class="absolute right-0 top-full mt-2 z-30 bg-white border border-slate-200 rounded-xl shadow-xl w-64 overflow-hidden animate-in fade-in zoom-in-95 duration-100"
                    >
                        <ul class="p-1.5 text-sm text-slate-700 font-medium max-h-60 overflow-y-auto space-y-1">
                            <li>
                                <button
                                    @click="courseFilter = ''; isCourseDropdownOpen = false"
                                    class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                    :class="{'text-orange-600 bg-orange-50': courseFilter === ''}"
                                >
                                    All Courses
                                </button>
                            </li>
                            <li v-for="course in availableCourses" :key="course.id">
                                <button
                                    @click="courseFilter = course.id; isCourseDropdownOpen = false"
                                    class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                    :class="{'text-orange-600 bg-orange-50': courseFilter === course.id}"
                                >
                                    {{ course.code }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                <tr class="bg-orange-50 border-b border-gray-100 text-[11px] uppercase tracking-widest text-gray-600 font-medium">
                    <th class="px-6 py-4">Name</th>
                    <th class="px-6 py-4">Assigned Courses</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                <tr v-for="lecturer in filteredLecturers" :key="lecturer.id" class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4">
                        <div class="font-medium text-slate-900">{{ lecturer.name }}</div>
                        <div class="text-xs text-gray-500">{{ lecturer.email }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-2">
                            <div v-for="group in getGroupedCourses(lecturer)" :key="group.course.id"
                                class="flex items-center gap-2 group">
                                <div class="flex items-center gap-1.5 truncate flex-1">
                                    <span class="text-[10px] font-medium text-sky-800">{{ group.course.code }}</span>
                                    <span class="text-[11px] font-medium text-slate-500 truncate">- {{ group.course.name }}</span>
                                    <span class="text-[10px] font-medium text-sky-600 whitespace-nowrap ml-1">
                                        ({{ group.labels.join(' | ') }})
                                    </span>
                                </div>
                                <button @click="deleteCourseAssignments(lecturer.id, group.course.id)" class="text-gray-300 hover:text-rose-600 transition shrink-0 cursor-pointer ml-auto" title="Remove all assignments for this course">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                </button>
                            </div>
                            <span v-if="getGroupedCourses(lecturer).length === 0" class="text-gray-400 italic text-xs">No assignments</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button @click="openAssignModal(lecturer.id)" class="text-slate-400 hover:text-orange-600 transition inline-block cursor-pointer" title="Assign Course">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        </button>
                    </td>
                </tr>
                <tr v-if="!filteredLecturers || filteredLecturers.length === 0">
                    <td colspan="3" class="px-6 py-10 text-center text-gray-500 font-medium">No lecturers found matching your search.</td>
                </tr>
                </tbody>
            </table>
        </div>

    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Filter, ChevronDown } from 'lucide-vue-next';

const props = defineProps({
    lecturers: Array,
    availableCourses: Array,
    availableLabs: Array
});

const showAddForm = ref(false);
const searchQuery = ref('');
const courseFilter = ref('');

// Dropdown state
const isCourseDropdownOpen = ref(false);
const courseDropdownRef = ref(null);

const handleClickOutside = (event) => {
    if (courseDropdownRef.value && !courseDropdownRef.value.contains(event.target)) {
        isCourseDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

const selectedCourseLabel = computed(() => {
    if (!courseFilter.value) return 'All Courses';
    const c = props.availableCourses.find(x => x.id === courseFilter.value);
    return c ? c.code : 'All Courses';
});

const filteredLecturers = computed(() => {
    let result = props.lecturers || [];

    if (courseFilter.value) {
        result = result.filter(lecturer => {
            const hasEnrollment = lecturer.course_enrollments?.some(enrollment => enrollment.course_id === courseFilter.value);
            const hasLab = lecturer.labs?.some(lab => lab.course_id === courseFilter.value);
            return hasEnrollment || hasLab;
        });
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(lecturer => {
            const matchName = lecturer.name?.toLowerCase().includes(query);
            const matchEmail = lecturer.email?.toLowerCase().includes(query);
            
            const matchCourseEnrollment = lecturer.course_enrollments?.some(enrollment =>
                enrollment.course?.code?.toLowerCase().includes(query) ||
                enrollment.course?.name?.toLowerCase().includes(query)
            );

            const matchLabCourse = lecturer.labs?.some(lab =>
                lab.course?.code?.toLowerCase().includes(query) ||
                lab.course?.name?.toLowerCase().includes(query) ||
                lab.name?.toLowerCase().includes(query)
            );

            return matchName || matchEmail || matchCourseEnrollment || matchLabCourse;
        });
    }

    return result;
});

const form = useForm({
    user_id: '',
    course_id: '',
    lab_id: '',
});

const filteredFormLabs = computed(() => {
    if (!form.course_id) return [];
    return props.availableLabs.filter(lab => lab.course_id === form.course_id);
});

const openAssignModal = (lecturerId = '') => {
    form.reset();
    form.user_id = lecturerId;
    showAddForm.value = true;
};

const submitLecturer = () => {
    form.post(route('admin.lecturers.assign'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddForm.value = false;
            form.reset();
        }
    });
};

const getGroupedCourses = (lecturer) => {
    const courseMap = new Map();

    const getOrAddCourse = (courseId, courseData) => {
        if (!courseMap.has(courseId)) {
            courseMap.set(courseId, { 
                course: courseData || props.availableCourses.find(c => c.id === courseId), 
                labels: []
            });
        }
        return courseMap.get(courseId);
    };

    // 1. Process Labs
    if (lecturer.labs) {
        lecturer.labs.forEach(lab => {
            const c = getOrAddCourse(lab.course_id, lab.course);
            c.labels.push(lab.name);
        });
    }

    // 2. Process Lecture from conducted_sessions
    if (lecturer.conducted_sessions) {
        lecturer.conducted_sessions.forEach(session => {
            if (!session.lab_id) {
                const c = getOrAddCourse(session.course_id);
                if (!c.labels.includes('Lecture')) {
                    c.labels.push('Lecture');
                }
            }
        });
    }

    // 3. Process CourseEnrollments (to ensure assigned but unscheduled courses appear)
    if (lecturer.course_enrollments) {
        lecturer.course_enrollments.forEach(enrollment => {
            const c = getOrAddCourse(enrollment.course_id, enrollment.course);
            if (c.labels.length === 0) {
                c.labels.push('Lecture');
            }
        });
    }

    return Array.from(courseMap.values());
};

const deleteCourseAssignments = (userId, courseId) => {
    if (!userId || !courseId) return;
    if (confirm('Are you sure you want to remove all assignments for this course?')) {
        router.post(route('admin.lecturers.remove.course', courseId), { user_id: userId }, { preserveScroll: true });
    }
};
</script>