<?php

namespace App\Actions\Orders;

use App\Enums\AcademicLevel;
use App\Enums\OrderFileKind;
use App\Models\AcademicOrder;
use App\Models\AcademicStudent;
use App\Models\OrderStatus;
use App\Models\Service;
use App\Models\User;
use App\Notifications\NewAcademicOrder;
use App\Notifications\OrderReceived;
use App\Services\Pricing;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * ACA-4, ACA-5: creates an order from validated form data. The price is
 * worked out here from the database; a quote request waits for the admin's
 * price. The order starts in the first status ("Awaiting payment").
 */
class PlaceOrder
{
    public function __construct(private Pricing $pricing, private StoreOrderFile $storeFile) {}

    /**
     * @param  array<string, mixed>  $data  validated order form
     * @param  array<int|string, int|string|null>  $quantities  add-on id => quantity
     * @param  list<UploadedFile>  $uploads
     * @param  array{source?: string, medium?: string, campaign?: string}  $utm
     */
    public function handle(AcademicStudent $student, Service $service, AcademicLevel $level, bool $quote, array $data, array $quantities, array $uploads, array $utm = []): AcademicOrder
    {
        $order = DB::transaction(function () use ($student, $service, $level, $quote, $data, $quantities, $utm) {
            $price = $quote ? null : $this->pricing->forService($service, $level, $quantities);
            // On a quote request the add-ons are priced from the list; the admin's quote covers the job.
            $lines = $price !== null ? $price->addons : $this->pricing->addonLines($service, $quantities);

            $order = new AcademicOrder(Arr::only($data, (new AcademicOrder)->getFillable()));
            $order->forceFill([
                'academic_student_id' => $student->id,
                'service_id' => $service->id,
                'service_title' => $service->title,
                'level' => $level,
                'is_quote' => $quote,
                'subtotal' => $price?->subtotal,
                'gst_percent' => $price->gstPercent ?? 0,
                'gst' => $price?->gst,
                'total' => $price?->total,
                'order_status_id' => OrderStatus::initial()->id,
                'revisions_allowed' => $service->free_revisions,
                'utm_source' => $utm['source'] ?? null,
                'utm_medium' => $utm['medium'] ?? null,
                'utm_campaign' => $utm['campaign'] ?? null,
            ])->save();

            foreach ($lines as $line) {
                $order->addons()->make()->forceFill([
                    'service_addon_id' => $line['addon']->id,
                    'name' => $line['addon']->name,
                    'kind' => $line['addon']->kind,
                    'unit_price' => $line['addon']->price,
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ])->save();
            }

            $order->history()->make()->forceFill([
                'to_status_id' => $order->order_status_id,
                'changed_by' => $student->user_id,
                'note' => $quote ? __('Quote requested') : __('Order placed'),
                'created_at' => now(),
            ])->save();

            return $order;
        });

        foreach ($uploads as $upload) {
            $this->storeFile->handle($order, $upload, OrderFileKind::Brief, $student->user);
        }

        Notification::send(User::query()->admins()->get(), new NewAcademicOrder($order));
        $student->user->notify(new OrderReceived($order));

        return $order;
    }
}
