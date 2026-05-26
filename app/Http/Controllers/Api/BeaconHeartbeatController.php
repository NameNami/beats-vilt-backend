<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Beacon;
use Illuminate\Http\Request;

class BeaconHeartbeatController extends Controller
{
    public function heartbeat(Request $request)
    {
        $request->validate([
            'mac_address' => 'required|string',
        ]);

        $beacon = Beacon::where('mac_address', $request->mac_address)->first();

        if (!$beacon) {
            $beacon = Beacon::create([
                'mac_address' => $request->mac_address,
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'status' => 'unassigned',
                'last_seen' => now(),
            ]);

            return response()->json(['uuid' => 'pending_uuid']);
        }

        $beacon->update(['last_seen' => now()]);

        return response()->json(['uuid' => $beacon->uuid]);
    }
}
