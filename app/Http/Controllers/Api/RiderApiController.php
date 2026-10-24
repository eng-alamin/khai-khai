<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\RiderEarning;
use App\Models\RiderProfile;
use App\Models\User;
use App\Services\RiderAssignmentService;
use App\Services\RiderCashService;
use App\Services\RiderDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RiderApiController extends Controller
{
    /** POST /api/rider/login */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required'],
            'password' => ['required'],
        ]);

        $phone = trim((string) $request->input('phone'));
        $user = User::where('phone', $phone)->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            return response()->json(['message' => 'ফোন নম্বর অথবা পাসওয়ার্ড সঠিক নয়।'], 422);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'আপনার অ্যাকাউন্টটি নিষ্ক্রিয় করা হয়েছে।'], 403);
        }

        if (! $user->isRider()) {
            return response()->json(['message' => 'এই অ্যাকাউন্টটি রাইডার হিসেবে নিবন্ধিত নয়। রোল: ' . ($user->role ?? 'none')], 403);
        }

        $token = $user->createToken('khaikhai-rider-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ]);
    }

    /** GET /api/rider/dashboard */
    public function dashboard(Request $request): JsonResponse
    {
        $rider = $request->user();
        $profile = RiderProfile::where('user_id', $rider->id)->first();

        // 1. Today's earnings
        $todayEarnings = RiderEarning::where('rider_id', $rider->id)
            ->whereDate('created_at', today())
            ->selectRaw('COUNT(*) as deliveries, COALESCE(SUM(amount), 0) as total')
            ->first();

        // 2. Cash in hand (COD orders)
        $cashService = app(RiderCashService::class);
        $cashInHand = $cashService->cashInHand($rider->id);

        // 3. Ongoing active order (if any)
        $activeOrder = Order::where('rider_id', $rider->id)
            ->whereIn('status', ['ready', 'picked_up'])
            ->with(['restaurant', 'items'])
            ->first();

        // 4. Available orders in zone
        $availableOrders = [];
        $isOnline = (bool) ($profile?->is_online ?? false);

        if ($profile && $isOnline && ! $activeOrder) {
            $availableOrders = Order::where('status', 'ready')
                ->whereNull('rider_id')
                ->with(['restaurant', 'items'])
                ->latest()
                ->take(10)
                ->get()
                ->map(fn($o) => $this->formatOrder($o, false));
        }

        return response()->json([
            'data' => [
                'rider' => [
                    'id' => $rider->id,
                    'name' => $rider->name,
                    'phone' => $rider->phone,
                    'is_online' => $isOnline,
                    'is_active' => $isOnline,
                    'zone' => $profile?->zone ?? 'Dhaka',
                ],
                'summary' => [
                    'today_deliveries' => (int) ($todayEarnings->deliveries ?? 0),
                    'today_earned' => (float) ($todayEarnings->total ?? 0),
                    'cash_in_hand' => (float) $cashInHand,
                ],
                'active_order' => $activeOrder ? $this->formatOrder($activeOrder, true) : null,
                'available_orders' => $availableOrders,
            ]
        ]);
    }

    /** POST /api/rider/toggle-duty */
    public function toggleDuty(Request $request): JsonResponse
    {
        $rider = $request->user();
        $profile = RiderProfile::where('user_id', $rider->id)->first();

        if (! $profile) {
            $profile = new RiderProfile();
            $profile->user_id = $rider->id;
            $profile->vehicle_type = 'bike';
            $profile->is_approved = true;
            $profile->is_online = false;
        }

        // Database uses is_online column
        $profile->is_online = ! $profile->is_online;
        $profile->save();

        return response()->json([
            'message' => $profile->is_online ? 'Online' : 'Offline',
            'is_online' => (bool) $profile->is_online,
            'is_active' => (bool) $profile->is_online,
        ]);
    }

    /** POST /api/rider/location */
    public function updateLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        RiderProfile::where('user_id', $request->user()->id)->update([
            'current_lat' => $validated['latitude'],
            'current_lng' => $validated['longitude'],
            'location_updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /** POST /api/rider/orders/{id}/accept */
    public function acceptOrder(int $id, Request $request): JsonResponse
    {
        $rider = $request->user();

        $affected = Order::where('id', $id)
            ->where('status', 'ready')
            ->whereNull('rider_id')
            ->update(['rider_id' => $rider->id, 'accepted_at' => now()]);

        if ($affected === 0) {
            return response()->json(['message' => 'অর্ডারটি অন্য কোনো রাইডার গ্রহণ করেছেন।'], 400);
        }

        app(RiderAssignmentService::class)->open($id, $rider->id);

        OrderStatusLog::create([
            'order_id' => $id,
            'from_status' => 'ready',
            'to_status' => 'ready',
            'changed_by' => $rider->id,
            'changed_type' => 'rider',
        ]);

        return response()->json(['message' => 'অর্ডার সফলভাবে গ্রহণ করেছেন!']);
    }

    /** POST /api/rider/orders/{id}/pickup */
    public function confirmPickup(int $id, Request $request): JsonResponse
    {
        $order = Order::where('id', $id)
            ->where('rider_id', $request->user()->id)
            ->where('status', 'ready')
            ->firstOrFail();

        $order->forceFill(['status' => 'picked_up', 'picked_up_at' => now()])->save();

        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => 'ready',
            'to_status' => 'picked_up',
            'changed_by' => $request->user()->id,
            'changed_type' => 'rider',
        ]);

        return response()->json(['message' => 'খাবার পিকআপ সম্পন্ন!']);
    }

    /** POST /api/rider/orders/{id}/delivered */
    public function completeDelivery(int $id, Request $request): JsonResponse
    {
        $order = app(RiderDeliveryService::class)->completeDelivery($id, $request->user());

        return response()->json([
            'message' => 'ডেলিভারি সফল এবং ক্যাশ টাকা সংগ্রহ রেকর্ড হয়েছে!',
            'order_number' => $order->order_number,
        ]);
    }

    private function formatOrder(Order $order, bool $isOwned): array
    {
        $snapshot = is_array($order->delivery_address_snapshot)
            ? $order->delivery_address_snapshot
            : json_decode($order->delivery_address_snapshot ?? '{}', true);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'total_amount' => (float) $order->total_amount,
            'delivery_fee' => (float) $order->delivery_fee,
            'customer' => [
                'name' => $isOwned ? ($order->customer->name ?? 'Customer') : 'Customer',
                'phone' => $isOwned ? ($order->customer->phone ?? '') : '',
                'address' => $snapshot['full_address'] ?? 'Customer Address',
            ],
            'restaurant' => [
                'name' => $order->restaurant->name ?? 'Restaurant',
                'phone' => $order->restaurant->phone ?? '',
                'address' => $order->restaurant->address ?? '',
            ],
            'items' => $order->items->map(fn($i) => [
                'name' => $i->item_name,
                'qty' => $i->quantity,
            ]),
        ];
    }
}