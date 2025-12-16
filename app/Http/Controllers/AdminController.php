<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Ensure admin access
     */
    private function ensureAdmin()
    {
        if (auth()->user()->role !== 'admin') {
            abort(response()->json(['message' => 'Admin only'], 403));
        }
    }

    /**
     * ADMIN DASHBOARD
     * GET /admin/dashboard
     */
    public function dashboard()
    {
        $this->ensureAdmin();

        return response()->json([
            'stats' => [
                'total_users'       => User::count(),
                'total_vendors'     => Vendor::count(),
                'pending_vendors'   => Vendor::where('status', 'pending')->count(),
                'total_products'    => Product::count(),
                'total_orders'      => Order::count(),
                'completed_orders'  => Order::where('status', 'delivered')->count(),
                'revenue'           => Order::where('payment_status', 'completed')->sum('total'),
            ],
            'sales' => $this->salesAnalyticsData()
        ]);
    }

    /**
     * SALES ANALYTICS (internal helper)
     */
    private function salesAnalyticsData()
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');

            $data[] = [
                'date'    => $date,
                'orders'  => Order::whereDate('created_at', $date)->count(),
                'revenue' => Order::where('payment_status', 'completed')
                                ->whereDate('created_at', $date)
                                ->sum('total'),
            ];
        }

        return $data;
    }

    /**
     * LIST USERS
     * GET /admin/users
     */
    public function users()
    {
        $this->ensureAdmin();

        return response()->json(
            User::orderBy('created_at', 'desc')->paginate(20)
        );
    }

    /**
     * UPDATE USER STATUS
     * PUT /admin/users/{id}/status
     */
    public function updateUserStatus(Request $request, $id)
    {
        $this->ensureAdmin();

        $request->validate([
            'status' => 'required|in:active,suspended'
        ]);

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->update(['status' => $request->status]);

        return response()->json(['message' => 'User status updated']);
    }

    /**
     * LIST VENDORS
     * GET /admin/vendors
     */
    public function vendors()
    {
        $this->ensureAdmin();

        return response()->json(
            Vendor::with('user')
                ->orderBy('created_at', 'desc')
                ->paginate(20)
        );
    }

    /**
     * APPROVE VENDOR
     * POST /admin/vendors/{id}/approve
     */
    public function approveVendor($id)
    {
        $this->ensureAdmin();

        $vendor = Vendor::find($id);
        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $vendor->update(['status' => 'approved']);

        return response()->json(['message' => 'Vendor approved']);
    }

    /**
     * REJECT VENDOR
     * POST /admin/vendors/{id}/reject
     */
    public function rejectVendor(Request $request, $id)
    {
        $this->ensureAdmin();

        $vendor = Vendor::find($id);
        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $vendor->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason ?? null
        ]);

        return response()->json(['message' => 'Vendor rejected']);
    }

    /**
     * LIST ALL ORDERS
     * GET /admin/orders
     */
    public function orders(Request $request)
    {
        $this->ensureAdmin();

        $orders = Order::with(['user', 'vendor'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($orders);
    }

    public function destroy($id)
    {
        Product::findOrFail($id)->delete();
    
        return response()->json([
            'message' => 'Product deleted by admin'
        ]);
    }

}
