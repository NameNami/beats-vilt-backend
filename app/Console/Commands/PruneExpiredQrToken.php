<?php

namespace App\Console\Commands;

use App\Models\QrToken;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('attendance:prune-expired-qr-token')]
#[Description('Prune Expired QR Tokens')]
class PruneExpiredQrToken extends Command
{
    public function handle()
    {
        QrToken::where('expires_at', '<', now())->delete();
        $this->info('Expired QR tokens have been pruned.');
    }
}
