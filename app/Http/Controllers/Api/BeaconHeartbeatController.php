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
            'id' => 'required|string', // ni primary key
        ]);

        $beacon = Beacon::findOrFail($request->id); // cari beacon guna id
        $beacon->update(['last_seen' => now()]); // update latest time jadi cron job tau dia still online
        return response()->json(['uuid' => $beacon->uuid]); // return latest uuid
    }
}
