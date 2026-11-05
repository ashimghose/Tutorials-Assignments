<?php

use App\Support\Phone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile numbers now come with a country code (India, UAE, Bangladesh, …),
 * stored in E.164 form such as +919876543210. Numbers saved before this were
 * 10-digit Indian mobiles, so they get +91 in front.
 */
return new class extends Migration
{
    /**
     * table => [column, nullable]
     *
     * @var array<string, array{0: string, 1: bool}>
     */
    private const array COLUMNS = [
        'parents' => ['mobile', false],
        'academic_students' => ['mobile', false],
        'students' => ['mobile', true],
        'leads' => ['mobile', false],
        'academic_orders' => ['delivery_phone', true],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => [$column, $nullable]) {
            // SQLite ignores string lengths, and rebuilding its tables is not needed.
            if (DB::getDriverName() !== 'sqlite') {
                Schema::table($table, function (Blueprint $table) use ($column, $nullable) {
                    $table->string($column, 20)->nullable($nullable)->change();
                });
            }

            DB::table($table)->whereNotNull($column)->where($column, 'not like', '+%')->orderBy('id')
                ->each(function (object $row) use ($table, $column) {
                    $e164 = Phone::e164($row->{$column}, Phone::DEFAULT_COUNTRY);
                    if ($e164 !== null) {
                        DB::table($table)->where('id', $row->id)->update([$column => $e164]);
                    }
                });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => [$column]) {
            DB::table($table)->where($column, 'like', '+91%')->orderBy('id')
                ->each(function (object $row) use ($table, $column) {
                    DB::table($table)->where('id', $row->id)->update([$column => substr($row->{$column}, 3)]);
                });
        }
        // Other countries' numbers keep their + form; the wider columns stay.
    }
};
