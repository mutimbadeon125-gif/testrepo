<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    private function ensureAdmin(){
        if (auth()->user()->role !== 'admin'){
            abort(response()->json(['message' => 'Unauthorized'],403));
        }
    }

    /**
     * GET /admin/orders
     */

    public function index(Request $request){
        $this->ensureAdmin();

        $orders = Order::with([
            'user:id,name,email,phone',
            'vendor:id,business_name'
        ])
        ->orderBy('created_at', 'desc')
        ->paginate($request->limit ?? 20);

        return response()->json($orders);
    }

    public function updateStatus(Request $request, Order $order){
        $this->ensureAdmin();

        $validated = $request->validate([
            'status'=> [
                'required',
                Rule::in([
                    'pending',
                    'confirmed',
                    'processing',
                    'raedy_for_pickup',
                    'in_transit',
                    'delivered',
                    'cancelled',
                ])
            ],
            'cancellation_reason' => 'nullable|string|max:500'
        ]);

        if ($validated['status'] === 'cancelled' && empty($validated['cancellation_reason'])){
            return response()->json(['message' => 'Cancellation reason is required when cancelling an order.'], 422);
        }

        $order->update([
            'status' => $validated['status'],
            'cancellation_reason' => $validated['cancellation_reason'] ?? null,
        ]);

        return response()->json([
            'message' => 'Order status updated successfully.',
            'order' => $order
        ]);
    }
}
