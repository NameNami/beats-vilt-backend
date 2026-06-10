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
                'rssi_threshold' => $beacon->rssi_threshold,
                'last_seen' => $beacon->last_seen?->diffForHumans(),
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
            'status' => 'required|string|in:active,inactive,maintenance,unassigned',
            'rssi_threshold' => 'required|integer|between:-100,0',
        ]);

        // If room_id is null, force status to unassigned (matching frontend logic)
        if ($validated['room_id'] === null) {
            $validated['status'] = 'unassigned';
        }

        $beacon->update($validated);

        return back()->with('success', "Beacon {$beacon->mac_address} updated successfully.");
    }

    public function unassign(Beacon $beacon)
    {
        $beacon->update([
            'room_id' => null,
            'status' => 'unassigned',
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
