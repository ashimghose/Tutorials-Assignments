<?php

namespace App\Models;

use App\Enums\OrderFileKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A private file on an order: the student's brief, or the expert's draft or
 * final. Downloaded only through a short-lived signed link after a policy
 * check, and deleted after the retention period.
 *
 * @property OrderFileKind $kind
 * @property int $size
 * @property Carbon|null $delete_after
 */
class OrderFile extends Model
{
    protected static function booted(): void
    {
        static::deleting(fn (OrderFile $file) => Storage::disk($file->disk)->delete($file->path));
    }

    protected function casts(): array
    {
        return [
            'kind' => OrderFileKind::class,
            'size' => 'integer',
            'delete_after' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AcademicOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(AcademicOrder::class, 'academic_order_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
