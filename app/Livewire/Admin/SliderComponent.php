<?php

namespace App\Livewire\Admin;

use App\Models\Slider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class SliderComponent extends Component
{
    use WithPagination, WithFileUploads;

    protected string $paginationTheme = 'bootstrap';

    // ── List / Filter ─────────────────────────────────────
    public int    $perPage       = 10;
    public string $filterStatus  = '';   // '' | 'active' | 'inactive'

    // ── Modal ─────────────────────────────────────────────
    public bool $showModal     = false;
    public bool $confirmDelete = false;
    public ?int $deleteId      = null;

    // ── Form ──────────────────────────────────────────────
    public ?int    $editId        = null;
    public         $image         = null;
    public ?string $existingImage = null;
    public string  $url           = '';
    public bool    $is_active     = true;

    // ── Validation ────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'image' => $this->editId
                ? 'nullable|image|max:2048'
                : 'required_without:existingImage|nullable|image|max:2048',
            'url'       => 'nullable|url|max:255',
            'is_active' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'image.required_without' => 'Please upload a slider image.',
            'image.image'             => 'The file must be an image (JPG, PNG, WEBP).',
            'image.max'               => 'Image must not exceed 2 MB.',
            'url.url'                 => 'Please enter a valid URL (e.g. https://example.com).',
        ];
    }

    // ── Watchers ─────────────────────────────────────────
    public function updatingFilterStatus(): void { $this->resetPage(); }

    // ── Open create modal ─────────────────────────────────
    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    // ── Open edit modal ───────────────────────────────────
    public function openEdit(int $id): void
    {
        $record = Slider::findOrFail($id);

        $this->editId        = $id;
        $this->existingImage = $record->image;
        $this->url            = $record->url ?? '';
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
            $stored    = $this->image->store('sliders', 'public');
            $imagePath = Storage::url($stored);
        }

        $data = [
            'image'     => $imagePath,
            'url'       => $this->url ?: null,
            'is_active' => $this->is_active,
        ];

        DB::transaction(function () use ($data) {
            if ($this->editId) {
                $slider = Slider::findOrFail($this->editId);
                $slider->update($data);
                activity()->causedBy(Auth::user())->performedOn($slider)->log('updated slider');
                session()->flash('success', 'Slider updated successfully!');
            } else {
                $slider = Slider::create($data);
                activity()->causedBy(Auth::user())->performedOn($slider)->log('created slider');
                session()->flash('success', 'New slider created!');
            }
        });

        $this->showModal = false;
        $this->resetForm();
    }

    // ── Quick toggle active status ────────────────────────
    public function toggleActive(int $id): void
    {
        $slider = Slider::findOrFail($id);

        DB::transaction(function () use ($slider) {
            $slider->update(['is_active' => ! $slider->is_active]);
            activity()->causedBy(Auth::user())->performedOn($slider)->log('toggled slider active status');
        });

        session()->flash('success', $slider->is_active ? 'Slider marked as active.' : 'Slider marked as inactive.');
    }

    // ── Confirm / Delete ─────────────────────────────────
    public function confirmDeleteRecord(int $id): void
    {
        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deleteRecord(): void
    {
        $record = Slider::findOrFail($this->deleteId);

        DB::transaction(function () use ($record) {
            if ($record->image && str_starts_with($record->image, '/storage/')) {
                Storage::disk('public')->delete(
                    str_replace('/storage/', '', $record->image)
                );
            }

            activity()->causedBy(Auth::user())->performedOn($record)->log('deleted slider');
            $record->delete();
        });

        $this->confirmDelete = false;
        $this->deleteId      = null;
        session()->flash('success', 'Slider deleted successfully!');
    }

    // ── Reset form ────────────────────────────────────────
    private function resetForm(): void
    {
        $this->reset(['editId', 'image', 'existingImage', 'url']);
        $this->is_active = true;
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────
    public function render()
    {
        $sliders = Slider::query()
            ->when($this->filterStatus === 'active', fn ($q) =>
                $q->where('is_active', true)
            )
            ->when($this->filterStatus === 'inactive', fn ($q) =>
                $q->where('is_active', false)
            )
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.admin.slider-component', [
            'sliders' => $sliders,
        ])->layout('layouts.admin', [
            'title'           => 'Sliders | KhaiKhai',
            'breadcrumbTitle' => 'Sliders',
        ]);
    }
}