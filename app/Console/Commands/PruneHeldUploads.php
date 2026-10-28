<?php

namespace App\Console\Commands;

use App\Support\HeldUploads;
use Illuminate\Console\Command;

/** Hourly: deletes files held for a form that came back with an error and was never sent again. */
class PruneHeldUploads extends Command
{
    protected $signature = 'app:prune-held-uploads';

    protected $description = 'Delete attachments held for unfinished forms after '.HeldUploads::HOURS.' hours';

    public function handle(): int
    {
        $this->info(HeldUploads::prune().' held file(s) deleted.');

        return self::SUCCESS;
    }
}
