<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\RiderProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RiderComponent extends Component
{
    use WithPagination;

    // ── Filters ──────────────────────────────────────────────
    public string $search       = '';
    public string $statusFilter = '';   // 'online' | 'offline' | 'inactive' | 'pending'
    public string $zoneFilter   = '';

    // ── Add Rider form ───────────────────────────────────────
    public string $name           = '';
    public string $email          = '';
    public string $phone          = '';
    public string $password       = '';
    public string $zone           = '';
    public string $vehicle_type   = '';
    public string $vehicle_plate  = '';
    public string $license_number = '';
    public string $nid_number     = '';

     public $showAddModal;
    // ── View Rider ───────────────────────────────────────────
    public ?int $viewRiderId = null;

    // ── Suspend / Activate confirm ───────────────────────────
    public ?int  $statusRiderId = null;
    public string $statusAction = ''; // 'deactivate' | 'activate'

    // ── Approve confirm ──────────────────────────────────────
    public ?int $approveRiderId = null;

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'zoneFilter'   => ['except' => ''],
    ];

    // ── Validation ───────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'name'           => 'required|string|max:100',
            'email'          => 'nullable|email|unique:users,email',
            'phone'          => 'required|string|max:15|unique:users,phone',
            'password'       => 'required|string|min:6',
            'zone'           => 'nullable|string|max:80',
            'vehicle_type'   => 'required|string|max:40',
            'vehicle_plate'  => 'nullable|string|max:20|unique:rider_profiles,vehicle_plate',
            'license_number' => 'nullable|string|max:30|unique:rider_profiles,license_number',
            'nid_number'     => 'nullable|string|max:20|unique:rider_profiles,nid_number',
        ];
    }

    // ── Reset page on filter change ──────────────────────────
    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingZoneFilter(): void   { $this->resetPage(); }

    // ═════════════════════════════════════════════════════════
    // ADD RIDER
    // ═════════════════════════════════════════════════════════
    public function openAddModal(): void
    {
        $this->resetAddForm();
        $this->dispatch('open-modal', modal: 'addRiderModal');
    }

    public function saveRider(): void
    {
        $this->validate();

        DB::transaction(function () {
            $user = User::create([
                'uuid'      => Str::uuid(),
                'name'      => $this->name,
                'email'     => $this->email ?: null,
                'phone'     => $this->phone,
                'password'  => Hash::make($this->password),
                'role'      => 'rider',
                'is_active' => true,
            ]);

            RiderProfile::create([
                'user_id'        => $user->id,
                'vehicle_type'   => $this->vehicle_type,
                'vehicle_plate'  => $this->vehicle_plate  ?: null,
                'license_number' => $this->license_number ?: null,
                'nid_number'     => $this->nid_number     ?: null,
                'zone'           => $this->zone            ?: null,
                'is_approved'    => true,
                'is_online'      => false,
            ]);
        });

        $this->dispatch('close-modal', modal: 'addRiderModal');
        $this->resetAddForm();
        session()->flash('success', 'Rider added successfully.');
    }

    private function resetAddForm(): void
    {
        $this->name           = '';
        $this->email          = '';
        $this->phone          = '';
        $this->password       = '';
        $this->zone           = '';
        $this->vehicle_type   = '';
        $this->vehicle_plate  = '';
        $this->license_number = '';
        $this->nid_number     = '';
        // $this->resetValidation();
    }

    // ═════════════════════════════════════════════════════════
    // VIEW RIDER
    // ═════════════════════════════════════════════════════════
    public function viewRider(int $id): void
    {
        $this->viewRiderId = $id;
        $this->dispatch('open-modal', modal: 'viewRiderModal');
    }

    public function getRiderViewProperty(): ?User
    {
        if (! $this->viewRiderId) return null;

        return User::with(['riderProfile', 'riderEarnings'])
            ->where('role', 'rider')
            ->find($this->viewRiderId);
    }

    // ═════════════════════════════════════════════════════════
    // ACTIVATE / DEACTIVATE
    // ═════════════════════════════════════════════════════════
    public function confirmStatusChange(int $id, string $action): void
    {
        $this->statusRiderId = $id;
        $this->statusAction  = $action;
        $this->dispatch('open-modal', modal: 'statusModal');
    }

    public function changeStatus(): void
    {
        $user = User::findOrFail($this->statusRiderId);
        $user->update(['is_active' => $this->statusAction === 'activate']);

        $this->dispatch('close-modal', modal: 'statusModal');
        $this->statusRiderId = null;
        $this->statusAction  = '';
        session()->flash('success', 'Rider status updated successfully.');
    }

    // ═════════════════════════════════════════════════════════
    // APPROVE RIDER
    // ═════════════════════════════════════════════════════════
    public function confirmApprove(int $id): void
    {
        $this->approveRiderId = $id;
        $this->dispatch('open-modal', modal: 'approveModal');
    }

    public function approveRider(): void
    {
        RiderProfile::where('user_id', $this->approveRiderId)
            ->update(['is_approved' => true]);

        $this->dispatch('close-modal', modal: 'approveModal');
        $this->approveRiderId = null;
        session()->flash('success', 'Rider approved successfully.');
    }

    // ═════════════════════════════════════════════════════════
    // ZONE LIST (for filter dropdown)
    // ═════════════════════════════════════════════════════════
    public function getZonesProperty(): array
    {
        return RiderProfile::distinct()
            ->whereNotNull('zone')
            ->orderBy('zone')
            ->pluck('zone')
            ->toArray();
    }

    // ═════════════════════════════════════════════════════════
    // RENDER
    // ═════════════════════════════════════════════════════════
    public function render()
    {
        $riders = User::with('riderProfile')
            ->where('role', 'rider')
            ->when($this->search, fn($q) =>
                $q->where(fn($q) =>
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('phone', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%")
                )
            )
            ->when($this->statusFilter === 'online', fn($q) =>
                $q->whereHas('riderProfile', fn($p) => $p->where('is_online', true))
            )
            ->when($this->statusFilter === 'offline', fn($q) =>
                $q->whereHas('riderProfile', fn($p) => $p->where('is_online', false))
            )
            ->when($this->statusFilter === 'inactive', fn($q) =>
                $q->where('is_active', false)
            )
            ->when($this->statusFilter === 'pending', fn($q) =>
                $q->whereHas('riderProfile', fn($p) => $p->where('is_approved', false))
            )
            ->when($this->zoneFilter, fn($q) =>
                $q->whereHas('riderProfile', fn($p) => $p->where('zone', $this->zoneFilter))
            )
            ->latest()
            ->paginate(15);

        // Today's deliveries per rider (from rider_earnings)
        $todayCounts = DB::table('rider_earnings')
            ->whereDate('created_at', today())
            ->select('rider_id', DB::raw('count(*) as total'))
            ->groupBy('rider_id')
            ->pluck('total', 'rider_id');

        // Summary stats
        $stats = [
            'total'    => User::where('role', 'rider')->count(),
            'online'   => User::where('role', 'rider')
                              ->whereHas('riderProfile', fn($p) => $p->where('is_online', true))
                              ->count(),
            'pending'  => User::where('role', 'rider')
                              ->whereHas('riderProfile', fn($p) => $p->where('is_approved', false))
                              ->count(),
            'inactive' => User::where('role', 'rider')->where('is_active', false)->count(),
        ];

        return view('livewire.admin.rider-component', [
            'riders'      => $riders,
            'todayCounts' => $todayCounts,
            'stats'       => $stats,
        ])->layout('layouts.admin', [
            'title' => 'Rider Management',
            'breadcrumbTitle' => 'Rider Management',
            ]);
    }
}