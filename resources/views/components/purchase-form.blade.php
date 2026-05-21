<?php

use App\Models\Brand;
use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    public $purchaseId = null;
    public $rows = [];

    public function mount(?Purchase $purchase = null)
    {
        if ($purchase && $purchase->exists) {
            Gate::authorize('update', $purchase);

            $purchase->load('items.item', 'items.brand');

            $this->purchaseId = $purchase->id;
            $this->rows = $purchase->items->map(fn ($pi) => [
                'item'     => $pi->item->name,
                'brand'    => $pi->brand->name,
                'quantity' => $pi->qty,
                'price'    => (float) $pi->price,
            ])->all();
        } else {
            Gate::authorize('create', Purchase::class);
            $this->rows = [$this->blankRow()];
        }
    }

    protected function blankRow()
    {
        return ['item' => '', 'brand' => '', 'quantity' => 1, 'price' => 0];
    }

    protected function rules()
    {
        return [
            'rows.*.item'     => ['required', 'string', 'max:255'],
            'rows.*.brand'    => ['required', 'string', 'max:255'],
            'rows.*.quantity' => ['required', 'integer', 'min:1'],
            'rows.*.price'    => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function messages()
    {
        return [
            'rows.*.item.required'     => 'Item is required.',
            'rows.*.brand.required'    => 'Brand is required.',
            'rows.*.quantity.required' => 'Quantity is required.',
            'rows.*.quantity.integer'  => 'Quantity must be a whole number.',
            'rows.*.quantity.min'      => 'Quantity must be at least 1.',
            'rows.*.price.required'    => 'Price is required.',
            'rows.*.price.numeric'     => 'Price must be a number.',
            'rows.*.price.min'         => 'Price cannot be negative.',
        ];
    }

    public function updated($name)
    {
        $this->validateOnly($name);
        $this->flagDuplicates();
    }

    public function addRow()
    {
        $this->rows[] = $this->blankRow();
    }

    public function removeRow($index)
    {
        if (count($this->rows) <= 1) return;
        array_splice($this->rows, $index, 1);
        $this->rows = array_values($this->rows);
        $this->resetErrorBag();
        $this->flagDuplicates();
    }

    protected function flagDuplicates()
    {
        $seen = [];
        foreach ($this->rows as $i => $row) {
            $item  = strtolower(trim((string) ($row['item']  ?? '')));
            $brand = strtolower(trim((string) ($row['brand'] ?? '')));

            if ($item === '' || $brand === '') continue;

            $key = $item . '|' . $brand;
            if (isset($seen[$key])) {
                $this->addError("rows.$i.item", 'Duplicate item + brand combination.');
                $this->addError("rows.$i.brand", 'Duplicate item + brand combination.');
            } else {
                $seen[$key] = $i;
            }
        }
    }

    public function save()
    {
        $purchase = $this->purchaseId ? Purchase::findOrFail($this->purchaseId) : null;

        Gate::authorize($purchase ? 'update' : 'create', $purchase ?: Purchase::class);

        $this->validate();
        $this->flagDuplicates();

        if ($this->getErrorBag()->isNotEmpty()) return;

        $total = collect($this->rows)
            ->sum(fn ($r) => ((int) $r['quantity']) * ((float) $r['price']));

        DB::transaction(function () use (&$purchase, $total) {
            if ($purchase) {
                $purchase->update(['total' => $total]);
                $purchase->items()->delete();
            } else {
                $purchase = Purchase::create(['total' => $total]);
            }

            foreach ($this->rows as $row) {
                $item  = Item::firstOrCreate(['name' => trim($row['item'])]);
                $brand = Brand::firstOrCreate(['name' => trim($row['brand'])]);

                $purchase->items()->create([
                    'item_id'  => $item->id,
                    'brand_id' => $brand->id,
                    'qty'      => (int) $row['quantity'],
                    'price'    => (float) $row['price'],
                ]);
            }
        });

        session()->flash('success', 'Purchase saved.');

        $this->redirect(route('purchases.index'), navigate: true);
    }
};
?>

<div class="max-w-5xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">Purchase Form</h1>

    @if (session('success'))
        <div class="mb-4 rounded bg-green-100 border border-green-300 text-green-800 px-4 py-2">
            {{ session('success') }}
        </div>
    @endif

    <div
        x-data="{
            rows: @entangle('rows').live,
            subtotal(row) {
                return (Number(row?.quantity) || 0) * (Number(row?.price) || 0);
            },
            get total() {
                return this.rows.reduce((sum, row) => sum + this.subtotal(row), 0);
            },
            money(value) {
                return Number(value || 0).toFixed(2);
            },
        }"
        class="space-y-4"
    >
        <div class="bg-white shadow rounded overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-3 py-2">Item</th>
                        <th class="px-3 py-2">Brand</th>
                        <th class="px-3 py-2 w-24">Qty</th>
                        <th class="px-3 py-2 w-32">Price</th>
                        <th class="px-3 py-2 w-32 text-right">Subtotal</th>
                        <th class="px-3 py-2 w-16"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $i => $row)
                        <tr wire:key="row-{{ $i }}" class="border-t align-top">
                            <td class="px-3 py-2">
                                <input type="text"
                                    x-model.debounce.300ms="rows[{{ $i }}].item"
                                    class="w-full rounded border px-2 py-1 focus:outline-none focus:ring focus:ring-blue-200 @error('rows.' . $i . '.item') border-red-500 @else border-gray-300 @enderror">
                                @error('rows.' . $i . '.item')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-3 py-2">
                                <input type="text"
                                    x-model.debounce.300ms="rows[{{ $i }}].brand"
                                    class="w-full rounded border px-2 py-1 focus:outline-none focus:ring focus:ring-blue-200 @error('rows.' . $i . '.brand') border-red-500 @else border-gray-300 @enderror">
                                @error('rows.' . $i . '.brand')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" min="1" step="1"
                                    x-model.number.debounce.300ms="rows[{{ $i }}].quantity"
                                    class="w-full rounded border px-2 py-1 focus:outline-none focus:ring focus:ring-blue-200 @error('rows.' . $i . '.quantity') border-red-500 @else border-gray-300 @enderror">
                                @error('rows.' . $i . '.quantity')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" min="0" step="0.01"
                                    x-model.number.debounce.300ms="rows[{{ $i }}].price"
                                    class="w-full rounded border px-2 py-1 focus:outline-none focus:ring focus:ring-blue-200 @error('rows.' . $i . '.price') border-red-500 @else border-gray-300 @enderror">
                                @error('rows.' . $i . '.price')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums"
                                x-text="money(subtotal(rows[{{ $i }}]))">
                            </td>
                            <td class="px-3 py-2 text-right">
                                <button type="button" wire:click="removeRow({{ $i }})"
                                    @disabled(count($rows) <= 1)
                                    class="text-red-600 hover:underline disabled:opacity-40 disabled:cursor-not-allowed">
                                    Remove
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t bg-gray-50">
                        <td colspan="4" class="px-3 py-2 text-right font-medium">Total</td>
                        <td class="px-3 py-2 text-right font-semibold tabular-nums"
                            x-text="money(total)">
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="flex justify-between">
            <button type="button" wire:click="addRow"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                + Add Row
            </button>
            <button type="button" wire:click="save"
                class="bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700">
                Save Purchase
            </button>
        </div>
    </div>
</div>
