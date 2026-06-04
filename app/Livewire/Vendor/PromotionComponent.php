<?php

namespace App\Livewire\Vendor;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Promotion;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PromotionComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // ── List / Filter ─────────────────────────────────────
    public string $search        = '';
    public int    $perPage       = 10;
    public string $sortField     = 'created_at';
    public string $sortDirection = 'desc';
    public string $filterType    = '';   // '' = all
    public string $filterStatus  = '';   // '' | 'active' | 'inactive'

    // ── Modal ─────────────────────────────────────────────
    public bool $showModal     = false;
    public bool $confirmDelete = false;
    public ?int $deleteId      = null;

    // ── Form ──────────────────────────────────────────────
    public ?int   $editId         = null;
    public string $title          = '';
    public string $description    = '';
    public string $type           = 'item_discount';
    public string $discount_value = '';
    public string $applies_to     = 'all_items';
    public ?int   $target_id      = null;
    public string $starts_at      = '';
    public string $ends_at        = '';
    public bool   $is_active      = true;

    // ── Restaurant helper ─────────────────────────────────
    private function restaurantId(): int
    {
        return Auth::user()->restaurant->id;
    }

    // ── Dropdowns ────────────────────────────────────────
    public function getCategories()
    {
        return MenuCategory::where('restaurant_id', $this->restaurantId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'emoji']);
    }

    public function getMenuItems()
    {
        return MenuItem::where('restaurant_id', $this->restaurantId())
            ->where('is_available', true)
            ->orderBy('name')
            ->get(['id', 'name', 'emoji', 'price']);
    }

    // ── Validation ────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'title'          => 'required|string|max:120',
            'description'    => 'nullable|string|max:500',
            'type'           => 'required|in:item_discount,buy_x_get_y,flash_deal',
            'discount_value' => 'required|numeric|min:0.01|max:99999',
            'applies_to'     => 'required|in:all_items,category,specific_item',
            'target_id'      => [
                'nullable',
                'required_if:applies_to,category',
                'required_if:applies_to,specific_item',
                'integer',
                'min:1',
            ],
            'starts_at' => 'nullable|date',
            'ends_at'   => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required'          => 'প্রমোশনের নাম দিন।',
            'title.max'               => 'নাম সর্বোচ্চ ১২০ অক্ষর হতে পারবে।',
            'type.required'           => 'প্রমোশনের ধরন নির্বাচন করুন।',
            'discount_value.required' => 'ডিসকাউন্টের পরিমাণ দিন।',
            'discount_value.min'      => 'ডিসকাউন্ট ০ এর বেশি হতে হবে।',
            'applies_to.required'     => 'প্রযোজ্য ক্ষেত্র নির্বাচন করুন।',
            'target_id.required_if'   => 'ক্যাটাগরি বা আইটেম নির্বাচন করুন।',
            'ends_at.after_or_equal'  => 'শেষ তারিখ শুরুর তারিখের পরে হতে হবে।',
        ];
    }

    // ── Watchers ─────────────────────────────────────────
    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterType(): void   { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function updatedAppliesTo(): void
    {
        $this->target_id = null;
        $this->resetValidation('target_id');
    }

    // ── Sorting ───────────────────────────────────────────
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    // ── Open create modal ─────────────────────────────────
    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    // ── Open edit modal ───────────────────────────────────
    public function openEdit(int $id): void
    {
        $record = Promotion::where('restaurant_id', $this->restaurantId())
            ->findOrFail($id);

        $this->editId         = $id;
        $this->title          = $record->title;
        $this->description    = $record->description ?? '';
        $this->type           = $record->type;
        $this->discount_value = (string) $record->discount_value;
        $this->applies_to     = $record->applies_to;
        $this->target_id      = $record->target_id;
        $this->starts_at      = $record->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->ends_at        = $record->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->is_active      = (bool) $record->is_active;
        $this->showModal      = true;
    }

    // ── Save ─────────────────────────────────────────────
    public function save(): void
    {
        $this->validate();

        $data = [
            'restaurant_id'  => $this->restaurantId(),
            'title'          => $this->title,
            'description'    => $this->description ?: null,
            'type'           => $this->type,
            'discount_value' => $this->discount_value,
            'applies_to'     => $this->applies_to,
            'target_id'      => $this->applies_to === 'all_items' ? null : $this->target_id,
            'starts_at'      => $this->starts_at ?: null,
            'ends_at'        => $this->ends_at ?: null,
            'is_active'      => $this->is_active,
        ];

        if ($this->editId) {
            Promotion::where('restaurant_id', $this->restaurantId())
                ->findOrFail($this->editId)
                ->update($data);
            session()->flash('success', 'প্রমোশন সফলভাবে আপডেট হয়েছে!');
        } else {
            Promotion::create($data);
            session()->flash('success', 'নতুন প্রমোশন তৈরি হয়েছে!');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    // ── Quick toggle ──────────────────────────────────────
    public function toggleActive(int $id): void
    {
        $promo = Promotion::where('restaurant_id', $this->restaurantId())
            ->findOrFail($id);
        $promo->update(['is_active' => ! $promo->is_active]);
        session()->flash(
            'success',
            $promo->is_active ? 'প্রমোশন সক্রিয় করা হয়েছে।' : 'প্রমোশন নিষ্ক্রিয় করা হয়েছে।'
        );
    }

    // ── Confirm / Delete ─────────────────────────────────
    public function confirmDeleteRecord(int $id): void
    {
        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deleteRecord(): void
    {
        Promotion::where('restaurant_id', $this->restaurantId())
            ->findOrFail($this->deleteId)
            ->delete();

        $this->confirmDelete = false;
        $this->deleteId      = null;
        session()->flash('success', 'প্রমোশন মুছে ফেলা হয়েছে!');
    }

    // ── Reset form ────────────────────────────────────────
    private function resetForm(): void
    {
        $this->reset(['title', 'description', 'editId', 'target_id', 'starts_at', 'ends_at']);
        $this->type           = 'item_discount';
        $this->discount_value = '';
        $this->applies_to     = 'all_items';
        $this->is_active      = true;
        $this->resetValidation();
    }

    // ── View helpers ──────────────────────────────────────
    public function typeLabel(string $type): string
    {
        return match ($type) {
            'item_discount' => 'আইটেম ডিসকাউন্ট',
            'buy_x_get_y'  => 'বাই X গেট Y',
            'flash_deal'   => 'ফ্ল্যাশ ডিল',
            default        => $type,
        };
    }

    public function typeBadgeClass(string $type): string
    {
        return match ($type) {
            'item_discount' => 'bg-primary-subtle text-primary',
            'buy_x_get_y'  => 'bg-success-subtle text-success',
            'flash_deal'   => 'bg-warning-subtle text-warning',
            default        => 'bg-secondary-subtle text-secondary',
        };
    }

    public function appliesToLabel(string $applies_to): string
    {
        return match ($applies_to) {
            'all_items'     => 'সব আইটেম',
            'category'      => 'ক্যাটাগরি',
            'specific_item' => 'নির্দিষ্ট আইটেম',
            default         => $applies_to,
        };
    }

    // ── Render ────────────────────────────────────────────
    public function render()
    {
        $now = now();

        $promotions = Promotion::query()
            ->where('restaurant_id', $this->restaurantId())
            ->with([
                'category:id,name,emoji',
                'menuItem:id,name,emoji,price',
            ])
            ->when($this->search, fn ($q) =>
                $q->where('title', 'like', "%{$this->search}%")
            )
            ->when($this->filterType, fn ($q) =>
                $q->where('type', $this->filterType)
            )
            ->when($this->filterStatus === 'active', fn ($q) =>
                $q->where('is_active', true)
                  ->where(fn ($q2) =>
                      $q2->whereNull('ends_at')->orWhere('ends_at', '>=', $now)
                  )
            )
            ->when($this->filterStatus === 'inactive', fn ($q) =>
                $q->where(fn ($q2) =>
                    $q2->where('is_active', false)
                       ->orWhere(fn ($q3) =>
                           $q3->whereNotNull('ends_at')->where('ends_at', '<', $now)
                       )
                )
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.vendor.promotion-component', [
            'promotions' => $promotions,
            'categories' => $this->getCategories(),
            'menuItems'  => $this->getMenuItems(),
        ])->layout('layouts.vendor', [
            'title'           => 'Promotions | KhaiKhai',
            'breadcrumbTitle' => 'Promotions',
        ]);
    }
}