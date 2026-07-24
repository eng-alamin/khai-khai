<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * QuickAddressComponent
 *
 * A lightweight, global, create-only address modal.
 * Meant to sit alongside <livewire:customer.cart-component /> in the
 * customer layout so it is available on every page.
 *
 * Flow:
 *  1. CartComponent::placeOrder() dispatches 'open-quick-address' when the
 *     authenticated customer has no saved address yet.
 *  2. This component opens its modal, lets the user pin a location on the
 *     map, and saves a new CustomerAddress.
 *  3. On success it dispatches 'address-saved', which CartComponent listens
 *     for to automatically resume/retry order placement.
 */
class QuickAddressComponent extends Component
{
    public bool $showModal = false;

    // Form fields
    public string $label       = 'home';
    public string $fullAddress = '';
    public string $city        = 'Dhaka';
    public string $postalCode  = '';
    public ?float $latitude    = null;
    public ?float $longitude   = null;

    public array $labelOptions = [
        'home'   => ['icon' => 'fa-home',     'text' => 'Home'],
        'office' => ['icon' => 'fa-briefcase','text' => 'Office'],
        'other'  => ['icon' => 'fa-map-pin',  'text' => 'Other'],
    ];

    protected function rules(): array
    {
        return [
            'label'       => 'required|in:home,office,other',
            'fullAddress' => 'required|string|max:500',
            'city'        => 'required|string|max:60',
            'postalCode'  => 'nullable|string|max:10',
            // Lat/Lng are required here (unlike the full AddressComponent)
            // because delivery-fee calculation and rider navigation depend
            // on having a precise pin for a brand-new / first address.
            'latitude'    => 'required|numeric|between:-90,90',
            'longitude'   => 'required|numeric|between:-180,180',
        ];
    }

    protected function messages(): array
    {
        return [
            'latitude.required'  => 'Please pin your location on the map.',
            'longitude.required' => 'Please pin your location on the map.',
        ];
    }

    /**
     * Triggered by CartComponent when the customer has no saved address.
     */
    #[On('open-quick-address')]
    public function open(): void
    {
        if (! Auth::check()) {
            return;
        }

        $this->resetForm();
        $this->showModal = true;
        $this->dispatch('open-quick-address-modal');
    }

    public function cancel(): void
    {
        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('close-quick-address-modal');
    }

    public function save(): void
    {
        $this->validate();

        $user = Auth::user();

        try {
            $address = DB::transaction(function () use ($user) {
                $isFirst = $user->addresses()->count() === 0;

                $address = $user->addresses()->create([
                    'label'        => $this->label,
                    'full_address' => $this->fullAddress,
                    'city'         => $this->city,
                    'postal_code'  => $this->postalCode ?: null,
                    'latitude'     => $this->latitude,
                    'longitude'    => $this->longitude,
                    'is_default'   => $isFirst,
                ]);

                if ($isFirst) {
                    $user->customerProfile?->update(['default_address_id' => $address->id]);
                }

                activity()
                    ->causedBy($user)
                    ->performedOn($address)
                    ->log('Customer address created (quick add before order)');

                return $address;
            });

            $this->showModal = false;
            $this->resetForm();

            $this->dispatch('close-quick-address-modal');
            $this->dispatch('show-toast', message: 'ঠিকানা যোগ হয়েছে ✅', type: 'success');

            // Tell CartComponent it can now retry placing the order.
            $this->dispatch('address-saved', addressId: $address->id);

        } catch (\Throwable $e) {
            Log::error('Quick address save failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $this->dispatch('show-toast', message: 'ঠিকানা সেভ করা যায়নি। আবার চেষ্টা করুন।', type: 'error');
        }
    }

    private function resetForm(): void
    {
        $this->label       = 'home';
        $this->fullAddress = '';
        $this->city        = 'Dhaka';
        $this->postalCode  = '';
        $this->latitude    = null;
        $this->longitude   = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.customer.quick-address-component');
    }
}