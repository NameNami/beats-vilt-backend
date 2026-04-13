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
            'id' => 'required|string',
        ]);

        $beacon = Beacon::findOrFail($request->id);
        $beacon->update(['last_seen' => now()]);
        return response()->json(['message' => $beacon->uuid]);
    }
}
