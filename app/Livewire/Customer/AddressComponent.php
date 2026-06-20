<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomerAddress;

class AddressComponent extends Component
{
    // Modal state
    public bool   $showModal    = false;
    public bool   $isEditing    = false;
    public ?int   $editingId    = null;

    // Form fields
    public string $label       = 'home';
    public string $fullAddress = '';
    public string $city        = 'Dhaka';
    public string $postalCode  = '';

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
        ];
    }

    /* ── Open modal for new address ── */
    public function openAddModal(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->editingId = null;
        $this->showModal = true;
        $this->dispatch('open-address-modal');
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
        $this->isEditing   = true;
        $this->showModal   = true;
        $this->dispatch('open-address-modal');
    }

    /* ── Save (create or update) ── */
    public function save(): void
    {
        $this->validate();

        $user = Auth::user();

        if ($this->isEditing && $this->editingId) {
            CustomerAddress::where('customer_id', $user->id)
                ->where('id', $this->editingId)
                ->update([
                    'label'        => $this->label,
                    'full_address' => $this->fullAddress,
                    'city'         => $this->city,
                    'postal_code'  => $this->postalCode ?: null,
                ]);

            $this->dispatch('show-toast', message: 'Address updated ✅', type: 'success');
        } else {
            $isFirst = $user->addresses()->count() === 0;

            $user->addresses()->create([
                'label'        => $this->label,
                'full_address' => $this->fullAddress,
                'city'         => $this->city,
                'postal_code'  => $this->postalCode ?: null,
                'is_default'   => $isFirst,
            ]);

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
        $user->addresses()->update(['is_default' => false]);
        $user->addresses()->where('id', $id)->update(['is_default' => true]);

        $user->customerProfile?->update(['default_address_id' => $id]);

        $this->dispatch('show-toast', message: 'Default address updated ✅', type: 'success');
    }

    /* ── Delete ── */
    public function delete(int $id): void
    {
        $addr = CustomerAddress::where('customer_id', Auth::id())->findOrFail($id);
        $wasDefault = $addr->is_default;
        $addr->delete();

        if ($wasDefault) {
            $next = Auth::user()->addresses()->first();
            if ($next) {
                $next->update(['is_default' => true]);
                Auth::user()->customerProfile?->update(['default_address_id' => $next->id]);
            }
        }

        $this->dispatch('show-toast', message: 'Address deleted.', type: 'info');
    }

    private function resetForm(): void
    {
        $this->label       = 'home';
        $this->fullAddress = '';
        $this->city        = 'Dhaka';
        $this->postalCode  = '';
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