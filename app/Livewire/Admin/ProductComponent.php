<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Livewire\Concerns\HasCatalogItemValidationRules;

class ProductComponent extends Component
{
    use WithPagination, WithFileUploads, HasCatalogItemValidationRules;

    protected string $paginationTheme = 'bootstrap';

    // ── List / Filter ─────────────────────────────────────
    public string $search         = '';
    public int    $perPage        = 10;
    public string $sortField      = 'sort_order';
    public string $sortDirection  = 'asc';
    public string $filterSection  = '';   // '' = all
    public string $filterStatus   = '';   // '' | 'active' | 'inactive'

    // ── Modal ─────────────────────────────────────────────
    public bool $showModal     = false;
    public bool $confirmDelete = false;
    public ?int $deleteId      = null;

    // ── Form ──────────────────────────────────────────────
    public ?int    $editId          = null;
    public ?int    $category_id     = null;
    public string  $name            = '';
    public string  $description     = '';
    public int     $price           = 0;
    public ?int    $compare_price   = null;
    public         $image           = null;
    public ?string $existingImage   = null;
    public string  $section         = '';
    public int     $sort_order      = 0;
    public bool    $is_active       = true;

    // ── Sections for dropdown ──────────────────────────────
    public function getSections(): array
    {
        return Product::SECTIONS;
    }

    // ── Categories for dropdown (product-type only) ────────
    public function getCategoryOptions()
    {
        return Category::query()
            ->where('type', Category::TYPE_PRODUCT)
            ->active()
            ->ordered()
            ->get(['id', 'name']);
    }

    // ── Validation ────────────────────────────────────────
    // DRY FIX: shared name/description/price/image/sort_order rules now
    // live in HasCatalogItemValidationRules (also used by Vendor\MenuItemComponent)
    // instead of being hand-copied in both places.
    protected function rules(): array
    {
        return array_merge($this->catalogItemRules(), [
            'category_id'   => 'nullable|integer|exists:categories,id',
            'compare_price' => 'nullable|integer|min:1|max:100000|gt:price',
            'section'       => ['required', Rule::in(Product::SECTIONS)],
            'is_active'     => 'boolean',
        ]);
    }

    protected function messages(): array
    {
        return array_merge($this->catalogItemMessages(), [
            'category_id.exists' => 'Please select a valid category.',
            'name.required'      => 'Please enter a product name.',
            'compare_price.gt'   => 'Compare price must be greater than the selling price.',
            'section.required'   => 'Please select a homepage section.',
            'section.in'         => 'Please select a valid homepage section.',
        ]);
    }

    // ── Watchers ─────────────────────────────────────────
    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingFilterSection(): void  { $this->resetPage(); }
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
        $record = Product::findOrFail($id);

        $this->editId        = $id;
        $this->category_id    = $record->category_id;
        $this->name           = $record->name;
        $this->description    = $record->description ?? '';
        $this->price          = (int) $record->price;
        $this->compare_price  = $record->compare_price !== null ? (int) $record->compare_price : null;
        $this->existingImage  = $record->image_url;
        $this->section        = $record->section;
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
            $stored    = $this->image->store('products', 'public');
            $imagePath = Storage::url($stored);
        }

        $data = [
            'category_id'   => $this->category_id ?: null,
            'name'          => $this->name,
            'description'   => $this->description ?: null,
            'price'         => $this->price,
            'compare_price' => $this->compare_price ?: null,
            'image_url'     => $imagePath,
            'section'       => $this->section,
            'sort_order'    => $this->sort_order,
            'is_active'     => $this->is_active,
        ];

        DB::transaction(function () use ($data) {
            if ($this->editId) {
                $product = Product::findOrFail($this->editId);
                $product->update($data);
                activity()->causedBy(Auth::user())->performedOn($product)->log('updated product');
                session()->flash('success', 'Product updated successfully!');
            } else {
                $product = Product::create($data);
                activity()->causedBy(Auth::user())->performedOn($product)->log('created product');
                session()->flash('success', 'New product created!');
            }
        });

        $this->showModal = false;
        $this->resetForm();
    }

    // ── Quick toggle active status ────────────────────────
    public function toggleActive(int $id): void
    {
        $product = Product::findOrFail($id);

        DB::transaction(function () use ($product) {
            $product->update(['is_active' => ! $product->is_active]);
            activity()->causedBy(Auth::user())->performedOn($product)->log('toggled product active status');
        });

        session()->flash('success', $product->is_active ? 'Product marked as active.' : 'Product marked as inactive.');
    }

    // ── Confirm / Delete ─────────────────────────────────
    public function confirmDeleteRecord(int $id): void
    {
        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deleteRecord(): void
    {
        $record = Product::findOrFail($this->deleteId);

        DB::transaction(function () use ($record) {
            if ($record->image_url && str_starts_with($record->image_url, '/storage/')) {
                Storage::disk('public')->delete(
                    str_replace('/storage/', '', $record->image_url)
                );
            }

            activity()->causedBy(Auth::user())->performedOn($record)->log('deleted product');
            $record->delete();
        });

        $this->confirmDelete = false;
        $this->deleteId      = null;
        session()->flash('success', 'Product deleted successfully!');
    }

    // ── Reset form ────────────────────────────────────────
    private function resetForm(): void
    {
        $this->reset(['name', 'description', 'editId', 'image', 'existingImage', 'compare_price', 'category_id']);
        $this->price       = 0;
        $this->section     = '';
        $this->sort_order  = 0;
        $this->is_active   = true;
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────
    public function render()
    {
        $products = Product::query()
            ->with('category:id,name')
            ->when($this->search, fn ($q) =>
                $q->where('name', 'like', "%{$this->search}%")
            )
            ->when($this->filterSection, fn ($q) =>
                $q->where('section', $this->filterSection)
            )
            ->when($this->filterStatus === 'active', fn ($q) =>
                $q->where('is_active', true)
            )
            ->when($this->filterStatus === 'inactive', fn ($q) =>
                $q->where('is_active', false)
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.product-component', [
            'products'        => $products,
            'sections'        => $this->getSections(),
            'categoryOptions' => $this->getCategoryOptions(),
        ])->layout('layouts.admin', [
            'title'           => 'Products | KhaiKhai',
            'breadcrumbTitle' => 'Products',
        ]);
    }
}