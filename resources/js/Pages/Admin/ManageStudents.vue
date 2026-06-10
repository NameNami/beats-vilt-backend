<template>
    <AdminLayout>
        <Head title="Student Management" />

        <!-- Enrollment Modal -->
        <div v-if="showEnrollModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-xl font-black text-slate-900">Enroll Student(s)</h3>
                    <button @click="showEnrollModal = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form @submit.prevent="submitEnrollment" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[10px] font-medium text-gray-400 uppercase tracking-widest mb-1">Select Student(s)</label>
                        <select v-model="form.user_ids" multiple class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-sm cursor-pointer h-32" required>
                            <option v-for="student in students" :key="student.id" :value="student.id">
                                {{ student.name }} ({{ student.student_id || 'N/A' }})
                            </option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple students.</p>
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
                        <button type="button" @click="showEnrollModal = false" class="px-6 py-2 text-slate-600 font-medium hover:text-slate-800 transition cursor-pointer">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-orange-600 text-white rounded-xl font-medium hover:bg-orange-700 transition cursor-pointer">Enroll Student(s)</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-semibold mb-2 text-gray-900">Student Database</h1>
                <p class="text-slate-600 text-sm font-medium">Manage student accounts and course enrollments with precision.</p>
            </div>
            <div class="flex gap-3 w-full md:w-auto">
                <button @click="openEnrollModal()" class="bg-orange-400 hover:bg-orange-500 text-white px-6 py-2.5 rounded-xl font-medium flex items-center justify-center gap-2 transition text-sm w-full md:w-auto cursor-pointer">
                    Enroll Student
                </button>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 bg-white p-2 border border-slate-300 rounded-xl">

            <!-- Search -->
            <div class="relative w-full sm:flex-1 md:max-w-md ml-1">
                <svg class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" v-model="searchQuery" placeholder="Search name, ID, programme, course..." class="w-full bg-[#f8fafc] border-gray-200 rounded-lg py-2 pl-10 pr-4 text-sm focus:ring-orange-600 focus:border-orange-600 outline-none">
            </div>

            <!-- Dropdown Filters -->
            <div class="flex flex-wrap gap-2 w-full sm:w-auto pr-1 pb-1 sm:pb-0">

                <!-- Programme Filter Dropdown -->
                <div class="relative w-full sm:w-auto" ref="programmeDropdownRef">
                    <button
                        @click="isProgrammeDropdownOpen = !isProgrammeDropdownOpen"
                        class="w-full sm:w-auto inline-flex items-center justify-between text-white bg-orange-400 hover:bg-orange-500 focus:ring-4 focus:ring-slate-100 font-medium rounded-xl text-sm px-4 py-2 transition-all outline-none cursor-pointer"
                        type="button"
                    >
                        <div class="flex items-center gap-2">
                            <span class="truncate max-w-[130px]">{{ selectedProgrammeLabel }}</span>
                        </div>
                        <ChevronDown class="w-4 h-4 ms-2 -me-1 text-white transition-transform duration-200" :class="{'rotate-180': isProgrammeDropdownOpen}" />
                    </button>

                    <!-- Dropdown menu -->
                    <div
                        v-if="isProgrammeDropdownOpen"
                        class="absolute right-0 top-full mt-2 z-30 bg-white border border-slate-200 rounded-xl shadow-xl w-64 overflow-hidden animate-in fade-in zoom-in-95 duration-100"
                    >
                        <ul class="p-1.5 text-sm text-slate-700 font-medium max-h-60 overflow-y-auto space-y-1">
                            <li>
                                <button
                                    @click="programmeFilter = ''; isProgrammeDropdownOpen = false"
                                    class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                    :class="{'text-orange-600 bg-orange-50': programmeFilter === ''}"
                                >
                                    All Programmes
                                </button>
                            </li>
                            <li v-for="prog in availableProgrammes" :key="prog.id">
                                <button
                                    @click="programmeFilter = prog.id; isProgrammeDropdownOpen = false"
                                    class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                    :class="{'text-orange-600 bg-orange-50': programmeFilter === prog.id}"
                                >
                                    {{ prog.code }} - {{ prog.name }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Course Filter Dropdown -->
                <div class="relative w-full sm:w-auto" ref="courseDropdownRef">
                    <button
                        @click="isCourseDropdownOpen = !isCourseDropdownOpen"
                        class="w-full sm:w-auto inline-flex items-center justify-between text-white bg-orange-400 hover:bg-orange-500 focus:ring-4 focus:ring-slate-100 font-medium rounded-xl text-sm px-4 py-2 transition-all outline-none cursor-pointer"
                        type="button"
                    >
                        <div class="flex items-center gap-2">
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

                <!-- Class/Lab Filter Dropdown -->
                <div v-if="courseFilter" class="relative w-full sm:w-auto" ref="labDropdownRef">
                    <button
                        @click="isLabDropdownOpen = !isLabDropdownOpen"
                        class="w-full sm:w-auto inline-flex items-center justify-between text-white bg-orange-400 hover:bg-orange-500 focus:ring-4 focus:ring-slate-100 font-medium rounded-xl text-sm px-4 py-2 transition-all outline-none cursor-pointer"
                        type="button"
                    >
                        <div class="flex items-center gap-2">
                            <span class="truncate max-w-[130px]">{{ selectedLabLabel }}</span>
                        </div>
                        <ChevronDown class="w-4 h-4 ms-2 -me-1 text-white transition-transform duration-200" :class="{'rotate-180': isLabDropdownOpen}" />
                    </button>

                    <div
                        v-if="isLabDropdownOpen"
                        class="absolute right-0 top-full mt-2 z-30 bg-white border border-slate-200 rounded-xl shadow-xl w-64 overflow-hidden animate-in fade-in zoom-in-95 duration-100"
                    >
                        <ul class="p-1.5 text-sm text-slate-700 font-medium max-h-60 overflow-y-auto space-y-1">
                            <li>
                                <button
                                    @click="labFilter = ''; isLabDropdownOpen = false"
                                    class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                    :class="{'text-orange-600 bg-orange-50': labFilter === ''}"
                                >
                                    All Classes
                                </button>
                            </li>
                            <li v-for="lab in filteredLabsForDropdown" :key="lab.id">
                                <button
                                    @click="labFilter = lab.id; isLabDropdownOpen = false"
                                    class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                    :class="{'text-orange-600 bg-orange-50': labFilter === lab.id}"
                                >
                                    {{ lab.name }} <span class="text-slate-400 ml-1">({{ lab.course?.code }})</span>
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
                    <th class="px-6 py-4">Student</th>
                    <th class="px-6 py-4">Programme</th>
                    <th class="px-6 py-4">Enrollments</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                <tr v-for="student in filteredStudents" :key="student.id" class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4">
                        <div class="font-medium text-slate-900">{{ student.name }}</div>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-[10px] font-bold text-orange-600">{{ student.student_id || 'N/A' }}</span>
                            <span class="text-gray-300">&bull;</span>
                            <span class="text-xs text-gray-500">{{ student.email }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold text-slate-700">
                        {{ student.programme?.code || 'N/A' }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-1.5">
                            <div v-for="enrollment in student.course_enrollments" :key="enrollment.id"
                                class="flex items-center gap-2">
                                <div class="flex items-center gap-1.5 truncate">
                                    <span class="text-[10px] font-medium text-sky-800">{{ enrollment.course?.code }}</span>
                                    <span class="text-[11px] font-medium text-slate-500 truncate">- {{ enrollment.course?.name }}</span>
                                    <span v-if="enrollment.lab" class="text-[10px] font-medium text-sky-600 whitespace-nowrap ml-1">({{ enrollment.lab.name }})</span>
                                </div>
                                <button @click="removeEnrollment(enrollment.id)" class="text-gray-300 hover:text-rose-600 transition shrink-0 cursor-pointer ml-auto">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                </button>
                            </div>
                            <span v-if="!student.course_enrollments?.length" class="text-gray-400 italic text-xs">No enrollments</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button @click="openEnrollModal([student.id])" class="text-slate-400 hover:text-orange-600 transition inline-block cursor-pointer" title="Enroll Student">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        </button>
                    </td>
                </tr>
                <tr v-if="!filteredStudents || filteredStudents.length === 0">
                    <td colspan="4" class="px-6 py-10 text-center text-gray-500 font-medium">No students found matching your search.</td>
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
    students: Array,
    availableCourses: Array,
    availableLabs: Array,
    availableProgrammes: {
        type: Array,
        default: () => []
    }
});

const showEnrollModal = ref(false);
const searchQuery = ref('');
const programmeFilter = ref('');
const courseFilter = ref('');
const labFilter = ref('');

// Dropdown state
const isProgrammeDropdownOpen = ref(false);
const programmeDropdownRef = ref(null);
const isCourseDropdownOpen = ref(false);
const courseDropdownRef = ref(null);
const isLabDropdownOpen = ref(false);
const labDropdownRef = ref(null);

const handleClickOutside = (event) => {
    if (programmeDropdownRef.value && !programmeDropdownRef.value.contains(event.target)) {
        isProgrammeDropdownOpen.value = false;
    }
    if (courseDropdownRef.value && !courseDropdownRef.value.contains(event.target)) {
        isCourseDropdownOpen.value = false;
    }
    if (labDropdownRef.value && !labDropdownRef.value.contains(event.target)) {
        isLabDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

// Dropdown Labels
const selectedProgrammeLabel = computed(() => {
    if (!programmeFilter.value) return 'All Programmes';
    const p = props.availableProgrammes.find(x => x.id === programmeFilter.value);
    return p ? p.code : 'All Programmes';
});
const selectedCourseLabel = computed(() => {
    if (!courseFilter.value) return 'All Courses';
    const c = props.availableCourses.find(x => x.id === courseFilter.value);
    return c ? c.code : 'All Courses';
});
const selectedLabLabel = computed(() => {
    if (!labFilter.value) return 'All Classes';
    const l = props.availableLabs.find(x => x.id === labFilter.value);
    return l ? l.name : 'All Classes';
});

// Filter labs for dropdown based on course filter
const filteredLabsForDropdown = computed(() => {
    if (!courseFilter.value) return props.availableLabs;
    return props.availableLabs.filter(lab => lab.course_id === courseFilter.value);
});


const form = useForm({
    user_ids: [],
    course_id: '',
    lab_id: '',
});

const filteredFormLabs = computed(() => {
    if (!form.course_id) return [];
    return props.availableLabs.filter(lab => lab.course_id === form.course_id);
});

const filteredModalStudents = computed(() => {
    if (!studentSearchQuery.value) return [];
    const query = studentSearchQuery.value.toLowerCase();
    return props.students.filter(s =>
        !form.user_ids.includes(s.id) &&
        (s.name.toLowerCase().includes(query) || (s.student_id && s.student_id.toLowerCase().includes(query)))
    );
});

const getStudentName = (id) => {
    const student = props.students.find(s => s.id === id);
    return student ? student.name : 'Unknown';
};

const addStudentToForm = (student) => {
    if (!form.user_ids.includes(student.id)) {
        form.user_ids.push(student.id);
    }
    studentSearchQuery.value = '';
    showStudentDropdown.value = false;
};

const removeStudentFromForm = (id) => {
    form.user_ids = form.user_ids.filter(userId => userId !== id);
};

const filteredStudents = computed(() => {
    let result = props.students || [];

    if (programmeFilter.value) {
        result = result.filter(student => student.programme_id === programmeFilter.value);
    }

    if (courseFilter.value) {
        result = result.filter(student => {
            return student.course_enrollments?.some(enrollment => enrollment.course_id === courseFilter.value);
        });
    }

    if (labFilter.value) {
        result = result.filter(student => {
            return student.course_enrollments?.some(enrollment => enrollment.lab_id === labFilter.value);
        });
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(student => {
            const matchName = student.name?.toLowerCase().includes(query);
            const matchId = student.student_id?.toLowerCase().includes(query);
            const matchProg = student.programme?.code?.toLowerCase().includes(query) || student.programme?.name?.toLowerCase().includes(query);

            const matchCourse = student.course_enrollments?.some(enrollment =>
                enrollment.course?.code?.toLowerCase().includes(query) ||
                enrollment.course?.name?.toLowerCase().includes(query)
            );

            return matchName || matchId || matchProg || matchCourse;
        });
    }

    return result;
});

const openEnrollModal = (studentIds = []) => {
    form.reset();
    form.user_ids = studentIds;
    showEnrollModal.value = true;
};

const submitEnrollment = () => {
    form.post(route('admin.students.assign'), {
        preserveScroll: true,
        onSuccess: () => {
            showEnrollModal.value = false;
            form.reset();
        }
    });
};

const removeEnrollment = (id) => {
    if (!id) {
        alert("Error: Enrollment ID is missing.");
        return;
    }
    if (confirm('Are you sure you want to remove this student from the course?')) {
        router.post(`/admin/lecturers/assignment/${id}`, { preserveScroll: true });
    }
};
</script>
