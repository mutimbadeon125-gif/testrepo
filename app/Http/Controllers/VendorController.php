<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VendorController extends Controller
{
    private function ensureVendor()
    {
        if (auth()->user()->role !== 'vendor') {
            abort(response()->json(['message' => 'Unauthorized'], 403));
        }
    }

    /**
     * GET /vendor/dashboard
     */
    public function dashboard()
    {
        $this->ensureVendor();
        $vendorId = auth()->user()->vendor->id;

        return response()->json([
            'stats' => [
                'total_orders' => Order::where('vendor_id', $vendorId)->count(),
                'pending_orders' => Order::where('vendor_id', $vendorId)->where('status', 'pending')->count(),
                'completed_orders' => Order::where('vendor_id', $vendorId)->where('status', 'delivered')->count(),
                'total_revenue' => Order::where('vendor_id', $vendorId)
                    ->where('payment_status', 'completed')
                    ->sum('total'),
                'active_products' => Product::where('vendor_id', $vendorId)->where('status', 'active')->count(),
                'low_stock' => Product::where('vendor_id', $vendorId)->where('stock', '<=', 5)->count(),
            ]
        ]);
    }

    /**
     * GET /vendor/orders
     */
    public function orders(Request $request)
    {
        $this->ensureVendor();

        return response()->json(
            Order::where('vendor_id', auth()->user()->vendor->id)
                ->with('items.product', 'user')
                ->orderBy('created_at', 'desc')
                ->paginate(20)
        );
    }

    /**
     * GET /vendor/analytics
     */
    public function analytics()
    {
        $this->ensureVendor();

        $vendorId = auth()->user()->vendor->id;
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');

            $data[] = [
                'date' => $date,
                'revenue' => Order::where('vendor_id', $vendorId)
                    ->where('payment_status', 'completed')
                    ->whereDate('created_at', $date)
                    ->sum('total'),
            ];
        }

        return response()->json($data);
    }

    /**
     * GET /vendors/{id}
     */
    public function show($id)
    {
        $vendor = Vendor::with('user')->find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        return response()->json($vendor);
    }

    /**
     * PUT /vendors/{id}
     */
    public function update(Request $request, $id)
    {
        $this->ensureVendor();

        $vendor = auth()->user()->vendor;
        if ($vendor->id != $id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'business_name' => 'required|string|max:255',
            'business_category' => 'required|string|max:100',
            'location' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        $vendor->update($request->only([
            'business_name',
            'business_category',
            'location',
            'phone'
        ]));

        return response()->json([
            'message' => 'Vendor profile updated',
            'vendor' => $vendor
        ]);
    }

    /**
     * GET /vendors/nearby
     */

    public function nearby(Request $request){
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|numeric'
        ]);

        //TEMP: simple implemantation (no GIS yet)
        return response()->json(
            Vendor::where('is_approved', true)
            ->with('user')
            ->limit(20)
            ->get()
        );
    }

    
    public function myProducts(Request $request)
    {
        if (auth()->user()->role !== 'vendor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        return response()->json(
            Product::where('vendor_id', auth()->user()->vendor->id)
                ->orderBy('created_at', 'desc')
                ->paginate($request->limit ?? 10)
        );
    }


}
