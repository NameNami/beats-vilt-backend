<template>
    <AdminLayout>
        <Head title="BLE Ecosystem" />

        <div v-if="showEditModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-slate-50">
                    <div>
                        <h3 class="text-xl font-black text-slate-900">Beacon Configuration</h3>
                        <p class="text-xs font-mono text-gray-500 mt-1">{{ form.mac_address }}</p>
                    </div>
                    <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form @submit.prevent="submitEdit" class="p-6 space-y-5">

                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Assign to Room</label>
                        <select v-model="form.room_id" @change="handleRoomChange" class="w-full bg-[#f8fafc] border-gray-200 rounded-xl text-sm focus:ring-amber-600 focus:border-amber-600">
                            <option :value="null">-- No Room (Unassigned) --</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">If no room is selected, the status will automatically change to Unassigned.</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Device Status</label>
                        <select v-model="form.status" class="w-full bg-[#f8fafc] border-gray-200 rounded-xl text-sm focus:ring-amber-600 focus:border-amber-600" :disabled="form.room_id === null">
                            <option value="Online">🟢 Online (Broadcasting)</option>
                            <option value="Maintenance">🟠 Maintenance</option>
                            <option value="Offline">🔴 Offline</option>
                            <option value="Unassigned" v-if="form.room_id === null">🟡 Unassigned</option>
                        </select>
                    </div>

                    <div class="pt-4 flex justify-end gap-3 border-t border-gray-50">
                        <button type="button" @click="showEditModal = false" class="px-6 py-2.5 text-slate-600 font-bold hover:text-slate-800 transition">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-amber-700 text-white rounded-xl font-bold shadow-sm hover:bg-amber-800 transition disabled:opacity-50">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-4 mb-8">
            <div>
                <h2 class="text-4xl font-black text-slate-900 tracking-tight leading-tight">
                    BLE Device Ecosystem
                </h2>
                <p class="text-slate-600 mt-2 text-sm font-medium">Real-time monitoring of campus Bluetooth proximity assets.</p>
            </div>
            <div class="flex gap-3">
                <button @click="initiateScan" class="bg-amber-800 hover:bg-amber-900 text-white px-6 py-2.5 rounded-xl font-bold shadow-sm transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Scan for Devices
                </button>
            </div>
        </div>

        <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-teal-50 text-teal-800 rounded-xl border border-teal-100 font-medium flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ $page.props.flash.success }}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 lg:col-span-2 overflow-hidden flex flex-col">
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
                            <th class="px-6 py-4">Battery</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                        <tr v-for="beacon in beacons" :key="beacon.id" class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900 text-sm">{{ beacon.name }}</p>
                                <p class="text-[11px] font-mono text-gray-500 mt-0.5">{{ beacon.mac_address }}</p>
                            </td>

                            <td class="px-6 py-4">
                                <span v-if="beacon.room_name" class="font-medium text-slate-700 text-sm">{{ beacon.room_name }}</span>
                                <span v-else class="text-[11px] font-bold text-gray-400 italic">No Room Assigned</span>
                            </td>

                            <td class="px-6 py-4">
                                    <span v-if="beacon.status === 'Online'" class="px-3 py-1 bg-teal-50 text-teal-700 border border-teal-200 rounded-full text-[10px] font-bold tracking-widest uppercase flex items-center gap-1.5 w-fit">
                                        <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-pulse"></span> Online
                                    </span>
                                <span v-else-if="beacon.status === 'Unassigned'" class="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[10px] font-bold tracking-widest uppercase flex items-center gap-1.5 w-fit">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Unassigned
                                    </span>
                                <span v-else-if="beacon.status === 'Maintenance'" class="px-3 py-1 bg-orange-50 text-orange-700 border border-orange-200 rounded-full text-[10px] font-bold tracking-widest uppercase flex items-center gap-1.5 w-fit">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span> Maintenance
                                    </span>
                                <span v-else class="px-3 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-[10px] font-bold tracking-widest uppercase flex items-center gap-1.5 w-fit">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Offline
                                    </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2" v-if="beacon.battery !== null">
                                    <span class="text-sm font-bold text-slate-700" :class="{'text-rose-600': beacon.battery < 20}">{{ beacon.battery }}%</span>
                                </div>
                                <span v-else class="text-sm font-bold text-gray-400">-</span>
                            </td>

                            <td class="px-6 py-4 text-right space-x-2">
                                <button @click="openEditModal(beacon)" class="p-2 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition" title="Edit Beacon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <button v-if="beacon.room_id" @click="unassignRoom(beacon)" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Unassign Room">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
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

            <div class="flex flex-col gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex-1 flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-black text-slate-900">Zone Map</h3>
                        <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-md text-[9px] font-black tracking-widest uppercase">LIVE</span>
                    </div>

                    <div class="flex-1 bg-[#d1ebe8] rounded-xl relative overflow-hidden flex items-center justify-center border-4 border-white shadow-inner min-h-[160px]">
                        <div class="absolute inset-0 opacity-30" style="background-image: linear-gradient(#b2dfdb 1px, transparent 1px), linear-gradient(90deg, #b2dfdb 1px, transparent 1px); background-size: 20px 20px;"></div>
                        <div class="w-3/4 h-3/4 bg-[#e8f6f5] opacity-50 absolute border-2 border-teal-200"></div>
                        <div class="w-1/2 h-full bg-white opacity-40 absolute border-2 border-teal-100"></div>

                        <div class="absolute top-1/4 left-1/3 w-3 h-3 bg-teal-500 rounded-full border-2 border-white shadow-md animate-ping"></div>
                        <div class="absolute bottom-1/3 right-1/4 w-3 h-3 bg-amber-500 rounded-full border-2 border-white shadow-md"></div>
                        <div class="absolute top-1/2 left-1/2 w-3 h-3 bg-teal-500 rounded-full border-2 border-white shadow-md animate-ping"></div>
                    </div>

                    <div class="flex justify-between items-center mt-3 text-[10px] font-bold text-slate-400">
                        <span>0.5s Latency</span>
                        <span>Scale 1:500</span>
                    </div>
                </div>

                <div class="bg-amber-800 rounded-2xl shadow-sm p-6 relative overflow-hidden">
                    <div class="absolute -right-8 -bottom-8 opacity-10">
                        <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 10c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z"/></svg>
                    </div>
                    <p class="text-[10px] font-bold text-amber-200 uppercase tracking-widest mb-1 relative z-10">Network Density</p>
                    <p class="text-5xl font-black text-white relative z-10">94.2<span class="text-2xl text-amber-300">%</span></p>
                    <p class="text-[11px] text-amber-100 font-medium mt-1 relative z-10">Optimal coverage reached</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    beacons: Array,
    rooms: Array
});

// Computed properties for the top counters
const onlineCount = computed(() => props.beacons ? props.beacons.filter(b => b.status === 'Online').length : 0);
const unassignedCount = computed(() => props.beacons ? props.beacons.filter(b => b.status === 'Unassigned').length : 0);

const showEditModal = ref(false);

const form = useForm({
    id: null,
    mac_address: '',
    room_id: null,
    status: ''
});

const openEditModal = (beacon) => {
    form.id = beacon.id;
    form.mac_address = beacon.mac_address;
    form.room_id = beacon.room_id;
    form.status = beacon.status;
    showEditModal.value = true;
};

// Automatic status adjustment based on room selection
const handleRoomChange = () => {
    if (form.room_id === null) {
        form.status = 'Unassigned';
    } else {
        if (form.status === 'Unassigned') {
            form.status = 'Online';
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
