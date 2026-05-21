<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public $email = '';
    public $password = '';

    protected function rules()
    {
        return [
            'email'    => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }

    protected function messages()
    {
        return [
            'email.required'    => 'Email is required.',
            'email.email'       => 'Enter a valid email address.',
            'password.required' => 'Password is required.',
            'password.min'      => 'Password must be at least 8 characters.',
        ];
    }

    public function submit()
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], true)) {
            $this->addError('email', 'Invalid credentials.');
            return;
        }

        request()->session()->regenerate();

        $this->redirect(route('purchases.index'), navigate: true);
    }
}; ?>

<div class="min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md bg-white shadow rounded p-6 space-y-4">
        <h1 class="text-xl font-semibold">Sign in</h1>

        <p class="text-xs text-gray-500">
            Demo: <code>admin@example.com</code> / <code>user@example.com</code> &mdash; password <code>password</code>
        </p>

        <form wire:submit="submit" class="space-y-3">
            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" wire:model.live.debounce.300ms="email"
                    class="w-full rounded border px-2 py-1 focus:outline-none focus:ring focus:ring-blue-200 @error('email') border-red-500 @else border-gray-300 @enderror">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <input type="password" wire:model="password"
                    class="w-full rounded border px-2 py-1 focus:outline-none focus:ring focus:ring-blue-200 @error('password') border-red-500 @else border-gray-300 @enderror">
                @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Sign in
            </button>
        </form>
    </div>
</div>
