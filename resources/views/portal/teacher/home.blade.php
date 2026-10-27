<x-layouts::app :title="__('Teacher home')">
    <div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Hello, :name', ['name' => auth()->user()->name]) }}</flux:heading>
            <flux:subheading>{{ __('The batches and orders assigned to you will appear here.') }}</flux:subheading>
        </div>
    </div>
</x-layouts::app>
