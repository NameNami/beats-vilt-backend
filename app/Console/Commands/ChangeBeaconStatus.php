<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use App\Models\Beacon;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

#[Signature('app:change-beacon-status')]
#[Description('Update beacon status to inactive if last_seen > 5 minutes, and reactive if seen recently.')]
class ChangeBeaconStatus extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = now()->subMinutes(5);

        // 1. Deactivate beacons that haven't been seen for > 5 minutes
        $offlineBeacons = Beacon::where('status', 'active')
            ->where('last_seen', '<', $threshold)
            ->get();

        foreach ($offlineBeacons as $beacon) {
            $beacon->update(['status' => 'inactive']);

            // Notify all admins
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'title'   => 'Beacon Offline',
                    'body'    => "Beacon with MAC {$beacon->mac_address} has gone offline. Last seen: {$beacon->last_seen->format('Y-m-d H:i:s')}",
                    'type'    => 'risk',
                ]);
            }

            $this->info("Beacon {$beacon->mac_address} set to inactive.");
        }

        // 2. Reactivate beacons that have been seen within the last 5 minutes
        $reactivatedCount = Beacon::where('status', 'inactive')
            ->where('last_seen', '>=', $threshold)
            ->update(['status' => 'active']);

        if ($reactivatedCount > 0) {
            $this->info("{$reactivatedCount} beacons reactivated.");
        }
    }
}
