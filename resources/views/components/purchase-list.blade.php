<?php

use App\Models\Purchase;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    public function mount()
    {
        Gate::authorize('viewAny', Purchase::class);
    }

    public function delete($id)
    {
        $purchase = Purchase::findOrFail($id);
        Gate::authorize('delete', $purchase);

        $purchase->items()->delete();
        $purchase->delete();

        session()->flash('success', 'Purchase #' . $id . ' deleted.');
    }

    public function with()
    {
        return [
            'purchases' => Purchase::with('items.item', 'items.brand')->latest()->get(),
        ];
    }
}; ?>

<div class="max-w-5xl mx-auto p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">Purchases</h1>
        @can('create', App\Models\Purchase::class)
            <a href="{{ route('purchases.create') }}" wire:navigate
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                + New Purchase
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="mb-4 rounded bg-green-100 border border-green-300 text-green-800 px-4 py-2">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow rounded overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2">#</th>
                    <th class="px-3 py-2">Date</th>
                    <th class="px-3 py-2">Items</th>
                    <th class="px-3 py-2 text-right">Total</th>
                    <th class="px-3 py-2 text-right w-40">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchases as $purchase)
                    <tr wire:key="purchase-{{ $purchase->id }}" class="border-t align-top">
                        <td class="px-3 py-2">{{ $purchase->id }}</td>
                        <td class="px-3 py-2">{{ $purchase->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2">
                            <ul class="space-y-0.5">
                                @foreach ($purchase->items as $pi)
                                    <li>
                                        {{ $pi->item->name }} &middot; {{ $pi->brand->name }} &middot;
                                        {{ $pi->qty }} &times; ${{ number_format($pi->price, 2) }}
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums">${{ number_format($purchase->total, 2) }}</td>
                        <td class="px-3 py-2 text-right space-x-2">
                            @can('update', $purchase)
                                <a href="{{ route('purchases.edit', $purchase) }}" wire:navigate
                                    class="text-blue-600 hover:underline">Edit</a>
                            @endcan
                            @can('delete', $purchase)
                                <button type="button"
                                    wire:click="delete({{ $purchase->id }})"
                                    wire:confirm="Delete purchase #{{ $purchase->id }}?"
                                    class="text-red-600 hover:underline">
                                    Delete
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-6 text-center text-gray-500">No purchases yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
