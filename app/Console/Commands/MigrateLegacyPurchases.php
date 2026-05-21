<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MigrateLegacyPurchases extends Command
{
    protected $signature = 'legacy:migrate-purchases';
    protected $description = 'Migrate legacy purchases into normalized tables';

    public function handle()
    {
        $legacyPurchases = [
            [
                'item_name'  => 'Sugar',
                'brand_name' => 'ABC',
                'qty'        => 10,
                'price'      => 100,
            ],
        ];

        $rules = [
            'item_name'  => 'required|string|max:255',
            'brand_name' => 'required|string|max:255',
            'qty'        => 'required|integer|min:1',
            'price'      => 'required|numeric|min:0',
        ];

        foreach ($legacyPurchases as $row) {
            $v = Validator::make($row, $rules);

            if ($v->fails()) {
                $this->warn("Skipped: " . $v->errors()->first());
                continue;
            }

            DB::transaction(function () use ($row) {
                $item  = Item::firstOrCreate(['name' => trim($row['item_name'])]);
                $brand = Brand::firstOrCreate(['name' => trim($row['brand_name'])]);

                $exists = PurchaseItem::where([
                    'item_id'  => $item->id,
                    'brand_id' => $brand->id,
                    'qty'      => $row['qty'],
                    'price'    => $row['price'],
                ])->exists();

                if ($exists) {
                    $this->warn("{$row['item_name']} already migrated.");
                    return;
                }

                $purchase = Purchase::create([
                    'total' => $row['qty'] * $row['price'],
                ]);

                $purchase->items()->create([
                    'item_id'  => $item->id,
                    'brand_id' => $brand->id,
                    'qty'      => $row['qty'],
                    'price'    => $row['price'],
                ]);

                $this->info("{$row['item_name']} migrated.");
            });
        }

        $this->info('Done.');
    }
}
