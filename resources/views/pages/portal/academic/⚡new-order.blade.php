<?php

use App\Actions\Orders\PlaceOrder;
use App\Actions\Orders\StoreOrderFile;
use App\Enums\AcademicLevel;
use App\Enums\AddonUnit;
use App\Http\Middleware\CaptureUtm;
use App\Models\AcademicOrder;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Services\PriceBreakdown;
use App\Services\Pricing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
| ACA-4, ACA-5: the order form. The price shown is worked out on the server
| from the price list; a job the list doesn't cover is sent as a quote
| request. Payment comes after the order (by link until online checkout).
*/
new #[Title('New order')] class extends Component {
    use WithFileUploads;

    public string $service_id = '';
    public string $level = '';
    public bool $quote = false;
    public string $university = '';
    public string $course = '';
    public string $stream = '';
    public string $topic = '';
    public string $deadline = '';
    public string $pages = '';
    public string $words = '';
    public string $notes = '';

    /** @var array<int|string, int|string|null> add-on id => quantity */
    public array $quantities = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    public string $delivery_name = '';
    public string $delivery_phone = '';
    public string $delivery_address = '';
    public string $delivery_city = '';
    public string $delivery_state = '';
    public string $delivery_pincode = '';

    public function mount(): void
    {
        $this->authorize('create', AcademicOrder::class);

        $profile = Auth::user()->academicStudent;
        $this->university = (string) $profile->university;
        $this->course = (string) $profile->course;
        $this->stream = (string) $profile->stream;
        $this->level = AcademicLevel::tryFrom((string) $profile->level)?->value ?? '';
        $this->delivery_name = Auth::user()->name;
        $this->delivery_phone = (string) $profile->mobile;
        $this->service_id = (string) request()->query('service', '');
    }

    /**
     * @return Collection<int, Service>
     */
    #[Computed]
    public function services(): Collection
    {
        return Service::query()->active()->get();
    }

    #[Computed]
    public function service(): ?Service
    {
        return $this->services->firstWhere('id', (int) $this->service_id);
    }

    /**
     * @return Collection<int, ServiceAddon>
     */
    #[Computed]
    public function addons(): Collection
    {
        return $this->service?->addons()->where('is_active', true)->orderBy('sort_order')->get() ?? collect();
    }

    /**
     * The service has no listed price at this level, so it must be quoted.
     */
    #[Computed]
    public function mustQuote(): bool
    {
        $level = AcademicLevel::tryFrom($this->level);

        return $this->service !== null && $level !== null && $this->service->priceFor($level) === null;
    }

    #[Computed]
    public function needsAddress(): bool
    {
        return $this->addons->contains(fn (ServiceAddon $addon) => $addon->kind->needsAddress() && (int) ($this->quantities[$addon->id] ?? 0) > 0);
    }

    #[Computed]
    public function price(): ?PriceBreakdown
    {
        $level = AcademicLevel::tryFrom($this->level);
        if ($this->service === null || $level === null || $this->quote || $this->mustQuote) {
            return null;
        }

        try {
            return app(Pricing::class)->forService($this->service, $level, $this->quantities);
        } catch (ValidationException) {
            return null;
        }
    }

    public function updatedServiceId(): void
    {
        $this->quantities = [];
    }

    /**
     * A ticked per-order add-on arrives as true; count it as one.
     */
    public function updatedQuantities(): void
    {
        $this->quantities = array_map(fn (mixed $quantity) => is_bool($quantity) ? (int) $quantity : $quantity, $this->quantities);
    }

    public function place(PlaceOrder $place): void
    {
        $this->authorize('create', AcademicOrder::class);

        $validated = $this->validate([
            'service_id' => ['required', Rule::exists('services', 'id')->where('is_active', true)],
            'level' => ['required', Rule::enum(AcademicLevel::class)],
            'university' => ['required', 'string', 'max:160'],
            'course' => ['required', 'string', 'max:120'],
            'stream' => ['nullable', 'string', 'max:120'],
            'topic' => ['required', 'string', 'max:255'],
            'deadline' => ['required', 'date', 'after:today'],
            'pages' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'words' => ['nullable', 'integer', 'min:1', 'max:2000000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'quantities' => ['array'],
            'quantities.*' => ['nullable', 'integer', 'min:0', 'max:'.Pricing::MAX_COPIES],
            'uploads' => ['array', 'max:'.config('academic.max_files')],
            'uploads.*' => StoreOrderFile::rules(),
            'delivery_name' => [Rule::requiredIf($this->needsAddress), 'nullable', 'string', 'max:120'],
            'delivery_phone' => [Rule::requiredIf($this->needsAddress), 'nullable', 'regex:/^[6-9]\d{9}$/'],
            'delivery_address' => [Rule::requiredIf($this->needsAddress), 'nullable', 'string', 'max:255'],
            'delivery_city' => [Rule::requiredIf($this->needsAddress), 'nullable', 'string', 'max:80'],
            'delivery_state' => [Rule::requiredIf($this->needsAddress), 'nullable', 'string', 'max:80'],
            'delivery_pincode' => [Rule::requiredIf($this->needsAddress), 'nullable', 'digits:6'],
        ], [
            'uploads.*.mimetypes' => __('Upload PDF or Word (.docx) files only.'),
            'uploads.*.max' => __('Each file can be up to 25 MB.'),
            'delivery_phone.regex' => __('Enter a 10-digit Indian mobile number.'),
        ]);

        // Collect a delivery address only when printing or courier is in the order.
        if (! $this->needsAddress) {
            foreach (['delivery_name', 'delivery_phone', 'delivery_address', 'delivery_city', 'delivery_state', 'delivery_pincode'] as $field) {
                $validated[$field] = null;
            }
        }
        foreach (['pages', 'words', 'stream', 'notes'] as $field) {
            $validated[$field] = $validated[$field] === '' ? null : $validated[$field];
        }

        $order = $place->handle(
            Auth::user()->academicStudent,
            $this->service,
            AcademicLevel::from($this->level),
            $this->quote || $this->mustQuote,
            $validated,
            $this->quantities,
            array_values($this->uploads),
            CaptureUtm::current(request()),
        );

        $this->redirectRoute('academic.orders.show', $order, navigate: true);
    }
}; ?>

<section class="flex w-full max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('New order') }}</flux:heading>
        <flux:subheading>{{ __('We guide, edit, format, check and print your own work. We do not write it for you.') }}</flux:subheading>
    </div>

    <form wire:submit="place" class="space-y-8">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Service') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="service_id" :label="__('Service')" :placeholder="__('Choose…')" required>
                    @foreach ($this->services as $option)
                        <flux:select.option :value="$option->id">{{ $option->title }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="level" :label="__('Level')" :placeholder="__('Choose…')" required>
                    @foreach (\App\Enums\AcademicLevel::cases() as $option)
                        <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            @if ($this->service)
                <flux:text>{{ $this->service->description }}</flux:text>
                <flux:text size="sm">{{ __('Turnaround: :time. Free revisions: :count.', ['time' => $this->service->turnaround ?: '—', 'count' => $this->service->free_revisions]) }}</flux:text>
            @endif
            @if ($this->mustQuote)
                <flux:callout icon="information-circle" color="blue" :heading="__('This is priced per job at your level. Send your details and we will reply with a quote.')" />
            @elseif ($this->service)
                <flux:checkbox wire:model.live="quote" :label="__('My work is longer or unusual: send me a quote instead')" />
            @endif
        </div>

        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Your work') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="university" :label="__('University')" required />
                <flux:input wire:model="course" :label="__('Course')" required />
                <flux:input wire:model="stream" :label="__('Stream or subject (optional)')" />
                <flux:input wire:model="deadline" type="date" :label="__('Deadline')" required />
            </div>
            <flux:input wire:model="topic" :label="__('Topic or title')" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="pages" type="number" min="1" :label="__('Pages (optional)')" />
                <flux:input wire:model="words" type="number" min="1" :label="__('Words (optional)')" />
            </div>
            <flux:textarea wire:model="notes" :label="__('Notes (optional)')" :placeholder="__('Your university\'s style guide, what you want us to focus on…')" rows="3" />
            <flux:field>
                <flux:label>{{ __('Your files (optional)') }}</flux:label>
                <flux:description>{{ __('Your draft and your university\'s guidelines. PDF or Word (.docx), up to 25 MB each, 5 files at most.') }}</flux:description>
                <input type="file" wire:model="uploads" multiple accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="block w-full text-sm text-zinc-700 file:me-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:font-medium">
                <div wire:loading wire:target="uploads" class="text-sm text-zinc-500">{{ __('Uploading…') }}</div>
                <flux:error name="uploads" />
                @foreach (array_keys($uploads) as $i)
                    <flux:error :name="'uploads.'.$i" />
                @endforeach
            </flux:field>
        </div>

        @if ($this->addons->isNotEmpty())
            <div class="space-y-3">
                <flux:heading size="lg">{{ __('Printing, delivery and express') }}</flux:heading>
                @foreach ($this->addons as $addon)
                    <div class="flex flex-wrap items-center justify-between gap-3" wire:key="addon-{{ $addon->id }}">
                        <div>
                            <p class="font-medium text-zinc-900">{{ $addon->name }}</p>
                            <p class="text-sm text-zinc-600">{{ \App\Support\Money::format($addon->price) }} {{ $addon->unit->label() }}</p>
                        </div>
                        @if ($addon->unit === \App\Enums\AddonUnit::PerCopy)
                            <flux:input wire:model.live.debounce.400ms="quantities.{{ $addon->id }}" type="number" min="0" max="{{ \App\Services\Pricing::MAX_COPIES }}" class="max-w-24" :aria-label="__('Copies of :name', ['name' => $addon->name])" placeholder="0" />
                        @else
                            <flux:checkbox wire:model.live="quantities.{{ $addon->id }}" value="1" :aria-label="$addon->name" />
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($this->needsAddress)
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Delivery address') }}</flux:heading>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="delivery_name" :label="__('Name')" required />
                    <flux:input wire:model="delivery_phone" type="tel" :label="__('Mobile')" required />
                </div>
                <flux:input wire:model="delivery_address" :label="__('Address')" required />
                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="delivery_city" :label="__('City')" required />
                    <flux:input wire:model="delivery_state" :label="__('State')" required />
                    <flux:input wire:model="delivery_pincode" inputmode="numeric" :label="__('PIN code')" required />
                </div>
            </div>
        @endif

        <flux:card class="space-y-3">
            <flux:heading>{{ __('Price') }}</flux:heading>
            @if ($this->price)
                @php($p = $this->price)
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between gap-4"><dt>{{ $this->service->title }}</dt><dd>{{ \App\Support\Money::format($p->base) }}</dd></div>
                    @foreach ($p->addons as $line)
                        <div class="flex justify-between gap-4"><dt>{{ $line['addon']->name }} × {{ $line['quantity'] }}</dt><dd>{{ \App\Support\Money::format($line['line_total']) }}</dd></div>
                    @endforeach
                    @if ($p->gst)
                        <div class="flex justify-between gap-4 text-zinc-600"><dt>{{ __('GST (:percent%)', ['percent' => $p->gstPercent]) }}</dt><dd>{{ \App\Support\Money::format($p->gst) }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-4 border-t border-zinc-200 pt-1 font-semibold text-zinc-900"><dt>{{ __('Total') }}</dt><dd data-test="order-total">{{ \App\Support\Money::format($p->total) }}</dd></div>
                </dl>
                <flux:text size="sm">{{ __('After you place the order we send you a secure payment link. Work starts once it is paid.') }}</flux:text>
            @elseif ($this->quote || $this->mustQuote)
                <flux:text>{{ __('We will send you a quote, usually within one working day.') }}</flux:text>
            @else
                <flux:text>{{ __('Choose a service and level to see the price.') }}</flux:text>
            @endif
        </flux:card>

        <flux:error name="service" />
        <flux:error name="addons" />

        <flux:button type="submit" variant="primary" class="w-full sm:w-auto" data-test="place-order">
            {{ $this->quote || $this->mustQuote ? __('Ask for a quote') : __('Place order') }}
        </flux:button>
    </form>
</section>
