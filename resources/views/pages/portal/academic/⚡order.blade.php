<?php

use App\Actions\Orders\ChangeOrderStatus;
use App\Actions\Orders\StoreOrderFile;
use App\Enums\OrderFileKind;
use App\Models\AcademicOrder;
use App\Models\OrderStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
| ACA-8: one order for the student: status, timeline, price, files,
| revisions left; add files and ask for a revision.
*/
new #[Title('Order')] class extends Component {
    use WithFileUploads;

    public AcademicOrder $order;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    public string $revision_note = '';

    public function mount(AcademicOrder $order): void
    {
        $this->authorize('view', $order);
        $this->order = $order;
    }

    public function addFiles(StoreOrderFile $store): void
    {
        $this->authorize('upload', $this->order);
        $this->validate([
            'uploads' => ['required', 'array', 'max:'.config('academic.max_files')],
            'uploads.*' => StoreOrderFile::rules(),
        ], ['uploads.*.mimetypes' => __('Upload PDF or Word (.docx) files only.')]);

        foreach ($this->uploads as $upload) {
            $store->handle($this->order, $upload, OrderFileKind::Brief, Auth::user());
        }

        $this->reset('uploads');
        $this->order->unsetRelation('files');
        Flux::toast(variant: 'success', text: __('Files added.'));
    }

    public function requestRevision(ChangeOrderStatus $change): void
    {
        $this->authorize('requestRevision', $this->order);
        $this->validate(['revision_note' => ['required', 'string', 'max:500']]);

        $status = OrderStatus::query()->where('is_revision', true)->ordered()->first();
        abort_if($status === null, 404);

        $this->order->forceFill(['revisions_used' => $this->order->revisions_used + 1])->save();
        $change->handle($this->order, $status, Auth::user(), $this->revision_note);

        $this->reset('revision_note');
        $this->order->unsetRelation('history');
        Flux::modal('revision')->close();
        Flux::toast(variant: 'success', text: __('Revision requested.'));
    }
}; ?>

@php($order->loadMissing('status', 'history.toStatus', 'files', 'addons'))
<section class="flex w-full max-w-4xl flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:text><a href="{{ route('academic.home') }}" wire:navigate class="hover:underline">{{ __('My orders') }}</a> / {{ $order->reference() }}</flux:text>
            <flux:heading size="xl" level="1">{{ $order->service_title }}</flux:heading>
            <flux:subheading>{{ $order->level->label() }} · {{ $order->topic }} · {{ __('due :date', ['date' => $order->deadline->format('j M Y')]) }}</flux:subheading>
        </div>
        <flux:badge :color="$order->status->badgeColor()">{{ $order->status->name }}</flux:badge>
    </div>

    @if (! $order->isPaid() && ! $order->isClosed())
        <flux:callout icon="banknotes" color="amber">
            @if ($order->is_quote && $order->total === null)
                <flux:callout.heading>{{ __('We are preparing your quote') }}</flux:callout.heading>
                <flux:callout.text>{{ __('You will get an email when it is ready.') }}</flux:callout.text>
            @elseif ($order->is_quote && ! $order->hasOpenQuote())
                <flux:callout.heading>{{ __('This quote has expired') }}</flux:callout.heading>
                <flux:callout.text>{{ __('Message us and we will send a new one.') }}</flux:callout.text>
            @else
                <flux:callout.heading>{{ __('Payment due: :amount', ['amount' => \App\Support\Money::format((int) $order->total)]) }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('We will send you a secure payment link on WhatsApp. Work starts once it is paid. Online payment on this page is coming soon.') }}
                    @if ($order->is_quote && $order->quote_expires_at)
                        {{ __('Quote open until :date.', ['date' => $order->quote_expires_at->format('j M Y')]) }}
                    @endif
                </flux:callout.text>
            @endif
        </flux:callout>
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="space-y-6 lg:col-span-3">
            <flux:card class="space-y-3">
                <flux:heading>{{ __('Files') }}</flux:heading>
                <x-orders.files :order="$order" />
                @can('upload', $order)
                    <form wire:submit="addFiles" class="space-y-2">
                        <input type="file" wire:model="uploads" multiple accept=".pdf,.docx" aria-label="{{ __('Add files') }}" class="block w-full text-sm text-zinc-700 file:me-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:font-medium">
                        <flux:error name="uploads" />
                        @foreach (array_keys($uploads) as $i)
                            <flux:error :name="'uploads.'.$i" />
                        @endforeach
                        <flux:button type="submit" size="sm">{{ __('Add files') }}</flux:button>
                    </form>
                @endcan
            </flux:card>

            <flux:card class="space-y-3">
                <flux:heading>{{ __('Progress') }}</flux:heading>
                <x-orders.timeline :order="$order" />
            </flux:card>
        </div>

        <div class="space-y-6 lg:col-span-2">
            <flux:card class="space-y-3">
                <flux:heading>{{ __('Price') }}</flux:heading>
                <x-orders.price :order="$order" />
            </flux:card>

            <flux:card class="space-y-2">
                <flux:heading>{{ __('Revisions') }}</flux:heading>
                <flux:text>{{ trans_choice(':count free revision left|:count free revisions left', $order->revisionsLeft()) }}</flux:text>
                @can('requestRevision', $order)
                    <flux:modal.trigger name="revision">
                        <flux:button size="sm">{{ __('Ask for a revision') }}</flux:button>
                    </flux:modal.trigger>
                @endcan
            </flux:card>

            @if ($order->delivery_address)
                <flux:card class="space-y-1">
                    <flux:heading>{{ __('Delivery') }}</flux:heading>
                    <flux:text>{{ $order->delivery_name }}, {{ $order->delivery_address }}, {{ $order->delivery_city }}, {{ $order->delivery_state }} {{ $order->delivery_pincode }}</flux:text>
                </flux:card>
            @endif
        </div>
    </div>

    <flux:modal name="revision" class="w-full max-w-md">
        <form wire:submit="requestRevision" class="space-y-4">
            <flux:heading size="lg">{{ __('Ask for a revision') }}</flux:heading>
            <flux:textarea wire:model="revision_note" :label="__('What should we change?')" rows="4" required />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="filled">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Send') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
