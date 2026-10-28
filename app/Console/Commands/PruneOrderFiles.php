<?php

namespace App\Console\Commands;

use App\Models\OrderFile;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Privacy, run daily: deletes order files whose retention period has
 * ended (the clock starts when the order is finished or cancelled).
 */
#[Signature('app:prune-order-files')]
#[Description('Delete order files past their retention date')]
class PruneOrderFiles extends Command
{
    public function handle(): int
    {
        $deleted = 0;
        OrderFile::query()->where('delete_after', '<', now())->each(function (OrderFile $file) use (&$deleted) {
            $file->delete();
            $deleted++;
        });

        $this->info("Files deleted: {$deleted}.");

        return self::SUCCESS;
    }
}
