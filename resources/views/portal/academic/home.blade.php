<x-layouts::app :title="__('My orders')">
    <div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Hello, :name', ['name' => auth()->user()->name]) }}</flux:heading>
            <flux:subheading>{{ __('Your academic service orders and their progress will appear here.') }}</flux:subheading>
        </div>

        <flux:card>
            <flux:text>{{ __('See our services and prices for research papers, synopses, theses and dissertations.') }}</flux:text>
            <flux:button class="mt-3" :href="route('academic')" variant="primary">{{ __('View academic services') }}</flux:button>
        </flux:card>
    </div>
</x-layouts::app>
