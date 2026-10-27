<?php

namespace App\Models;

use App\Enums\AcademicLevel;
use Database\Factories\AcademicOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * ACA-4: an academic student's order. Prices, status, expert, payment and
 * revision counts are set by the server only, never mass-assigned.
 *
 * @property AcademicLevel $level
 * @property Carbon $deadline
 * @property bool $is_quote
 * @property int|null $quote_amount
 * @property Carbon|null $quote_expires_at
 * @property int|null $subtotal
 * @property int|null $gst
 * @property int|null $total
 * @property int $gst_percent
 * @property int $revisions_allowed
 * @property int $revisions_used
 * @property Carbon|null $paid_at
 * @property Carbon|null $completed_at
 */
#[Fillable(['university', 'course', 'stream', 'topic', 'deadline', 'pages', 'words', 'notes', 'delivery_name', 'delivery_phone', 'delivery_address', 'delivery_city', 'delivery_state', 'delivery_pincode'])]
class AcademicOrder extends Model
{
    /** @use HasFactory<AcademicOrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'level' => AcademicLevel::class,
            'deadline' => 'date',
            'is_quote' => 'boolean',
            'quote_amount' => 'integer',
            'quote_expires_at' => 'date',
            'subtotal' => 'integer',
            'gst' => 'integer',
            'total' => 'integer',
            'gst_percent' => 'integer',
            'revisions_allowed' => 'integer',
            'revisions_used' => 'integer',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AcademicStudent, $this>
     */
    public function academicStudent(): BelongsTo
    {
        return $this->belongsTo(AcademicStudent::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<OrderStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function expert(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'expert_id');
    }

    /**
     * @return HasMany<AcademicOrderAddon, $this>
     */
    public function addons(): HasMany
    {
        return $this->hasMany(AcademicOrderAddon::class);
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return HasMany<OrderFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class);
    }

    public function reference(): string
    {
        return 'A-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /**
     * ACA-5: a quote the student can still accept.
     */
    public function hasOpenQuote(): bool
    {
        return $this->is_quote && $this->quote_amount !== null && ! $this->isPaid()
            && ($this->quote_expires_at === null || $this->quote_expires_at->toDateString() >= now(config('app.display_timezone'))->toDateString());
    }

    /**
     * The amount due: the price list total, or the quote with GST.
     */
    public function amountDue(): ?int
    {
        return $this->total;
    }

    public function revisionsLeft(): int
    {
        return max(0, $this->revisions_allowed - $this->revisions_used);
    }

    public function needsDelivery(): bool
    {
        return $this->addons->contains(fn (AcademicOrderAddon $addon) => $addon->kind->needsAddress());
    }

    public function isClosed(): bool
    {
        return $this->status->is_final;
    }
}
