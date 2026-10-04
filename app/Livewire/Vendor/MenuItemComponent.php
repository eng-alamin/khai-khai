<?php

namespace App\Livewire\Vendor;

use App\Models\Food;
use App\Models\Category;
use App\Models\MenuSection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Livewire\Concerns\HasCatalogItemValidationRules;

class MenuItemComponent extends Component
{
    use WithPagination, WithFileUploads, HasCatalogItemValidationRules;

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
    public            $menu_section_id = null;   // null = no section (optional)
    public string  $name          = '';
    public string  $description   = '';
    public int     $price         = 0;
    public string  $emoji         = '';
    public         $image         = null;
    public ?string $existingImage = null;
    public int     $sort_order    = 0;
    public bool    $is_available  = true;

    // ── Menu sections manager ─────────────────────────────
    public bool   $showSectionModal = false;
    public string $newSectionName   = '';
    public int    $newSectionSort   = 0;

    // ── Restaurant helper ─────────────────────────────────
    private function restaurantId(): int
    {
        $restaurant = Auth::user()->restaurant;

        abort_if(
            ! $restaurant,
            403,
            'No restaurant is linked to your account yet. Please contact support.'
        );

        return $restaurant->id;
    }

    // ── Categories for dropdown ───────────────────────────
    public function getCategories()
    {
        // Category ekhon global (product o food duitatei share hoy), tai
        // restaurant_id diye filter hoy na — shudhu type='food' + active.
        return Category::where('type', Category::TYPE_FOOD)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'emoji']);
    }

    // ── Validation ────────────────────────────────────────
    // DRY FIX: shared name/description/price/image/sort_order rules now
    // live in HasCatalogItemValidationRules (also used by Admin\ProductComponent)
    // instead of being hand-copied in both places.
    protected function rules(): array
    {
        return array_merge($this->catalogItemRules(), [
            'category_id'  => [
                'required',
                'integer',
                'min:1',
                Rule::exists('categories', 'id')
                    ->where('type', Category::TYPE_FOOD)
                    ->whereNull('deleted_at'),
            ],
            'emoji'        => 'nullable|string|max:10',
            // Section is optional, but if given it must belong to THIS restaurant.
            'menu_section_id' => [
                'nullable',
                'integer',
                Rule::exists('menu_sections', 'id')
                    ->where('restaurant_id', $this->restaurantId()),
            ],
            'is_available' => 'boolean',
        ]);
    }

    protected function messages(): array
    {
        return array_merge($this->catalogItemMessages(), [
            'category_id.required' => 'Please select a category.',
            'category_id.min'      => 'Please select a category.',
            'category_id.exists'   => 'Please select a valid category.',
            'menu_section_id.exists' => 'Please select a valid menu section.',
            'name.required'        => 'Please enter an item name.',
        ]);
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
        $record = Food::where('restaurant_id', $this->restaurantId())
            ->findOrFail($id);

        // FIX (Medium Bug #7): framework-enforced authorization backstop,
        // in addition to the where('restaurant_id', ...) scoping above.
        $this->authorize('view', $record);

        $this->editId        = $id;
        $this->category_id   = $record->category_id ?? 0;
        $this->menu_section_id = $record->menu_section_id;
        $this->name          = $record->name;
        $this->description   = $record->description ?? '';
        $this->price         = (int) ($record->price);
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
            $stored    = $this->image->store('foods', 'public');
            $imagePath = Storage::url($stored);
        }

        $data = [
            'restaurant_id' => $this->restaurantId(),
            'category_id'   => $this->category_id ?: null,
            'menu_section_id' => $this->menu_section_id ? (int) $this->menu_section_id : null,
            'name'          => $this->name,
            'description'   => $this->description ?: null,
            'price'         => $this->price,
            'emoji'         => $this->emoji ?: null,
            'image_url'     => $imagePath,
            'sort_order'    => $this->sort_order,
            'is_available'  => $this->is_available,
        ];

        DB::transaction(function () use ($data) {
            if ($this->editId) {
                $item = Food::where('restaurant_id', $this->restaurantId())
                    ->findOrFail($this->editId);

                // FIX (Medium Bug #7): framework-enforced authorization backstop.
                $this->authorize('update', $item);

                $item->update($data);

                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($item)
                    ->withProperties(['attributes' => $data])
                    ->log('Vendor updated menu item');

                session()->flash('success', 'Menu item updated successfully!');
            } else {
                // FIX (Medium Bug #7): framework-enforced authorization backstop.
                $this->authorize('create', [Food::class, $data['restaurant_id']]);

                $item = Food::create($data);

                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($item)
                    ->withProperties(['attributes' => $data])
                    ->log('Vendor created menu item');

                session()->flash('success', 'New menu item created!');
            }
        });

        $this->showModal = false;
        $this->resetForm();
    }

    // ── Quick toggle availability ─────────────────────────
    public function toggleAvailability(int $id): void
    {
        $item = Food::where('restaurant_id', $this->restaurantId())->findOrFail($id);

        // FIX (Medium Bug #7): framework-enforced authorization backstop.
        $this->authorize('update', $item);

        $item->update(['is_available' => ! $item->is_available]);

        activity()
            ->causedBy(Auth::user())
            ->performedOn($item)
            ->withProperties(['is_available' => $item->is_available])
            ->log($item->is_available ? 'Vendor marked menu item available' : 'Vendor marked menu item unavailable');

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
        $record = Food::where('restaurant_id', $this->restaurantId())
            ->findOrFail($this->deleteId);

        // FIX (Medium Bug #7): framework-enforced authorization backstop.
        $this->authorize('delete', $record);

        DB::transaction(function () use ($record) {
            if ($record->image_url && str_starts_with($record->image_url, '/storage/')) {
                Storage::disk('public')->delete(
                    str_replace('/storage/', '', $record->image_url)
                );
            }

            $itemName = $record->name;
            $itemId   = $record->id;

            $record->delete();

            activity()
                ->causedBy(Auth::user())
                ->withProperties(['item_id' => $itemId, 'name' => $itemName])
                ->log('Vendor deleted menu item');
        });

        $this->confirmDelete = false;
        $this->deleteId      = null;
        session()->flash('success', 'Menu item deleted successfully!');
    }

    // ── Menu sections (restaurant's own menu groups) ──────
    public function getSections()
    {
        return MenuSection::where('restaurant_id', $this->restaurantId())
            ->withCount('foods')
            ->ordered()
            ->get();
    }

    public function openSections(): void
    {
        $this->reset(['newSectionName']);
        $this->newSectionSort = 0;
        $this->resetValidation();
        $this->showSectionModal = true;
    }

    public function addSection(): void
    {
        $restaurantId = $this->restaurantId();

        $this->validate([
            'newSectionName' => [
                'required',
                'string',
                'max:80',
                Rule::unique('menu_sections', 'name')->where('restaurant_id', $restaurantId),
            ],
            'newSectionSort' => ['required', 'integer', 'min:0', 'max:65535'],
        ], [
            'newSectionName.required' => 'Please enter a section name.',
            'newSectionName.unique'   => 'You already have a section with this name.',
        ]);

        $section = MenuSection::create([
            'restaurant_id' => $restaurantId,
            'name'          => trim($this->newSectionName),
            'sort_order'    => $this->newSectionSort,
        ]);

        activity()
            ->causedBy(Auth::user())
            ->performedOn($section)
            ->log('Vendor created menu section');

        $this->reset(['newSectionName']);
        $this->newSectionSort = 0;
        session()->flash('success', 'Menu section created!');
    }

    public function deleteSection(int $id): void
    {
        // Scoped to this restaurant: a vendor can never touch another vendor's section.
        $section = MenuSection::where('restaurant_id', $this->restaurantId())->findOrFail($id);
        $name    = $section->name;

        // foods.menu_section_id is set to NULL by the database; the items stay on the menu.
        $section->delete();

        activity()
            ->causedBy(Auth::user())
            ->withProperties(['section_id' => $id, 'name' => $name])
            ->log('Vendor deleted menu section');

        session()->flash('success', 'Section deleted. Its items are still on your menu.');
    }

    // ── Reset form ────────────────────────────────────────
    private function resetForm(): void
    {
        $this->reset(['name', 'description', 'emoji', 'editId', 'image', 'existingImage']);
        $this->category_id  = 0;
        $this->menu_section_id = null;
        $this->price        = 0;
        $this->sort_order   = 0;
        $this->is_available = true;
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────
    public function render()
    {
        $items = Food::query()
            ->where('restaurant_id', $this->restaurantId())
            ->with('category', 'menuSection')
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
            'sections'   => $this->getSections(),
        ])->layout('layouts.vendor', [
            'title'           => 'Menu Items | KhaiKhai',
            'breadcrumbTitle' => 'Items',
        ]);
    }
}