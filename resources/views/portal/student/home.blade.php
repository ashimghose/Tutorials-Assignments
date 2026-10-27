<x-layouts::app :title="__('My classes')">
    <div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Hello, :name', ['name' => auth()->user()->name]) }}</flux:heading>
            <flux:subheading>{{ __('Your classes and recordings will appear here once your parent enrols you in a batch.') }}</flux:subheading>
        </div>
    </div>
</x-layouts::app>
