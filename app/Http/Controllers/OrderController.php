<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Place a new order
     */
    public function store(OrderRequest $request)
    {
        return DB::transaction(function () use ($request) {

            $user = auth()->user();

            $cart = Cart::with('items.product')
                ->where('user_id', $user->id)
                ->first();

            if (!$cart || $cart->items->isEmpty()) {
                abort(400, 'Your cart is empty');
            }

            $vendorId = $cart->items->first()->product->vendor_id;

            $subtotal = $cart->items->sum(fn ($i) => $i->price * $i->quantity);
            $tax = $subtotal * 0.10;
            $total = $subtotal + $tax;

            $order = Order::create([
                'user_id' => $user->id,
                'vendor_id' => $vendorId,
                'order_number' => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'total' => $total,
                'status' => 'pending',
                'delivery_address' => $request->delivery_address,
                'phone_number' => $request->phone_number,
                'delivery_date' => $request->delivery_date,
                'special_instructions' => $request->special_instructions,
                'payment_status' => 'pending'
            ]);

            foreach ($cart->items as $item) {

                if ($item->product->stock < $item->quantity) {
                    abort(400, "Not enough stock for {$item->product->name}");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->quantity * $item->price
                ]);

                $item->product->decrement('stock', $item->quantity);
            }

            $cart->items()->delete();

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order->load('items.product')
            ], 201);
        });
    }


    /**
     * Get logged-in user's orders
     */
    public function index(Request $request)
    {
        return response()->json(
            Order::where('user_id', auth()->id())
                ->with('vendor')
                ->when($request->status, fn ($q) => $q->where('status', $request->status))
                ->orderByDesc('created_at')
                ->paginate($request->limit ?? 10)
        );
    }

    /**
     * Get single order (customer)
     */
    public function show($id)
    {
        $order = Order::with(['items.product', 'vendor', 'payment'])
            ->where('user_id', auth()->id())
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    /**
     * Cancel order (customer)
     */
    public function cancel(Request $request, $id)
    {
        $order = Order::where('user_id', auth()->id())
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json(['message' => 'Order can no longer be cancelled'], 400);
        }

        $order->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->reason
        ]);

        return response()->json([
            'message' => 'Order cancelled',
            'order' => $order
        ]);
    }

    /**
     * Vendor: get vendor orders
     */
    public function vendorOrders(Request $request)
    {
        if (auth()->user()->role !== 'vendor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(
            Order::where('vendor_id', auth()->user()->vendor->id)
                ->with('items.product', 'user')
                ->when($request->status, fn ($q) => $q->where('status', $request->status))
                ->orderByDesc('created_at')
                ->paginate($request->limit ?? 10)
        );
    }

    /**
     * Vendor: update order status
     */
    public function updateStatus(Request $request, $id)
    {
        if (auth()->user()->role !== 'vendor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,ready for pickup,in_transit,delivered,cancelled'
        ]);

        $order = Order::where('vendor_id', auth()->user()->vendor->id)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Order status updated',
            'order' => $order
        ]);
    }
}
