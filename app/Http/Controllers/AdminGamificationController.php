<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\Redemption;
use App\Models\Reward;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminGamificationController extends Controller
{
    /**
     * Badge Management
     */
    public function manageBadges()
    {
        return Inertia::render('Admin/ManageBadges', [
            'badges' => Badge::all(),
        ]);
    }

    public function storeBadge(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'icon_path' => 'nullable|string',
            'type' => 'required|in:achievement,streak,xp',
            'requirement_type' => 'required|in:present_checkins,on_time_checkins,streak_count,total_xp',
            'requirement_value' => 'required|integer|min:1',
        ]);

        Badge::create($validated);

        return back()->with('success', 'Badge created successfully.');
    }

    public function updateBadge(Request $request, $id)
    {
        $badge = Badge::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'icon_path' => 'nullable|string',
            'type' => 'required|in:achievement,streak,xp',
            'requirement_type' => 'required|in:present_checkins,on_time_checkins,streak_count,total_xp',
            'requirement_value' => 'required|integer|min:1',
        ]);

        $badge->update($validated);

        return back()->with('success', 'Badge updated successfully.');
    }

    public function deleteBadge($id)
    {
        Badge::findOrFail($id)->delete();

        return back()->with('success', 'Badge deleted successfully.');
    }

    /**
     * Reward & Redemption Management
     */
    public function manageRedemptions()
    {
        return Inertia::render('Admin/ManageRedemptions', [
            'rewards' => Reward::all(),
            'redemptions' => Redemption::with(['user', 'reward'])->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function storeReward(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cost_points' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        Reward::create($validated);

        return back()->with('success', 'Reward created successfully.');
    }

    public function updateReward(Request $request, $id)
    {
        $reward = Reward::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cost_points' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $reward->update($validated);

        return back()->with('success', 'Reward updated successfully.');
    }

    public function deleteReward($id)
    {
        Reward::findOrFail($id)->delete();

        return back()->with('success', 'Reward deleted successfully.');
    }

    public function updateRedemptionStatus(Request $request, $id)
    {
        $redemption = Redemption::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,collected',
        ]);

        $redemption->update($validated);

        return back()->with('success', 'Redemption status updated.');
    }
}
