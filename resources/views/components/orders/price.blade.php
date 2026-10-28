@props(['order'])

{{-- PAY-1: the itemised price worked out on the server. --}}
@php($money = fn ($paise) => \App\Support\Money::format((int) $paise))
<dl {{ $attributes->class('space-y-1 text-sm') }}>
    @if ($order->is_quote)
        <div class="flex justify-between gap-4"><dt>{{ $order->service_title }} ({{ __('quote') }})</dt><dd>{{ $order->quote_amount !== null ? $money($order->quote_amount) : __('Waiting for our quote') }}</dd></div>
    @else
        <div class="flex justify-between gap-4"><dt>{{ $order->service_title }} · {{ $order->level->label() }}</dt><dd>{{ $money($order->subtotal - $order->addons->sum('line_total')) }}</dd></div>
    @endif
    @foreach ($order->addons as $addon)
        <div class="flex justify-between gap-4"><dt>{{ $addon->name }} × {{ $addon->quantity }}</dt><dd>{{ $money($addon->line_total) }}</dd></div>
    @endforeach
    @if ($order->total !== null)
        @if ($order->gst)
            <div class="flex justify-between gap-4 text-zinc-600"><dt>{{ __('GST (:percent%)', ['percent' => $order->gst_percent]) }}</dt><dd>{{ $money($order->gst) }}</dd></div>
        @endif
        <div class="flex justify-between gap-4 border-t border-zinc-200 pt-1 font-semibold text-zinc-900"><dt>{{ __('Total') }}</dt><dd>{{ $money($order->total) }}</dd></div>
    @endif
</dl>
