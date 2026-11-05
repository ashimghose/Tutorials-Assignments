<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        // AUTH-7: someone who joined with Google has no password, so they type DELETE.
        $this->validate([
            'password' => Auth::user()->password === null ? ['required', 'in:DELETE'] : $this->currentPasswordRules(),
        ], ['password.in' => __('Type DELETE in capital letters to confirm.')]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
    <form method="POST" wire:submit="deleteUser" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Are you sure you want to delete your account?') }}</flux:heading>

            <flux:subheading>
                @if (auth()->user()->password === null)
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Type DELETE to confirm you would like to permanently delete your account.') }}
                @else
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                @endif
            </flux:subheading>
        </div>

        @if (auth()->user()->password === null)
            <flux:input wire:model="password" :label="__('Type DELETE')" autocomplete="off" />
        @else
            <flux:input wire:model="password" :label="__('Password')" type="password" viewable />
        @endif

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>

            <flux:button variant="danger" type="submit" data-test="confirm-delete-user-button">
                {{ __('Delete account') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
