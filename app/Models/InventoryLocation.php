<?php

namespace App\Models;

use Database\Factories\InventoryLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLocation extends Model
{
    /** @use HasFactory<InventoryLocationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];
}
