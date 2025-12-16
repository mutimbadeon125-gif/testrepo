<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'order_number',
        'total',
        'status',
        'delivery_address',
        'phone_number',
        'delivery_date',
        'special_instructions',
        'payment_method',
        'payment_status',
        'cancellation_reason',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'delivery_date' => 'date',
    ];

    // Relationships
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
