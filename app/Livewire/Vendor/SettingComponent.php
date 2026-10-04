<?php

namespace App\Livewire\Vendor;

use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
    #[Locked] public ?string $logo_url   = null;   // set on the server after an upload
    #[Locked] public ?string $banner_url = null;   // set on the server after an upload
    public string  $address       = '';
    public string  $city          = '';
    public ?float  $latitude      = null;
    public ?float  $longitude     = null;
    public ?string $phone         = null;
    public ?string $tag           = null;
    public bool    $is_open       = true;
    #[Locked] public bool $is_active     = true;   // read-only here: only admin can block/unblock
    #[Locked] public bool $initialIsOpen = true;   // value when the form was loaded (to detect a real change)

    // ── vendor_settings fields ──────────────────────────────────────
    public bool $auto_accept          = false;
    public int  $prep_time_min        = 20;
    public bool $notification_sound   = true;
    public ?int $min_order_amount     = 0;

    // ── file uploads ────────────────────────────────────────────────
    public $logoUpload   = null;
    public $bannerUpload = null;

    // ── UI state ────────────────────────────────────────────────────
    public string $activeTab = 'basic';

    // The restaurant is NEVER kept in a public property: anything public can be
    // changed from the browser. It is loaded from the logged-in owner on every
    // request (see boot()), so a vendor can only ever edit their own restaurant.
    protected Restaurant    $restaurant;
    protected VendorSetting $vendorSetting;

    // ── categories / cities lists (filled from config/delivery.php in mount()) ──
    public array $categories = [];

    public array $cities = [];

    // ── validation ──────────────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:120'],
            'slug'              => ['required', 'string', 'max:130', Rule::unique('restaurants', 'slug')->ignore($this->restaurant->id)],
            'category'          => ['required', 'string', 'max:60', Rule::in(config('delivery.categories', []))],
            'emoji'             => ['nullable', 'string', 'max:10'],
            'address'           => ['required', 'string'],
            'city'              => ['required', 'string', 'max:60', Rule::in(config('delivery.cities', []))],
            'latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'         => ['nullable', 'numeric', 'between:-180,180'],
            'phone'             => ['nullable', 'string', 'max:15'],
            'tag'               => ['nullable', 'string', 'max:40'],
            'is_open'           => ['boolean'],
            'logoUpload'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'bannerUpload'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
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
        'prep_time_min.required'    => 'Prep time is required.',
        'logoUpload.image'          => 'Logo must be an image file.',
        'logoUpload.mimes'          => 'Logo must be a JPG, PNG or WebP file.',
        'logoUpload.max'            => 'Logo must not exceed 2 MB.',
        'bannerUpload.image'        => 'Banner must be an image file.',
        'bannerUpload.mimes'        => 'Banner must be a JPG, PNG or WebP file.',
        'bannerUpload.max'          => 'Banner must not exceed 4 MB.',
        'latitude.between'          => 'Latitude must be between -90 and 90.',
        'longitude.between'         => 'Longitude must be between -180 and 180.',
    ];

    // ── mount ────────────────────────────────────────────────────────
    public function mount(): void
    {
        // $this->restaurant and $this->vendorSetting are already loaded by boot().
        $this->categories = config('delivery.categories', []);
        $this->cities     = config('delivery.cities', []);

        $this->fill([
            'name'             => $this->restaurant->name,
            'slug'             => $this->restaurant->slug,
            // An old value that is not in the list is shown as empty, so the owner can pick a valid one.
            'category'         => in_array($this->restaurant->category, $this->categories, true) ? $this->restaurant->category : '',
            'emoji'            => $this->restaurant->emoji ?? '',
            'logo_url'         => $this->restaurant->logo_url,
            'banner_url'       => $this->restaurant->banner_url,
            'address'          => $this->restaurant->address,
            'city'             => in_array($this->restaurant->city, $this->cities, true) ? $this->restaurant->city : '',
            'latitude'         => $this->restaurant->latitude !== null ? (float) $this->restaurant->latitude : null,
            'longitude'        => $this->restaurant->longitude !== null ? (float) $this->restaurant->longitude : null,
            'phone'            => $this->restaurant->phone,
            'tag'              => $this->restaurant->tag,
            'is_open'          => $this->restaurant->is_open,
            'is_active'        => $this->restaurant->is_active,
            'initialIsOpen'    => $this->restaurant->is_open,
            // vendor_settings
            'auto_accept'          => $this->vendorSetting->auto_accept,
            'prep_time_min'        => $this->vendorSetting->prep_time_min,
            'notification_sound'   => $this->vendorSetting->notification_sound,
            'min_order_amount'     => $this->vendorSetting->min_order_amount,
        ]);
    }

    // ── boot: runs on EVERY request (first load and every Livewire update) ──
    // The restaurant always comes from the logged-in owner, never from the page.
    public function boot(): void
    {
        abort_unless(Auth::check(), 403);

        $this->restaurant = Restaurant::where('owner_id', Auth::id())->firstOrFail();

        $this->vendorSetting = VendorSetting::firstOrCreate(
            ['restaurant_id' => $this->restaurant->id],
            [
                'auto_accept'        => false,
                'prep_time_min'      => 20,
                'notification_sound' => true,
                'min_order_amount'   => 0,
            ]
        );
    }

    // ── auto-generate slug from name ─────────────────────────────────
    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    // ── clear validation errors as soon as map sets new coordinates ──
    public function updatedLatitude($value): void
    {
        $this->resetErrorBag('latitude');
    }

    public function updatedLongitude($value): void
    {
        $this->resetErrorBag('longitude');
    }

    // ── min_order_amount: plain taka, display helper only ────────────
    // vendor_settings.min_order_amount decimal(12,2) column e already
    // taka-e store hoy, kono paisa conversion nai — eta shudhu comma-
    // formatted display value bananor jonno.
    public function getMinOrderTakaProperty(): string
    {
        return $this->min_order_amount !== null
            ? number_format($this->min_order_amount, 0)
            : '';
    }

    public function setMinOrderTaka(string $taka): void
    {
        $this->min_order_amount = $taka === '' ? null : (int) round((float) $taka);
    }

    // ── tab switch ──────────────────────────────────────────────────
    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;

        // Let the front-end know a tab switch happened so the Leaflet
        // map (only present in the DOM while the "basic" tab is active)
        // can be (re)initialised at the right time.
        $this->dispatch(
            'tab-switched',
            tab: $tab,
            latitude: $this->latitude,
            longitude: $this->longitude,
        );
    }

    // ── save all settings ────────────────────────────────────────────
    public function save(): void
    {
        $this->validate();

        if ($this->logoUpload) {
            $this->logo_url = 'storage/' . $this->logoUpload->store('restaurants/logos', 'public');
            $this->logoUpload = null;
        }

        if ($this->bannerUpload) {
            $this->banner_url = 'storage/' . $this->bannerUpload->store('restaurants/banners', 'public');
            $this->bannerUpload = null;
        }

        $restaurantData = [
            'name'             => $this->name,
            'slug'             => $this->slug,
            'category'         => $this->category,
            'emoji'            => $this->emoji ?: null,
            'logo_url'         => $this->logo_url,
            'banner_url'       => $this->banner_url,
            'address'          => $this->address,
            'city'             => $this->city,
            'latitude'         => $this->latitude,
            'longitude'        => $this->longitude,
            'phone'            => $this->phone ?: null,
            'tag'              => $this->tag ?: null,
        ];

        // is_open is written only when the vendor really changed it in this form.
        // The opening-hours schedule may have flipped it since the page was loaded;
        // saving the old value back would undo that.
        // is_active is never written here: only the admin can block/unblock a restaurant.
        if ($this->is_open !== $this->initialIsOpen) {
            $restaurantData['is_open'] = $this->is_open;
        }

        $settingData = [
            'auto_accept'        => $this->auto_accept,
            'prep_time_min'      => $this->prep_time_min,
            'notification_sound' => $this->notification_sound,
            'min_order_amount'   => $this->min_order_amount,
        ];

        DB::transaction(function () use ($restaurantData, $settingData) {
            $this->restaurant->update($restaurantData);
            $this->vendorSetting->update($settingData);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($this->restaurant)
                ->withProperties([
                    'restaurant' => $restaurantData,
                    'settings'   => $settingData,
                ])
                ->log('Vendor updated restaurant settings');
        });

        // Form ke DB er asol maan er shathe milie rakhi.
        $fresh = $this->restaurant->fresh();
        $this->is_open       = (bool) $fresh->is_open;
        $this->initialIsOpen = $this->is_open;
        $this->is_active     = (bool) $fresh->is_active;

        $this->dispatch('settings-saved');
        session()->flash('success', 'Settings saved successfully ✅');
    }

    // ── toggle is_open directly ──────────────────────────────────────
    public function toggleOpen(): void
    {
        // Base the toggle on the real DB value (boot() reloads it every request),
        // not on the form value, which may be stale after an automatic schedule change.
        $this->is_open       = ! $this->restaurant->is_open;
        $this->initialIsOpen = $this->is_open;
        $this->restaurant->update(['is_open' => $this->is_open]);

        activity()
            ->causedBy(Auth::user())
            ->performedOn($this->restaurant)
            ->withProperties(['is_open' => $this->is_open])
            ->log($this->is_open ? 'Vendor opened restaurant' : 'Vendor closed restaurant');

        $this->dispatch(
            'show-toast',
            message: $this->is_open ? 'Restaurant is now Open 🟢' : 'Restaurant is now Closed 🔴',
            type: $this->is_open ? 'success' : 'warning'
        );
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