<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->decimal('total', 13, 2);
            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'ready for pickup',
                'in_transit',
                'delivered',
                'cancelled',
            ])->default('pending');
            $table->text('delivery_address');
            $table->string('phone_number');
            $table->date('delivery_date')->nullable();
            $table->text('special_instructions')->nullable();
            // $table->string('payment')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('pending');
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
