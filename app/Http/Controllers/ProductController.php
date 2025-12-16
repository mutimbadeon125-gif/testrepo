<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Public: list products
     */
    public function index(Request $request)
    {
        $query = Product::with(['vendor', 'images'])
            ->where('status', 'active');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('featured')) {
            $query->where('featured', (bool) $request->featured);
        }

        match ($request->sort) {
            'latest' => $query->orderByDesc('created_at'),
            'price_low' => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            default => null,
        };

        return response()->json(
            $query->paginate($request->limit ?? 10)
        );
    }

    /**
     * Public: search products
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1',
        ]);

        $query = Product::with(['vendor', 'images'])
            ->where('status', 'active')
            ->where('name', 'like', '%' . $request->q . '%');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        return response()->json(
            $query->paginate($request->limit ?? 10)
        );
    }

    /**
     * Public: vendor products
     */
    public function vendorProducts($vendorId)
    {
        return response()->json(
            Product::where('vendor_id', $vendorId)
                ->with('images')
                ->paginate(10)
        );
    }

    /**
     * Vendor: create product
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'vendor') {
            return response()->json(['message' => 'Only vendors allowed'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'stock' => 'required|integer|min:0',
            'image_url' => 'nullable|image|max:2048',
            'featured' => 'boolean',
        ]);

        if ($request->hasFile('image_url')){
            $data['image_url'] = $request->file("image_url")->store('products', 'public');
        }

        $data['vendor_id'] = auth()->user()->vendor->id;

        $data['sku'] = strtoupper(Str::random(10));     // AUTO SKU
        $data['original_price'] = $request->original_price ?? $data['price'];
        $data['status'] = 'active';

        $product = Product::create($data);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    /**
     * Public: show product details
     */
    public function vendorShow($id){
        if (auth()->user()->role !== 'vendor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product = Product::where('vendor_id', auth()->user()->vendor->id)
            ->where('id', $id)
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        return response()->json($product);
    }

    public function show($id)
    {
        $product = Product::with([
            'vendor',
            'images',
            'reviews.user'
        ])->find($id);
        
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    
        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category,
            'price' => $product->price,
            'original_price' => $product->original_price,
            'stock' => $product->stock,
            'sku' => $product->sku,
            'weight' => $product->weight,
            'origin' => $product->origin,
            'harvest_date' => $product->harvest_date,
            'image_url' => $product->image_url,
            'images' => $product->images,
        
            // ⭐ vendor (safe)
            'vendor_id' => optional($product->vendor)->id,
            'vendor_name' => optional($product->vendor)->business_name,
            'vendor_location' => optional($product->vendor)->location,
        
            // ⭐ reviews
            'rating' => round($product->reviews->avg('rating'), 1),
            'reviews_count' => $product->reviews->count(),
            'reviews' => $product->reviews,
        
            'created_at' => $product->created_at,
        ]);
    }


    /**
     * Vendor: update product
     */
    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'vendor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product = Product::where('vendor_id', auth()->user()->vendor->id)
            ->where('id', $id)
            ->first();

        if ($request->hasFile('image_url')){
            Storage::disk('public')->delete($product->image_url);
            $product->image_url = $request->file('image_url')->store('products', 'public');
        }

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->update(
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|numeric|min:0',
                'category' => 'sometimes|string|max:100',
                'stock' => 'sometimes|integer|min:0',
                'status' => 'sometimes|in:active,inactive',
                'featured' => 'boolean',
            ])
        );

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    /**
     * Vendor: delete product
     */
    public function destroy($id)
    {
        if (auth()->user()->role !== 'vendor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product = Product::where('vendor_id', auth()->user()->vendor->id)
            ->where('id', $id)
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
