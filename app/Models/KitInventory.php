<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitInventory extends Model
{
    protected $table = 'kit_inventory';

    protected $fillable = [
        'part_name',
        'current_stock',
        'min_level',
        'amazon_stock',
        'amazon_min_level',
        'shopify_stock',
        'shopify_min_level',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'integer',
            'min_level' => 'integer',
            'amazon_stock' => 'integer',
            'amazon_min_level' => 'integer',
            'shopify_stock' => 'integer',
            'shopify_min_level' => 'integer',
        ];
    }
}
