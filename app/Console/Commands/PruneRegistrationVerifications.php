<?php

namespace App\Console\Commands;

use App\Models\RegistrationVerification;
use Illuminate\Console\Command;

class PruneRegistrationVerifications extends Command
{
    protected $signature = 'verification:prune';

    protected $description = 'Delete expired pending registration verification challenges';

    public function handle(): int
    {
        $deleted = RegistrationVerification::query()
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("Pruned {$deleted} expired registration verification challenge(s).");

        return self::SUCCESS;
    }
}
