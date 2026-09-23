<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ExpireTemporaryVbps extends Command
{
    protected $signature = 'vbp:expire-temporary';

    protected $description = 'Deactivate Temporary VBP accounts whose three months ran out without converting';

    public function handle(): int
    {
        $expired = User::query()
            ->where('is_temporary_vbp', true)
            ->where('is_active', true)
            ->where('temporary_vbp_expires_at', '<=', now())
            ->update(['is_active' => false]);

        $this->info("Deactivated {$expired} Temporary VBP account(s).");

        return self::SUCCESS;
    }
}
