<?php

namespace App\Livewire\Admin;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VendorComponent extends Component
{
    use WithPagination, WithFileUploads;

    protected string $paginationTheme = 'bootstrap';

    // ── List / Filter ─────────────────────────────────────
    public string $search       = '';
    public int    $perPage      = 10;
    public string $statusFilter = '';

    // ── Modal ─────────────────────────────────────────────
    public bool $showModal     = false;
    public bool $showView      = false;
    public bool $confirmDelete = false;
    public ?int $deleteId      = null;
    public ?int $viewId        = null;

    // ── Form ──────────────────────────────────────────────
    public ?int    $editId          = null;
    public string  $name            = '';
    public string  $category        = '';
    public string  $emoji           = '';
    public string  $tag             = '';
    public string  $address         = '';
    public string  $city            = '';
    public string  $phone           = '';
    public string  $owner_name      = '';
    public string  $owner_email     = '';
    public string  $owner_password  = '';
    public string  $commission_rate = '15.00';
    public bool    $is_open         = true;
    public bool    $is_approved     = false;
    public bool    $is_active       = true;
    public         $logo            = null;
    public ?string $existingLogo    = null;
    public         $banner          = null;
    public ?string $existingBanner  = null;

    // ── Validation ────────────────────────────────────────
    protected function rules(): array
    {
        $passwordRule = $this->editId ? 'nullable|string|min:6' : 'required|string|min:6';

        return [
            'name'             => 'required|string|max:120',
            'category'         => 'required|string|max:60',
            'emoji'            => 'nullable|string|max:10',
            'tag'              => 'nullable|string|max:40',
            'address'          => 'required|string',
            'city'             => 'required|string|max:60',
            'phone'            => 'nullable|string|max:15',
            'commission_rate'  => 'required|numeric|min:0|max:100',
            'owner_name'       => 'required|string|max:100',
            'owner_email'      => 'required|email|max:150' . ($this->editId ? '|unique:users,email,' . $this->getOwnerId() : '|unique:users,email'),
            'owner_password'   => $passwordRule,
            'logo'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required'           => 'Restaurant name is required.',
            'category.required'       => 'Category is required.',
            'address.required'       => 'Address is required.',
            'city.required'          => 'City is required.',
            'owner_name.required'    => 'Owner name is required.',
            'owner_email.required'   => 'Owner email is required.',
            'owner_email.unique'     => 'This email is already registered.',
            'owner_password.required'=> 'Password is required.',
            'owner_password.min'     => 'Password must be at least 6 characters.',
            'logo.max'               => 'Logo must not exceed 2 MB.',
            'banner.max'             => 'Banner must not exceed 4 MB.',
        ];
    }

    // ── Helpers ───────────────────────────────────────────
    private function getOwnerId(): int
    {
        if (!$this->editId) return 0;
        return Restaurant::find($this->editId)?->owner_id ?? 0;
    }

    public function statusOf(Restaurant $r): string
    {
        if (!$r->is_approved) return 'pending';
        if (!$r->is_active)   return 'blocked';
        return 'active';
    }

    // ── Watchers ──────────────────────────────────────────
    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    // ── Open Modals ───────────────────────────────────────
    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $r = Restaurant::with('owner')->findOrFail($id);

        $this->editId           = $id;
        $this->name             = $r->name;
        $this->category         = $r->category;
        $this->emoji            = $r->emoji ?? '';
        $this->tag              = $r->tag ?? '';
        $this->address          = $r->address;
        $this->city             = $r->city;
        $this->phone            = $r->phone ?? '';
        $this->commission_rate  = (string) $r->commission_rate;
        $this->is_open          = (bool) $r->is_open;
        $this->is_approved      = (bool) $r->is_approved;
        $this->is_active        = (bool) $r->is_active;
        $this->existingLogo     = $r->logo_url;
        $this->existingBanner   = $r->banner_url;
        $this->owner_name       = $r->owner->name ?? '';
        $this->owner_email      = $r->owner->email ?? '';
        $this->owner_password   = '';

        $this->showModal = true;
    }

    public function openView(int $id): void
    {
        $this->viewId   = $id;
        $this->showView = true;
    }

    // ── Save (Create / Update) ────────────────────────────
    public function save(): void
    {
        $this->validate();

        // Handle logo upload
        $logoPath = $this->existingLogo;
        if ($this->logo) {
            if ($this->existingLogo && str_starts_with($this->existingLogo, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $this->existingLogo));
            }
            $stored   = $this->logo->store('vendor-logos', 'public');
            $logoPath = Storage::url($stored);
        }

        // Handle banner upload
        $bannerPath = $this->existingBanner;
        if ($this->banner) {
            if ($this->existingBanner && str_starts_with($this->existingBanner, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $this->existingBanner));
            }
            $stored     = $this->banner->store('vendor-banners', 'public');
            $bannerPath = Storage::url($stored);
        }

        DB::transaction(function () use ($logoPath, $bannerPath) {
            if ($this->editId) {
                // Update restaurant
                $r = Restaurant::findOrFail($this->editId);
                $r->update([
                    'name'             => $this->name,
                    'slug'             => str($this->name)->slug(),
                    'category'         => $this->category,
                    'emoji'            => $this->emoji ?: null,
                    'tag'              => $this->tag ?: null,
                    'address'          => $this->address,
                    'city'             => $this->city,
                    'phone'            => $this->phone ?: null,
                    'commission_rate'  => $this->commission_rate,
                    'is_open'          => $this->is_open,
                    'is_approved'      => $this->is_approved,
                    'is_active'        => $this->is_active,
                    'logo_url'         => $logoPath,
                    'banner_url'       => $bannerPath,
                ]);

                // Update owner
                $ownerData = ['name' => $this->owner_name, 'email' => $this->owner_email];
                if ($this->owner_password) {
                    $ownerData['password'] = Hash::make($this->owner_password);
                }
                $r->owner->update($ownerData);

                activity()->causedBy(Auth::user())->performedOn($r)->log('Admin updated vendor');

                session()->flash('success', 'Vendor updated successfully!');
            } else {
                // Create owner user
                $owner = User::create([
                    'uuid'        => (string) Str::uuid(),
                    'name'        => $this->owner_name,
                    'email'       => $this->owner_email,
                    'password'    => $this->owner_password,
                    'role'        => 'vendor',
                    'is_verified' => true,
                    'is_active'   => true,
                    'points'      => 0,
                ]);

                // Create restaurant
                $r = Restaurant::create([
                    'owner_id'         => $owner->id,
                    'name'             => $this->name,
                    'slug'             => str($this->name)->slug(),
                    'category'         => $this->category,
                    'emoji'            => $this->emoji ?: null,
                    'tag'              => $this->tag ?: null,
                    'address'          => $this->address,
                    'city'             => $this->city,
                    'phone'            => $this->phone ?: null,
                    'commission_rate'  => $this->commission_rate,
                    'is_open'          => $this->is_open,
                    'is_approved'      => $this->is_approved,
                    'is_active'        => $this->is_active,
                    'logo_url'         => $logoPath,
                    'banner_url'       => $bannerPath,
                ]);

                activity()->causedBy(Auth::user())->performedOn($r)->log('Admin created vendor');

                session()->flash('success', 'Vendor created successfully!');
            }
        });

        $this->showModal = false;
        $this->resetForm();
    }

    // ── Quick Approve / Block ─────────────────────────────
    public function approveVendor(int $id): void
    {
        $r = Restaurant::findOrFail($id);

        DB::transaction(function () use ($r) {
            $r->update(['is_approved' => true, 'is_active' => true]);
            activity()->causedBy(Auth::user())->performedOn($r)->log('Admin approved vendor');
        });

        session()->flash('success', 'Vendor approved.');
    }

    public function rejectVendor(int $id): void
    {
        $r = Restaurant::findOrFail($id);

        DB::transaction(function () use ($r) {
            $r->update(['is_approved' => false, 'is_active' => false]);
            activity()->causedBy(Auth::user())->performedOn($r)->log('Admin rejected vendor');
        });

        session()->flash('success', 'Vendor rejected.');
    }

    public function toggleBlock(int $id): void
    {
        $r = Restaurant::findOrFail($id);

        DB::transaction(function () use ($r) {
            $r->update(['is_active' => !$r->is_active]);
            activity()->causedBy(Auth::user())->performedOn($r)
                ->log($r->is_active ? 'Admin unblocked vendor' : 'Admin blocked vendor');
        });

        session()->flash('success', $r->is_active ? 'Vendor unblocked.' : 'Vendor blocked.');
    }

    // ── Delete ────────────────────────────────────────────
    public function confirmDeleteRecord(int $id): void
    {
        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deleteRecord(): void
    {
        $r = Restaurant::findOrFail($this->deleteId);

        DB::transaction(function () use ($r) {
            if ($r->logo_url && str_starts_with($r->logo_url, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $r->logo_url));
            }
            if ($r->banner_url && str_starts_with($r->banner_url, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $r->banner_url));
            }

            activity()->causedBy(Auth::user())->performedOn($r)->log('Admin deleted vendor');
            $r->delete();
        });

        $this->confirmDelete = false;
        $this->deleteId      = null;
        session()->flash('success', 'Vendor deleted.');
    }

    // ── Reset Form ────────────────────────────────────────
    private function resetForm(): void
    {
        $this->reset([
            'editId', 'name', 'category', 'emoji', 'tag', 'address', 'city', 'phone',
            'owner_name', 'owner_email', 'owner_password', 'logo', 'existingLogo',
            'banner', 'existingBanner',
        ]);
        $this->commission_rate  = '15.00';
        $this->is_open          = true;
        $this->is_approved      = false;
        $this->is_active        = true;
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────
    public function render()
    {
        $vendors = Restaurant::query()
            ->with('owner')
            ->withCount('orders')
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('category', 'like', "%{$this->search}%")
                  ->orWhere('city', 'like', "%{$this->search}%")
            )
            ->when($this->statusFilter === 'pending',  fn($q) => $q->where('is_approved', false))
            ->when($this->statusFilter === 'active',   fn($q) => $q->where('is_approved', true)->where('is_active', true))
            ->when($this->statusFilter === 'blocked',  fn($q) => $q->where('is_approved', true)->where('is_active', false))
            ->latest()
            ->paginate($this->perPage);

        $viewVendor = $this->viewId ? Restaurant::with('owner')->withCount('orders')->find($this->viewId) : null;

        return view('livewire.admin.vendor-component', compact('vendors', 'viewVendor'))
            ->layout('layouts.admin', [
                'title'           => 'Vendor Management | KhaiKhai',
                'breadcrumbTitle' => 'Vendors',
            ]);
    }
}