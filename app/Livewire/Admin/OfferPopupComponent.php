<?php

namespace App\Livewire\Admin;

use App\Models\OfferPopup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class OfferPopupComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // ── List / Filter ─────────────────────────────────────
    public int    $perPage      = 10;
    public string $filterStatus = '';   // '' | 'active' | 'inactive'

    // ── Modal ─────────────────────────────────────────────
    public bool $showModal     = false;
    public bool $confirmDelete = false;
    public ?int $deleteId      = null;

    // ── Form ──────────────────────────────────────────────
    public ?int    $editId        = null;
    public string  $badge         = '';
    public string  $highlight     = '';
    public string  $highlight_sub = '';
    public string  $title         = '';
    public string  $message       = '';
    public string  $chips         = '';
    public string  $button_text   = 'এখনই অর্ডার করুন';
    public string  $button_url    = '';
    public int     $repeat_hours  = 24;
    public string  $starts_at     = '';
    public string  $ends_at       = '';
    public bool    $is_active     = true;

    // ── Validation ────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'badge'         => 'nullable|string|max:60',
            'highlight'     => 'required|string|max:60',
            'highlight_sub' => 'nullable|string|max:60',
            'title'         => 'required|string|max:120',
            'message'       => 'nullable|string|max:500',
            'chips'         => 'nullable|string|max:255',
            'button_text'   => 'required|string|max:60',
            // full URL (https://...) ba site-er vitorer path (/restaurants)
            'button_url'    => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/|\/).+/i'],
            'repeat_hours'  => 'required|integer|min:0|max:8760',
            'starts_at'     => 'nullable|date',
            'ends_at'       => 'nullable|date|after_or_equal:starts_at',
            'is_active'     => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'highlight.required'     => 'Highlight likhun (jemon: ৪০%–৫০%).',
            'title.required'         => 'Title likhun.',
            'button_text.required'   => 'Button text likhun.',
            'button_url.regex'       => 'Link must start with https:// or / (e.g. /restaurants).',
            'ends_at.after_or_equal' => 'End time must be after the start time.',
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
        $record = OfferPopup::findOrFail($id);

        $this->editId        = $id;
        $this->badge         = $record->badge ?? '';
        $this->highlight     = $record->highlight;
        $this->highlight_sub = $record->highlight_sub ?? '';
        $this->title         = $record->title;
        $this->message       = $record->message ?? '';
        $this->chips         = $record->chips ?? '';
        $this->button_text   = $record->button_text;
        $this->button_url    = $record->button_url ?? '';
        $this->repeat_hours  = (int) $record->repeat_hours;
        $this->starts_at     = $record->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->ends_at       = $record->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->is_active     = (bool) $record->is_active;
        $this->resetValidation();
        $this->showModal     = true;
    }

    // ── Save ─────────────────────────────────────────────
    public function save(): void
    {
        $this->validate();

        $data = [
            'badge'         => $this->badge ?: null,
            'highlight'     => $this->highlight,
            'highlight_sub' => $this->highlight_sub ?: null,
            'title'         => $this->title,
            'message'       => $this->message ?: null,
            'chips'         => $this->chips ?: null,
            'button_text'   => $this->button_text,
            'button_url'    => $this->button_url ?: null,
            'repeat_hours'  => $this->repeat_hours,
            'starts_at'     => $this->starts_at ?: null,
            'ends_at'       => $this->ends_at ?: null,
            'is_active'     => $this->is_active,
        ];

        DB::transaction(function () use ($data) {
            if ($this->editId) {
                $popup = OfferPopup::findOrFail($this->editId);
                $popup->update($data);
                activity()->causedBy(Auth::user())->performedOn($popup)->log('updated offer popup');
                session()->flash('success', 'Offer popup updated successfully!');
            } else {
                $popup = OfferPopup::create($data);
                activity()->causedBy(Auth::user())->performedOn($popup)->log('created offer popup');
                session()->flash('success', 'New offer popup created!');
            }
        });

        $this->showModal = false;
        $this->resetForm();
    }

    // ── Quick toggle active status ────────────────────────
    public function toggleActive(int $id): void
    {
        $popup = OfferPopup::findOrFail($id);

        DB::transaction(function () use ($popup) {
            $popup->update(['is_active' => ! $popup->is_active]);
            activity()->causedBy(Auth::user())->performedOn($popup)->log('toggled offer popup active status');
        });

        session()->flash('success', $popup->is_active ? 'Offer popup marked as active.' : 'Offer popup marked as inactive.');
    }

    // ── Confirm / Delete ─────────────────────────────────
    public function confirmDeleteRecord(int $id): void
    {
        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deleteRecord(): void
    {
        $record = OfferPopup::findOrFail($this->deleteId);

        DB::transaction(function () use ($record) {
            activity()->causedBy(Auth::user())->performedOn($record)->log('deleted offer popup');
            $record->delete();
        });

        $this->confirmDelete = false;
        $this->deleteId      = null;
        session()->flash('success', 'Offer popup deleted successfully!');
    }

    // ── Reset form ────────────────────────────────────────
    private function resetForm(): void
    {
        $this->reset([
            'editId', 'badge', 'highlight', 'highlight_sub', 'title',
            'message', 'chips', 'button_url', 'starts_at', 'ends_at',
        ]);
        $this->button_text  = 'এখনই অর্ডার করুন';
        $this->repeat_hours = 24;
        $this->is_active    = true;
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────
    public function render()
    {
        $popups = OfferPopup::query()
            ->when($this->filterStatus === 'active', fn ($q) =>
                $q->where('is_active', true)
            )
            ->when($this->filterStatus === 'inactive', fn ($q) =>
                $q->where('is_active', false)
            )
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.admin.offer-popup-component', [
            'popups' => $popups,
        ])->layout('layouts.admin', [
            'title'           => 'Offer Popups | KhaiKhai',
            'breadcrumbTitle' => 'Offer Popups',
        ]);
    }
}
