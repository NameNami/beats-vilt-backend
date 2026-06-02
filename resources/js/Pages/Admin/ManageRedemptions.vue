<template>
    <AdminLayout>
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-2xl font-semibold mb-2 text-gray-900">Redemption Management</h1>
                <p class="text-slate-600 text-sm font-medium">Manage rewards and track student redemptions.</p>
            </div>
        </div>

        <!-- Rewards Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            <!-- Create/Edit Reward Form -->
            <div class="bg-white p-6 rounded-2xl  border border-gray-100 col-span-1 h-fit">
                <h2 class="text-lg font-bold text-slate-900 mb-5 border-b border-slate-100 pb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                    </svg>
                    {{ isEditingReward ? 'Edit Reward' : 'Create New Reward' }}
                </h2>

                <form @submit.prevent="submitReward" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Reward Name</label>
                        <input type="text" v-model="rewardForm.name" placeholder="e.g. Starbucks Voucher" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg  focus:ring-indigo-600 focus:border-indigo-600 text-sm py-2.5" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">XP Cost</label>
                            <input type="number" v-model="rewardForm.cost_points" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg  focus:ring-indigo-600 focus:border-indigo-600 text-sm py-2.5" required min="0">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Stock</label>
                            <input type="number" v-model="rewardForm.stock" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg  focus:ring-indigo-600 focus:border-indigo-600 text-sm py-2.5" required min="0">
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="rewardForm.is_active" id="is_active" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                        <label for="is_active" class="text-sm font-medium text-slate-700">Active and available for redemption</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 mt-4">
                        <button v-if="isEditingReward" type="button" @click="cancelEditReward" class="bg-gray-100 hover:bg-gray-200 text-slate-700 px-4 py-2 rounded-lg font-bold transition text-sm">
                            Cancel
                        </button>
                        <button type="submit" :disabled="rewardForm.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg font-bold transition disabled:opacity-50 w-full  text-sm">
                            {{ isEditingReward ? 'Update Reward' : 'Save Reward' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Rewards List -->
            <div class="bg-white rounded-xl  border border-gray-200 overflow-hidden col-span-1 lg:col-span-2">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
                    <h2 class="text-lg font-bold text-slate-900">Available Rewards</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100 text-[11px] uppercase tracking-widest text-gray-500 font-bold">
                                <th class="px-6 py-4">Reward</th>
                                <th class="px-6 py-4">Cost</th>
                                <th class="px-6 py-4">Stock</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="reward in rewards" :key="reward.id" class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4 font-bold text-slate-900">{{ reward.name }}</td>
                                <td class="px-6 py-4 font-medium text-slate-700">{{ reward.cost_points }} XP</td>
                                <td class="px-6 py-4">
                                    <span :class="reward.stock > 0 ? 'text-slate-700' : 'text-rose-600 font-bold'">{{ reward.stock }} remaining</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="reward.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-200'" class="px-3 py-1 border rounded-full text-[10px] font-bold tracking-widest uppercase">
                                        {{ reward.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <button @click="editReward(reward)" class="text-slate-400 hover:text-indigo-600 transition">
                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>
                                    <button @click="deleteReward(reward.id)" class="text-slate-400 hover:text-rose-600 transition">
                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="rewards.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium">No rewards found. Add your first reward!</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Redemptions Section -->
        <div class="bg-white rounded-xl  border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
                <h2 class="text-lg font-bold text-slate-900">Recent Redemptions</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100 text-[11px] uppercase tracking-widest text-gray-500 font-bold">
                            <th class="px-6 py-4">Student</th>
                            <th class="px-6 py-4">Reward</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="redemption in redemptions" :key="redemption.id" class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-bold text-slate-900">{{ redemption.user?.name }}</p>
                                    <p class="text-[11px] text-gray-500 font-medium">{{ redemption.user?.student_id }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-700">{{ redemption.reward?.name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ formatDate(redemption.created_at) }}</td>
                            <td class="px-6 py-4">
                                <span :class="statusClass(redemption.status)" class="px-3 py-1 border rounded-full text-[10px] font-bold tracking-widest uppercase">
                                    {{ redemption.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <select @change="updateStatus(redemption, $event.target.value)" class="bg-white border-gray-200 rounded-lg text-xs py-1 px-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="" disabled selected>Change Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="collected">Collected</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </td>
                        </tr>
                        <tr v-if="redemptions.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium">No redemptions found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    rewards: Array,
    redemptions: Array,
});

const isEditingReward = ref(false);

const rewardForm = useForm({
    id: null,
    name: '',
    cost_points: 0,
    stock: 0,
    is_active: true,
});

const submitReward = () => {
    if (isEditingReward.value) {
        rewardForm.post(`/admin/rewards/update/${rewardForm.id}`, {
            preserveScroll: true,
            onSuccess: () => resetRewardForm()
        });
    } else {
        rewardForm.post('/admin/rewards', {
            preserveScroll: true,
            onSuccess: () => resetRewardForm()
        });
    }
};

const editReward = (reward) => {
    isEditingReward.value = true;
    rewardForm.id = reward.id;
    rewardForm.name = reward.name;
    rewardForm.cost_points = reward.cost_points;
    rewardForm.stock = reward.stock;
    rewardForm.is_active = reward.is_active;
    rewardForm.clearErrors();
};

const deleteReward = (id) => {
    if (confirm('Are you sure you want to delete this reward?')) {
        router.post(`/admin/rewards/delete/${id}`, { preserveScroll: true });
    }
};

const cancelEditReward = () => {
    resetRewardForm();
};

const resetRewardForm = () => {
    isEditingReward.value = false;
    rewardForm.id = null;
    rewardForm.reset();
    rewardForm.clearErrors();
};

const updateStatus = (redemption, status) => {
    router.post(`/admin/redemptions/status/${redemption.id}`, { status }, { preserveScroll: true });
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const statusClass = (status) => {
    switch (status) {
        case 'pending': return 'bg-amber-50 text-amber-700 border-amber-100';
        case 'approved': return 'bg-blue-50 text-blue-700 border-blue-100';
        case 'collected': return 'bg-emerald-50 text-emerald-700 border-emerald-100';
        case 'rejected': return 'bg-rose-50 text-rose-700 border-rose-100';
        default: return 'bg-slate-100 text-slate-600 border-slate-200';
    }
};
</script>
