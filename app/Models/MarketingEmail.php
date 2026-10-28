<?php

namespace App\Models;

use App\Enums\MarketingAudience;
use App\Observers\AuditAdminChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * NOT-3: a newsletter or announcement, sent only to people who opted in,
 * each with a one-click unsubscribe link.
 *
 * @property MarketingAudience $audience
 * @property Carbon|null $sent_at
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['audience', 'subject', 'body'])]
class MarketingEmail extends Model
{
    protected function casts(): array
    {
        return [
            'audience' => MarketingAudience::class,
            'sent_at' => 'datetime',
        ];
    }
}
