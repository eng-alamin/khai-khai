<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The customer's notification list (the rows OrderNotifier writes) and their
 * push settings. Every query is limited to the signed-in customer.
 */
class NotificationController extends Controller
{
    /** GET /notifications — newest 50 + unread count. */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $items = Notification::query()
            ->forUser($userId)
            ->latest()
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (Notification $n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'title'      => $n->title,
                'body'       => $n->body,
                'data'       => (object) ($n->data ?? []),
                'read'       => $n->read_at !== null,
                'created_at' => $n->created_at?->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'data' => [
                'items'        => $items,
                'unread_count' => Notification::query()->forUser($userId)->unread()->count(),
            ],
        ]);
    }

    /** POST /notifications/{id}/read */
    public function read(Request $request, int $id): JsonResponse
    {
        $notification = Notification::query()->forUser($request->user()->id)->findOrFail($id);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['message' => 'Read.']);
    }

    /** POST /notifications/read-all */
    public function readAll(Request $request): JsonResponse
    {
        Notification::query()
            ->forUser($request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All read.']);
    }

    /** GET /notification-settings */
    public function settings(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->settingsFor($request->user()->id)]);
    }

    /** PUT /notification-settings {push_order_updates?, push_promotions?} */
    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'push_order_updates' => ['sometimes', 'boolean'],
            'push_promotions'    => ['sometimes', 'boolean'],
        ]);

        $profile = CustomerProfile::query()->firstOrCreate(['customer_id' => $request->user()->id]);

        // These columns are deliberately not mass-assignable.
        $profile->forceFill($data)->save();

        return response()->json(['data' => $this->settingsFor($request->user()->id)]);
    }

    /** @return array{push_order_updates: bool, push_promotions: bool} */
    private function settingsFor(int $userId): array
    {
        $profile = CustomerProfile::query()->where('customer_id', $userId)->first();

        return [
            'push_order_updates' => $profile ? (bool) $profile->push_order_updates : true,
            'push_promotions'    => $profile ? (bool) $profile->push_promotions : true,
        ];
    }
}
