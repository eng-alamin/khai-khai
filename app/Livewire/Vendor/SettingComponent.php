<?php

namespace App\Livewire\Vendor;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\Restaurant;
use App\Models\VendorSetting;

class SettingComponent extends Component
{
    use WithFileUploads;

    // ── restaurants fields ──────────────────────────────────────────
    public string  $name          = '';
    public string  $slug          = '';
    public string  $category      = '';
    public string  $emoji         = '';
    public ?string $logo_url      = null;
    public ?string $banner_url    = null;
    public string  $address       = '';
    public string  $city          = '';
    public ?string $latitude      = null;
    public ?string $longitude     = null;
    public ?string $phone         = null;
    public ?int    $avg_delivery_min = null;
    public ?int    $avg_delivery_max = null;
    public int     $delivery_fee  = 4900;
    public ?string $tag           = null;
    public bool    $is_open       = true;
    public bool    $is_active     = true;

    // ── vendor_settings fields ──────────────────────────────────────
    public bool $auto_accept          = false;
    public int  $prep_time_min        = 20;
    public bool $notification_sound   = true;
    public ?int $min_order_amount     = null;

    // ── file uploads ────────────────────────────────────────────────
    public $logoUpload   = null;
    public $bannerUpload = null;

    // ── UI state ────────────────────────────────────────────────────
    public string $activeTab = 'basic';

    // ── store restaurant id for rules() ─────────────────────────────
    public ?int $restaurantId = null;

    protected Restaurant    $restaurant;
    protected VendorSetting $vendorSetting;

    // ── categories list ─────────────────────────────────────────────
    public array $categories = [
        'Bengali Food',
        'Biryani',
        'Fast Food',
        'Pizza',
        'Chinese',
        'Italian',
        'Seafood',
        'Desserts & Sweets',
        'Beverages',
        'Other',
    ];

    public array $cities = [
        'Dhaka', 'Chittagong', 'Sylhet', 'Rajshahi',
        'Khulna', 'Barisal', 'Rangpur', 'Mymensingh',
        'Gazipur', 'Narayanganj',
    ];

    // ── validation ──────────────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:120'],
            'slug'              => ['required', 'string', 'max:130', "unique:restaurants,slug,{$this->restaurantId}"],
            'category'          => ['required', 'string', 'max:60'],
            'emoji'             => ['nullable', 'string', 'max:10'],
            'address'           => ['required', 'string'],
            'city'              => ['required', 'string', 'max:60'],
            'latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'         => ['nullable', 'numeric', 'between:-180,180'],
            'phone'             => ['nullable', 'string', 'max:15'],
            'avg_delivery_min'  => ['nullable', 'integer', 'min:1', 'max:999'],
            'avg_delivery_max'  => ['nullable', 'integer', 'min:1', 'max:999', 'gte:avg_delivery_min'],
            'delivery_fee'      => ['required', 'integer', 'min:0'],
            'tag'               => ['nullable', 'string', 'max:40'],
            'is_open'           => ['boolean'],
            'is_active'         => ['boolean'],
            'logoUpload'        => ['nullable', 'image', 'max:2048'],
            'bannerUpload'      => ['nullable', 'image', 'max:4096'],
            // vendor_settings
            'auto_accept'       => ['boolean'],
            'prep_time_min'     => ['required', 'integer', 'min:1', 'max:120'],
            'notification_sound'=> ['boolean'],
            'min_order_amount'  => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected array $messages = [
        'name.required'             => 'Restaurant name is required.',
        'slug.required'             => 'Slug is required.',
        'slug.unique'               => 'This slug is already taken.',
        'category.required'         => 'Please select a category.',
        'address.required'          => 'Address is required.',
        'city.required'             => 'Please select a city.',
        'avg_delivery_max.gte'      => 'Max delivery time must be greater than min.',
        'delivery_fee.required'     => 'Delivery fee is required.',
        'prep_time_min.required'    => 'Prep time is required.',
        'logoUpload.image'          => 'Logo must be an image file.',
        'logoUpload.max'            => 'Logo must not exceed 2 MB.',
        'bannerUpload.image'        => 'Banner must be an image file.',
        'bannerUpload.max'          => 'Banner must not exceed 4 MB.',
    ];

    // ── mount ────────────────────────────────────────────────────────
    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->restaurant = Restaurant::where('owner_id', $user->id)->firstOrFail();
        $this->restaurantId = $this->restaurant->id;

        $this->vendorSetting = VendorSetting::firstOrCreate(
            ['restaurant_id' => $this->restaurant->id],
            [
                'auto_accept'        => false,
                'prep_time_min'      => 20,
                'notification_sound' => true,
                'min_order_amount'   => null,
            ]
        );

        $this->fill([
            'name'             => $this->restaurant->name,
            'slug'             => $this->restaurant->slug,
            'category'         => $this->restaurant->category,
            'emoji'            => $this->restaurant->emoji ?? '',
            'logo_url'         => $this->restaurant->logo_url,
            'banner_url'       => $this->restaurant->banner_url,
            'address'          => $this->restaurant->address,
            'city'             => $this->restaurant->city,
            'latitude'         => $this->restaurant->latitude,
            'longitude'        => $this->restaurant->longitude,
            'phone'            => $this->restaurant->phone,
            'avg_delivery_min' => $this->restaurant->avg_delivery_min,
            'avg_delivery_max' => $this->restaurant->avg_delivery_max,
            'delivery_fee'     => $this->restaurant->delivery_fee,
            'tag'              => $this->restaurant->tag,
            'is_open'          => $this->restaurant->is_open,
            'is_active'        => $this->restaurant->is_active,
            // vendor_settings
            'auto_accept'          => $this->vendorSetting->auto_accept,
            'prep_time_min'        => $this->vendorSetting->prep_time_min,
            'notification_sound'   => $this->vendorSetting->notification_sound,
            'min_order_amount'     => $this->vendorSetting->min_order_amount,
        ]);
    }

    // ── boot: re-hydrate protected models after Livewire re-renders ──
    public function boot(): void
    {
        if ($this->restaurantId) {
            $this->restaurant    = Restaurant::findOrFail($this->restaurantId);
            $this->vendorSetting = VendorSetting::where('restaurant_id', $this->restaurantId)->firstOrFail();
        }
    }

    // ── auto-generate slug from name ─────────────────────────────────
    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    // ── delivery fee: paisa ↔ taka helpers ─────────────────────────
    public function getDeliveryFeeTakaProperty(): string
    {
        return number_format($this->delivery_fee / 100, 2);
    }

    public function setDeliveryFeeTaka(string $taka): void
    {
        $this->delivery_fee = (int) round((float) $taka * 100);
    }

    // ── min_order_amount: paisa ↔ taka ─────────────────────────────
    public function getMinOrderTakaProperty(): string
    {
        return $this->min_order_amount !== null
            ? number_format($this->min_order_amount / 100, 2)
            : '';
    }

    public function setMinOrderTaka(string $taka): void
    {
        $this->min_order_amount = $taka === '' ? null : (int) round((float) $taka * 100);
    }

    // ── tab switch ──────────────────────────────────────────────────
    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // ── save all settings ────────────────────────────────────────────
    public function save(): void
    {
        $this->validate();

        if ($this->logoUpload) {
            $this->logo_url  = $this->logoUpload->store('restaurants/logos', 'public');
            $this->logoUpload = null;
        }

        if ($this->bannerUpload) {
            $this->banner_url  = $this->bannerUpload->store('restaurants/banners', 'public');
            $this->bannerUpload = null;
        }

        $this->restaurant->update([
            'name'             => $this->name,
            'slug'             => $this->slug,
            'category'         => $this->category,
            'emoji'            => $this->emoji ?: null,
            'logo_url'         => $this->logo_url,
            'banner_url'       => $this->banner_url,
            'address'          => $this->address,
            'city'             => $this->city,
            'latitude'         => $this->latitude ?: null,
            'longitude'        => $this->longitude ?: null,
            'phone'            => $this->phone ?: null,
            'avg_delivery_min' => $this->avg_delivery_min,
            'avg_delivery_max' => $this->avg_delivery_max,
            'delivery_fee'     => $this->delivery_fee,
            'tag'              => $this->tag ?: null,
            'is_open'          => $this->is_open,
            'is_active'        => $this->is_active,
        ]);

        $this->vendorSetting->update([
            'auto_accept'        => $this->auto_accept,
            'prep_time_min'      => $this->prep_time_min,
            'notification_sound' => $this->notification_sound,
            'min_order_amount'   => $this->min_order_amount,
        ]);

        $this->dispatch('settings-saved');
        session()->flash('success', 'Settings saved successfully ✅');
    }

    // ── toggle is_open directly ──────────────────────────────────────
    public function toggleOpen(): void
    {
        $this->is_open = ! $this->is_open;
        $this->restaurant->update(['is_open' => $this->is_open]);
        $this->dispatch('toast', [
            'message' => $this->is_open ? 'Restaurant is now Open 🟢' : 'Restaurant is now Closed 🔴',
            'type'    => $this->is_open ? 'success' : 'warning',
        ]);
    }

    public function render()
    {
        return view('livewire.vendor.setting-component')
            ->layout('layouts.vendor', [
                'title'           => 'Settings | KhaiKhai',
                'breadcrumbTitle' => 'Settings',
            ]);
    }
}