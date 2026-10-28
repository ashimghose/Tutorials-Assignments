<?php

namespace App\Models;

use App\Observers\AuditAdminChanges;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * MKT-6: a resources/blog guide, written in Markdown.
 *
 * @property Carbon $published_on
 * @property bool $is_published
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['slug', 'title', 'description', 'offering', 'body', 'published_on', 'is_published'])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'published_on' => 'date',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->whereDate('published_on', '<=', now());
    }
}
