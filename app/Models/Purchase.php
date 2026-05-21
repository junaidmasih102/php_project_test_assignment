<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = ['total'];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
