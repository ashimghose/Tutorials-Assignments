<?php

use App\Concerns\AccountValidationRules;
use App\Concerns\PasswordValidationRules;
use App\Enums\AccountStatus;
use App\Enums\ConsentPurpose;
use App\Enums\Role;
use App\Models\Consent;
use App\Models\Student;
use App\Models\User;
use App\Support\SiteContent;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/*
| AUTH-3: a parent adds each child, gives consent for the child's data
| (DPDP Act), creates the child's login and can reset its password.
| Withdrawing consent deactivates the child's login.
*/
new #[Title('My children')] class extends Component {
    use AccountValidationRules, PasswordValidationRules;

    public string $first_name = '';
    public string $class_level = '';
    public string $board = '';
    public string $school = '';
    public string $username = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $consent = false;

    public ?int $resetStudentId = null;
    public string $new_password = '';
    public string $new_password_confirmation = '';

    /**
     * @return Collection<int, Student>
     */
    #[Computed]
    public function children(): Collection
    {
        return Student::query()
            ->whereBelongsTo(Auth::user()->parentProfile, 'parent')
            ->with(['user', 'consents' => fn ($query) => $query->latest('given_at')])
            ->orderBy('first_name')
            ->get();
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function boards(): array
    {
        return [...SiteContent::section('tuition')['boards'], 'Other'];
    }

    public function addChild(): void
    {
        $this->authorize('create', Student::class);

        $this->username = strtolower(trim($this->username));

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'class_level' => ['required', 'integer', 'between:1,12'],
            'board' => ['required', 'string', 'in:'.implode(',', $this->boards)],
            'school' => ['nullable', 'string', 'max:120'],
            'username' => $this->usernameRules(),
            'password' => $this->passwordRules(),
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => __('Please give consent so we can hold your child\'s details.'),
        ]);

        DB::transaction(function () use ($validated) {
            $parent = Auth::user();

            $login = new User(['name' => $validated['first_name'], 'password' => $validated['password']]);
            $login->forceFill(['role' => Role::Student, 'username' => $validated['username']])->save();

            $student = $parent->parentProfile->students()->make([
                'first_name' => $validated['first_name'],
                'class_level' => (int) $validated['class_level'],
                'board' => $validated['board'],
                'school' => $validated['school'] ?: null,
            ]);
            $student->user()->associate($login)->save();

            Consent::record($parent, ConsentPurpose::ChildData, $student);
        });

        $this->reset('first_name', 'class_level', 'board', 'school', 'username', 'password', 'password_confirmation', 'consent');
        unset($this->children);
        Flux::modal('add-child')->close();
        Flux::toast(variant: 'success', text: __('Child added. They can now log in with their username.'));
    }

    public function startReset(int $studentId): void
    {
        $this->authorize('update', $this->findChild($studentId));

        $this->resetErrorBag();
        $this->reset('new_password', 'new_password_confirmation');
        $this->resetStudentId = $studentId;
        Flux::modal('reset-password')->show();
    }

    public function resetPassword(): void
    {
        $student = $this->findChild((int) $this->resetStudentId);
        $this->authorize('update', $student);

        $this->validate(['new_password' => ['required', 'string', Password::default(), 'confirmed']]);

        $student->user->forceFill([
            'password' => $this->new_password,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $this->reset('resetStudentId', 'new_password', 'new_password_confirmation');
        Flux::modal('reset-password')->close();
        Flux::toast(variant: 'success', text: __('Password changed.'));
    }

    public function withdrawConsent(int $studentId): void
    {
        $student = $this->findChild($studentId);
        $this->authorize('update', $student);

        $student->consents()->whereNull('withdrawn_at')->update(['withdrawn_at' => now()]);
        $student->user?->forceFill(['status' => AccountStatus::Deactivated])->save();

        unset($this->children);
        Flux::toast(text: __('Consent withdrawn. :name\'s login is switched off.', ['name' => $student->first_name]));
    }

    public function giveConsent(int $studentId): void
    {
        $student = $this->findChild($studentId);
        $this->authorize('update', $student);

        Consent::record(Auth::user(), ConsentPurpose::ChildData, $student);
        $student->user?->forceFill(['status' => AccountStatus::Active])->save();

        unset($this->children);
        Flux::toast(variant: 'success', text: __('Consent given. :name can log in again.', ['name' => $student->first_name]));
    }

    private function findChild(int $studentId): Student
    {
        return Student::query()->with('user')->findOrFail($studentId);
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('My children') }}</flux:heading>
            <flux:subheading>{{ __('Add each child, give consent for their details, and set up their login.') }}</flux:subheading>
        </div>

        <flux:modal.trigger name="add-child">
            <flux:button variant="primary" icon="plus" data-test="add-child-button">{{ __('Add a child') }}</flux:button>
        </flux:modal.trigger>
    </div>

    <x-portal.notices />

    @forelse ($this->children as $child)
        <flux:card class="flex flex-wrap items-start justify-between gap-4" wire:key="child-{{ $child->id }}">
            <div class="space-y-1">
                <flux:heading size="lg">{{ $child->first_name }}</flux:heading>
                <flux:text>
                    {{ __('Class :class', ['class' => $child->class_level]) }} · {{ $child->board }}@if ($child->school) · {{ $child->school }}@endif
                </flux:text>
                @if ($child->user)
                    <flux:text>{{ __('Username') }}: <span class="font-medium text-zinc-800">{{ $child->user->username }}</span></flux:text>
                @endif
                @if ($child->consents->whereNull('withdrawn_at')->isNotEmpty())
                    <flux:badge color="green" size="sm">{{ __('Consent given') }}</flux:badge>
                @else
                    <flux:badge color="amber" size="sm">{{ __('Consent withdrawn, login off') }}</flux:badge>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($child->user)
                    <flux:button size="sm" wire:click="startReset({{ $child->id }})">{{ __('Reset password') }}</flux:button>
                @endif
                @if ($child->consents->whereNull('withdrawn_at')->isNotEmpty())
                    <flux:button size="sm" variant="ghost" wire:click="withdrawConsent({{ $child->id }})" wire:confirm="{{ __('Withdraw consent? :name will not be able to log in until you give it again.', ['name' => $child->first_name]) }}">{{ __('Withdraw consent') }}</flux:button>
                @else
                    <flux:button size="sm" variant="ghost" wire:click="giveConsent({{ $child->id }})">{{ __('Give consent again') }}</flux:button>
                @endif
            </div>
        </flux:card>
    @empty
        <flux:card class="text-center">
            <flux:heading>{{ __('No children added yet') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Add your child to choose their classes. You\'ll pick a username and password for them to log in with.') }}</flux:text>
        </flux:card>
    @endforelse

    <flux:modal name="add-child" class="w-full max-w-lg">
        <form wire:submit="addChild" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Add a child') }}</flux:heading>
                <flux:subheading>{{ __('We only ask for what we need to place them in the right class.') }}</flux:subheading>
            </div>

            <flux:input wire:model="first_name" :label="__('First name')" required autocomplete="off" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="class_level" :label="__('Class')" :placeholder="__('Choose…')" required>
                    @foreach (range(1, 12) as $level)
                        <flux:select.option :value="$level">{{ __('Class :class', ['class' => $level]) }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="board" :label="__('Board')" :placeholder="__('Choose…')" required>
                    @foreach ($this->boards as $option)
                        <flux:select.option :value="$option">{{ $option }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input wire:model="school" :label="__('School (optional)')" autocomplete="off" />

            <flux:input wire:model="username" :label="__('Username for logging in')" :description="__('Lower-case letters and numbers, at least 4 characters.')" required autocomplete="off" autocapitalize="none" />

            <flux:input wire:model="password" :label="__('Password')" type="password" required autocomplete="new-password" viewable />
            <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" viewable />

            <flux:field variant="inline">
                <flux:checkbox wire:model="consent" />
                <flux:label>{{ __('I am this child\'s parent or guardian and I consent to :app holding these details to provide tuition, as set out in the privacy policy.', ['app' => config('app.name')]) }}</flux:label>
                <flux:error name="consent" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" data-test="save-child-button">{{ __('Add child') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="reset-password" class="w-full max-w-md">
        <form wire:submit="resetPassword" class="space-y-5">
            <flux:heading size="lg">{{ __('Reset your child\'s password') }}</flux:heading>

            <flux:input wire:model="new_password" :label="__('New password')" type="password" required autocomplete="new-password" viewable />
            <flux:input wire:model="new_password_confirmation" :label="__('Confirm new password')" type="password" required autocomplete="new-password" viewable />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Change password') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
