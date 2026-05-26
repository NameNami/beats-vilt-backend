<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use App\Models\Beacon;
use Illuminate\Support\Str;

#[Signature('app:renew-beacon-uuid')]
#[Description('Renew UUID for all active beacons every 5 minutes.')]
class RenewBeaconUUID extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $activeBeacons = Beacon::where('status', 'active')->get();

        foreach ($activeBeacons as $beacon) {
            $newUuid = (string) Str::uuid();
            $beacon->update(['uuid' => $newUuid]);
            $this->info("Renewed UUID for Beacon {$beacon->mac_address}: {$newUuid}");
        }

        $this->info("Successfully renewed UUIDs for " . $activeBeacons->count() . " active beacons.");
    }
}
