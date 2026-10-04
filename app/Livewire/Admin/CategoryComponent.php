<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
    public string $filterType    = '';   // '' | 'food' | 'product' | 'bazar' | 'service'
    public string $filterLevel   = '';   // '' | 'category' | 'sub' | 'child'

    // ── Modal ─────────────────────────────────────────────
    public bool $showModal     = false;
    public bool $confirmDelete = false;
    public ?int $deleteId      = null;

    // ── Form ──────────────────────────────────────────────
    public ?int    $editId          = null;
    public         $parent_id       = null;   // null = top level Category
    public string  $type            = Category::TYPE_FOOD;
    public string  $name            = '';
    public string  $emoji           = '';
    public         $image           = null;
    public ?string $existingImage   = null;
    public int     $sort_order      = 0;
    public bool    $is_active       = true;

    // ── Validation ────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'parent_id'  => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'type'       => 'required|string|in:' . implode(',', array_keys(Category::TYPES)),
            'name'       => 'required|string|max:80',
            'emoji'      => 'nullable|string|max:10',
            'image'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'sort_order' => 'required|integer|min:0',
            'is_active'  => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'parent_id.exists' => 'Selected parent category is invalid.',
            'type.required'    => 'Please select a category type.',
            'type.in'          => 'Selected category type is invalid.',
            'name.required'    => 'Please enter a category name.',
            'name.max'         => 'Name must not exceed 80 characters.',
            'image.max'        => 'Image must not exceed 2 MB.',
        ];
    }

    // ── Watchers ─────────────────────────────────────────
    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterType(): void   { $this->resetPage(); }
    public function updatingFilterLevel(): void  { $this->resetPage(); }

    /** Type bodlale parent option bodle jay, tai parent reset. */
    public function updatedType(): void
    {
        $this->parent_id = null;
    }

    /** Parent select korle type parent-er type-i hoye jabe. */
    public function updatedParentId($value): void
    {
        $parent = $value ? Category::find((int) $value) : null;

        if ($parent) {
            $this->type = $parent->type;
        }
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
        $record = Category::findOrFail($id);

        $this->editId         = $id;
        $this->parent_id      = $record->parent_id;
        $this->type           = $record->type;
        $this->name           = $record->name;
        $this->emoji          = $record->emoji ?? '';
        $this->existingImage  = $record->image_url;
        $this->sort_order     = $record->sort_order;
        $this->is_active      = (bool) $record->is_active;
        $this->resetValidation();
        $this->showModal      = true;
    }

    // ── Parent rule check (max 3 level, no loop) ──────────
    private function parentError(Category $parent): ?string
    {
        $selfDepth = 0;

        if ($this->editId) {
            $self = Category::find($this->editId);

            if ($self) {
                if ($parent->id === $self->id || in_array($parent->id, $self->descendantIds(), true)) {
                    return 'A category cannot be placed under itself or its own child.';
                }
                $selfDepth = $self->maxSubtreeDepth();
            }
        }

        if ($parent->level + 1 + $selfDepth > Category::MAX_LEVEL) {
            return $selfDepth > 0
                ? 'This category already has sub/child categories, so it cannot go under this parent (max 3 levels).'
                : 'A Child Category cannot have further categories under it (max 3 levels).';
        }

        return null;
    }

    // ── Save ─────────────────────────────────────────────
    public function save(): void
    {
        $this->validate();

        $parentId = $this->parent_id ? (int) $this->parent_id : null;

        if ($parentId) {
            $parent = Category::find($parentId);

            if (! $parent) {
                $this->addError('parent_id', 'Selected parent category not found.');
                return;
            }

            if ($error = $this->parentError($parent)) {
                $this->addError('parent_id', $error);
                return;
            }

            // Sub/Child sobsomoy parent-er type-i hoy
            $this->type = $parent->type;
        }

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
            'parent_id'  => $parentId,
            'type'       => $this->type,
            'name'       => $this->name,
            'emoji'      => $this->emoji !== '' ? $this->emoji : null,
            'image_url'  => $imagePath,
            'sort_order' => $this->sort_order,
            'is_active'  => $this->is_active,
        ];

        DB::transaction(function () use ($data) {
            if ($this->editId) {
                $category = Category::findOrFail($this->editId);
                $category->update($data);

                // Type bodlale tar sob child-er type-o bodle jabe
                if ($category->wasChanged('type')) {
                    $descendants = $category->descendantIds();
                    if ($descendants) {
                        Category::whereIn('id', $descendants)->update(['type' => $this->type]);
                    }
                }

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

        if ($record->children()->exists()) {
            session()->flash('error', 'Cannot delete: this category still has sub/child categories. Delete or move them first.');
            $this->confirmDelete = false;
            $this->deleteId      = null;
            return;
        }

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
        $this->reset(['name', 'emoji', 'editId', 'image', 'existingImage', 'parent_id']);
        $this->type        = Category::TYPE_FOOD;
        $this->sort_order  = 0;
        $this->is_active   = true;
        $this->resetValidation();
    }

    // ── Parent dropdown options (Category + Sub Category only) ──
    /** @return array<int,string> id => "Bazar-er Category › Sub Category" */
    private function parentOptions(): array
    {
        $all = Category::query()
            ->ofType($this->type)
            ->ordered()
            ->get(['id', 'name', 'parent_id'])
            ->keyBy('id');

        $exclude   = [];
        $selfDepth = 0;

        if ($this->editId && ($self = Category::find($this->editId))) {
            $exclude   = array_merge([$self->id], $self->descendantIds());
            $selfDepth = $self->maxSubtreeDepth();
        }

        $options = [];

        foreach ($all as $category) {
            if (in_array($category->id, $exclude, true)) {
                continue;
            }

            $level = 1;
            $names = [$category->name];
            $node  = $category;

            while ($node->parent_id && $all->has($node->parent_id) && $level < 10) {
                $node = $all[$node->parent_id];
                array_unshift($names, $node->name);
                $level++;
            }

            // new level = $level + 1, sub-tree soho max 3 level
            if ($level + 1 + $selfDepth > Category::MAX_LEVEL) {
                continue;
            }

            $options[$category->id] = implode(' › ', $names);
        }

        asort($options);

        return $options;
    }

    // ── Render ────────────────────────────────────────────
    public function render()
    {
        $categories = Category::query()
            ->with('parent.parent')
            ->withCount(['products', 'foods', 'children'])
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
            ->when($this->filterLevel === 'category', fn ($q) =>
                $q->whereNull('parent_id')
            )
            ->when($this->filterLevel === 'sub', fn ($q) =>
                $q->whereHas('parent', fn ($p) => $p->whereNull('parent_id'))
            )
            ->when($this->filterLevel === 'child', fn ($q) =>
                $q->whereHas('parent', fn ($p) => $p->whereNotNull('parent_id'))
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.category-component', [
            'categories'    => $categories,
            'categoryTypes' => Category::TYPES,
            'parentOptions' => $this->showModal ? $this->parentOptions() : [],
        ])->layout('layouts.admin', [
            'title'           => 'Categories | KhaiKhai',
            'breadcrumbTitle' => 'Categories',
        ]);
    }
}
