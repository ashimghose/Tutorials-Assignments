{{-- Each role sees only its own pages (AUTH-5); the routes check it again on the server. --}}
@switch(auth()->user()->role)
    @case(\App\Enums\Role::Parent)
        <flux:sidebar.item icon="users" :href="route('parent.children')" :current="request()->routeIs('parent.children')" wire:navigate>
            {{ __('My children') }}
        </flux:sidebar.item>
        @if (config('features.batches'))
            <flux:sidebar.item icon="calendar-days" :href="route('parent.classes')" :current="request()->routeIs('parent.classes')" wire:navigate>
                {{ __('Classes') }}
            </flux:sidebar.item>
        @endif
        @break
    @case(\App\Enums\Role::Student)
        <flux:sidebar.item icon="academic-cap" :href="route('student.home')" :current="request()->routeIs('student.home')" wire:navigate>
            {{ __('My classes') }}
        </flux:sidebar.item>
        @break
    @case(\App\Enums\Role::AcademicStudent)
        <flux:sidebar.item icon="document-text" :href="route('academic.home')" :current="request()->routeIs('academic.*')" wire:navigate>
            {{ __('My orders') }}
        </flux:sidebar.item>
        @break
    @case(\App\Enums\Role::Teacher)
        <flux:sidebar.item icon="home" :href="route('teacher.home')" :current="request()->routeIs('teacher.home')" wire:navigate>
            {{ __('Teacher home') }}
        </flux:sidebar.item>
        @if (config('features.academic_orders'))
            <flux:sidebar.item icon="document-text" :href="route('teacher.orders')" :current="request()->routeIs('teacher.orders*')" wire:navigate>
                {{ __('Assigned orders') }}
            </flux:sidebar.item>
        @endif
        @break
    @case(\App\Enums\Role::Admin)
        <flux:sidebar.item icon="cog-6-tooth" href="/admin">
            {{ __('Admin panel') }}
        </flux:sidebar.item>
        @break
@endswitch
