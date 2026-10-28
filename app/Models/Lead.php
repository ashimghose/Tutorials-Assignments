<?php

namespace App\Models;

use App\Enums\LeadKind;
use App\Enums\LeadStatus;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * CMS-3, MKT-3: an enquiry, demo booking or waitlist sign-up from a site
 * form, with the campaign it came from. Personal data: never log it.
 *
 * @property LeadKind $kind
 * @property LeadStatus $status
 * @property bool $marketing_consent
 * @property Carbon|null $became_customer_at
 */
#[Fillable(['name', 'mobile', 'email', 'interest', 'class_or_service', 'message', 'price_range', 'marketing_consent'])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kind' => LeadKind::class,
            'status' => LeadStatus::class,
            'marketing_consent' => 'boolean',
            'became_customer_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
