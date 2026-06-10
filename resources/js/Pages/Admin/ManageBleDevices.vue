<template>
    <AdminLayout>
        <Head title="BLE Ecosystem" />

        <div v-if="showEditModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-slate-50">
                    <div>
                        <h3 class="text-xl font-black text-slate-900">Beacon Configuration</h3>
                        <p class="text-xs font-mono text-gray-500 mt-1">{{ form.mac_address }}</p>
                    </div>
                    <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <X class="w-6 h-6" />
                    </button>
                </div>
                <form @submit.prevent="submitEdit" class="p-6 space-y-5">

                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Assign to Room</label>
                        <select v-model="form.room_id" @change="handleRoomChange" class="w-full bg-[#f8fafc] border-gray-200 rounded-xl text-sm focus:ring-amber-600 focus:border-amber-600 cursor-pointer">
                            <option :value="null">-- No Room (Unassigned) --</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">If no room is selected, the status will automatically change to Unassigned.</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Device Status</label>
                        <select v-model="form.status" class="w-full bg-[#f8fafc] border-gray-200 rounded-xl text-sm focus:ring-amber-600 focus:border-amber-600 cursor-pointer" :disabled="form.room_id === null">
                            <option value="active">🟢 Online (Broadcasting)</option>
                            <option value="maintenance">🟠 Maintenance</option>
                            <option value="inactive">🔴 Offline</option>
                            <option value="unassigned" v-if="form.room_id === null">🟡 Unassigned</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Check-in RSSI Threshold (dBm)</label>
                        <input type="number" v-model="form.rssi_threshold" min="-100" max="0" step="1"
                               class="w-full bg-[#f8fafc] border-gray-200 rounded-xl text-sm focus:ring-amber-600 focus:border-amber-600 font-mono font-bold"
                               placeholder="e.g. -55">
                        <p class="text-[10px] text-gray-400 mt-2">Determines how close a student must be to check in. Valid range: -100 to 0. (Higher values like -50 require being closer than -80).</p>
                    </div>

                    <div class="pt-4 flex justify-end gap-3 border-t border-gray-50">
                        <button type="button" @click="showEditModal = false" class="px-6 py-2.5 text-slate-600 font-bold hover:text-slate-800 transition cursor-pointer">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-amber-700 text-white rounded-xl font-bold  hover:bg-amber-800 transition disabled:opacity-50 cursor-pointer">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-semibold mb-2 text-gray-900">BLE Beacons</h1>
                <p class="text-slate-600 text-sm font-medium">Real-time monitoring of campus Bluetooth proximity assets.</p>
            </div>
        </div>

        <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-teal-50 text-teal-800 rounded-xl border border-teal-100 font-medium flex items-center gap-2 ">
            <CheckCircle class="w-5 h-5 text-teal-600" />
            {{ $page.props.flash.success }}
        </div>

        <div class="grid grid-cols-1 gap-6 mb-6">

            <div class="bg-white rounded-2xl  border border-gray-100 overflow-hidden flex flex-col">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-black text-slate-900">Transmitter Management</h3>
                    <div class="flex gap-2">
                        <span class="px-3 py-1 bg-sky-100 text-sky-800 rounded-full text-[10px] font-bold tracking-widest uppercase">{{ onlineCount }} ONLINE</span>
                        <span class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-[10px] font-bold tracking-widest uppercase">{{ unassignedCount }} UNASSIGNED</span>
                    </div>
                </div>
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                        <tr class="bg-gray-50/50 text-[10px] uppercase tracking-widest text-gray-400 font-bold border-b border-gray-100">
                            <th class="px-6 py-4">Device ID / MAC</th>
                            <th class="px-6 py-4">Room Allocation</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Check-in Threshold</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                        <tr v-for="beacon in beacons" :key="beacon.id" class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center border border-slate-100">
                                        <Radio class="w-5 h-5 text-slate-400" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900 text-sm">{{ beacon.mac_address }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span v-if="beacon.room_name" class="font-medium text-slate-700 text-sm">{{ beacon.room_name }}</span>
                                <span v-else class="text-[11px] font-bold text-gray-400 italic">No Room Assigned</span>
                            </td>

                            <td class="px-6 py-4">
                                    <span v-if="beacon.status === 'active'" class="px-3 py-1 bg-teal-50 text-teal-700 border border-teal-200 rounded-full text-[10px] font-bold tracking-widest uppercase flex items-center gap-1.5 w-fit">
                                        <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-pulse"></span> Online
                                    </span>
                                <span v-else-if="beacon.status === 'unassigned'" class="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[10px] font-bold tracking-widest uppercase flex items-center gap-1.5 w-fit">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Unassigned
                                    </span>
                                <span v-else-if="beacon.status === 'maintenance'" class="px-3 py-1 bg-orange-50 text-orange-700 border border-orange-200 rounded-full text-[10px] font-bold tracking-widest uppercase flex items-center gap-1.5 w-fit">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span> Maintenance
                                    </span>
                                <span v-else class="px-3 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-[10px] font-bold tracking-widest uppercase flex items-center gap-1.5 w-fit">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Offline
                                    </span>
                                <p class="text-[9px] text-gray-400 mt-1 font-medium" v-if="beacon.last_seen">Seen {{ beacon.last_seen }}</p>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-700">{{ beacon.rssi_threshold }} <span class="text-[10px] text-gray-400">dBm</span></span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-right space-x-2">
                                <button @click="openEditModal(beacon)" class="p-2 text-slate-400 cursor-pointer hover:text-sky-600  rounded-lg transition" title="Edit Beacon">
                                    <Pencil class="w-5 h-5 inline" />
                                </button>
                                <button v-if="beacon.room_id" @click="unassignRoom(beacon)" class="p-2 cursor-pointer text-slate-400 hover:text-rose-600  rounded-lg transition" title="Unassign Room">
                                    <Link2Off class="w-5 h-5 inline" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!beacons || beacons.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium">No beacons registered in the system yet.</td>
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
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { X, CheckCircle, RefreshCw, Radio, Pencil, Link2Off } from 'lucide-vue-next';

const props = defineProps({
    beacons: Array,
    rooms: Array
});

// Polling for real-time updates
let pollingInterval = null;

onMounted(() => {
    pollingInterval = setInterval(() => {
        router.reload({
            only: ['beacons'],
            preserveScroll: true,
            preserveState: true
        });
    }, 3000);
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});

// Computed properties for the top counters
const onlineCount = computed(() => props.beacons ? props.beacons.filter(b => b.status === 'active').length : 0);
const unassignedCount = computed(() => props.beacons ? props.beacons.filter(b => b.status === 'unassigned').length : 0);

const showEditModal = ref(false);

const form = useForm({
    id: null,
    mac_address: '',
    room_id: null,
    status: '',
    rssi_threshold: -55
});

const openEditModal = (beacon) => {
    form.id = beacon.id;
    form.mac_address = beacon.mac_address;
    form.room_id = beacon.room_id;
    form.status = beacon.status;
    form.rssi_threshold = beacon.rssi_threshold;
    showEditModal.value = true;
};

// Automatic status adjustment based on room selection
const handleRoomChange = () => {
    if (form.room_id === null) {
        form.status = 'unassigned';
    } else {
        if (form.status === 'unassigned') {
            form.status = 'active';
        }
    }
};

// Inertia put request to unassign the room quickly
const unassignRoom = (beacon) => {
    if (confirm(`Are you sure you want to remove ${beacon.name} from ${beacon.room_name}? The device will become Unassigned.`)) {
        router.put(`/admin/ble-devices/${beacon.id}/unassign`, {}, {
            preserveScroll: true
        });
    }
};

const initiateScan = () => {
    router.post('/admin/ble-devices/scan', {}, {
        preserveScroll: true
    });
};

// Inertia put request to save modal changes
const submitEdit = () => {
    form.put(`/admin/ble-devices/${form.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
        }
    });
};
</script>
