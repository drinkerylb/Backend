<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $coupons = Coupon::query()
            ->when(request('active_only'), function ($query) {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('valid_until')
                            ->orWhere('valid_until', '>=', now());
                    });
            })
            ->paginate(request('per_page', 15));

        return response()->json($coupons);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'valid_from' => 'required|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'max_uses' => 'nullable|integer|min:1',
            'min_order_value' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $coupon = Coupon::create($validated);

        return response()->json($coupon, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Coupon $coupon)
    {
        return response()->json($coupon);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|unique:coupons,code,' . $coupon->id,
            'type' => 'sometimes|in:percentage,fixed',
            'value' => 'sometimes|numeric|min:0',
            'valid_from' => 'sometimes|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'max_uses' => 'nullable|integer|min:1',
            'min_order_value' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $coupon->update($validated);

        return response()->json($coupon);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json(null, 204);
    }

    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'order_total' => 'required|numeric|min:0'
        ]);

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'message' => 'Coupon not found'
            ], 404);
        }

        if (!$coupon->isValid()) {
            return response()->json([
                'valid' => false,
                'message' => 'Coupon is not valid'
            ], 400);
        }

        $discount = $coupon->calculateDiscount($request->order_total);

        return response()->json([
            'valid' => true,
            'discount' => $discount,
            'final_total' => $request->order_total - $discount,
            'coupon' => $coupon
        ]);
    }
}
