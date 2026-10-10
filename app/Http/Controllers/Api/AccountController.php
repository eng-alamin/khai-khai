<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeleteAccountRequest;
use App\Models\CustomerAddress;
use App\Models\DeviceToken;
use App\Models\Favorite;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Refund;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * In-app account deletion (required by Google Play and the App Store).
 *
 * The user row is ANONYMISED, not removed: orders, coupon usages and reviews
 * point at it with restricting foreign keys, and order / payment records must
 * be kept for the business. The person can no longer sign in, their phone and
 * email are freed (a new account may use them), and everything personal that
 * is not a business record is deleted.
 */
class AccountController extends Controller
{
    /** Orders in these statuses are finished; anything else is still in progress. */
    private const FINAL_STATUSES = ['delivered', 'cancelled', 'rejected'];

    /** POST /me/delete {password} */
    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check((string) $request->validated('password'), $user->password)) {
            // 422, never 401: the app signs the customer out on every 401.
            throw ValidationException::withMessages([
                'password' => ['The password is incorrect.'],
            ]);
        }

        $blocked = DB::transaction(function () use ($user): ?array {
            // Same lock the order-placing code relies on for this customer, so
            // an order cannot slip in between the check and the anonymising.
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $hasActiveOrder = Order::query()
                ->where('customer_id', $user->id)
                ->whereNotIn('status', self::FINAL_STATUSES)
                ->exists();

            if ($hasActiveOrder) {
                return [
                    'message' => 'You have an order in progress. Please wait until it is delivered or cancelled, then try again.',
                    'code'    => 'active_orders',
                ];
            }

            $hasPendingRefund = Refund::query()
                ->where('customer_id', $user->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingRefund) {
                return [
                    'message' => 'A refund for you is still being processed. Please try again after it is paid.',
                    'code'    => 'pending_refund',
                ];
            }

            // All sessions, this one included.
            $user->tokens()->delete();

            // Personal data that is not a business record.
            DeviceToken::query()->where('user_id', $user->id)->delete();
            Favorite::query()->where('user_id', $user->id)->delete();
            Notification::query()->where('user_id', $user->id)->forceDelete();
            SupportTicket::query()->where('customer_id', $user->id)->delete(); // messages cascade
            CustomerAddress::query()->where('customer_id', $user->id)->delete();

            // Anonymise the account itself. The random password cannot be guessed
            // or reset, and is_active=false blocks any leftover token.
            $user->forceFill([
                'name'              => 'Deleted user',
                'phone'             => null,
                'email'             => null,
                'avatar'            => null,
                'password'          => Str::random(64), // hashed by the model cast
                'is_active'         => false,
                'is_verified'       => false,
                'email_verified_at' => null,
                'points'            => 0,
                'remember_token'    => null,
                'last_login_ip'     => null,
            ])->save();

            // No personal data in the log entry.
            activity()->performedOn($user)->log('Customer deleted account (mobile app)');

            return null;
        });

        if ($blocked !== null) {
            return response()->json($blocked, 422);
        }

        return response()->json(['message' => 'Your account has been deleted.']);
    }
}
