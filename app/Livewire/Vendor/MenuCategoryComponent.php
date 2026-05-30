<?php

namespace App\Livewire\Vendor;

use App\Models\MenuCategory;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MenuCategoryComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // ── List ─────────────────────────────────────────────
    public string $search        = '';
    public int    $perPage       = 10;
    public string $sortField     = 'sort_order';
    public string $sortDirection = 'asc';

    // ── Modal ────────────────────────────────────────────
    public bool  $showModal     = false;
    public bool  $confirmDelete = false;
    public ?int  $deleteId      = null;

    // ── Form ─────────────────────────────────────────────
    public ?int   $editId     = null;
    public string $name       = '';
    public string $emoji      = '';
    public int    $sort_order = 0;
    public bool   $is_active  = true;

    // ── Restaurant of logged-in vendor ───────────────────
    private function restaurantId(): int
    {
        return Auth::user()->restaurant->id;
    }

    // ── Validation ───────────────────────────────────────
    protected function rules(): array
    {
        return [
            'name'       => 'required|string|max:80',
            'emoji'      => 'nullable|string|max:10',
            'sort_order' => 'required|integer|min:0',
            'is_active'  => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'ক্যাটাগরির নাম দিন।',
            'name.max'      => 'নাম সর্বোচ্চ ৮০ অক্ষর হতে পারবে।',
        ];
    }

    // ── Pagination reset on search ────────────────────────
    public function updatingSearch(): void
    {
        $this->resetPage();
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
        $record = MenuCategory::where('restaurant_id', $this->restaurantId())
            ->findOrFail($id);

        $this->editId     = $id;
        $this->name       = $record->name;
        $this->emoji      = $record->emoji ?? '';
        $this->sort_order = $record->sort_order;
        $this->is_active  = (bool) $record->is_active;
        $this->showModal  = true;
    }

    // ── Save (create or update) ───────────────────────────
    public function save(): void
    {
        $this->validate();

        $data = [
            'restaurant_id' => $this->restaurantId(),
            'name'          => $this->name,
            'emoji'         => $this->emoji ?: null,
            'sort_order'    => $this->sort_order,
            'is_active'     => $this->is_active,
        ];

        if ($this->editId) {
            MenuCategory::where('restaurant_id', $this->restaurantId())
                ->findOrFail($this->editId)
                ->update($data);
            session()->flash('success', 'ক্যাটাগরি সফলভাবে আপডেট হয়েছে!');
        } else {
            MenuCategory::create($data);
            session()->flash('success', 'নতুন ক্যাটাগরি তৈরি হয়েছে!');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    // ── Confirm delete ────────────────────────────────────
    public function confirmDeleteRecord(int $id): void
    {
        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    // ── Delete ────────────────────────────────────────────
    public function deleteRecord(): void
    {
        $record = MenuCategory::where('restaurant_id', $this->restaurantId())
            ->findOrFail($this->deleteId);

        // Prevent delete if items exist
        if ($record->menuItems()->count() > 0) {
            session()->flash('error', 'এই ক্যাটাগরিতে আইটেম আছে। আগে আইটেমগুলো মুছুন।');
            $this->confirmDelete = false;
            $this->deleteId      = null;
            return;
        }

        $record->delete();
        $this->confirmDelete = false;
        $this->deleteId      = null;
        session()->flash('success', 'ক্যাটাগরি মুছে ফেলা হয়েছে!');
    }

    // ── Reset form fields ─────────────────────────────────
    private function resetForm(): void
    {
        $this->reset(['name', 'emoji', 'editId']);
        $this->sort_order = 0;
        $this->is_active  = true;
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────
    public function render()
    {
        $categories = MenuCategory::query()
            ->where('restaurant_id', $this->restaurantId())
            ->when(
                $this->search,
                fn ($q) => $q->where('name', 'like', "%{$this->search}%")
            )
            ->withCount('menuItems')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.vendor.menu-category-component', compact('categories'))
            ->layout('layouts.app', ['title' => 'মেনু ক্যাটাগরি | KhaiKhai']);
    }
}