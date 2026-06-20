<div>
  <div class="row g-3">

    {{-- PROFILE CARD --}}
    <div class="col-md-6">
      <div class="card">

        {{-- Avatar & Info --}}
        <div class="text-center py-4">
          @if(str_starts_with($avatar, 'http') || str_starts_with($avatar, '/'))
            <img
              src="{{ $avatar }}"
              style="width:80px; height:80px; border-radius:50%; object-fit:cover; margin:0 auto 12px; display:block; border:3px solid var(--pink);"
              alt="{{ $name }}"
            >
          @else
            <div style="width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,var(--pink),var(--accent)); display:flex; align-items:center; justify-content:center; font-size:32px; color:#fff; font-weight:700; margin:0 auto 12px;">
              {{ $avatar }}
            </div>
          @endif

          <div style="font-size:20px; font-weight:800;">{{ $name }}</div>
          <div style="color:var(--text-3); font-size:13px;">{{ $role }}</div>
          <div class="mt-3">
            <span class="badge-kk badge-green">
              <i class="fa fa-check-circle"></i> Verified
            </span>
          </div>
        </div>

        {{-- Edit Form --}}
        <div style="border-top:1px solid var(--border); padding-top:16px;">

          <div class="form-group">
            <label class="form-label-kk">Phone Number</label>
            <input class="form-control-kk" wire:model="phone" type="tel" placeholder="01XXXXXXXXX">
            @error('phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label class="form-label-kk">Email</label>
            <input class="form-control-kk" wire:model="email" type="email" placeholder="you@example.com">
            @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label class="form-label-kk">Default Address</label>
            <input class="form-control-kk" wire:model="fullAddress" type="text" placeholder="House, Road, Area...">
            @error('fullAddress') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="row g-2 mb-3">
            <div class="col-8">
              <label class="form-label-kk">City</label>
              <input class="form-control-kk" wire:model="city" type="text" placeholder="Dhaka">
              @error('city') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-4">
              <label class="form-label-kk">Postal Code</label>
              <input class="form-control-kk" wire:model="postalCode" type="text" placeholder="1200">
              @error('postalCode') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
          </div>

          <button
            class="btn-kk btn-primary-kk"
            style="width:100%; justify-content:center;"
            wire:click="saveProfile"
            wire:loading.attr="disabled"
            wire:target="saveProfile"
          >
            <span wire:loading.remove wire:target="saveProfile">
              <i class="fa fa-save"></i> Save Changes
            </span>
            <span wire:loading wire:target="saveProfile">
              <i class="fa fa-spinner fa-spin"></i> Saving...
            </span>
          </button>

        </div>
      </div>
    </div>

    {{-- STATS + QUICK ACTIONS --}}
    <div class="col-md-6">
      <div class="d-flex flex-column gap-3">

        {{-- Stat Cards --}}
        <div class="stat-card sc-pink">
          <div class="stat-icon"><i class="fa fa-shopping-bag"></i></div>
          <div class="stat-info">
            <div class="num">{{ $totalOrders }}</div>
            <div class="label">Total Orders</div>
          </div>
        </div>

        <div class="stat-card sc-green">
          <div class="stat-icon"><i class="fa fa-star"></i></div>
          <div class="stat-info">
            <div class="num">
              {{ $avgRating ? number_format($avgRating, 1) . '★' : 'N/A' }}
            </div>
            <div class="label">Avg. Rating Given</div>
          </div>
        </div>

        <div class="stat-card sc-orange">
          <div class="stat-icon"><i class="fa fa-coins"></i></div>
          <div class="stat-info">
            <div class="num">৳{{ number_format($points) }}</div>
            <div class="label">KK Points</div>
          </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card">
          <div class="card-title mb-3">Quick Actions</div>
          <div class="d-flex flex-column gap-2">

            <button
              class="btn-kk btn-ghost-kk"
              style="justify-content:flex-start;"
              wire:click="changePassword"
            >
              <i class="fa fa-lock"></i> Change Password
            </button>

            <button
              class="btn-kk btn-ghost-kk"
              style="justify-content:flex-start;"
              wire:click="notifications"
            >
              <i class="fa fa-bell"></i> Notifications
            </button>

            <button
              class="btn-kk btn-ghost-kk"
              style="justify-content:flex-start; color:var(--danger);"
              wire:click="logout"
              wire:confirm="Are you sure you want to logout?"
            >
              <i class="fa fa-sign-out-alt"></i> Logout
            </button>

          </div>
        </div>

      </div>
    </div>

  </div>
</div>