<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Enums\Offering;
use App\Observers\AuditAdminChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A printing, courier or other cost, for the money report (ADM-4).
 *
 * @property Offering $offering
 * @property ExpenseCategory $category
 * @property int $amount
 * @property Carbon $spent_on
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['offering', 'category', 'academic_order_id', 'amount', 'spent_on', 'note'])]
class Expense extends Model
{
    protected function casts(): array
    {
        return [
            'offering' => Offering::class,
            'category' => ExpenseCategory::class,
            'amount' => 'integer',
            'spent_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<AcademicOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(AcademicOrder::class, 'academic_order_id');
    }
}
