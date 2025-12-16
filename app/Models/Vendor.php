<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory,HasUuids;

    
    protected $fillable = [
        'user_id',
        'business_name',
        'business_category',
        'description',
        'phone',
        'email',
        'location',
        'latitude',
        'longitude',
        'status',
        'rejection_reason',
        'verified_at',
        'document_url',
        'rating',
        'total_sales'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
