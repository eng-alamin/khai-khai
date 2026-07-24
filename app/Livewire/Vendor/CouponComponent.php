<?php

namespace App\Livewire\Vendor;

use Livewire\Component;
use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\WithPagination;

class CouponComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // ── List / Filter ─────────────────────────────────────
    public string $search        = '';
    public int    $perPage       = 10;
    public string $sortField     = 'created_at';
    public string $sortDirection = 'desc';
    public string $filterType    = '';     // '' = all
    public string $filterStatus  = '';     // '' | 'active' | 'inactive' | 'expired'

    // ── Modal ─────────────────────────────────────────────
    public bool $showModal     = false;
    public bool $confirmDelete = false;
    public ?int $deleteId      = null;

    // ── Form ──────────────────────────────────────────────
    public ?int   $editId          = null;
    public string $code            = '';
    public string $description     = '';
    public string $type            = 'percentage';
    public string $value           = '';
    public string $min_order_amount = '';  // user টাকায় enter করবে, save এ paisa তে convert
    public string $max_discount    = '';   // same
    public string $usage_limit     = '';
    public string $per_user_limit  = '';
    public string $valid_from      = '';
    public string $valid_until     = '';
    public bool   $is_active       = true;

    // ── Validation ────────────────────────────────────────
    protected function rules(): array
    {
        $unique = $this->editId
            ? 'unique:coupons,code,' . $this->editId
            : 'unique:coupons,code';

        return [
            'code'             => "required|string|max:20|{$unique}",
            'description'      => 'required|string|max:255',
            'type'             => 'required|in:percentage,fixed_amount,free_delivery',
            'value'            => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount'     => 'nullable|numeric|min:0',
            'usage_limit'      => 'nullable|integer|min:1',
            'per_user_limit'   => 'nullable|integer|min:1',
            'valid_from'       => 'nullable|date',
            'valid_until'      => 'nullable|date|after_or_equal:valid_from',
            'is_active'        => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'code.required'        => 'কুপন কোড দিন।',
            'code.unique'          => 'এই কোড ইতোমধ্যে আছে।',
            'code.max'             => 'কোড সর্বোচ্চ ২০ অক্ষর।',
            'description.required' => 'বিবরণ দিন।',
            'type.required'        => 'ধরন নির্বাচন করুন।',
            'value.required'       => 'ডিসকাউন্টের মান দিন।',
            'value.min'            => 'মান ০ এর বেশি হতে হবে।',
            'valid_until.after_or_equal' => 'শেষ তারিখ শুরুর তারিখের পরে হতে হবে।',
        ];
    }

    // ── Watchers ─────────────────────────────────────────
    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterType(): void   { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    // type পরিবর্তন হলে value রিসেট
    public function updatedType(): void
    {
        $this->value       = '';
        $this->max_discount = '';
        $this->resetValidation(['value', 'max_discount']);
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

    // ── Generate random code ──────────────────────────────
    public function generateCode(): void
    {
        $this->code = strtoupper(Str::random(8));
    }

    // ── Open create modal ─────────────────────────────────
    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
        $this->dispatch('open-coupon-modal');
    }

    // ── Open edit modal ───────────────────────────────────
    public function openEdit(int $id): void
    {
        $coupon = Coupon::findOrFail($id);

        $this->editId           = $id;
        $this->code             = $coupon->code;
        $this->description      = $coupon->description;
        $this->type             = $coupon->type;
        $this->value            = (string) $coupon->value;
        $this->min_order_amount = $coupon->min_order_amount
            ? (string) ($coupon->min_order_amount) : '';
        $this->max_discount     = $coupon->max_discount
            ? (string) ($coupon->max_discount) : '';
        $this->usage_limit      = (string) ($coupon->usage_limit ?? '');
        $this->per_user_limit   = (string) ($coupon->per_user_limit ?? '');
        $this->valid_from       = $coupon->valid_from?->format('Y-m-d\TH:i') ?? '';
        $this->valid_until      = $coupon->valid_until?->format('Y-m-d\TH:i') ?? '';
        $this->is_active        = (bool) $coupon->is_active;
        $this->showModal        = true;
        $this->dispatch('open-coupon-modal');
    }

    // ── Save ─────────────────────────────────────────────
    public function save(): void
    {
        $this->code = strtoupper(trim($this->code));
        $this->validate();

        $data = [
            'code'             => $this->code,
            'description'      => $this->description,
            'type'             => $this->type,
            'value'            => $this->value,
            // টাকা → paisa
            'min_order_amount' => $this->min_order_amount
                ? (int) round((float) $this->min_order_amount) : null,
            'max_discount'     => ($this->type === 'percentage' && $this->max_discount !== '')
                ? (int) round((float) $this->max_discount) : null,
            'usage_limit'      => $this->usage_limit ?: null,
            'per_user_limit'   => $this->per_user_limit ?: null,
            'valid_from'       => $this->valid_from ?: null,
            'valid_until'      => $this->valid_until ?: null,
            'is_active'        => $this->is_active,
        ];

        if ($this->editId) {
            Coupon::findOrFail($this->editId)->update($data);
            $this->dispatch('show-toast', message: 'কুপন আপডেট হয়েছে ✅', type: 'success');
        } else {
            $data['created_by'] = Auth::id();
            $data['used_count'] = 0;
            Coupon::create($data);
            $this->dispatch('show-toast', message: 'নতুন কুপন তৈরি হয়েছে ✅', type: 'success');
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('close-coupon-modal');
    }

    // ── Quick toggle ──────────────────────────────────────
    public function toggleActive(int $id): void
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update(['is_active' => ! $coupon->is_active]);
        $this->dispatch(
            'show-toast',
            message: $coupon->is_active ? 'কুপন সক্রিয় করা হয়েছে।' : 'কুপন নিষ্ক্রিয় করা হয়েছে।',
            type: 'success'
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
        Coupon::findOrFail($this->deleteId)->delete();
        $this->confirmDelete = false;
        $this->deleteId      = null;
        $this->dispatch('show-toast', message: 'কুপন মুছে ফেলা হয়েছে।', type: 'info');
    }

    // ── Reset form ────────────────────────────────────────
    private function resetForm(): void
    {
        $this->reset([
            'editId', 'code', 'description', 'value',
            'min_order_amount', 'max_discount',
            'usage_limit', 'per_user_limit',
            'valid_from', 'valid_until',
        ]);
        $this->type      = 'percentage';
        $this->is_active = true;
        $this->resetValidation();
    }

    // ── View helpers ──────────────────────────────────────
    public function typeLabel(string $type): string
    {
        return match ($type) {
            'percentage'   => '% ছাড়',
            'fixed_amount' => 'নির্দিষ্ট ছাড়',
            'free_delivery'=> 'ফ্রি ডেলিভারি',
            default        => $type,
        };
    }

    public function typeEmoji(string $type): string
    {
        return match ($type) {
            'percentage'    => '💯',
            'fixed_amount'  => '৳',
            'free_delivery' => '🛵',
            default         => '🏷️',
        };
    }

    // ── Render ────────────────────────────────────────────
    public function render()
    {
        $coupons = Coupon::query()
            ->with('createdBy:id,name')
            ->when($this->search, fn ($q) =>
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%")
            )
            ->when($this->filterType, fn ($q) =>
                $q->where('type', $this->filterType)
            )
            ->when($this->filterStatus === 'active', fn ($q) =>
                $q->where('is_active', true)
                  ->where(fn ($q2) =>
                      $q2->whereNull('valid_until')->orWhere('valid_until', '>=', now())
                  )
            )
            ->when($this->filterStatus === 'inactive', fn ($q) =>
                $q->where('is_active', false)
            )
            ->when($this->filterStatus === 'expired', fn ($q) =>
                $q->whereNotNull('valid_until')->where('valid_until', '<', now())
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.vendor.coupon-component', [
            'coupons' => $coupons,
        ])->layout('layouts.vendor', [
            'title'           => 'Coupons | KhaiKhai',
            'breadcrumbTitle' => 'Coupons',
        ]);
    }
}