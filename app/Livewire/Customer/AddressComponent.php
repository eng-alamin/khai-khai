<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerAddress;

class AddressComponent extends Component
{
    // Modal state
    public bool   $showModal    = false;
    public bool   $isEditing    = false;
    public ?int   $editingId    = null;

    // Form fields
    public string  $label       = 'home';
    public string  $fullAddress = '';
    public string  $city        = 'Dhaka';
    public string  $postalCode  = '';
    public ?float  $latitude    = null;
    public ?float  $longitude   = null;

    public array $labelOptions = [
        'home'   => ['icon' => 'fa-home',    'text' => 'Home'],
        'office' => ['icon' => 'fa-briefcase','text' => 'Office'],
        'other'  => ['icon' => 'fa-map-pin', 'text' => 'Other'],
    ];

    protected function rules(): array
    {
        return [
            'label'       => 'required|in:home,office,other',
            'fullAddress' => 'required|string|max:500',
            'city'        => 'required|string|max:60',
            'postalCode'  => 'nullable|string|max:10',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
        ];
    }

    /* ── Open modal for new address ── */
    public function openAddModal(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->editingId = null;
        $this->showModal = true;
        $this->dispatch(
            'open-address-modal',
            latitude: null,
            longitude: null,
        );
    }

    /* ── Open modal for editing ── */
    public function openEditModal(int $id): void
    {
        $addr = CustomerAddress::where('customer_id', Auth::id())
                    ->findOrFail($id);

        $this->editingId   = $id;
        $this->label       = $addr->label;
        $this->fullAddress = $addr->full_address;
        $this->city        = $addr->city;
        $this->postalCode  = $addr->postal_code ?? '';
        $this->latitude    = $addr->latitude !== null ? (float) $addr->latitude : null;
        $this->longitude   = $addr->longitude !== null ? (float) $addr->longitude : null;
        $this->isEditing   = true;
        $this->showModal   = true;
        $this->dispatch(
            'open-address-modal',
            latitude: $this->latitude,
            longitude: $this->longitude,
        );
    }

    /* ── Set coordinates (called from the map picker via @this.set, kept here for validation clarity) ── */
    public function updatedLatitude($value): void
    {
        $this->resetErrorBag('latitude');
    }

    public function updatedLongitude($value): void
    {
        $this->resetErrorBag('longitude');
    }

    /* ── Save (create or update) ── */
    public function save(): void
    {
        $this->validate();

        $user = Auth::user();

        $payload = [
            'label'        => $this->label,
            'full_address' => $this->fullAddress,
            'city'         => $this->city,
            'postal_code'  => $this->postalCode ?: null,
            'latitude'     => $this->latitude,
            'longitude'    => $this->longitude,
        ];

        if ($this->isEditing && $this->editingId) {
            CustomerAddress::where('customer_id', $user->id)
                ->where('id', $this->editingId)
                ->update($payload);

            activity()->causedBy($user)
                ->performedOn($user->addresses()->find($this->editingId))
                ->log('Customer address updated');

            $this->dispatch('show-toast', message: 'Address updated ✅', type: 'success');
        } else {
            $isFirst = $user->addresses()->count() === 0;

            $address = $user->addresses()->create([
                ...$payload,
                'is_default' => $isFirst,
            ]);

            activity()->causedBy($user)
                ->performedOn($address)
                ->log('Customer address created');

            $this->dispatch('show-toast', message: 'Address added ✅', type: 'success');
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('close-address-modal');
    }

    /* ── Set as default ── */
    public function setDefault(int $id): void
    {
        $user = Auth::user();

        DB::transaction(function () use ($user, $id) {
            $user->addresses()->update(['is_default' => false]);
            $user->addresses()->where('id', $id)->update(['is_default' => true]);
            $user->customerProfile?->update(['default_address_id' => $id]);

            // BUG FIX: was CustomerAddress::find($id) with no customer_id
            // scope — harmless today since the update above already scoped
            // the change, but it meant the activity log's performedOn()
            // could point at the wrong record if $id ever belonged to a
            // different customer. Scope it the same way as the update.
            activity()->causedBy($user)
                ->performedOn($user->addresses()->find($id))
                ->log('Customer default address changed');
        });

        $this->dispatch('show-toast', message: 'Default address updated ✅', type: 'success');
    }

    /* ── Delete ── */
    public function delete(int $id): void
    {
        $addr = CustomerAddress::where('customer_id', Auth::id())->findOrFail($id);
        $wasDefault = $addr->is_default;
        $user = Auth::user();

        DB::transaction(function () use ($addr, $wasDefault, $user) {
            $addr->delete();

            activity()->causedBy($user)->log('Customer address deleted');

            if ($wasDefault) {
                $next = $user->addresses()->first();
                if ($next) {
                    $next->update(['is_default' => true]);
                    $user->customerProfile?->update(['default_address_id' => $next->id]);
                }
            }
        });

        $this->dispatch('show-toast', message: 'Address deleted.', type: 'info');
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
        $addresses = Auth::user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get();

        return view('livewire.customer.address-component', [
            'addresses' => $addresses,
        ])->layout('layouts.customer', [
            'title'           => 'Addresses | KhaiKhai',
            'breadcrumbTitle' => 'My Addresses',
        ]);
    }
}