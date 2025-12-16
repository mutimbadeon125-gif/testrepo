<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartUpdateRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * GET /cart
     */
    public function index()
    {
        $cart = Cart::firstOrCreate([
            'user_id' => auth()->id()
        ]);

        $cart->load('items.product.vendor');

        $subtotal = $cart->items->sum(fn ($item) => $item->price * $item->quantity);
        $tax = round($subtotal * 0.10, 2);
        $total = $subtotal + $tax;

        return response()->json([
            'id' => $cart->id,
            'items' => $cart->items->map(function ($item) {
                return [
                    'product_id'   => $item->product_id,
                    'product_name' => optional($item->product)->name,
                    'vendor_name'  => optional($item->product?->vendor)->business_name ?? null,
                    'image_url'    => optional($item->product)->image_url,
                    'price'        => $item->price,
                    'quantity'     => $item->quantity,
                    'line_total'   => $item->price * $item->quantity,
                ];
            }),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);
    }

    /**
     * POST /cart/add
     */
    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);
        $product = Product::findOrFail($data['product_id']);

        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        $newQuantity = $existingItem
            ? $existingItem->quantity + $data['quantity']
            : $data['quantity'];

        if ($product->stock < $newQuantity) {
            return response()->json([
                'message' => 'Not enough stock available'
            ], 400);
        }

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $newQuantity
            ]);
        } else {
            CartItem::create([
                'cart_id'   => $cart->id,
                'product_id'=> $product->id,
                'quantity'  => $data['quantity'],
                'price'     => $product->price,
            ]);
        }

        return response()->json([
            'message' => 'Product added to cart'
        ], 201);
    }

    /**
     * DELETE /cart/remove/{productId}
     */
    public function remove($productId)
    {
        $cart = Cart::where('user_id', auth()->id())->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Item removed from cart']);
    }

    /**
     * PUT /cart/update/{productId}
     */
    public function update(CartUpdateRequest $request, $productId)
    {
        $cart = Cart::where('user_id', auth()->id())->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        if ($item->product->stock < $request->quantity) {
            return response()->json(['message' => 'Not enough stock'], 400);
        }

        $item->update([
            'quantity' => $request->quantity
        ]);

        return response()->json(['message' => 'Cart updated']);
    }
}
