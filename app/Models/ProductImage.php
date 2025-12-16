<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductImage extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'product_id',
        'url'
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
