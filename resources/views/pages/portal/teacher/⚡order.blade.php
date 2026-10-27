<?php

use App\Actions\Orders\ChangeOrderStatus;
use App\Actions\Orders\StoreOrderFile;
use App\Enums\OrderFileKind;
use App\Models\AcademicOrder;
use App\Models\OrderStatus;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
| ACA-9: the assigned expert reads the brief, uploads drafts and finals,
| and moves the order to the statuses the admin allows experts to set.
*/
new #[Title('Order')] class extends Component {
    use WithFileUploads;

    public AcademicOrder $order;

    public string $kind = 'draft';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    public string $status_id = '';

    public string $note = '';

    public function mount(AcademicOrder $order): void
    {
        $this->authorize('view', $order);
        $this->order = $order;
    }

    /**
     * @return Collection<int, OrderStatus>
     */
    #[Computed]
    public function statuses(): Collection
    {
        return OrderStatus::query()->ordered()->where('expert_can_set', true)->whereKeyNot($this->order->order_status_id)->get();
    }

    public function upload(StoreOrderFile $store): void
    {
        $this->authorize('upload', $this->order);
        $this->validate([
            'kind' => ['required', Rule::in([OrderFileKind::Draft->value, OrderFileKind::Final->value])],
            'uploads' => ['required', 'array', 'max:'.config('academic.max_files')],
            'uploads.*' => StoreOrderFile::rules(),
        ], ['uploads.*.mimetypes' => __('Upload PDF or Word (.docx) files only.')]);

        foreach ($this->uploads as $upload) {
            $store->handle($this->order, $upload, OrderFileKind::from($this->kind), Auth::user());
        }

        $this->reset('uploads');
        $this->order->unsetRelation('files');
        Flux::toast(variant: 'success', text: __('Uploaded. The student can download it from their order page.'));
    }

    public function setStatus(ChangeOrderStatus $change): void
    {
        $this->validate([
            'status_id' => ['required', Rule::exists('order_statuses', 'id')],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $status = OrderStatus::query()->findOrFail((int) $this->status_id);
        $this->authorize('setStatus', [$this->order, $status]);

        $change->handle($this->order, $status, Auth::user(), $this->note ?: null);

        $this->reset('status_id', 'note');
        $this->order->unsetRelation('history');
        unset($this->statuses);
        Flux::toast(variant: 'success', text: __('Status updated.'));
    }
}; ?>

@php($order->loadMissing('status', 'history.toStatus', 'files', 'addons'))
<section class="flex w-full max-w-4xl flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:text><a href="{{ route('teacher.orders') }}" wire:navigate class="hover:underline">{{ __('Assigned orders') }}</a> / {{ $order->reference() }}</flux:text>
            <flux:heading size="xl" level="1">{{ $order->service_title }}</flux:heading>
            <flux:subheading>{{ $order->level->label() }} · {{ __('due :date', ['date' => $order->deadline->format('j M Y')]) }}</flux:subheading>
        </div>
        <flux:badge :color="$order->status->badgeColor()">{{ $order->status->name }}</flux:badge>
    </div>

    @unless ($order->isPaid())
        <flux:callout icon="clock" color="amber" :heading="__('Not paid yet. Please wait for the payment before starting work.')" />
    @endunless

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="space-y-6 lg:col-span-3">
            <flux:card class="space-y-2">
                <flux:heading>{{ __('Brief') }}</flux:heading>
                <dl class="grid gap-x-4 gap-y-1 text-sm sm:grid-cols-2">
                    <dt class="text-zinc-500">{{ __('University') }}</dt><dd>{{ $order->university }}</dd>
                    <dt class="text-zinc-500">{{ __('Course') }}</dt><dd>{{ $order->course }}@if ($order->stream) · {{ $order->stream }}@endif</dd>
                    <dt class="text-zinc-500">{{ __('Topic') }}</dt><dd>{{ $order->topic }}</dd>
                    <dt class="text-zinc-500">{{ __('Length') }}</dt><dd>{{ collect([$order->pages ? $order->pages.' pages' : null, $order->words ? number_format($order->words).' words' : null])->filter()->implode(', ') ?: '—' }}</dd>
                    <dt class="text-zinc-500">{{ __('Revisions used') }}</dt><dd>{{ $order->revisions_used }} / {{ $order->revisions_allowed }}</dd>
                </dl>
                @if ($order->notes)
                    <flux:text class="whitespace-pre-line">{{ $order->notes }}</flux:text>
                @endif
                @if ($order->addons->isNotEmpty())
                    <flux:text size="sm">{{ __('Add-ons: :list', ['list' => $order->addons->map(fn ($a) => $a->name.' × '.$a->quantity)->implode(', ')]) }}</flux:text>
                @endif
            </flux:card>

            <flux:card class="space-y-3">
                <flux:heading>{{ __('Files') }}</flux:heading>
                <x-orders.files :order="$order" />
                @can('upload', $order)
                    <form wire:submit="upload" class="space-y-2">
                        <flux:radio.group wire:model="kind" variant="segmented" size="sm">
                            <flux:radio value="draft" :label="__('Draft')" />
                            <flux:radio value="final" :label="__('Final')" />
                        </flux:radio.group>
                        <input type="file" wire:model="uploads" multiple accept=".pdf,.docx" aria-label="{{ __('Files to upload') }}" class="block w-full text-sm text-zinc-700 file:me-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:font-medium">
                        <flux:error name="uploads" />
                        @foreach (array_keys($uploads) as $i)
                            <flux:error :name="'uploads.'.$i" />
                        @endforeach
                        <flux:button type="submit" size="sm" variant="primary">{{ __('Upload') }}</flux:button>
                    </form>
                @endcan
            </flux:card>
        </div>

        <div class="space-y-6 lg:col-span-2">
            @if ($order->isPaid() && ! $order->isClosed() && $this->statuses->isNotEmpty())
                <flux:card>
                    <form wire:submit="setStatus" class="space-y-3">
                        <flux:heading>{{ __('Update status') }}</flux:heading>
                        <flux:select wire:model="status_id" :placeholder="__('Choose…')" :aria-label="__('New status')">
                            @foreach ($this->statuses as $option)
                                <flux:select.option :value="$option->id">{{ $option->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:textarea wire:model="note" :label="__('Note for the student (optional)')" rows="2" />
                        <flux:error name="status" />
                        <flux:button type="submit" size="sm" variant="primary">{{ __('Update') }}</flux:button>
                    </form>
                </flux:card>
            @endif

            <flux:card class="space-y-3">
                <flux:heading>{{ __('Progress') }}</flux:heading>
                <x-orders.timeline :order="$order" />
            </flux:card>
        </div>
    </div>
</section>
