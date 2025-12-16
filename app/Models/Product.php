<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'vendor_id',
        'name',
        'description',
        'category',
        'price',
        'original_price',
        'stock',
        'sku',
        'weight',
        'origin',
        'harvest_date',
        'image_url',
        'rating',
        'status',
        'featured'
    ];

    protected $casts = [
        'harvest_date' => 'date',
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'featured' => 'boolean',
        'rating' => 'decimal:2',
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
        // return $this->morphMany(Review::class, 'reviewable');
    }

}
