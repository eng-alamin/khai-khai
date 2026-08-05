<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class CategoryComponent extends Component
{
    use WithPagination, WithFileUploads;

    protected string $paginationTheme = 'bootstrap';

    // ── List / Filter ─────────────────────────────────────
    public string $search        = '';
    public int    $perPage       = 10;
    public string $sortField     = 'sort_order';
    public string $sortDirection = 'asc';
    public string $filterStatus  = '';   // '' | 'active' | 'inactive'
    public string $filterType    = '';   // '' | 'food' | 'product'

    // ── Modal ─────────────────────────────────────────────
    public bool $showModal     = false;
    public bool $confirmDelete = false;
    public ?int $deleteId      = null;

    // ── Form ──────────────────────────────────────────────
    public ?int    $editId          = null;
    public string  $type            = Category::TYPE_FOOD;
    public string  $name            = '';
    public         $image           = null;
    public ?string $existingImage   = null;
    public int     $sort_order      = 0;
    public bool    $is_active       = true;

    // ── Validation ────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'type'       => 'required|string|in:' . implode(',', array_keys(Category::TYPES)),
            'name'       => 'required|string|max:80',
            'image'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'sort_order' => 'required|integer|min:0',
            'is_active'  => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'type.required'  => 'Please select a category type.',
            'type.in'        => 'Selected category type is invalid.',
            'name.required'  => 'Please enter a category name.',
            'name.max'       => 'Name must not exceed 80 characters.',
            'image.max'      => 'Image must not exceed 2 MB.',
        ];
    }

    // ── Watchers ─────────────────────────────────────────
    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterType(): void   { $this->resetPage(); }

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
        $record = Category::findOrFail($id);

        $this->editId        = $id;
        $this->type           = $record->type;
        $this->name           = $record->name;
        $this->existingImage  = $record->image_url;
        $this->sort_order     = $record->sort_order;
        $this->is_active      = (bool) $record->is_active;
        $this->showModal      = true;
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
            $stored    = $this->image->store('categories', 'public');
            $imagePath = Storage::url($stored);
        }

        $data = [
            'type'       => $this->type,
            'name'       => $this->name,
            'image_url'  => $imagePath,
            'sort_order' => $this->sort_order,
            'is_active'  => $this->is_active,
        ];

        DB::transaction(function () use ($data) {
            if ($this->editId) {
                $category = Category::findOrFail($this->editId);
                $category->update($data);
                activity()->causedBy(Auth::user())->performedOn($category)->log('updated category');
                session()->flash('success', 'Category updated successfully!');
            } else {
                $category = Category::create($data);
                activity()->causedBy(Auth::user())->performedOn($category)->log('created category');
                session()->flash('success', 'New category created!');
            }
        });

        $this->showModal = false;
        $this->resetForm();
    }

    // ── Quick toggle active status ────────────────────────
    public function toggleActive(int $id): void
    {
        $category = Category::findOrFail($id);

        DB::transaction(function () use ($category) {
            $category->update(['is_active' => ! $category->is_active]);
            activity()->causedBy(Auth::user())->performedOn($category)->log('toggled category active status');
        });

        session()->flash('success', $category->is_active ? 'Category marked as active.' : 'Category marked as inactive.');
    }

    // ── Confirm / Delete ─────────────────────────────────
    public function confirmDeleteRecord(int $id): void
    {
        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deleteRecord(): void
    {
        $record = Category::findOrFail($this->deleteId);

        if ($record->items()->exists()) {
            $itemLabel = $record->type === Category::TYPE_FOOD ? 'food items' : 'products';
            session()->flash('error', "Cannot delete: this category still has {$itemLabel} linked to it.");
            $this->confirmDelete = false;
            $this->deleteId      = null;
            return;
        }

        DB::transaction(function () use ($record) {
            if ($record->image_url && str_starts_with($record->image_url, '/storage/')) {
                Storage::disk('public')->delete(
                    str_replace('/storage/', '', $record->image_url)
                );
            }

            activity()->causedBy(Auth::user())->performedOn($record)->log('deleted category');
            $record->delete(); // soft delete
        });

        $this->confirmDelete = false;
        $this->deleteId      = null;
        session()->flash('success', 'Category deleted successfully!');
    }

    // ── Reset form ────────────────────────────────────────
    private function resetForm(): void
    {
        $this->reset(['name', 'editId', 'image', 'existingImage']);
        $this->type        = Category::TYPE_FOOD;
        $this->sort_order  = 0;
        $this->is_active   = true;
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────
    public function render()
    {
        $categories = Category::query()
            ->withCount(['products', 'foods'])
            ->when($this->search, fn ($q) =>
                $q->where('name', 'like', "%{$this->search}%")
            )
            ->when($this->filterStatus === 'active', fn ($q) =>
                $q->where('is_active', true)
            )
            ->when($this->filterStatus === 'inactive', fn ($q) =>
                $q->where('is_active', false)
            )
            ->when($this->filterType !== '', fn ($q) =>
                $q->where('type', $this->filterType)
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.category-component', [
            'categories'   => $categories,
            'categoryTypes' => Category::TYPES,
        ])->layout('layouts.admin', [
            'title'           => 'Categories | KhaiKhai',
            'breadcrumbTitle' => 'Categories',
        ]);
    }
}