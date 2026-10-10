<?php

namespace App\Services;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * One place for the customer address rules, used by the mobile API.
 * Same behaviour as the website's AddressComponent / ProfileComponent:
 *  - the first address becomes the default
 *  - exactly one default per customer
 *  - deleting the default promotes another address
 *  - customer_profiles.default_address_id is kept in step (when a profile row exists)
 *
 * Every query starts from $user->addresses(), so a customer can never read
 * or change another customer's address.
 */
class CustomerAddressService
{
    /** Stops one account from storing an endless number of addresses. */
    public const MAX_ADDRESSES = 10;

    /** Default first, then oldest first (same order as the website). */
    public function listFor(User $user): Collection
    {
        return $user->addresses()
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function findFor(User $user, int $id): CustomerAddress
    {
        return $user->addresses()->findOrFail($id);
    }

    /** @param array<string, mixed> $data label, full_address, city, postal_code, latitude, longitude */
    public function create(User $user, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($user, $data) {
            // Lock the user row so two quick taps cannot create two defaults.
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $count = $user->addresses()->count();

            if ($count >= self::MAX_ADDRESSES) {
                throw ValidationException::withMessages([
                    'full_address' => ['You can save up to ' . self::MAX_ADDRESSES . ' addresses. Delete one first.'],
                ]);
            }

            $isFirst = $count === 0;

            $address = $user->addresses()->create([
                'label'        => $data['label'],
                'full_address' => $data['full_address'],
                'city'         => $data['city'],
                'postal_code'  => $data['postal_code'] ?? null,
                'latitude'     => $data['latitude'] ?? null,
                'longitude'    => $data['longitude'] ?? null,
                'is_default'   => $isFirst,
            ]);

            if ($isFirst) {
                $this->syncProfileDefault($user, $address->id);
            }

            activity()->causedBy($user)->performedOn($address)->log('Customer address created (mobile app)');

            return $address;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, CustomerAddress $address, array $data): CustomerAddress
    {
        $address->update([
            'label'        => $data['label'],
            'full_address' => $data['full_address'],
            'city'         => $data['city'],
            'postal_code'  => $data['postal_code'] ?? null,
            'latitude'     => $data['latitude'] ?? null,
            'longitude'    => $data['longitude'] ?? null,
        ]);

        activity()->causedBy($user)->performedOn($address)->log('Customer address updated (mobile app)');

        return $address;
    }

    public function setDefault(User $user, CustomerAddress $address): void
    {
        DB::transaction(function () use ($user, $address) {
            $user->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);

            $this->syncProfileDefault($user, $address->id);

            activity()->causedBy($user)->performedOn($address)->log('Customer default address changed (mobile app)');
        });
    }

    public function delete(User $user, CustomerAddress $address): void
    {
        DB::transaction(function () use ($user, $address) {
            $wasDefault = (bool) $address->is_default;

            $address->delete();

            if ($wasDefault) {
                $next = $user->addresses()->orderBy('id')->first();

                if ($next) {
                    $next->update(['is_default' => true]);
                    $this->syncProfileDefault($user, $next->id);
                }
            }

            activity()->causedBy($user)->log('Customer address deleted (mobile app)');
        });
    }

    /**
     * Profile screen behaviour (same as ProfileComponent::saveProfile):
     * edit the default address in place, or create one if none exists.
     *
     * @param array<string, mixed> $data full_address, city, postal_code
     */
    public function saveDefaultFromProfile(User $user, array $data): void
    {
        $default = $user->addresses()->where('is_default', true)->first();

        if ($default) {
            $changed = trim((string) $default->full_address) !== trim((string) $data['full_address'])
                || $default->city !== $data['city'];

            $default->fill([
                'full_address' => $data['full_address'],
                'city'         => $data['city'],
                'postal_code'  => $data['postal_code'] ?? null,
            ]);

            // The profile screen has no map. If the text or city changed, the
            // old pin no longer describes this address (it would keep pricing
            // delivery from the previous place), so it is cleared and the
            // customer pins the new spot at checkout.
            if ($changed) {
                $default->latitude  = null;
                $default->longitude = null;
            }

            $default->save();

            return;
        }

        $user->addresses()->update(['is_default' => false]);

        $address = $user->addresses()->create([
            'label'        => 'home',
            'full_address' => $data['full_address'],
            'city'         => $data['city'],
            'postal_code'  => $data['postal_code'] ?? null,
            'is_default'   => true,
        ]);

        $this->syncProfileDefault($user, $address->id);
    }

    private function syncProfileDefault(User $user, int $addressId): void
    {
        $user->customerProfile?->update(['default_address_id' => $addressId]);
    }
}
