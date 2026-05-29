<?php

namespace App\Http\Controllers;

use App\Models\Beacon;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminBleDeviceController extends Controller
{
    public function index()
    {
        $beacons = Beacon::with('room')->get()->map(function ($beacon) {
            return [
                'id' => $beacon->id,
                'name' => $beacon->name ?? 'Unnamed Beacon',
                'mac_address' => $beacon->mac_address,
                'room_id' => $beacon->room_id,
                'room_name' => $beacon->room?->name,
                'status' => $beacon->status,
                'battery' => $beacon->battery,
            ];
        });

        $rooms = Room::select('id', 'name')->get();

        return Inertia::render('Admin/ManageBleDevices', [
            'beacons' => $beacons,
            'rooms' => $rooms,
        ]);
    }

    public function update(Request $request, Beacon $beacon)
    {
        $validated = $request->validate([
            'room_id' => 'nullable|exists:rooms,id',
            'status' => 'required|string|in:Online,Offline,Maintenance,Unassigned',
        ]);

        // If room_id is null, force status to Unassigned (matching frontend logic)
        if ($validated['room_id'] === null) {
            $validated['status'] = 'Unassigned';
        }

        $beacon->update($validated);

        return back()->with('success', "Beacon {$beacon->mac_address} updated successfully.");
    }

    public function unassign(Beacon $beacon)
    {
        $beacon->update([
            'room_id' => null,
            'status' => 'Unassigned',
        ]);

        return back()->with('success', "Beacon {$beacon->mac_address} unassigned successfully.");
    }

    public function scan()
    {
        // This would normally trigger a physical scan or update from a gateway
        // For now, we'll just return with a message
        return back()->with('success', 'Scan initiated. Device list will be updated shortly.');
    }
}
