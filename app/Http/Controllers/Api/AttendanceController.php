<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Beacon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function checkInBle(Request $request)
    {

        // TODO: make sure log history
        // TODO: make sure compare uuid and rssi
        // TODO: make sure guna service arrival classification
        // TODO: make sure guna service arrival timeframe
        $request->validate([
            'timestamp' => 'required|string', // timestamp untuk compare dgn timeframe kelas
            'uuid' => 'required|string', // beacon punye uuid untuk make sure checkin kelas yg betul gitu
            'rssi' => 'required|integer', // double check rssi dia beacon nk elak fraud
        ]);



        $beacon = Beacon::findOrFail($request->id); // cari beacon guna id
        $beacon->update(['last_seen' => now()]); // update latest time jadi cron job tau dia still online
        return response()->json(['uuid' => $beacon->uuid]); // return latest uuid
    }
}
