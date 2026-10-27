<?php

namespace App\Enums;

enum EnrolmentStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting for fee',
            self::Active => 'Active',
            self::Expired => 'Paused (fee due)',
            self::Removed => 'Removed',
        };
    }

    /**
     * Filament colour name.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Active => 'success',
            self::Expired => 'danger',
            self::Removed => 'gray',
        };
    }

    /**
     * Flux badge colour on the portal pages.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Active => 'green',
            self::Expired => 'red',
            self::Removed => 'zinc',
        };
    }

    /**
     * Statuses that hold a seat in the batch.
     *
     * @return list<self>
     */
    public static function holdingSeat(): array
    {
        return [self::Pending, self::Active];
    }
}
