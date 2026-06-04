<?php

namespace App\Livewire\Vendor;

use App\Models\MenuItem;
use App\Models\MenuCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class MenuItemComponent extends Component
{
    use WithPagination, WithFileUploads;

    protected string $paginationTheme = 'bootstrap';

    // ── List / Filter ─────────────────────────────────────
    public string $search          = '';
    public int    $perPage         = 10;
    public string $sortField       = 'sort_order';
    public string $sortDirection   = 'asc';
    public string $filterCategory  = '';   // '' = all
    public string $filterStatus    = '';   // '' | 'available' | 'unavailable'

    // ── Modal ─────────────────────────────────────────────
    public bool $showModal     = false;
    public bool $confirmDelete = false;
    public ?int $deleteId      = null;

    // ── Form ──────────────────────────────────────────────
    public ?int    $editId        = null;
    public int     $category_id   = 0;
    public string  $name          = '';
    public string  $description   = '';
    public int     $price         = 0;
    public string  $emoji         = '';
    public         $image         = null;
    public ?string $existingImage = null;
    public int     $sort_order    = 0;
    public bool    $is_available  = true;

    // ── Restaurant helper ─────────────────────────────────
    private function restaurantId(): int
    {
        return Auth::user()->restaurant->id;
    }

    // ── Categories for dropdown ───────────────────────────
    public function getCategories()
    {
        return MenuCategory::where('restaurant_id', $this->restaurantId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'emoji']);
    }

    // ── Validation ────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'category_id'  => 'required|integer|min:1',
            'name'         => 'required|string|max:120',
            'description'  => 'nullable|string|max:500',
            'price'        => 'required|integer|min:1|max:100000',
            'emoji'        => 'nullable|string|max:10',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'sort_order'   => 'required|integer|min:0',
            'is_available' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category.',
            'category_id.min'      => 'Please select a category.',
            'name.required'        => 'Please enter an item name.',
            'name.max'             => 'Name must not exceed 120 characters.',
            'price.required'       => 'Please enter a price.',
            'price.min'            => 'Price must be at least ৳1.',
            'image.max'            => 'Image must not exceed 2 MB.',
        ];
    }

    // ── Watchers ─────────────────────────────────────────
    public function updatingSearch(): void         { $this->resetPage(); }
    public function updatingFilterCategory(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void   { $this->resetPage(); }

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
        $record = MenuItem::where('restaurant_id', $this->restaurantId())
            ->findOrFail($id);

        $this->editId        = $id;
        $this->category_id   = $record->category_id ?? 0;
        $this->name          = $record->name;
        $this->description   = $record->description ?? '';
        $this->price         = (int) ($record->price / 100);
        $this->emoji         = $record->emoji ?? '';
        $this->existingImage = $record->image_url;
        $this->sort_order    = $record->sort_order;
        $this->is_available  = (bool) $record->is_available;
        $this->showModal     = true;
    }

    // ── Save ─────────────────────────────────────────────
    public function save(): void
    {
        $this->validate();

        $imagePath = $this->existingImage;
        if ($this->image) {
            if ($this->existingImage && str_starts_with($this->existingImage, '/storage/')) {
                Storage::disk('public')->delete(
                    str_replace('/storage/', '', $this->existingImage)
                );
            }
            $stored    = $this->image->store('menu-items', 'public');
            $imagePath = Storage::url($stored);
        }

        $data = [
            'restaurant_id' => $this->restaurantId(),
            'category_id'   => $this->category_id ?: null,
            'name'          => $this->name,
            'description'   => $this->description ?: null,
            'price'         => $this->price * 100,
            'emoji'         => $this->emoji ?: null,
            'image_url'     => $imagePath,
            'sort_order'    => $this->sort_order,
            'is_available'  => $this->is_available,
        ];

        if ($this->editId) {
            MenuItem::where('restaurant_id', $this->restaurantId())
                ->findOrFail($this->editId)
                ->update($data);
            session()->flash('success', 'Menu item updated successfully!');
        } else {
            MenuItem::create($data);
            session()->flash('success', 'New menu item created!');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    // ── Quick toggle availability ─────────────────────────
    public function toggleAvailability(int $id): void
    {
        $item = MenuItem::where('restaurant_id', $this->restaurantId())->findOrFail($id);
        $item->update(['is_available' => ! $item->is_available]);
        session()->flash('success', $item->is_available ? 'Item marked as available.' : 'Item marked as unavailable.');
    }

    // ── Confirm / Delete ─────────────────────────────────
    public function confirmDeleteRecord(int $id): void
    {
        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deleteRecord(): void
    {
        $record = MenuItem::where('restaurant_id', $this->restaurantId())
            ->findOrFail($this->deleteId);

        if ($record->image_url && str_starts_with($record->image_url, '/storage/')) {
            Storage::disk('public')->delete(
                str_replace('/storage/', '', $record->image_url)
            );
        }

        $record->delete();
        $this->confirmDelete = false;
        $this->deleteId      = null;
        session()->flash('success', 'Menu item deleted successfully!');
    }

    // ── Reset form ────────────────────────────────────────
    private function resetForm(): void
    {
        $this->reset(['name', 'description', 'emoji', 'editId', 'image', 'existingImage']);
        $this->category_id  = 0;
        $this->price        = 0;
        $this->sort_order   = 0;
        $this->is_available = true;
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────
    public function render()
    {
        $items = MenuItem::query()
            ->where('restaurant_id', $this->restaurantId())
            ->with('category')
            ->when($this->search, fn ($q) =>
                $q->where('name', 'like', "%{$this->search}%")
            )
            ->when($this->filterCategory, fn ($q) =>
                $q->where('category_id', $this->filterCategory)
            )
            ->when($this->filterStatus === 'available', fn ($q) =>
                $q->where('is_available', true)
            )
            ->when($this->filterStatus === 'unavailable', fn ($q) =>
                $q->where('is_available', false)
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.vendor.menu-item-component', [
            'items'      => $items,
            'categories' => $this->getCategories(),
        ])->layout('layouts.vendor', [
            'title'           => 'Menu Items | KhaiKhai',
            'breadcrumbTitle' => 'Items',
        ]);
    }
}